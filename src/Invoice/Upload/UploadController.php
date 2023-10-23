<?php

declare(strict_types=1);

namespace App\Invoice\Upload;

use App\Invoice\Entity\Upload;
use App\Invoice\Upload\UploadForm;
use App\Invoice\Upload\UploadService;
use App\Invoice\Upload\UploadRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Client\ClientRepository;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;
use \Exception;

final class UploadController {
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
    public function index(Request $request, CurrentRoute $currentRoute, UploadRepository $uploadRepository): \Yiisoft\DataResponse\DataResponse {
        /** @var string $query_params['sort'] */
        $page = (int) $currentRoute->getArgument('page', '1');
        $query_params = $request->getQueryParams();
        $sort = Sort::only(['id', 'client_id', 'file_name_original'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest uploads first => -id
                ->withOrderString($query_params['sort'] ?? '-id');
        $uploads = $this->uploads_with_sort($uploadRepository, $sort);
        $paginator = (new OffsetPaginator($uploads))
                ->withPageSize((int) $this->s->get_setting('default_list_limit'))
                ->withCurrentPage($page)
                ->withNextPageToken((string) $page);

        $parameters = [
            'paginator' => $paginator,
            'grid_summary' => $this->s->grid_summary($paginator, $this->translator, (int) $this->s->get_setting('default_list_limit'), $this->translator->translate('invoice.upload.plural'), ''),
            'uploads' => $this->uploads($uploadRepository),
            'alert' => $this->alert()
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request,
            FormHydrator $formHydrator,
            ClientRepository $clientRepository
    ): Response {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['upload/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            'head' => $head,
            'clients' => $clientRepository->findAllPreloaded(),
        ];

        if ($request->getMethod() === Method::POST) {
            $form = new UploadForm();
            $upload = new Upload();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->uploadService->saveUpload($upload, $form);
                return $this->webService->getRedirectResponse('upload/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @return string
     */
    private function alert(): string {
      return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
      [ 
        'flash' => $this->flash,
        'errors' => [],
      ]);
    }

    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
      $this->flash->add($level, $message, true);
      return $this->flash;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param UploadRepository $uploadRepository
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute,
            UploadRepository $uploadRepository,
            SettingRepository $settingRepository
    ): Response {
        try {
            $upload = $this->upload($currentRoute, $uploadRepository);
            if ($upload) {
                $this->uploadService->deleteUpload($upload, $settingRepository);
                $inv_id = (string) $this->session->get('inv_id');
                $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
                return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                                        ['heading' => '', 'message' => $settingRepository->trans('record_successfully_deleted'), 'url' => 'inv/view', 'id' => $inv_id]));
            }
            return $this->webService->getRedirectResponse('upload/index');
        } catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('upload/index');
        }
    }

    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param UploadRepository $uploadRepository
     * @param SettingRepository $settingRepository
     * @param ClientRepository $clientRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, 
            Request $request, CurrentRoute $currentRoute,
            FormHydrator $formHydrator,
            UploadRepository $uploadRepository,
            SettingRepository $settingRepository,
            ClientRepository $clientRepository
    ): Response {
        $upload = $this->upload($currentRoute, $uploadRepository);
        if ($upload) {
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['upload/edit', ['id' => $upload->getId()]],
                'errors' => [],
                'body' => $this->body($upload),
                'head' => $head,
                'clients' => $clientRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new UploadForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->uploadService->saveUpload($upload, $form);
                    return $this->webService->getRedirectResponse('upload/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('upload/index');
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param UploadRepository $uploadRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, UploadRepository $uploadRepository,
            SettingRepository $settingRepository,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $upload = $this->upload($currentRoute, $uploadRepository);
        if ($upload) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['upload/view', ['id' => $upload->getId()]],
                'errors' => [],
                'body' => $this->body($upload),
                's' => $settingRepository,
                'upload' => $uploadRepository->repoUploadquery($upload->getId()),
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
    public function upload(CurrentRoute $currentRoute, UploadRepository $uploadRepository): Upload|null {
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
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function uploads(UploadRepository $uploadRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader {
        $uploads = $uploadRepository->findAllPreloaded();
        return $uploads;
    }

    /**
     * @param Upload $upload
     * @return array
     */
    private function body(Upload $upload): array {
        $body = [
            'id' => $upload->getId(),
            'client_id' => $upload->getClient_id(),
            'url_key' => $upload->getUrl_key(),
            'file_name_original' => $upload->getFile_name_original(),
            'file_name_new' => $upload->getFile_name_new(),
            'description' => $upload->getDescription(),
            'uploaded_date' => $upload->getUploaded_date()
        ];
        return $body;
    }

    /**
     * @param UploadRepository $uploadRepository
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Upload>
     */
    private function uploads_with_sort(UploadRepository $uploadRepository, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {
        $uploads = $uploadRepository->findAllPreloaded()
                ->withSort($sort);
        return $uploads;
    }

}
