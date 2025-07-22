<?php

declare(strict_types=1);

namespace App\Invoice\Upload;

use App\Invoice\BaseController;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Entity\Client;
use App\Invoice\Entity\Upload;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class UploadController extends BaseController
{
    protected string $controllerName = 'invoice/upload';

    public function __construct(
        private DataResponseFactoryInterface $factory,
        private UploadService $uploadService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->factory       = $factory;
        $this->uploadService = $uploadService;
    }

    /** Note: An Upload can only be viewed with editInv permission.
     *
     * Refer to: config/common/routes/routes.php ... specifically AccessChecker
     *
     * Route::methods([Method::GET, Method::POST], '/upload/view/{id}')
     * ->name('upload/view')
     * ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
     * ->middleware(Authentication::class)
     * ->action([UploadController::class, 'view']),
     */
    public function index(Request $request, CurrentRoute $currentRoute, UploadRepository $uploadRepository): \Yiisoft\DataResponse\DataResponse
    {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $page > 0 ? (int) $page : 1;
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['id', 'client_id', 'file_name_original'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest uploads first => -id
            ->withOrderString($query_params['sort'] ?? '-id');
        $uploads   = $this->uploads_with_sort($uploadRepository, $sort);
        $paginator = (new OffsetPaginator($uploads))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withToken(PageToken::next((string) $page));

        $parameters = [
            'paginator' => $paginator,
            'uploads'   => $this->uploads($uploadRepository),
            'alert'     => $this->alert(),
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    public function add(
        Request $request,
        FormHydrator $formHydrator,
        ClientRepository $clientRepository,
    ): Response {
        $upload     = new Upload();
        $form       = new UploadForm($upload);
        $parameters = [
            'title'              => $this->translator->translate('add'),
            'actionName'         => 'upload/add',
            'actionArguments'    => [],
            'form'               => $form,
            'errors'             => [],
            'optionsDataClients' => $this->optionsDataClients($clientRepository->findAllPreloaded()),
        ];

        if (Method::POST === $request->getMethod()) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->uploadService->saveUpload($upload, $body);

                    return $this->webService->getRedirectResponse('upload/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form']   = $form;
        }

        return $this->viewRenderer->render('_form', $parameters);
    }

    public function delete(
        CurrentRoute $currentRoute,
        UploadRepository $uploadRepository,
    ): Response {
        try {
            $upload = $this->upload($currentRoute, $uploadRepository);
            if ($upload) {
                $this->uploadService->deleteUpload($upload, $this->sR);
                $inv_id = (string) $this->session->get('inv_id');
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));

                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                    '//invoice/setting/inv_message',
                    ['heading' => '', 'message' => $this->translator->translate('record.successfully.deleted'), 'url' => 'inv/view', 'id' => $inv_id],
                ));
            }

            return $this->webService->getRedirectResponse('upload/index');
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());

            return $this->webService->getRedirectResponse('upload/index');
        }
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        UploadRepository $uploadRepository,
        ClientRepository $clientRepository,
    ): Response {
        $upload = $this->upload($currentRoute, $uploadRepository);
        if ($upload) {
            $form       = new UploadForm($upload);
            $parameters = [
                'title'              => $this->translator->translate('edit'),
                'actionName'         => 'upload/edit',
                'actionArguments'    => ['id' => $upload->getId()],
                'errors'             => [],
                'form'               => $form,
                'optionsDataClients' => $this->optionsDataClients($clientRepository->findAllPreloaded()),
            ];
            if (Method::POST === $request->getMethod()) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->uploadService->saveUpload($upload, $body);

                        return $this->webService->getRedirectResponse('upload/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('_form', $parameters);
        }

        return $this->webService->getRedirectResponse('upload/index');
    }

    public function view(CurrentRoute $currentRoute, ClientRepository $clientRepository, UploadRepository $uploadRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $upload = $this->upload($currentRoute, $uploadRepository);
        if ($upload) {
            $form       = new UploadForm($upload);
            $parameters = [
                'title'              => $this->translator->translate('view'),
                'actionName'         => 'upload/view',
                'actionArguments'    => ['id' => $upload->getId()],
                'form'               => $form,
                'optionsDataClients' => $this->optionsDataClients($clientRepository->findAllPreloaded()),
            ];

            return $this->viewRenderer->render('_view', $parameters);
        }

        return $this->webService->getRedirectResponse('upload/index');
    }

    public function upload(CurrentRoute $currentRoute, UploadRepository $uploadRepository): ?Upload
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $uploadRepository->repoUploadquery($id);
        }

        return null;
    }

    /**
     * @psalm-return EntityReader
     */
    private function uploads(UploadRepository $uploadRepository): EntityReader
    {
        return $uploadRepository->findAllPreloaded();
    }

    /**
     * @return \Yiisoft\Data\Reader\DataReaderInterface&\Yiisoft\Data\Reader\SortableDataInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Upload>
     */
    private function uploads_with_sort(UploadRepository $uploadRepository, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        return $uploadRepository->findAllPreloaded()
            ->withSort($sort);
    }

    private function optionsDataClients(EntityReader $clients): array
    {
        $optionsDataClients = [];
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $key                                      = $client->getClient_id();
            null !== $key ? $optionsDataClients[$key] = $client->getClient_full_name() : '';
        }

        return $optionsDataClients;
    }
}
