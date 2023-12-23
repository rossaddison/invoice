<?php

declare(strict_types=1);

namespace App\Invoice\ClientPeppol;

use App\Invoice\Entity\ClientPeppol;
use App\Invoice\ClientPeppol\ClientPeppolForm;
use App\Invoice\ClientPeppol\ClientPeppolService;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\Helpers\StoreCove\StoreCoveArrays;
use App\User\UserService;
use App\Service\WebControllerService;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Yiisoft
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\ViewRenderer;
use \Exception;

final class ClientPeppolController {

  private Flash $flash;
  private SessionInterface $session;
  private ViewRenderer $viewRenderer;
  private WebControllerService $webService;
  private UserService $userService;
  private ClientPeppolService $clientpeppolService;
  private TranslatorInterface $translator;
  private DataResponseFactoryInterface $factory;

  public function __construct(
    SessionInterface $session,
    ViewRenderer $viewRenderer,
    WebControllerService $webService,
    UserService $userService,
    ClientPeppolService $clientpeppolService,
    TranslatorInterface $translator,
    DataResponseFactoryInterface $factory
  ) {
    $this->session = $session;
    $this->flash = new Flash($session);
    $this->viewRenderer = $viewRenderer;
    $this->webService = $webService;
    $this->userService = $userService;
    if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
      $this->viewRenderer = $viewRenderer->withControllerName('invoice/clientpeppol')
        ->withLayout('@views/layout/guest.php');
    }
    if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
      $this->viewRenderer = $viewRenderer->withControllerName('invoice/clientpeppol')
        ->withLayout('@views/layout/invoice.php');
    }
    $this->clientpeppolService = $clientpeppolService;
    $this->translator = $translator;
    $this->factory = $factory;
  }

  /**
   * 
   * @param CurrentRoute $currentRoute
   * @param Request $request
   * @param FormHydrator $formHydrator
   * @param SettingRepository $settingRepository
   * @return Response
   */
  public function add(CurrentRoute $currentRoute, Request $request,
    FormHydrator $formHydrator,
    SettingRepository $settingRepository
  ): Response {
    $client_id = $currentRoute->getArgument('client_id');
    $client_peppol = new ClientPeppol();
    $body = $request->getParsedBody() ?? [];
    $form = new ClientPeppolForm($client_peppol);
    $electronic_address_scheme = PeppolArrays::electronic_address_scheme();
    $peppolarrays = new PeppolArrays();
    if (null !== $client_id) {
      $parameters = [
        'title' => $this->translator->translate('invoice.add'),
        'action' => ['clientpeppol/add', ['client_id' => $client_id]],
        'form' => $form,  
        'pep' => $this->pep(),
        'setting' => $settingRepository->get_setting('enable_client_peppol_defaults'),
        'defaults' => $settingRepository->get_setting('enable_client_peppol_defaults') == '1' ? true : false,
        'buttons' => $this->viewRenderer->renderPartialAsString('/invoice/layout/header_buttons', ['s' => $settingRepository, 'hide_submit_button' => false, 'hide_cancel_button' => false]),
        's' => $settingRepository,
        'client_id' => $client_id,
        'receiver_identifier_array' => StoreCoveArrays::store_cove_receiver_identifier_array(),
        'electronic_address_scheme' => $electronic_address_scheme,
        'iso_6523_array' => $peppolarrays->getIso_6523_icd()
      ];
      if ($request->getMethod() === Method::POST) {
        /**
         * @psalm-suppress PossiblyInvalidArgument $body
         */  
        if ($formHydrator->populate($form, $body) && $form->isValid()) {
         /**
          * @psalm-suppress PossiblyInvalidArgument $body
          */  
          $this->clientpeppolService->saveClientPeppol($client_peppol, $body);
          return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/clientpeppol_successful_guest',
                ['url' => $this->userService->hasPermission('editClientPeppol') && $this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv') ? 'client/guest' : 'client/index',
                  'heading' => $this->translator->translate('invoice.client.peppol'), 'message' => $settingRepository->trans('record_successfully_updated')]));
        }
        $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
        $parameters['form'] = $form;
      } // if
      return $this->viewRenderer->render('/invoice/clientpeppol/_form', $parameters);
    } // null !== $client
    return $this->webService->getNotFoundResponse();
  }

  /**
   * 
   * @return array
   */
  private function pep(): array {
    $pep = [
      'endpointid' => [
        'eg' => 'joe.bloggs@web.com',
        'url' => 'cac-AccountingSupplierParty/cac-Party/cbc-EndpointID/'
      ],
      'endpointid_schemeid' => [
        'eg' => '0192',
        'url' => 'cac-AccountingSupplierParty/cac-Party/cbc-EndpointID/schemeID/'
      ],
      'identificationid' => [
        'eg' => 'SE8765456787',
        'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyIdentification/cbc-ID/'
      ],
      'identificationid_schemeid' => [
        'eg' => '0088',
        'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyIdentification/cbc-ID/schemeID/'
      ],
      'taxschemecompanyid' => [
        'eg' => 'SE8765456787',
        'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyTaxScheme/cbc-CompanyID/'
      ],
      'taxschemeid' => [
        'eg' => 'VAT',
        'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyTaxScheme/cac-TaxScheme/cbc-ID/'
      ],
      'legal_entity_registration_name' => [
        'eg' => 'Buyer Full Name AS',
        'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyLegalEntity/cbc-RegistrationName/'
      ],
      'legal_entity_companyid' => [
        'eg' => '5560104525',
        'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyLegalEntity/cbc-CompanyID/'
      ],
      'legal_entity_companyid_schemeid' => [
        'eg' => '0007',
        'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyLegalEntity/cbc-CompanyID/schemeID/'
      ],
      'legal_entity_company_legal_form' => [
        'eg' => 'Share Capital',
        'url' => 'cac-AccountingSupplierParty/cac-Party/cac-PartyLegalEntity/cbc-CompanyLegalForm/'
      ],
      'financial_institution_branchid' => [
        'eg' => '9999',
        'url' => 'cac-PaymentMeans/cac-PayeeFinancialAccount/cac-FinancialInstitutionBranch/'
      ],
      'accounting_cost' => [
        'eg' => '4217:2323:2323',
        'url' => 'cbc-AccountingCost/'
      ],
      'buyer_reference' => [
        'eg' => 'abs1234',
        'url' => 'cbc-BuyerReference/'
      ],
      'supplier_assigned_accountid' => [
        'eg' => '',
        'url' => ''
      ],
    ];
    return $pep;
  }

  /**
   * 
   * @param ClientPeppolRepository $clientpeppolRepository
   * @param SettingRepository $settingRepository
   * @param Request $request
   * @param ClientPeppolService $service
   * @return Response
   */
  public function index(ClientPeppolRepository $clientpeppolRepository, SettingRepository $settingRepository, Request $request, ClientPeppolService $service): Response {
    $parameters = [
      'clientpeppols' => $this->clientpeppols($clientpeppolRepository),
      'alert' => $this->alert()
    ];
    return $this->viewRenderer->render('index', $parameters);
  }

  /**
   *
   * @param SettingRepository $settingRepository
   * @param CurrentRoute $currentRoute
   * @param ClientPeppolRepository $clientpeppolRepository
   * @return Response
   */
  public function delete(SettingRepository $settingRepository, CurrentRoute $currentRoute, ClientPeppolRepository $clientpeppolRepository
  ): Response {
    try {
      $clientpeppol = $this->clientpeppol($currentRoute, $clientpeppolRepository);
      if ($clientpeppol) {
        $this->clientpeppolService->deleteClientPeppol($clientpeppol);
        $this->flash_message('info', $settingRepository->trans('record_successfully_deleted'));
        return $this->webService->getRedirectResponse('clientpeppol/index');
      }
      return $this->webService->getRedirectResponse('clientpeppol/index');
    } catch (Exception $e) {
      $this->flash_message('danger', $e->getMessage());
      return $this->webService->getRedirectResponse('clientpeppol/index');
    }
  }

  /**
   * 
   * @param ViewRenderer $head
   * @param Request $request
   * @param CurrentRoute $currentRoute
   * @param FormHydrator $formHydrator
   * @param ClientPeppolRepository $clientpeppolRepository
   * @param SettingRepository $settingRepository
   * @param ClientRepository $clientRepository
   * @return Response
   */
  public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
    FormHydrator $formHydrator,
    ClientPeppolRepository $clientpeppolRepository,
    SettingRepository $settingRepository,
    ClientRepository $clientRepository
  ): Response {
    $clientpeppol = $this->clientpeppol($currentRoute, $clientpeppolRepository);
    $body = $request->getParsedBody() ?? [];
    if ($clientpeppol) {
      $peppolarrays = new PeppolArrays();
      $form = new ClientPeppolForm($clientpeppol);
      $parameters = [
        'title' => $settingRepository->trans('edit'),
        'action' => ['clientpeppol/edit', ['client_id' => $clientpeppol->getClient_id()]],
        'buttons' => $this->viewRenderer->renderPartialAsString('/invoice/layout/header_buttons', ['s' => $settingRepository, 'hide_submit_button' => false, 'hide_cancel_button' => false]),
        'errors' => [],
        'form' => $form,  
        'head' => $head,
        'pep' => $this->pep(),
        'setting' => $settingRepository->get_setting('enable_client_peppol_defaults'),
        'defaults' => $settingRepository->get_setting('enable_client_peppol_defaults') == '1' ? true : false,
        'client_id' => $clientpeppol->getClient_id(),
        's' => $settingRepository,
        'clients' => $clientRepository->findAllPreloaded(),
        'receiver_identifier_array' => StoreCoveArrays::store_cove_receiver_identifier_array(),
        'electronic_address_scheme' => PeppolArrays::electronic_address_scheme(),
        'iso_6523_array' => $peppolarrays->getIso_6523_icd()
      ];
      if ($request->getMethod() === Method::POST) {
        /**
         * @psalm-suppress PossiblyInvalidArgument $body
         */  
        if ($formHydrator->populate($form, $body) && $form->isValid()) {
         /**
          * @psalm-suppress PossiblyInvalidArgument $body
          */  
          $this->clientpeppolService->saveClientPeppol($clientpeppol, $body);
          // Guest user's return url to see user's clients
          if ($this->userService->hasPermission('editClientPeppol') && $this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/clientpeppol_successful_guest',
                  ['url' => 'client/guest', 'heading' => $this->translator->translate('invoice.client.peppol'), 'message' => $settingRepository->trans('record_successfully_updated')]));
          }
          // Administrator's return url to see all clients
          if ($this->userService->hasPermission('editClientPeppol') && $this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            return $this->webService->getRedirectResponse('client/index');
          }
        }
        $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
        $parameters['form'] = $form;
      }
      return $this->viewRenderer->render('_form', $parameters);
    }
    return $this->webService->getNotFoundResponse();
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

  //For rbac refer to AccessChecker

  /**
   * @param CurrentRoute $currentRoute
   * @param ClientPeppolRepository $clientpeppolRepository
   * @return ClientPeppol|null
   */
  private function clientpeppol(CurrentRoute $currentRoute, ClientPeppolRepository $clientpeppolRepository): ClientPeppol|null {
    $client_id = $currentRoute->getArgument('client_id');
    if (null !== $client_id) {
      $clientpeppol = $clientpeppolRepository->repoClientPeppolLoadedquery($client_id);
      return $clientpeppol;
    }
    return null;
  }

  /**
   * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
   *
   * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
   */
  private function clientpeppols(ClientPeppolRepository $clientpeppolRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader {
    $clientpeppols = $clientpeppolRepository->findAllPreloaded();
    return $clientpeppols;
  }

  /**
   * @param CurrentRoute $currentRoute
   * @param ClientPeppolRepository $clientpeppolRepository
   * @param SettingRepository $settingRepository
   * @return \Yiisoft\DataResponse\DataResponse|Response
   */
  public function view(CurrentRoute $currentRoute, ClientPeppolRepository $clientpeppolRepository,
    SettingRepository $settingRepository,
  ): \Yiisoft\DataResponse\DataResponse|Response {
    $clientpeppol = $this->clientpeppol($currentRoute, $clientpeppolRepository);
    if ($clientpeppol) {
      $form = new ClientPeppolForm($clientpeppol);  
      $parameters = [
        'title' => $settingRepository->trans('view'),
        'action' => ['clientpeppol/view', ['id' => $clientpeppol->getId()]],
        'errors' => [],
        'form' => $form,
        'clientpeppol' => $clientpeppol,
      ];
      return $this->viewRenderer->render('_view', $parameters);
    }
    return $this->webService->getRedirectResponse('clientpeppol/index');
  }

}
