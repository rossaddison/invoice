<?php

declare(strict_types=1);

namespace App\Invoice\Upload;

use App\Invoice\Client\ClientRepository;
use App\Invoice\Entity\Client;
use App\Invoice\Entity\Upload;
use App\Invoice\Upload\UploadForm;
use App\Invoice\Upload\UploadService;
use App\Invoice\Upload\UploadRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Traits\FlashMessage;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Exception;

final class UploadController
{
    use FlashMessage;
    
    private Flash $flash;
    private SessionInterface $session;
    private SettingRepository $s;
    private DataResponseFactoryInterface $factory;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private UploadService $uploadService;
    private TranslatorInterface $translator;

    public function __construct(
        SettingRepository $s,
        SessionInterface $session,
        DataResponseFactoryInterface $factory,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        UploadService $uploadService,
        TranslatorInterface $translator,
    ) {
        $this->s = $s;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->factory = $factory;
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/upload')
             ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->uploadService = $uploadService;
        $this->translator = $translator;
    }

    /** Note: An Upload can only be viewed with editInv permission
     *
     * Refer to: config/common/routes/routes.php ... specifically AccessChecker
     *
     * Route::methods([Method::GET, Method::POST], '/upload/view/{id}')
      ->name('upload/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UploadController::class, 'view']),
     */

    /**
     *
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param UploadRepository $uploadRepository
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(Request $request, CurrentRoute $currentRoute, UploadRepository $uploadRepository): \Yiisoft\DataResponse\DataResponse
    {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int)$page > 0 ? (int)$page : 1;
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['id', 'client_id', 'file_name_original'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest uploads first => -id
                ->withOrderString($query_params['sort'] ?? '-id');
        $uploads = $this->uploads_with_sort($uploadRepository, $sort);
        $paginator = (new OffsetPaginator($uploads))
                ->withPageSize((int)$this->s->getSetting('default_list_limit'))
                ->withCurrentPage($currentPageNeverZero)
                ->withToken(PageToken::next((string)$page));

        $parameters = [
            'paginator' => $paginator,
            'uploads' => $this->uploads($uploadRepository),
            'alert' => $this->alert()
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
        ClientRepository $clientRepository
    ): Response {
        $upload = new Upload();
        $form = new UploadForm($upload);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'upload/add',
            'actionArguments' => [],
            'form' => $form,
            'errors' => [],
            'optionsDataClients' => $this->optionsDataClients($clientRepository->findAllPreloaded()),
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->uploadService->saveUpload($upload, $body);
                    return $this->webService->getRedirectResponse('upload/index');
                }
            }    
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
        'flash' => $this->flash
      ]
        );
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param UploadRepository $uploadRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        UploadRepository $uploadRepository,
        SettingRepository $settingRepository
    ): Response {
        try {
            $upload = $this->upload($currentRoute, $uploadRepository);
            if ($upload) {
                $this->uploadService->deleteUpload($upload, $settingRepository);
                $inv_id = (string) $this->session->get('inv_id');
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                    '//invoice/setting/inv_message',
                    ['heading' => '', 'message' => $this->translator->translate('i.record_successfully_deleted'), 'url' => 'inv/view', 'id' => $inv_id]
                ));
            }
            return $this->webService->getRedirectResponse('upload/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('upload/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param UploadRepository $uploadRepository
     * @param SettingRepository $settingRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        UploadRepository $uploadRepository,
        ClientRepository $clientRepository
    ): Response {
        $upload = $this->upload($currentRoute, $uploadRepository);
        if ($upload) {
            $form = new UploadForm($upload);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'upload/edit',
                'actionArguments' => ['id' => $upload->getId()],
                'errors' => [],
                'form' => $form,
                'optionsDataClients' => $this->optionsDataClients($clientRepository->findAllPreloaded()),
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->uploadService->saveUpload($upload, $body);
                        return $this->webService->getRedirectResponse('upload/index');
                    }
                }    
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('upload/index');
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param ClientRepository $clientRepository
     * @param UploadRepository $uploadRepository
     */
    public function view(CurrentRoute $currentRoute, ClientRepository $clientRepository, UploadRepository $uploadRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $upload = $this->upload($currentRoute, $uploadRepository);
        if ($upload) {
            $form = new UploadForm($upload);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'upload/view',
                'actionArguments' => ['id' => $upload->getId()],
                'form' => $form,
                'optionsDataClients' => $this->optionsDataClients($clientRepository->findAllPreloaded()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('upload/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param UploadRepository $uploadRepository
     * @return Upload|null
     */
    public function upload(CurrentRoute $currentRoute, UploadRepository $uploadRepository): Upload|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            $upload = $uploadRepository->repoUploadquery($id);
            return $upload;
        }
        return null;
    }

    /**
     * @param UploadRepository $uploadRepository
     *
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function uploads(UploadRepository $uploadRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $uploads = $uploadRepository->findAllPreloaded();
        return $uploads;
    }

    /**
     * @param UploadRepository $uploadRepository
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Upload>
     */
    private function uploads_with_sort(UploadRepository $uploadRepository, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        $uploads = $uploadRepository->findAllPreloaded()
                ->withSort($sort);
        return $uploads;
    }

    /**
     * @param EntityReader $clients
     * @return array
     */
    private function optionsDataClients(EntityReader $clients): array
    {
        $optionsDataClients = [];
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $key = $client->getClient_id();
            null !== $key ? $optionsDataClients[$key] = $client->getClient_full_name() : '';
        }
        return $optionsDataClients;
    }

}
