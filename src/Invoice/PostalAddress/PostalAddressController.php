<?php

declare(strict_types=1);

namespace App\Invoice\PostalAddress;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\PostalAddress;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator as FastRouteGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class PostalAddressController extends BaseController
{
    protected string $controllerName = 'invoice/postaladdress';

    public function __construct(
        private PostalAddressService $postaladdressService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->postaladdressService = $postaladdressService;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ClientRepository $clientRepo
     * @return Response
     */
    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
    ): Response {
        $client_id = $currentRoute->getArgument('client_id');
        $queryParams = $request->getQueryParams();
        /**
         * @var array $queryParams
         */
        $origin = (string) $queryParams['origin'];
        $origin_id = (int) $queryParams['origin_id'];
        $action = (string) $queryParams['action'];
        $postalAddress = new PostalAddress();
        $form = new PostalAddressForm($this->translator, $postalAddress, (int) $client_id);
        $parameters = [
            'canEdit' => ($this->userService->hasPermission(Permissions::VIEW_INV) && $this->userService->hasPermission(Permissions::EDIT_INV)) ? true : false,
            'client_id' => $client_id,
            'title' => $this->translator->translate('add'),
            'actionName' => 'postaladdress/add',
            'actionArguments' => ['client_id' => $client_id],
            'actionQueryParameters' => [
                'origin' => $origin,
                'origin_id' => $origin_id,
                // origin form action e.g. normally 'add', or 'edit
                'action' => $action,
            ],
            'errors' => [],
            'form' => $form,
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->postaladdressService->savePostalAddress($postalAddress, $body);
                    $this->flashMessage('success', $this->translator->translate('record.successfully.created'));
                    $url = $origin . '/' . $action;
                    if ($origin_id) {
                        /**
                         * @psalm-suppress MixedArgumentTypeCoercion
                         */
                        return $this->webService->getRedirectResponse($url, [
                            'id' => $origin_id,
                        ]);
                    }
                    return $this->webService->getRedirectResponse($url);
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param FastRouteGenerator $urlFastRouteGenerator
     * @param CurrentRoute $routeCurrent
     * @param PostalAddressRepository $postaladdressRepository
     * @param ClientRepository $cR
     * @param string $page
     * @return Response
     */
    public function index(
        FastRouteGenerator $urlFastRouteGenerator,
        CurrentRoute $routeCurrent,
        PostalAddressRepository $postaladdressRepository,
        ClientRepository $cR,
        #[RouteArgument('page')]
        string $page = '1',
    ): Response {
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $postaladdresses = $this->postaladdresses($postaladdressRepository);
        $paginator = (new OffsetPaginator($postaladdresses))
        ->withPageSize($this->sR->positiveListLimit())
        ->withCurrentPage($currentPageNeverZero)
        ->withToken(PageToken::next($page));
        $parameters = [
            'canEdit' => ($this->userService->hasPermission(Permissions::VIEW_INV) && $this->userService->hasPermission(Permissions::EDIT_INV)) ? true : false,
            'postaladdresses' => $postaladdresses,
            'alert' => $this->alert(),
            'paginator' => $paginator,
            'max' => (int) $this->sR->getSetting('default_list_limit'),
            'cR' => $cR,
            'routeCurrent' => $routeCurrent,
            'urlFastRouteGenerator' => $urlFastRouteGenerator,
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PostalAddressRepository $postaladdressRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, PostalAddressRepository $postaladdressRepository): Response
    {
        try {
            $postaladdress = $this->postaladdress($currentRoute, $postaladdressRepository);
            if ($postaladdress) {
                $this->postaladdressService->deletePostalAddress($postaladdress);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('postaladdress/index');
            }
            return $this->webService->getRedirectResponse('postaladdress/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('postaladdress/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param PostalAddressRepository $postalAddressRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        PostalAddressRepository $postalAddressRepository,
    ): Response {
        $postalAddress = $this->postaladdress($currentRoute, $postalAddressRepository);
        if ($postalAddress) {
            $queryParams = $request->getQueryParams();
            /**
             * Related logic: see config/common/routes/routes.php '/postaladdress/edit/{id}[/{origin}/{origin_id}/{action}]'
             * @var array $queryParams
             */
            $origin = (string) $queryParams['origin'];
            $origin_id = (int) $queryParams['origin_id'];
            $action = (string) $queryParams['action'];
            $form = new PostalAddressForm($this->translator, $postalAddress, (int) $postalAddress->getClient_id());
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'postaladdress/edit',
                'actionArguments' => ['id' => $postalAddress->getId()],
                'actionQueryParameters' => [
                    'origin' => $origin,
                    'origin_id' => $origin_id,
                    'action' => $action,
                ],
                'errors' => [],
                'form' => $form,
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $this->postaladdressService->savePostalAddress($postalAddress, $body);
                        $this->flashMessage('success', $this->translator->translate('record.successfully.created'));
                        $url = $origin . '/' . $action;
                        // Route::methods([Method::GET, Method::POST], '/postaladdress/edit/{client_id}[/{origin}/{origin_id}/{action}]')
                        if ($origin_id) {
                            /**
                             * Related logic: see http://invoice.myhost/invoice/postaladdress/edit/1?origin=inv&origin_id=1&action=edit
                             * @psalm-suppress MixedArgumentTypeCoercion
                             */
                            return $this->webService->getRedirectResponse($url, [
                                'id' => $origin_id,
                            ]);
                        }
                        // Redirect to inv/index
                        return $this->webService->getRedirectResponse($url);
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('postaladdress/index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param PostalAddressRepository $postaladdressRepository
     * @return PostalAddress|null
     */
    private function postaladdress(CurrentRoute $currentRoute, PostalAddressRepository $postaladdressRepository): ?PostalAddress
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            /* @var PostalAddress $postaladdress */
            return $postaladdressRepository->repoPostalAddressLoadedquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function postaladdresses(PostalAddressRepository $postaladdressRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $postaladdressRepository->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param PostalAddressRepository $postalAddressRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        CurrentRoute $currentRoute,
        PostalAddressRepository $postalAddressRepository,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $postalAddress = $this->postaladdress($currentRoute, $postalAddressRepository);
        if ($postalAddress) {
            $form = new PostalAddressForm($this->translator, $postalAddress, (int) $postalAddress->getClient_id());
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'postaladdress/view',
                'actionArguments' => ['id' => $postalAddress->getId()],
                'form' => $form,
                'postaladdress' => $postalAddress,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('postaladdress/index');
    }
}
