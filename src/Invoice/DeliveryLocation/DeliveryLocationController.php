<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryLocation;

use App\Invoice\Entity\DeliveryLocation;
use App\Invoice\DeliveryLocation\DeliveryLocationForm;
use App\Invoice\DeliveryLocation\DeliveryLocationService;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Inv\InvRepository as IR;  
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use \Exception;

final class DeliveryLocationController {

  private SessionInterface $session;
  private Flash $flash;
  private ViewRenderer $viewRenderer;
  private WebControllerService $webService;
  private UserService $userService;
  private DeliveryLocationService $delService;

  private const DELS_PER_PAGE = 1;

  private TranslatorInterface $translator;
  private DataResponseFactoryInterface $factory;

  public function __construct(
    SessionInterface $session,
    ViewRenderer $viewRenderer,
    WebControllerService $webService,
    UserService $userService,
    DeliveryLocationService $delService,
    TranslatorInterface $translator,
    DataResponseFactoryInterface $factory
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
    $this->delService = $delService;
    $this->translator = $translator;
    $this->factory = $factory;
  }

  /**
   * @see config/common/routes/routes.php 'del/index'
   * @see Currently the accesschecker only allows an administrator for delivery locations i.e. no viewInv permissions granted
   * @param CurrentRoute $currentRoute
   * @param DeliveryLocationRepository $delRepository
   * @param SettingRepository $sR
   * @param CR $cR
   * @param IR $iR
   * @param QR $qR
   * @return Response
   */
  public function index(CurrentRoute $currentRoute, DeliveryLocationRepository $delRepository, SettingRepository $sR, CR $cR, IR $iR, QR $qR): Response {
    $page = (int)$currentRoute->getArgument('page', '1');
    /** @psalm-var positive-int $currentPageNeverZero */
    $currentPageNeverZero = $page > 0 ? $page : 1;
    $dels = $delRepository->findAllPreloaded();
    $paginator = (new OffsetPaginator($dels))
      ->withPageSize((int) $sR->get_setting('default_list_limit'))
      ->withCurrentPage($currentPageNeverZero)
      ->withToken(PageToken::next((string)$page));
    $this->add_in_invoice_flash();
    $parameters = [
      'dels' => $this->dels($delRepository),
      'alert' => $this->alert(),
      'paginator' => $paginator,
      'cR' => $cR,
      // Use the invoice Repository to locate all the invoices relevant to this location
      'iR' => $iR,
      'qR' => $qR,  
      'alerts' => $this->alert(),
      'max' => (int) $sR->get_setting('default_list_limit'),
    ];
    return $this->viewRenderer->render('del/index', $parameters);
  }
  
  public function add_in_invoice_flash() : void {
    $this->flash_message('info', $this->translator->translate('invoice.invoice.delivery.location.add.in.invoice'));
  }

  /**
   * 
   * @param CurrentRoute $currentRoute
   * @param Request $request
   * @param FormHydrator $formHydrator
   * @return Response
   */
  public function add(CurrentRoute $currentRoute, Request $request,
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
            'action' => $action
      ],
      'errors' => [],
      'form' => $form,
      'session' => $this->session,
      'electronic_address_scheme' => PeppolArrays::electronic_address_scheme()
    ];

    if ($request->getMethod() === Method::POST) {
      $body = $request->getParsedBody();
      if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
        /**
         * @psalm-suppress PossiblyInvalidArgument $body
         */  
        $this->delService->saveDeliveryLocation($delivery_location, $body);
        $this->flash_message('success', $this->translator->translate('i.record_successfully_created'));
        $url = $origin.'/'.$action;
        // Route::methods([Method::GET, Method::POST], '/del/add/{client_id}[/{origin}/{origin_id}/{action}]')
        if ($origin_id) {
            // Redirect to client/view: invoice.myhost/invoice/del/add/25/client/25/view
            /**
             * @psalm-suppress MixedArgumentTypeCoercion 
             */
            return $this->webService->getRedirectResponse($url, [
                'id' => $origin_id
            ]);
        } else {
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
  public function edit(Request $request, CurrentRoute $currentRoute,
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
        'electronic_address_scheme' => PeppolArrays::electronic_address_scheme()
      ];
      if ($request->getMethod() === Method::POST) {
        $body = $request->getParsedBody();
        /**
         * @psalm-suppress PossiblyInvalidArgument $body
         */
        if ($formHydrator->populateFromPostAndValidate($form, $request)) {
           /**
            * @psalm-suppress PossiblyInvalidArgument $body
            */  
           $this->delService->saveDeliveryLocation($del, $body);
           $this->flash_message('success', $this->translator->translate('i.record_successfully_created'));
           $url = $origin.'/'.$action;
           // Route::methods([Method::GET, Method::POST], '/del/edit/{client_id}[/{origin}/{origin_id}/{action}]')
           if ($origin_id) {
               // Redirect to client/view: invoice.myhost/invoice/del/add/25/client/25/view
               /**
                * @psalm-suppress MixedArgumentTypeCoercion 
                */
               return $this->webService->getRedirectResponse($url, [
                   'id' => $origin_id
               ]);
           } else {
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
  public function delete(CurrentRoute $currentRoute, DeliveryLocationRepository $delRepository
  ): Response {
    try {
      $del = $this->del($currentRoute, $delRepository);
      if ($del) {
        $this->delService->deleteDeliveryLocation($del);
        $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
        return $this->webService->getRedirectResponse('del/index');
      }
      return $this->webService->getRedirectResponse('del/index');
    } catch (Exception $e) {
      $this->flash_message('danger', $e->getMessage());
      return $this->webService->getRedirectResponse('del/index');
    }
  }

  /**
   * @param CurrentRoute $currentRoute
   * @param DeliveryLocationRepository $delRepository
   * @return \Yiisoft\DataResponse\DataResponse|Response
   */
  public function view(CurrentRoute $currentRoute, DeliveryLocationRepository $delRepository): \Yiisoft\DataResponse\DataResponse|Response {
    $del = $this->del($currentRoute, $delRepository);
    if ($del) {
      $form = new DeliveryLocationForm($del);  
      $parameters = [
        'title' => $this->translator->translate('i.view'),
        'actionName' => 'del/view', 
        'actionArguments' => ['id' => $del->getId()],
        'form' => $form,
        'del' => $delRepository->repoDeliveryLocationquery((string) $del->getId()),
        'electronic_address_scheme' => PeppolArrays::electronic_address_scheme()  
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
  private function del(CurrentRoute $currentRoute, DeliveryLocationRepository $delRepository): DeliveryLocation|null {
    $id = $currentRoute->getArgument('id');
    if (null!==$id) {
      $del = $delRepository->repoDeliveryLocationquery($id);
      return $del;
    }
    return null;
  }

  /**
   * @return \Yiisoft\Data\Cycle\Reader\EntityReader
   *
   * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
   */
  private function dels(DeliveryLocationRepository $delRepository): \Yiisoft\Data\Cycle\Reader\EntityReader {
    $dels = $delRepository->findAllPreloaded();
    return $dels;
  }

  /**
  * @return string
  */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert',
     [ 
       'flash' => $this->flash
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
}