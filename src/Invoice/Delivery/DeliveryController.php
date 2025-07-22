<?php

declare(strict_types=1);

namespace App\Invoice\Delivery;

use App\Invoice\BaseController;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\Entity\Delivery;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\SortableDataInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class DeliveryController extends BaseController
{
    protected string $controllerName = 'invoice/delivery';

    public function __construct(
        private DeliveryService $deliveryService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->deliveryService = $deliveryService;
    }

    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        InvRepository $iR,
        DLR $delRepo,
    ): Response {
        $inv_id = $currentRoute->getArgument('inv_id');
        $inv    = $iR->repoInvLoadedquery((string) $inv_id);
        if (null !== $inv) {
            $dels     = $delRepo->repoClientquery($inv->getClient_id());
            $delivery = new Delivery();
            // inv_id is a hidden field and is static
            $delivery->setInv_id((int) $inv_id);
            $form       = new DeliveryForm($delivery);
            $parameters = [
                'title'           => $this->translator->translate('delivery.add'),
                'actionName'      => 'delivery/add',
                'actionArguments' => ['inv_id' => $inv->getId()],
                'errors'          => [],
                'form'            => $form,
                'del_count'       => $delRepo->repoClientCount($inv->getClient_id()),
                'dels'            => $dels,
                'inv'             => $inv,
            ];
            if (Method::POST === $request->getMethod()) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->deliveryService->saveDelivery($delivery, $body, $this->sR);

                        return $this->webService->getRedirectResponse('inv/edit', ['id' => $inv_id]);
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form']   = $form;
            }

            return $this->viewRenderer->render('_form', $parameters);
        }

        return $this->webService->getNotFoundResponse();
    }

    public function index(CurrentRoute $currentRoute, DeliveryRepository $dR, Request $request): Response
    {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $page > 0 ? (int) $page : 1;
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['id', 'delivery_location_id'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest quotes first => -id
            ->withOrderString($query_params['sort'] ?? '-id');
        $deliveries = $this->deliveries_with_sort($dR, $sort);
        $paginator  = (new OffsetPaginator($deliveries))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withToken(PageToken::next((string) $page));
        $parameters = [
            'alert'      => $this->alert(),
            'paginator'  => $paginator,
            'deliveries' => $this->deliveries($dR),
            'max'        => (int) $this->sR->getSetting('default_list_limit'),
        ];

        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @return DataReaderInterface&SortableDataInterface
     *
     * @psalm-return SortableDataInterface&DataReaderInterface<int, Delivery>
     */
    private function deliveries_with_sort(DeliveryRepository $dR, Sort $sort): SortableDataInterface
    {
        return $dR->findAllPreloaded()
            ->withSort($sort);
    }

    public function delete(
        CurrentRoute $currentRoute,
        DeliveryRepository $deliveryRepository,
    ): Response {
        try {
            $delivery = $this->delivery($currentRoute, $deliveryRepository);
            if ($delivery) {
                $this->deliveryService->deleteDelivery($delivery);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));

                return $this->webService->getRedirectResponse('delivery/index');
            }

            return $this->webService->getRedirectResponse('delivery/index');
        } catch (\Exception $e) {
            $this->flashMessage('danger', $e->getMessage());

            return $this->webService->getRedirectResponse('delivery/index');
        }
    }

    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        DeliveryRepository $deliveryRepository,
        DLR $delRepo,
        InvRepository $iR,
    ): Response {
        $delivery = $this->delivery($currentRoute, $deliveryRepository);
        if ($delivery) {
            $form   = new DeliveryForm($delivery);
            $inv_id = $delivery->getInv_id();
            $inv    = $iR->repoInvLoadedquery((string) $inv_id);
            if (null !== $inv) {
                $dels       = $delRepo->repoClientquery($inv->getClient_id());
                $parameters = [
                    'title'           => $this->translator->translate('edit'),
                    'actionName'      => 'delivery/edit',
                    'actionArguments' => ['id' => $delivery->getId()],
                    'errors'          => [],
                    'form'            => $form,
                    'inv'             => $inv,
                    'del_count'       => $delRepo->repoClientCount($inv->getClient_id()),
                    'dels'            => $dels,
                ];
                if (Method::POST === $request->getMethod()) {
                    $body = $request->getParsedBody() ?? [];
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        if (is_array($body)) {
                            $this->deliveryService->saveDelivery($delivery, $body, $this->sR);

                            return $this->webService->getRedirectResponse('delivery/index');
                        }
                    }
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                    $parameters['form']   = $form;
                }

                return $this->viewRenderer->render('_form', $parameters);
            } // null!==$inv
        }

        return $this->webService->getRedirectResponse('delivery/index');
    }

    // For rbac refer to AccessChecker

    private function delivery(CurrentRoute $currentRoute, DeliveryRepository $deliveryRepository): ?Delivery
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $deliveryRepository->repoDeliveryquery($id);
        }

        return null;
    }

    /**
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function deliveries(DeliveryRepository $deliveryRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $deliveryRepository->findAllPreloaded();
    }

    public function view(CurrentRoute $currentRoute, DeliveryRepository $deliveryRepository): \Yiisoft\DataResponse\DataResponse|Response
    {
        $delivery = $this->delivery($currentRoute, $deliveryRepository);
        if ($delivery) {
            $form       = new DeliveryForm($delivery);
            $parameters = [
                'title'           => $this->translator->translate('view'),
                'actionName'      => 'delivery/view',
                'actionArguments' => ['id' => $delivery->getId()],
                'errors'          => [],
                'form'            => $form,
                'delivery'        => $delivery,
            ];

            return $this->viewRenderer->render('_view', $parameters);
        }

        return $this->webService->getRedirectResponse('delivery/index');
    }
}
