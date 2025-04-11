<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryLocation;

use App\Invoice\BaseController;
use App\Invoice\Entity\DeliveryLocation;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class DeliveryLocationController extends BaseController
{
    protected string $controllerName = 'invoice/del';
    
    public function __construct(
        private DeliveryLocationService $delService,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator, 
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR);
        $this->delService = $delService;
        $this->factory = $factory;
    }    
    /**
     * @see ../resources/views/invoice/del/index.php
     * @param DeliveryLocationRepository $delRepository
     * @param CR $cR
     * @param IR $iR
     * @param QR $qR
     * @param string $queryPage
     * @param string $querySort
     * @return Response
     */
    public function index(
        DeliveryLocationRepository $delRepository,
        CR $cR,
        IR $iR,
        QR $qR,
        #[Query('page')] string $queryPage = null,
        #[Query('sort')] string $querySort = null,
    ): Response {
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int)$queryPage > 0 ? (int)$queryPage : 1;
        $this->add_in_invoice_flash();
        $parameters = [
            'alert' => $this->alert(),
            'cR' => $cR,
            'dels' => $this->dels($delRepository),
            'iR' => $iR,
            'page' => $currentPageNeverZero,
            // Use the invoice Repository to locate all the invoices relevant to this location
            'qR' => $qR,
            'sortString' => $querySort ?? '-id',
        ];
        return $this->viewRenderer->render('del/index', $parameters);
    }

    public function add_in_invoice_flash(): void
    {
        $this->flashMessage('info', $this->translator->translate('invoice.invoice.delivery.location.add.in.invoice'));
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
    ): Response {
        $client_id = $currentRoute->getArgument('client_id');
        /**
         * Query parameters are between the square brackets in the example below
         * @see config/common/routes/routes/routes.php Route::methods([Method::GET, Method::POST], '/del/add/{client_id}[/{origin}/{origin_id}/{action}]')
         * @see vendor/yiisoft/router/src/UrlGeneratorInterface Query parameters
         * Delivery locations can be added from either the quote form or the invoice form
         * Origin allows us to return to either the quote form or invoice form on completion
         * of creating the delivery location by creating a return $url
         */
        $queryParams = $request->getQueryParams();
        /**
         * @var array $queryParams
         */
        $origin = (string)$queryParams['origin'];
        $origin_id = (int)$queryParams['origin_id'];
        $action = (string)$queryParams['action'];

        $delivery_location = new DeliveryLocation();
        $delivery_location->setClient_id((int)$client_id);
        $form = new DeliveryLocationForm($delivery_location);

        $parameters = [
            'title' => $this->translator->translate('invoice.invoice.delivery.location.add'),
            'actionName' => 'del/add',
            'actionArguments' => ['client_id' => $client_id],
            'actionQueryParameters' => [
                'origin' => $origin,
                'origin_id' => $origin_id,
                // origin form action e.g. normally 'add', or 'edit
                'action' => $action,
            ],
            'errors' => [],
            'form' => $form,
            'session' => $this->session,
            'electronic_address_scheme' => PeppolArrays::electronic_address_scheme(),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->delService->saveDeliveryLocation($delivery_location, $body);
                    $this->flashMessage('success', $this->translator->translate('i.record_successfully_created'));
                    $url = $origin . '/' . $action;
                    // Route::methods([Method::GET, Method::POST], '/del/add/{client_id}[/{origin}/{origin_id}/{action}]')
                    if ($origin_id) {
                        // Redirect to client/view: invoice.myhost/invoice/del/add/25/client/25/view
                        /**
                         * @psalm-suppress MixedArgumentTypeCoercion
                         */
                        return $this->webService->getRedirectResponse($url, [
                            'inv_id' => $origin_id,
                            '_language' => $currentRoute->getArgument('_language'),
                        ]);
                    }
                    // Redirect to inv/index
                    return $this->webService->getRedirectResponse($url);
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('del/_form', $parameters);
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param DeliveryLocationRepository $delRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        DeliveryLocationRepository $delRepository,
    ): Response {
        $del = $this->del($currentRoute, $delRepository);
        if ($del) {
            $queryParams = $request->getQueryParams();

            /**
             * @var array $queryParams
             */
            $origin = (string)$queryParams['origin'];
            $origin_id = (int)$queryParams['origin_id'];
            $action = (string)$queryParams['action'];

            $form = new DeliveryLocationForm($del);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'del/edit',
                'actionArguments' => ['id' => $del->getId()],
                'actionQueryParameters' => ['origin' => $origin, 'origin_id' => $origin_id, 'action' => $action],
                'errors' => [],
                'form' => $form,
                'electronic_address_scheme' => PeppolArrays::electronic_address_scheme(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        $this->delService->saveDeliveryLocation($del, $body);
                        $this->flashMessage('success', $this->translator->translate('i.record_successfully_created'));
                        $url = $origin . '/' . $action;
                        // Route::methods([Method::GET, Method::POST], '/del/edit/{client_id}[/{origin}/{origin_id}/{action}]')
                        if ($origin_id) {
                            // Redirect to client/view: invoice.myhost/invoice/del/add/25/client/25/view
                            /**
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
            return $this->viewRenderer->render('del/_form', $parameters);
        }
        return $this->webService->getRedirectResponse('del/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryLocationRepository $delRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        DeliveryLocationRepository $delRepository
    ): Response {
        try {
            $del = $this->del($currentRoute, $delRepository);
            if ($del) {
                $this->delService->deleteDeliveryLocation($del);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('del/index');
            }
            return $this->webService->getRedirectResponse('del/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('del/index');
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryLocationRepository $delRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(CurrentRoute $currentRoute, DeliveryLocationRepository $delRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $del = $this->del($currentRoute, $delRepository);
        if ($del) {
            $form = new DeliveryLocationForm($del);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'del/view',
                'actionArguments' => ['id' => $del->getId()],
                'form' => $form,
                'del' => $delRepository->repoDeliveryLocationquery((string) $del->getId()),
                'electronic_address_scheme' => PeppolArrays::electronic_address_scheme(),
            ];
            return $this->viewRenderer->render('del/_view', $parameters);
        }
        return $this->webService->getRedirectResponse('delivery_location/index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryLocationRepository $delRepository
     * @return DeliveryLocation|null
     */
    private function del(CurrentRoute $currentRoute, DeliveryLocationRepository $delRepository): DeliveryLocation|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $delRepository->repoDeliveryLocationquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function dels(DeliveryLocationRepository $delRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $delRepository->findAllPreloaded();
    }
}
