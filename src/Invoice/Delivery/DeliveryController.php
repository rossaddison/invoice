<?php

declare(strict_types=1);

namespace App\Invoice\Delivery;

use App\Invoice\Entity\Delivery;
use App\Invoice\Inv\InvRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Traits\FlashMessage;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\SortableDataInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class DeliveryController
{
    use FlashMessage;
    private Flash $flash;

    public function __construct(
        private SessionInterface $session,
        private ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private UserService $userService,
        private DeliveryService $deliveryService,
        private TranslatorInterface $translator
    ) {
        $this->flash = new Flash($this->session);
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $this->viewRenderer->withControllerName('invoice')
                    ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $this->viewRenderer->withControllerName('invoice')
                    ->withLayout('@views/layout/invoice.php');
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @param InvRepository $iR
     * @param DLR $delRepo
     * @return Response
     */
    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        SettingRepository $settingRepository,
        InvRepository $iR,
        DLR $delRepo
    ): Response {
        $inv_id = $currentRoute->getArgument('inv_id');
        $inv = $iR->repoInvLoadedquery((string) $inv_id);
        if (null !== $inv) {
            $dels = $delRepo->repoClientquery($inv->getClient_id());
            $delivery = new Delivery();
            // inv_id is a hidden field and is static
            $delivery->setInv_id((int)$inv_id);
            $form = new DeliveryForm($delivery);
            $parameters = [
                'title' => $this->translator->translate('invoice.invoice.delivery.add'),
                'actionName' => 'delivery/add',
                'actionArguments' => ['inv_id' => $inv->getId()],
                'errors' => [],
                'form' => $form,
                'del_count' => $delRepo->repoClientCount($inv->getClient_id()),
                'dels' => $dels,
                'inv' => $inv,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->deliveryService->saveDelivery($delivery, $body, $settingRepository);
                        return $this->webService->getRedirectResponse('inv/edit', ['id' => $inv_id]);
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('delivery/_form', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
                'flash' => $this->flash,
                'errors' => [],
            ]
        );
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryRepository $dR
     * @param SettingRepository $sR
     * @param Request $request
     * @return Response
     */
    public function index(CurrentRoute $currentRoute, DeliveryRepository $dR, SettingRepository $sR, Request $request): Response
    {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int)$page > 0 ? (int)$page : 1;
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['id', 'delivery_location_id'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest quotes first => -id
                ->withOrderString($query_params['sort'] ?? '-id');
        $deliveries = $this->deliveries_with_sort($dR, $sort);
        $paginator = (new OffsetPaginator($deliveries))
                ->withPageSize($sR->positiveListLimit())
                ->withCurrentPage($currentPageNeverZero)
                ->withToken(PageToken::next((string)$page));
        $parameters = [
            'alert' => $this->alert(),
            'paginator' => $paginator,
            'deliveries' => $this->deliveries($dR),
            'max' => (int) $sR->getSetting('default_list_limit'),
        ];
        return $this->viewRenderer->render('delivery/index', $parameters);
    }

    /**
     * @param DeliveryRepository $dR
     * @param Sort $sort
     *
     * @return DataReaderInterface&SortableDataInterface
     *
     * @psalm-return SortableDataInterface&DataReaderInterface<int, Delivery>
     */
    private function deliveries_with_sort(DeliveryRepository $dR, Sort $sort): SortableDataInterface
    {
        return $dR->findAllPreloaded()
                         ->withSort($sort);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryRepository $deliveryRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        DeliveryRepository $deliveryRepository
    ): Response {
        try {
            $delivery = $this->delivery($currentRoute, $deliveryRepository);
            if ($delivery) {
                $this->deliveryService->deleteDelivery($delivery);
                $this->flashMessage('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('delivery/index');
            }
            return $this->webService->getRedirectResponse('delivery/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('delivery/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param DeliveryRepository $deliveryRepository
     * @param SettingRepository $settingRepository
     * @param DLR $delRepo
     * @param InvRepository $iR
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        DeliveryRepository $deliveryRepository,
        SettingRepository $settingRepository,
        DLR $delRepo,
        InvRepository $iR
    ): Response {
        $delivery = $this->delivery($currentRoute, $deliveryRepository);
        if ($delivery) {
            $form = new DeliveryForm($delivery);
            $inv_id = $delivery->getInv_id();
            $inv = $iR->repoInvLoadedquery((string) $inv_id);
            if (null !== $inv) {
                $dels = $delRepo->repoClientquery($inv->getClient_id());
                $parameters = [
                    'title' => $this->translator->translate('i.edit'),
                    'actionName' => 'delivery/edit',
                    'actionArguments' => ['id' => $delivery->getId()],
                    'errors' => [],
                    'form' => $form,
                    'inv' => $inv,
                    'del_count' => $delRepo->repoClientCount($inv->getClient_id()),
                    'dels' => $dels,
                ];
                if ($request->getMethod() === Method::POST) {
                    $body = $request->getParsedBody() ?? [];
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        if (is_array($body)) {
                            $this->deliveryService->saveDelivery($delivery, $body, $settingRepository);
                            return $this->webService->getRedirectResponse('delivery/index');
                        }
                    }
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
                return $this->viewRenderer->render('delivery/_form', $parameters);
            } // null!==$inv
        }
        return $this->webService->getRedirectResponse('delivery/index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryRepository $deliveryRepository
     * @return Delivery|null
     */
    private function delivery(CurrentRoute $currentRoute, DeliveryRepository $deliveryRepository): Delivery|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $deliveryRepository->repoDeliveryquery($id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function deliveries(DeliveryRepository $deliveryRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $deliveryRepository->findAllPreloaded();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryRepository $deliveryRepository
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(CurrentRoute $currentRoute, DeliveryRepository $deliveryRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $delivery = $this->delivery($currentRoute, $deliveryRepository);
        if ($delivery) {
            $form = new DeliveryForm($delivery);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'delivery/view',
                'actionArguments' => ['id' => $delivery->getId()],
                'errors' => [],
                'form' => $form,
                'delivery' => $delivery,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('delivery/index');
    }
}
