<?php

declare(strict_types=1);

namespace App\Invoice\ClientPeppol;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\ClientPeppol;
use App\Invoice\Setting\SettingRepository as sR;
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
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Exception;

final class ClientPeppolController extends BaseController
{
    protected string $controllerName = 'invoice/clientpeppol';

    public function __construct(
        private ClientPeppolService $clientPeppolService,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->clientPeppolService = $clientPeppolService;
        $this->factory = $factory;
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
        $client_peppol = new ClientPeppol();
        $form = new ClientPeppolForm($client_peppol);
        $electronic_address_scheme = PeppolArrays::electronic_address_scheme();
        $peppolArrays = new PeppolArrays();
        if (null !== $client_id) {
            $parameters = [
                'title' => $this->translator->translate('add'),
                'actionName' => 'clientpeppol/add',
                'actionArguments' => ['client_id' => $client_id],
                'errors' => [],
                'form' => $form,
                'pep' => $this->pep(),
                'setting' => $this->sR->getSetting('enable_client_peppol_defaults'),
                'defaults' => $this->sR->getSetting('enable_client_peppol_defaults') == '1' ? true : false,
                'client_id' => $client_id,
                'receiver_identifier_array' => StoreCoveArrays::store_cove_receiver_identifier_array(),
                'electronic_address_scheme' => $electronic_address_scheme,
                'iso_6523_array' => $peppolArrays->getIso_6523_icd(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->clientPeppolService->saveClientPeppol($client_peppol, $body);
                        return $this->factory->createResponse(
                            $this->viewRenderer->renderPartialAsString(
                                '//invoice/setting/clientpeppol_successful_guest',
                                [
                                    'url' => $this->userService->hasPermission(Permissions::EDIT_CLIENT_PEPPOL)
                                             && $this->userService->hasPermission(Permissions::VIEW_INV)
                                             && !$this->userService->hasPermission(Permissions::EDIT_INV)
                                             ? 'client/guest'
                                             : 'client/index',
                                    'heading' => $this->translator->translate('client.peppol'),
                                    'message' => $this->translator->translate('record.successfully.updated'),
                                ],
                            ),
                        );
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            } // if
            return $this->viewRenderer->render('_form', $parameters);
        } // null !== $client
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @return array
     */
    private function pep(): array
    {
        return [
            'endpointid' => [
                'eg' => 'joe.bloggs@web.com',
                'url' => 'cac-AccountingSupplierParty/cac-Party/cbc-EndpointID/',
            ],
            'endpointid_schemeid' => [
                'eg' => '0192',
                'url' => 'cac-AccountingSupplierParty/cac-Party/cbc-EndpointID/schemeID/',
            ],
            'identificationid' => [
                'eg' => 'SE8765456787',
                'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyIdentification/cbc-ID/',
            ],
            'identificationid_schemeid' => [
                'eg' => '0088',
                'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyIdentification/cbc-ID/schemeID/',
            ],
            'taxschemecompanyid' => [
                'eg' => 'SE8765456787',
                'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyTaxScheme/cbc-CompanyID/',
            ],
            'taxschemeid' => [
                'eg' => 'VAT',
                'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyTaxScheme/cac-TaxScheme/cbc-ID/',
            ],
            'legal_entity_registration_name' => [
                'eg' => 'Buyer Full Name AS',
                'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyLegalEntity/cbc-RegistrationName/',
            ],
            'legal_entity_companyid' => [
                'eg' => '5560104525',
                'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyLegalEntity/cbc-CompanyID/',
            ],
            'legal_entity_companyid_schemeid' => [
                'eg' => '0007',
                'url' => 'cac-AccountingCustomerParty/cac-Party/cac-PartyLegalEntity/cbc-CompanyID/schemeID/',
            ],
            'legal_entity_company_legal_form' => [
                'eg' => 'Share Capital',
                'url' => 'cac-AccountingSupplierParty/cac-Party/cac-PartyLegalEntity/cbc-CompanyLegalForm/',
            ],
            'financial_institution_branchid' => [
                'eg' => '9999',
                'url' => 'cac-PaymentMeans/cac-PayeeFinancialAccount/cac-FinancialInstitutionBranch/',
            ],
            'accounting_cost' => [
                'eg' => '4217:2323:2323',
                'url' => 'cbc-AccountingCost/',
            ],
            'buyer_reference' => [
                'eg' => 'abs1234',
                'url' => 'cbc-BuyerReference/',
            ],
            'supplier_assigned_accountid' => [
                'eg' => '',
                'url' => '',
            ],
        ];
    }

    /**
     * @param ClientPeppolRepository $clientpeppolRepository
     * @return Response
     */
    public function index(ClientPeppolRepository $clientpeppolRepository): Response
    {
        $parameters = [
            'clientpeppols' => $this->clientpeppols($clientpeppolRepository),
            'alert' => $this->alert(),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ClientPeppolRepository $clientpeppolRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        ClientPeppolRepository $clientpeppolRepository,
    ): Response {
        try {
            $clientpeppol = $this->clientpeppol($currentRoute, $clientpeppolRepository);
            if ($clientpeppol) {
                $this->clientPeppolService->deleteClientPeppol($clientpeppol);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('clientpeppol/index');
            }
            return $this->webService->getRedirectResponse('clientpeppol/index');
        } catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('clientpeppol/index');
        }
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ClientPeppolRepository $clientpeppolRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        ClientPeppolRepository $clientpeppolRepository,
    ): Response {
        $clientpeppol = $this->clientpeppol($currentRoute, $clientpeppolRepository);
        $body = $request->getParsedBody() ?? [];
        if ($clientpeppol) {
            $peppolarrays = new PeppolArrays();
            $form = new ClientPeppolForm($clientpeppol);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'clientpeppol/edit',
                'actionArguments' => ['client_id' => $clientpeppol->getClient_id()],
                'buttons' => $this->viewRenderer->renderPartialAsString(
                    '//invoice/layout/header_buttons',
                    ['hide_submit_button' => false, 'hide_cancel_button' => false],
                ),
                'errors' => [],
                'form' => $form,
                'pep' => $this->pep(),
                'setting' => $this->sR->getSetting('enable_client_peppol_defaults'),
                'defaults' => $this->sR->getSetting('enable_client_peppol_defaults') == '1' ? true : false,
                'client_id' => $clientpeppol->getClient_id(),
                'receiver_identifier_array' => StoreCoveArrays::store_cove_receiver_identifier_array(),
                'electronic_address_scheme' => PeppolArrays::electronic_address_scheme(),
                'iso_6523_array' => $peppolarrays->getIso_6523_icd(),
            ];
            if ($request->getMethod() === Method::POST) {
                if (is_array($body)) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        $this->clientPeppolService->saveClientPeppol($clientpeppol, $body);
                        // Guest user's return url to see user's clients
                        if ($this->userService->hasPermission(Permissions::EDIT_CLIENT_PEPPOL) && $this->userService->hasPermission(Permissions::VIEW_INV) && !$this->userService->hasPermission(Permissions::EDIT_INV)) {
                            return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                                '//invoice/setting/clientpeppol_successful_guest',
                                ['url' => 'client/guest', 'heading' => $this->translator->translate('client.peppol'), 'message' => $this->translator->translate('record.successfully.updated')],
                            ));
                        }
                        // Administrator's return url to see all clients
                        if ($this->userService->hasPermission(Permissions::EDIT_CLIENT_PEPPOL) && $this->userService->hasPermission(Permissions::VIEW_INV) && $this->userService->hasPermission(Permissions::EDIT_INV)) {
                            return $this->webService->getRedirectResponse('client/index');
                        }
                    }
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param ClientPeppolRepository $clientpeppolRepository
     * @return ClientPeppol|null
     */
    private function clientpeppol(CurrentRoute $currentRoute, ClientPeppolRepository $clientpeppolRepository): ?ClientPeppol
    {
        $client_id = $currentRoute->getArgument('client_id');
        if (null !== $client_id) {
            return $clientpeppolRepository->repoClientPeppolLoadedquery($client_id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function clientpeppols(ClientPeppolRepository $clientpeppolRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $clientpeppolRepository->findAllPreloaded();
    }
}
