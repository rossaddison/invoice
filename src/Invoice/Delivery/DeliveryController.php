<?php

declare(strict_types=1);

namespace App\Invoice\Delivery;

use App\Invoice\Entity\Delivery;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Delivery\DeliveryForm;
use App\Invoice\Delivery\DeliveryService;
use App\Invoice\Delivery\DeliveryRepository;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\Setting\SettingRepository;
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
use \Exception;

final class DeliveryController {

    private SessionInterface $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private DeliveryService $deliveryService;
    private TranslatorInterface $translator;

    public function __construct(
        SessionInterface $session,            
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        DeliveryService $deliveryService,
        TranslatorInterface $translator
    ) {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer;
        $this->webService = $webService;
        $this->userService = $userService;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                    ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice')
                    ->withLayout('@views/layout/invoice.php');
        }
        $this->deliveryService = $deliveryService;
        $this->translator = $translator;
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
    public function add(CurrentRoute $currentRoute, Request $request,
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
                'inv' => $inv
            ];
            if ($request->getMethod() === Method::POST) {                
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $this->deliveryService->saveDelivery($delivery, $body, $settingRepository);
                    return $this->webService->getRedirectResponse('inv/edit', ['id' => $inv_id]);
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('delivery/_form', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

 /**
  * @return string
  */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert',
     [ 
       'flash' => $this->flash,
       'errors' => [],
     ]);
   }

    /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flash_message(string $level, string $message): Flash|null {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryRepository $dR
     * @param SettingRepository $sR
     * @param Request $request
     * @return Response
     */
    public function index(CurrentRoute $currentRoute, DeliveryRepository $dR, SettingRepository $sR, Request $request): Response {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['id', 'delivery_location_id'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest quotes first => -id
                ->withOrderString($query_params['sort'] ?? '-id');
        $deliveries = $this->deliveries_with_sort($dR, $sort);
        $paginator = (new OffsetPaginator($deliveries))
                ->withPageSize((int) $sR->get_setting('default_list_limit'))
                ->withCurrentPage((int)$page)
                ->withToken(PageToken::next((string)$page));
        $parameters = [
            'alert' => $this->alert(),
            'paginator' => $paginator,
            'deliveries' => $this->deliveries($dR),
            'max' =>(int) $sR->get_setting('default_list_limit'),
        ];
        return $this->viewRenderer->render('delivery/index', $parameters);
    }

    /**
     * @param DeliveryRepository $dR
     * @param Sort $sort
     *
     * @return SortableDataInterface&DataReaderInterface
     *
     * @psalm-return SortableDataInterface&DataReaderInterface<int, Delivery>
     */
    private function deliveries_with_sort(DeliveryRepository $dR, Sort $sort): SortableDataInterface {
        $deliveries = $dR->findAllPreloaded()
                         ->withSort($sort);
        return $deliveries;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryRepository $deliveryRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, DeliveryRepository $deliveryRepository
    ): Response {
        try {
            $delivery = $this->delivery($currentRoute, $deliveryRepository);
            if ($delivery) {
                $this->deliveryService->deleteDelivery($delivery);
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->webService->getRedirectResponse('delivery/index');
            }
            return $this->webService->getRedirectResponse('delivery/index');
        } catch (Exception $e) {
            $this->flash_message('danger', $e->getMessage());
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
    public function edit(Request $request, CurrentRoute $currentRoute,
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
          if (null!==$inv) {
            $dels = $delRepo->repoClientquery($inv->getClient_id());
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'delivery/edit', 
                'actionArguments' => ['id' => $delivery->getId()],
                'errors' => [],
                'form' => $form,
                'inv' => $inv,
                'del_count' => $delRepo->repoClientCount($inv->getClient_id()),
                'dels' => $dels
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                    $this->deliveryService->saveDelivery($delivery, $body, $settingRepository);
                    return $this->webService->getRedirectResponse('delivery/index');
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
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
    private function delivery(CurrentRoute $currentRoute, DeliveryRepository $deliveryRepository): Delivery|null {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            $delivery = $deliveryRepository->repoDeliveryquery($id);
            return $delivery;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function deliveries(DeliveryRepository $deliveryRepository): \Yiisoft\Data\Cycle\Reader\EntityReader {
        $deliveries = $deliveryRepository->findAllPreloaded();
        return $deliveries;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param DeliveryRepository $deliveryRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, DeliveryRepository $deliveryRepository) : \Yiisoft\DataResponse\DataResponse|Response {
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
