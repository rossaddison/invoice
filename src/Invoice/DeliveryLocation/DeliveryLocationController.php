<?php

declare(strict_types=1);

namespace App\Invoice\DeliveryLocation;

use App\Invoice\Entity\DeliveryLocation;
use App\Invoice\DeliveryLocation\DeliveryLocationService;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Inv\InvRepository as IR;  
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;
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

  public function index(CurrentRoute $currentRoute, DeliveryLocationRepository $delRepository, SettingRepository $sR, CR $cR, IR $iR): Response {
    $page = (int) $currentRoute->getArgument('page', '1');
    $dels = $delRepository->findAllPreloaded();
    $paginator = (new OffsetPaginator($dels))
      ->withPageSize((int) $sR->get_setting('default_list_limit'))
      ->withCurrentPage($page)
      ->withNextPageToken((string) $page);
    $this->add_in_invoice_flash();
    $parameters = [
      'dels' => $this->dels($delRepository),
      'alert' => $this->alert(),
      'paginator' => $paginator,
      'cR' => $cR,
      // Use the invoice Repository to locate all the invoices relevant to this location
      'iR' => $iR,  
      'alerts' => $this->alert(),
      'max' => (int) $sR->get_setting('default_list_limit'),
      'canEdit' => $this->userService->hasPermission('editInv'),
      'grid_summary' => $sR->grid_summary($paginator, $this->translator, (int) $sR->get_setting('default_list_limit'), $this->translator->translate('invoice.delivery.location.plural'), ''),
    ];
    return $this->viewRenderer->render('/invoice/del/index', $parameters);
  }
  
  public function add_in_invoice_flash() : void {
    $this->flash_message('info', $this->translator->translate('invoice.invoice.delivery.location.add.in.invoice'));
  }

  public function add(CurrentRoute $currentRoute, ViewRenderer $head, Request $request,
    FormHydrator $formHydrator,
    SettingRepository $settingRepository,
  ): Response {
    $client_id = $currentRoute->getArgument('client_id');
    $parameters = [
      'title' => $this->translator->translate('invoice.invoice.delivery.location.add'),
      'action' => ['del/add', ['client_id' => $client_id]],
      'errors' => [],
      'client_id' => $client_id,
      'body' => $request->getParsedBody(),
      's' => $settingRepository,
      'head' => $head,
      'session' => $this->session,
      'electronic_address_scheme' => PeppolArrays::electronic_address_scheme()
    ];

    if ($request->getMethod() === Method::POST) {
      $form = new DeliveryLocationForm();
      if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
        $this->delService->saveDeliveryLocation(new DeliveryLocation(), $form);
        return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
              ['heading' => 'Successful', 'message' => $settingRepository->trans('record_successfully_created'), 'url' => 'client/view', 'id' => $client_id]));
      }
      $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
    }
    return $this->viewRenderer->render('/invoice/del/_form', $parameters);
  }

  /**
   * @param Request $request
   * @param CurrentRoute $currentRoute
   * @param FormHydrator $formHydrator
   * @param DeliveryLocationRepository $delRepository
   * @param SettingRepository $settingRepository
   * @return Response
   */
  public function edit(Request $request, CurrentRoute $currentRoute,
    FormHydrator $formHydrator,
    DeliveryLocationRepository $delRepository,
    SettingRepository $settingRepository,
  ): Response {
    $del = $this->del($currentRoute, $delRepository);
    if ($del) {
      $parameters = [
        'title' => $settingRepository->trans('edit'),
        'action' => ['del/edit', ['id' => $del->getId()]],
        'errors' => [],
        'body' => $this->body($del),
        'head' => $this->viewRenderer,
        's' => $settingRepository,
        'electronic_address_scheme' => PeppolArrays::electronic_address_scheme()
      ];
      if ($request->getMethod() === Method::POST) {
        $form = new DeliveryLocationForm();
        $body = $request->getParsedBody();
        if ($formHydrator->populate($form, $body) && $form->isValid()) {
          $this->delService->saveDeliveryLocation($del, $form);
          return $this->webService->getRedirectResponse('del/index');
        }
        $parameters['body'] = $body;
        $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
      }
      return $this->viewRenderer->render('/invoice/del/_form', $parameters);
    }
    return $this->webService->getRedirectResponse('del/index');
  }

  /**
   *
   * @param SettingRepository $settingRepository
   * @param CurrentRoute $currentRoute
   * @param DeliveryLocationRepository $delRepository
   * @return Response
   */
  public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute, DeliveryLocationRepository $delRepository
  ): Response {
    try {
      $del = $this->del($currentRoute, $delRepository);
      if ($del) {
        $this->delService->deleteDeliveryLocation($del);
        $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
        return $this->webService->getRedirectResponse('del/index');
      }
      return $this->webService->getRedirectResponse('del/index');
    } catch (Exception $e) {
      $this->flash_message('danger', $e->getMessage());
      return $this->webService->getRedirectResponse('del/index');
    }
  }

  /**
   *
   * @param CurrentRoute $currentRoute
   * @param DeliveryLocationRepository $delRepository
   * @param SettingRepository $settingRepository
   * @return \Yiisoft\DataResponse\DataResponse|Response
   */
  public function view(CurrentRoute $currentRoute, DeliveryLocationRepository $delRepository,
    SettingRepository $settingRepository,
  ): \Yiisoft\DataResponse\DataResponse|Response {
    $del = $this->del($currentRoute, $delRepository);
    if ($del) {
      $parameters = [
        'title' => $settingRepository->trans('view'),
        'action' => ['del/view', ['id' => $del->getId()]],
        'errors' => [],
        'body' => $this->body($del),
        'del' => $delRepository->repoDeliveryLocationquery((string) $del->getId()),
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
    if ($id) {
      $del = $delRepository->repoDeliveryLocationquery($id);
      return $del;
    }
    return null;
  }

  /**
   * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
   *
   * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
   */
  private function dels(DeliveryLocationRepository $delRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader {
    $dels = $delRepository->findAllPreloaded();
    return $dels;
  }

  /**
   * @param DeliveryLocation $del
   * @return array
   */
  private function body(DeliveryLocation $del): array {
    $body = [
      'date_created' => $del->getDate_created(),
      'date_modified' => $del->getDate_modified(),
      'id' => $del->getId(),
      'client_id' => $del->getClient_id(),
      'name' => $del->getName(),
      'address_1' => $del->getAddress_1(),
      'address_2' => $del->getAddress_2(),
      'city' => $del->getCity(),
      'state' => $del->getState(),
      'zip' => $del->getZip(),
      'country' => $del->getCountry(),
      // 13 digit code
      'global_location_number' => $del->getGlobal_location_number(),
      // the key of the array is saved
      'electronic_address_scheme' => $del->getElectronic_address_scheme()
    ];
    return $body;
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
}
