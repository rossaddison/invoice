<?php


declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\BaseController;
// Entity's
use App\Invoice\Entity\Contract;
use App\Invoice\Entity\CustomField;
use App\Invoice\Entity\DeliveryLocation;
use App\Invoice\Entity\EmailTemplate;
use App\Invoice\Entity\Group;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvCustom;
use App\Invoice\Entity\InvTaxRate;
use App\Invoice\Entity\Quote;
use App\Invoice\Entity\QuoteAmount;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\QuoteCustom;
use App\Invoice\Entity\QuoteTaxRate;
use App\Invoice\Entity\SalesOrder as SoEntity;
use App\Invoice\Entity\SalesOrderAmount as SoAmount;
use App\Invoice\Entity\SalesOrderItem as SoItem;
use App\Invoice\Entity\SalesOrderCustom as SoCustom;
use App\Invoice\Entity\SalesOrderTaxRate as SoTaxRate;
use App\Invoice\Entity\TaxRate;
// Services
// Inv
use App\User\UserService;
use App\User\User;
use App\Invoice\Inv\InvService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvItemAmount\InvItemAmountService;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\InvCustom\InvCustomService;
// PO
use App\Invoice\SalesOrder\SalesOrderService as soS;
use App\Invoice\SalesOrderAmount\SalesOrderAmountService as soAS;
use App\Invoice\SalesOrderCustom\SalesOrderCustomService as soCS;
use App\Invoice\SalesOrderItem\SalesOrderItemService as soIS;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountService as soIAS;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateService as soTRS;
// Quote
use App\Invoice\QuoteAmount\QuoteAmountService;
use App\Invoice\QuoteCustom\QuoteCustomService;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\QuoteTaxRate\QuoteTaxRateService;
use App\Service\WebControllerService;
// Forms
use App\Invoice\SalesOrderItem\SalesOrderItemForm as SoItemForm;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateForm as SoTaxRateForm;
use App\Invoice\SalesOrderCustom\SalesOrderCustomForm as SoCustomForm;
use App\Invoice\SalesOrder\SalesOrderForm as SoForm;
use App\Invoice\Inv\InvForm;
use App\Invoice\InvAmount\InvAmountForm;
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvCustom\InvCustomForm;
use App\Invoice\InvTaxRate\InvTaxRateForm;
use App\Invoice\QuoteItem\QuoteItemForm;
use App\Invoice\QuoteTaxRate\QuoteTaxRateForm;
use App\Invoice\QuoteCustom\QuoteCustomForm;
// Repositories
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\Contract\ContractRepository as ContractRepo;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Group\Exception\GroupException;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductImage\ProductImageRepository as PIR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as soAR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as soIAR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserClient\Exception\NoClientsAssignedToUserException;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;
// App Helpers
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\MailerHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\PdfHelper;
use App\Invoice\Helpers\TemplateHelper;
use App\Widget\Bootstrap5ModalQuote;
// Yii
use Yiisoft\Data\Paginator\OffsetPaginator as DataOffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Html\Html;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
// Psr\Http
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class QuoteController extends BaseController
{
    protected string $controllerName = 'invoice/quote';

    private readonly NumberHelper $number_helper;
    private readonly PdfHelper $pdf_helper;

    /**
     * @param DataResponseFactoryInterface $factory
     * @param InvAmountService $inv_amount_service
     * @param InvService $inv_service
     * @param InvCustomService $inv_custom_service
     * @param InvItemService $inv_item_service
     * @param InvTaxRateService $inv_tax_rate_service
     * @param LoggerInterface $logger
     * @param MailerInterface $mailer
     * @param QuoteAmountService $quote_amount_service
     * @param QuoteCustomService $quote_custom_service
     * @param QuoteItemService $quote_item_service
     * @param QuoteService $quote_service
     * @param QuoteTaxRateService $quote_tax_rate_service
     * @param Session $session
     * @param SR $sR
     * @param Translator $translator
     * @param UserService $userService
     * @param UrlGenerator $url_generator
     * @param ViewRenderer $viewRenderer
     * @param WebControllerService $webService
     */
    public function __construct(
        private readonly DataResponseFactoryInterface $factory,
        private readonly InvAmountService $inv_amount_service,
        private readonly InvService $inv_service,
        private readonly InvCustomService $inv_custom_service,
        private readonly InvItemService $inv_item_service,
        private readonly InvTaxRateService $inv_tax_rate_service,
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        private readonly soAS $so_amount_service,
        private readonly soCS $so_custom_service,
        private readonly soIS $so_item_service,
        private readonly soS $so_service,
        private readonly soTRS $so_tax_rate_service,
        private readonly QuoteAmountService $quote_amount_service,
        private readonly QuoteCustomService $quote_custom_service,
        private readonly QuoteItemService $quote_item_service,
        private readonly QuoteService $quote_service,
        private readonly QuoteTaxRateService $quote_tax_rate_service,
        private readonly UrlGenerator $url_generator,
        Session $session,
        SR $sR,
        Translator $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->number_helper = new NumberHelper($sR);
        $this->pdf_helper = new PdfHelper($sR, $session);
    }

    /**
     * @param string $client_id
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return User|null
     */
    private function active_user(string $client_id, UR $uR, UCR $ucR, UIR $uiR): User|null
    {
        $user_client = $ucR->repoUserquery($client_id);
        if (null !== $user_client) {
            $user_client_count = $ucR->repoUserquerycount($client_id);
            if ($user_client_count == 1) {
                $user_id = $user_client->getUser_id();
                $user = $uR->findById($user_id);
                if (null !== $user) {
                    $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                    if (null !== $user_inv && $user_inv->getActive()) {
                        return $user;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param Request $request
     * @param string $origin
     * @param FormHydrator $formHydrator
     * @param CR $clientRepository
     * @param GR $gR
     * @param TRR $trR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function add(
        Request $request,
        #[RouteArgument('origin')]
        string $origin,
        FormHydrator $formHydrator,
        CR $clientRepository,
        GR $gR,
        TRR $trR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $quote = new Quote();
        $errors = [];
        $form = new QuoteForm($quote);
        $bootstrap5ModalQuote = new Bootstrap5ModalQuote(
            $this->translator,
            $this->viewRenderer,
            $clientRepository,
            $gR,
            $this->sR,
            $ucR,
            $form,
        );
        $layoutWithForm = $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString($origin, $errors);
        $layoutParameters = [];
        $parametersNonModalForm = [];
        // do not use a modal if originating from the main menu or from the dashboard
        if (($origin == 'main') || ($origin == 'dashboard')) {
            $parametersNonModalForm = [
                'form' => $bootstrap5ModalQuote->getFormParameters(),
                /**
                 * Purpose: To build the delivery location route
                 * A delivery location can be added from the quote form when a quote is being added or edited
                 * Once the delivery location is added, use the below action to return back to this form
                 */
                'return_url_action' => 'add',
            ];
        }
        // use a modal if originating from the quote/view
        if ($origin == 'quote') {
            $layoutParameters = [
                // use type to id the quote\modal_layout.php eg.  ->options(['id' => 'modal-add-'.$type,
                'type' => 'quote',
                'form' => $layoutWithForm,
                'return_url_action' => 'add',
            ];
        }
        // otherwise it will be a client number eg. 25
        if (($origin != 'main') && ($origin != 'quote') && ($origin != 'dashboard')) {
            $layoutParameters = [
                'type' => 'client',
                'form' => $layoutWithForm,
                'return_url_action' => 'add',
            ];
        }

        // A quote can originate and be added from the following pages:
        // 1. Main Menu e.g /invoice
        // 2. Client Menu e.g. /invoice/client/view/25
        // 3. Quote Menu e.g. /invoice/quote
        // 4. Dashboard e.g. /invoice/dashboard
        // Use the RouteArgument's origin argument to return to correct origin
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                // Only clients that were assigned to user accounts were made available in dropdown
                // therefore use the 'user client' user id
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    /**
                     * @var string $body['client_id']
                     */
                    $client_id = $body['client_id'];
                    $client_fullname = '';
                    $user_client = $ucR->repoUserquery($client_id);
                    if (null !== $user_client && null !== $user_client->getClient()) {
                        $client_first_name = $user_client->getClient()?->getClient_name();
                        $client_surname = $user_client->getClient()?->getClient_surname();
                        $client_fullname = ($client_first_name ?? '') .
                                         ' ' .
                                         ($client_surname ?? '');
                    } else {
                        $this->flashMessage('danger', $clientRepository->repoClientquery($client_id)->getClient_full_name() . ': ' . $this->translator->translate('user.client.no.account'));
                    }
                    // Ensure that the client has only one (paying) user account otherwise reject this quote
                    // Related logic: see UserClientRepository function get_not_assigned_to_user which ensures that only
                    // clients that have   NOT   been assigned to a user account are presented in the dropdown box for available clients
                    // So this line is an extra measure to ensure that the quote is being made out to the correct payer
                    // ie. not more than one user is associated with the client.
                    $user = $this->active_user($client_id, $uR, $ucR, $uiR);
                    if (null !== $user) {
                        $saved_model = $this->quote_service->saveQuote($user, $quote, $body, $this->sR, $gR);
                        /**
                         * The QuoteAmount entity is created automatically during the above saveQuote
                         * Related logic: see src\Invoice\Entity\Quote $this->quoteAmount = new QuoteAmount();
                         */
                        $model_id = $saved_model->getId();
                        if (null !== $model_id) {
                            $this->default_taxes($quote, $trR, $formHydrator);
                            // Inform the user of generated quote number for draft setting
                            $this->flashMessage('info', $this->sR->getSetting('generate_quote_number_for_draft') === '1'
                            ? $this->translator->translate('generate_quote_number_for_draft') . '=>' . $this->translator->translate('yes')
                            : $this->translator->translate('generate_quote_number_for_draft') . '=>' . $this->translator->translate('no'));
                        } //$model_id
                        $this->flashMessage('success', $this->translator->translate('record.successfully.created'));
                        if ($origin == 'main' || $origin == 'quote') {
                            return $this->webService->getRedirectResponse('quote/index');
                        }
                        if ($origin == 'dashboard') {
                            return $this->webService->getRedirectResponse('invoice/dashboard');
                        }
                    }
                }
            }
            $errors = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        } // POST
        // show the form without a modal when using the main menu or dashboard
        if ($origin == 'main' || $origin == 'dashboard') {
            // update the errors array with latest errors
            $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString($origin, $errors);
            // do not use the layout just get the formParameters
            $parameters = $bootstrap5ModalQuote->getFormParameters();
            /**
             * @psalm-suppress MixedArgumentTypeCoercion $parameters
             */
            return $this->viewRenderer->render('modal_add_quote_form', $parameters);
        }
        // show the form inside a modal when engaging with a view
        if ($origin == 'quote') {
            return $this->viewRenderer->render('modal_layout', [
                // use type to id the quote\modal_layout.php eg.  ->options(['id' => 'modal-add-'.$type,
                'type' => 'quote',
                'form' => $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString($origin, $errors),
                'return_url_action' => 'add',
            ]);
        }
        // Otherwise return to client
        if (($origin != 'main') && ($origin != 'quote')) {
            return $this->viewRenderer->render('modal_layout', [
                'type' => 'client',
                'form' => $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString($origin, $errors),
                'return_url_action' => 'add',
            ]);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * Client approves quote WITH purchase order number(if needed by the client ie. can be empty). Sales Order generated recording client's purchase order number
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CFR $cfR
     * @param GR $gR
     * @param soIAS $soiaS
     * @param PR $pR
     * @param QAR $qaR
     * @param soAR $soaR
     * @param QCR $qcR
     * @param soIAR $soiaR
     * @param QIR $qiR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param TRR $trR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @param UNR $unR
     *
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function approve(
        Request $request,
        FormHydrator $formHydrator,
        CFR $cfR,
        GR $gR,
        soIAS $soiaS,
        PR $pR,
        QAR $qaR,
        soAR $soaR,
        QCR $qcR,
        soIAR $soiaR,
        QIR $qiR,
        QR $qR,
        QTRR $qtrR,
        TRR $trR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
        UNR $unR,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $body = $request->getQueryParams();
        $url_key = (string) $body['url_key'];
        $purchase_order_number = (string) $body['client_po_number'];
        $purchase_order_person = (string) $body['client_po_person'];
        if (!empty($url_key)) {
            if ($qR->repoUrl_key_guest_count($url_key) > 0) {
                $quote = $qR->repoUrl_key_guest_loaded($url_key);
                $number = $gR->generate_number((int) $this->sR->getSetting('default_sales_order_group'));
                if (null !== $number) {
                    if ($quote && null !== $quote->getId()) {
                        $quote_id = $quote->getId();
                        $so_body = [
                            'quote_id' => $quote_id,
                            'inv_id' => 0,
                            'client_id' => $quote->getClient_id(),
                            'group_id' => $this->sR->getSetting('default_sales_order_group'),
                            'status_id' => 4,
                            'client_po_number' => $purchase_order_number,
                            'client_po_person' => $purchase_order_person,
                            'number' => $number,
                            'discount_amount' => (float) $quote->getDiscount_amount(),
                            'discount_percent' => (float) $quote->getDiscount_percent(),
                            // The quote's url will be the same for the po allowing for a trace
                            'url_key' => $quote->getUrl_key(),
                            'password' => $quote->getPassword() ?? '',
                            'notes' => $quote->getNotes(),
                        ];
                        $this->flashMessage('info', $this->translator->translate('salesorder.agree.to.terms'));
                        $new_so = new SoEntity();
                        $form = new SoForm($new_so);
                        if ($formHydrator->populateAndValidate($form, $so_body) && ($quote->getSo_id() === (string) 0)) {
                            $quote_id = $so_body['quote_id'];
                            $client_id = $so_body['client_id'];
                            $user = $this->active_user($client_id, $uR, $ucR, $uiR);
                            if (null !== $user) {
                                $this->so_service->addSo($user, $new_so, $so_body);
                                // Ensure that the quote has a specific po and therefore cannot be copied again.
                                $new_so_id = $new_so->getId();
                                // Transfer each quote_item to so_item and the corresponding so_item_amount to so_item_amount for each item
                                if (null !== $new_so_id && null !== $quote_id) {
                                    $this->quote_to_so_quote_items($quote_id, $new_so_id, $soiaR, $soiaS, $pR, $qiR, $trR, $unR, $formHydrator);
                                    $this->quote_to_so_quote_tax_rates($quote_id, $new_so_id, $qtrR, $formHydrator);
                                    $this->quote_to_so_quote_custom($quote_id, $new_so_id, $qcR, $cfR, $formHydrator);
                                    $this->quote_to_so_quote_amount($quote_id, $new_so_id, $qaR, $soaR);
                                    // Set the quote's sales order id so that it cannot be copied in the future
                                    $quote->setSo_id($new_so_id);
                                    // The quote has been approved with purchase order number
                                    $quote->setStatus_id(4);
                                    $qR->save($quote);
                                    $parameters = ['success' => 1];
                                    //return response to quote.js to reload page at location
                                    return $this->factory->createResponse(Json::encode($parameters));
                                } // null!==$new_so_id
                            } // null!==$user
                        } else {
                            $parameters = [
                                'success' => 0,
                            ];
                            //return response to quote.js to reload page at location
                            return $this->factory->createResponse(Json::encode($parameters));
                        }
                    } // quote
                    return $this->webService->getNotFoundResponse();
                }
                throw new GroupException($this->translator);
            } // if $qR
            return $this->webService->getNotFoundResponse();
        } // null!==$url_key
        return $this->webService->getNotFoundResponse();
    } // approve_with

    /**
     * @param string $url_key
     * @param QR $qR
     * @return Response
     */
    public function reject(#[RouteArgument('url_key')] string $url_key, QR $qR): Response
    {
        if ($url_key) {
            if ($qR->repoUrl_key_guest_count($url_key) > 0) {
                $quote = $qR->repoUrl_key_guest_loaded($url_key);
                if ($quote) {
                    $quote_id = $quote->getId();
                    $quote->setStatus_id(5);
                    $qR->save($quote);
                    return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                        '//invoice/setting/quote_successful',
                        ['heading' => $this->translator->translate('record.successfully.updated'),'url' => 'quote/view','id' => $quote_id],
                    ));
                }
                return $this->webService->getNotFoundResponse();
            }
            return $this->webService->getNotFoundResponse();
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param Quote $quote
     * @return array
     */
    private function body(Quote $quote): array
    {
        return [
            'number' => $quote->getNumber(),

            'id' => $quote->getId(),
            'inv_id' => $quote->getInv_id(),
            'so_id' => $quote->getSo_id(),

            'user_id' => $quote->getUser()?->getId(),
            'group_id' => $quote->getGroup()?->getId(),
            'client_id' => $quote->getClient()?->getClient_id(),

            'date_created' => $quote->getDate_created(),
            'date_modified' => $quote->getDate_modified(),
            'date_expires' => $quote->getDate_expires(),

            'status_id' => $quote->getStatus_id(),

            'discount_amount' => $quote->getDiscount_amount(),
            'discount_percent' => $quote->getDiscount_percent(),
            'url_key' => $quote->getUrl_key(),
            'password' => $quote->getPassword(),
            'notes' => $quote->getNotes(),
        ];
    }

    /**
     * Data fed from quote.js->$(document).on('click', '#quote_create_confirm', function () {
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param GR $gR
     * @param TRR $trR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function create_confirm(Request $request, FormHydrator $formHydrator, GR $gR, TRR $trR, UR $uR, UCR $ucR, UIR $uiR): \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        $ajax_body = [
            'inv_id' => null,
            'so_id' => 0,
            'client_id' => (int) $body['client_id'],
            'group_id' => (int) $body['quote_group_id'],
            'status_id' => 1,
            // Generate a number based on the GroupRepository Next id value and not on a newly generated quote_id
            // if generate_quote_number_for_draft is set to 'yes' otherwise set to empty string ie. nothing.
            // Note: Clients cannot see draft quotes
            'number' => $this->sR->getSetting('generate_quote_number_for_draft') === '1' ? $gR->generate_number((int) $body['quote_group_id'], true) : '',
            'discount_amount' => (float) 0,
            'discount_percent' => (float) 0,
            'url_key' => '',
            'password' => $body['quote_password'],
            'notes' => '',
        ];
        $unsuccessful = $this->translator->translate('quote.creation.unsuccessful');
        $quote = new Quote();
        $ajax_content = new QuoteForm($quote);
        if ($formHydrator->populate($ajax_content, $ajax_body) && $ajax_content->isValid()) {
            $client_id = $ajax_body['client_id'];
            $user_client = $ucR->repoUserquery((string) $client_id);
            // Ensure that the client has only one (paying) user account otherwise reject this quote
            // Related logic: see UserClientRepository function get_not_assigned_to_user which ensures that only
            // clients that have   NOT   been assigned to a user account are presented in the dropdown box for available clients
            // So this line is an extra measure to ensure that the invoice is being made out to the correct payer
            // ie. not more than one user is associated with the client.
            $user_client_count = $ucR->repoUserquerycount((string) $client_id);
            if (null !== $user_client && $user_client_count == 1) {
                // Only one user account per client
                $user_id = $user_client->getUser_id();
                $user = $uR->findById($user_id);
                if (null !== $user) {
                    $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                    if (null !== $user_inv && $user_inv->getActive()) {
                        $saved_model = $this->quote_service->saveQuote($user, $quote, $ajax_body, $this->sR, $gR);
                        $model_id = $saved_model->getId();
                        if (null !== $model_id) {
                            $this->quote_amount_service->initializeQuoteAmount(new QuoteAmount(), (int) $model_id);
                            $this->default_taxes($quote, $trR, $formHydrator);
                            $parameters = ['success' => 1];
                            // Inform the user of generated invoice number for drat setting
                            $this->flashMessage(
                                'info',
                                $this->sR->getSetting('generate_quote_number_for_draft') === '1'
                                  ? $this->translator->translate('generate_quote_number_for_draft') . '=>' . $this->translator->translate('yes')
                                  : $this->translator->translate('generate_quote_number_for_draft') . '=>' . $this->translator->translate('no'),
                            );
                            //return response to quote.js to reload page at location
                            return $this->factory->createResponse(Json::encode($parameters));
                        }
                    } // null!==$user_inv && $user_inv->getActive()
                    return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => $unsuccessful]));
                } // null!==$user
                return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => $unsuccessful]));
            } // null!== $user_client && $user_client_count==1
            // In the event of the database being manually edited (highly unlikely) present this warning anyway
            if ($user_client_count > 1) {
                $this->flashMessage('warning', $this->translator->translate('user.inv.more.than.one.assigned'));
            }
            return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => $unsuccessful]));
        }
        $parameters = [
            'success' => 0,
            'message' => $unsuccessful,
        ];
        //return response to quote.js to reload page at location
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     * @param FormHydrator $formHydrator
     * @param array $array
     * @param int $quote_id
     * @param QCR $qcR
     */
    public function custom_fields(FormHydrator $formHydrator, array $array, int $quote_id, QCR $qcR): void
    {
        if (!empty($array['custom'])) {
            $db_array = [];
            $values = [];
            /**
             * @var array $custom
             * @var string $custom['value']
             * @var string $custom['name']
             */
            foreach ($array['custom'] as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                    $values[$matches[1]][] = $custom['value'] ;
                } else {
                    $values[$custom['name']] = $custom['value'];
                }
            }
            /**
             * @var string $value
             */
            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    // Reduce eg.  customview[4] to 4
                    $key_value = preg_match('/\d+/', $key, $m) ? $m[0] : '';
                    $db_array[$key_value] = $value;
                }
            }
            foreach ($db_array as $key => $value) {
                if ($value !== '') {
                    $quoteCustom = new QuoteCustom();
                    $ajax_custom = new QuoteCustomForm($quoteCustom);
                    $quote_custom = [];
                    $quote_custom['quote_id'] = $quote_id;
                    $quote_custom['custom_field_id'] = $key;
                    $quote_custom['value'] = $value;
                    if ($qcR->repoQuoteCustomCount((string) $quote_id, $key) > 0) {
                        $model = $qcR->repoFormValuequery((string) $quote_id, $key);
                    } else {
                        $model = new QuoteCustom();
                    }
                    if (null !== $model && $formHydrator->populate($ajax_custom, $quote_custom) && $ajax_custom->isValid()) {
                        $this->quote_custom_service->saveQuoteCustom($model, $quote_custom);
                    }
                }
            }
        }
    }

    /**
     * @param Quote $quote
     * @param TRR $trR
     * @param FormHydrator $formHydrator
     */
    public function default_taxes(Quote $quote, TRR $trR, FormHydrator $formHydrator): void
    {
        if ($trR->repoCountAll() > 0) {
            $taxrates = $trR->findAllPreloaded();
            /** @var TaxRate $taxrate */
            foreach ($taxrates as $taxrate) {
                $taxrate->getTaxRateDefault()  == 1 ? $this->default_tax_quote($taxrate, $quote, $formHydrator) : '';
            }
        }
    }

    /**
     * @param TaxRate|null $taxrate
     * @param Quote $quote
     * @param FormHydrator $formHydrator
     */
    public function default_tax_quote(TaxRate|null $taxrate, Quote $quote, FormHydrator $formHydrator): void
    {
        $quoteTaxRate = new QuoteTaxRate();
        $quoteTaxRateForm = new QuoteTaxRateForm($quoteTaxRate);
        $quote_tax_rate = [];
        $quote_tax_rate['quote_id'] = $quote->getId();
        if (null !== $taxrate) {
            $quote_tax_rate['tax_rate_id'] = $taxrate->getTaxRateId();
        } else {
            $quote_tax_rate['tax_rate_id'] = 1;
        }
        /**
         * Related logic: see Settings ... View ... Taxes ... Default Invoice Tax Rate Placement
         * Related logic: see ..\resources\views\invoice\setting\views partial_settings_taxes.php
         */
        $quote_tax_rate['include_item_tax'] = ($this->sR->getSetting('default_include_item_tax') == '1' ? 1 : 0);
        $quote_tax_rate['quote_tax_rate_amount'] = 0;
        if ($formHydrator->populate($quoteTaxRateForm, $quote_tax_rate) && $quoteTaxRateForm->isValid()) {
            $this->quote_tax_rate_service->saveQuoteTaxRate($quoteTaxRate, $quote_tax_rate);
        }
    }

    /**
     * @param int $id
     * @param QuoteRepository $quoteRepo
     * @param QCR $qcR
     * @param QuoteCustomService $qcS
     * @param QIR $qiR
     * @param QuoteItemService $qiS
     * @param QTRR $qtrR
     * @param QuoteTaxRateService $qtrS
     * @param QAR $qaR
     * @param QuoteAmountService $qaS
     * @return Response
     */
    public function delete(
        #[RouteArgument('id')]
        int $id,
        QR $quoteRepo,
        QCR $qcR,
        QuoteCustomService $qcS,
        QIR $qiR,
        QuoteItemService $qiS,
        QTRR $qtrR,
        QuoteTaxRateService $qtrS,
        QAR $qaR,
        QuoteAmountService $qaS,
    ): Response {
        try {
            $quote = $this->quote($id, $quoteRepo);
            if ($quote) {
                $this->quote_service->deleteQuote($quote, $qcR, $qcS, $qiR, $qiS, $qtrR, $qtrS, $qaR, $qaS);
                $this->flashMessage('success', $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('quote/index');
            }
            return $this->webService->getNotFoundResponse();
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('quote.delete.not'));
            return $this->webService->getRedirectResponse('quote/index');
        }
    }

    /**
     * @param int $id
     * @param QIR $qiR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function delete_quote_item(#[RouteArgument('id')] int $id, QIR $qiR): \Yiisoft\DataResponse\DataResponse|Response
    {
        $quote_id = (string) $this->session->get('quote_id');
        try {
            $quoteItem = $this->quote_item($id, $qiR);

            if ($quoteItem) {
                $this->quote_item_service->deleteQuoteItem($quoteItem);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
            }
            $this->flashMessage('danger', $this->translator->translate('quote.item.cannot.delete'));
            return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('quote.item.cannot.delete'));
        }
        return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
            '//invoice/setting/quote_successful',
            ['heading' => '','message' => $this->translator->translate('record.successfully.deleted'),'url' => 'quote/view','id' => $quote_id],
        ));
    }

    /**
     * @param int $id
     * @param QTRR $quotetaxrateRepository
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function delete_quote_tax_rate(#[RouteArgument('id')] int $id, QTRR $quotetaxrateRepository): \Yiisoft\DataResponse\DataResponse
    {
        try {
            $this->quote_tax_rate_service->deleteQuoteTaxRate($this->quotetaxrate($id, $quotetaxrateRepository));
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('quote.tax.rate.cannot.delete'));
        }
        $quote_id = (string) $this->session->get('quote_id');
        return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
            '//invoice/setting/inv_message',
            ['heading' => $this->translator->translate('quote.tax.rate'),'message' => $this->translator->translate('record.successfully.deleted'),'url' => 'quote/view','id' => $quote_id],
        ));
    }

    /**
     * @param Request $request
     * @param int $id
     * @param FormHydrator $formHydrator
     * @param QR $quoteRepo
     * @param IR $invRepo
     * @param CR $clientRepo
     * @param ContractRepo $contractRepo
     * @param DLR $delRepo
     * @param GR $groupRepo
     * @param CFR $cfR
     * @param CVR $cvR
     * @param QCR $qcR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function edit(
        Request $request,
        #[RouteArgument('id')]
        int $id,
        FormHydrator $formHydrator,
        QR $quoteRepo,
        IR $invRepo,
        CR $clientRepo,
        ContractRepo $contractRepo,
        DLR $delRepo,
        GR $groupRepo,
        CFR $cfR,
        CVR $cvR,
        QCR $qcR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $quote = $this->quote($id, $quoteRepo, true);
        if (null !== $quote) {
            $form = new QuoteForm($quote);
            $quoteCustom = new QuoteCustom();
            $quoteCustomForm = new QuoteCustomForm($quoteCustom);
            $quote_id = $quote->getId();
            $client_id = $quote->getClient_id();
            $dels = $delRepo->repoClientquery($quote->getClient_id());
            $parameters = [
                'title' => '',
                'alert' => $this->alert(),
                'actionName' => 'quote/edit',
                'actionArguments' => ['id' => $quote_id],
                'errors' => [],
                'form' => $form,
                'optionsData' => $this->editOptionsData(
                    $quote,
                    (int) $client_id,
                    $clientRepo,
                    $contractRepo,
                    $delRepo,
                    $groupRepo,
                    $quoteRepo,
                    $ucR,
                ),
                'invs' => $invRepo->findAllPreloaded(),
                'clients' => $clientRepo->findAllPreloaded(),
                'dels' => $dels,
                'groups' => $groupRepo->findAllPreloaded(),
                'numberhelper' => new NumberHelper($this->sR),
                'quote_statuses' => $quoteRepo->getStatuses($this->translator),
                'cvH' => new CVH($this->sR),
                'customFields' => $cfR->repoTablequery('quote_custom'),
                // Applicable to normally building up permanent selection lists eg. dropdowns
                'customValues' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                // There will initially be no custom_values attached to this quote until they are filled in the field on the form
                'quoteCustomValues' => null !== $quote_id ? $this->quote_custom_values($quote_id, $qcR) : null,
                'quote' => $quote,
                'quoteCustomForm' => $quoteCustomForm,
                'delCount' => $delRepo->repoClientCount($quote->getClient_id()),
                'returnUrlAction' => 'edit',
            ];
            $delRepo->repoClientCount($quote->getClient_id()) > 0 ? '' : $this->flashMessage('warning', $this->translator->translate('quote.delivery.location.none'));
            if ($request->getMethod() === Method::POST) {
                $body = (array) $request->getParsedBody();
                $quote = $this->quote($id, $quoteRepo, false);
                if ($quote) {
                    $form = new QuoteForm($quote);
                    $client_id = $quote->getClient_id();
                    $user = $this->active_user($client_id, $uR, $ucR, $uiR);
                    if (null !== $user) {
                        if ($formHydrator->populateAndValidate($form, $body)) {
                            $this->quote_service->saveQuote($user, $quote, $body, $this->sR, $groupRepo);
                            $this->edit_save_custom_fields($body, $formHydrator, $qcR, $quote_id);
                            $this->flashMessage('success', $this->translator->translate('record.successfully.updated'));
                            return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
                        }
                        $parameters['form'] = $form;
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                        return $this->viewRenderer->render('_form', $parameters);
                    }
                }
            }
            return $this->viewRenderer->render('_form', $parameters);
        } // $quote
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param array|object|null $parse
     * @param string|null $quote_id
     */
    public function edit_save_custom_fields(array|object|null $parse, FormHydrator $formHydrator, QCR $qcR, string|null $quote_id): void
    {
        /**
         * @var array $custom
         */
        $custom = $parse['custom'] ?? [];
        /** @var array|string $value */
        foreach ($custom as $custom_field_id => $value) {
            if ($qcR->repoQuoteCustomCount((string) $quote_id, (string) $custom_field_id) == 0) {
                $quoteCustom = new QuoteCustom();
                $quote_custom_input = [
                    'quote_id' => (int) $quote_id,
                    'custom_field_id' => (int) $custom_field_id,
                    'value' => is_array($value) ? serialize($value) : $value,
                ];
                $form = new QuoteCustomForm($quoteCustom);
                if ($formHydrator->populateAndValidate($form, $quote_custom_input)) {
                    $this->quote_custom_service->saveQuoteCustom($quoteCustom, $quote_custom_input);
                }
            } else {
                $quote_custom = $qcR->repoFormValuequery((string) $quote_id, (string) $custom_field_id);
                if ($quote_custom) {
                    $quote_custom_input = [
                        'quote_id' => (int) $quote_id,
                        'custom_field_id' => (int) $custom_field_id,
                        'value' => is_array($value) ? serialize($value) : $value,
                    ];
                    $form = new QuoteCustomForm($quote_custom);
                    if ($formHydrator->populateAndValidate($form, $quote_custom_input)) {
                        $this->quote_custom_service->saveQuoteCustom($quote_custom, $quote_custom_input);
                    }
                }
            }
        }
    }

    /**
     * @psalm-param 'pdf' $type
     */
    public function email_get_quote_templates(string $type = 'pdf'): array
    {
        return $this->sR->get_quote_templates($type);
    }

    /**
     * @param ViewRenderer $head
     * @param int $id
     * @param CCR $ccR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param ETR $etR
     * @param ICR $icR
     * @param QR $qR
     * @param PCR $pcR
     * @param SOCR $socR
     * @param QCR $qcR
     * @param UIR $uiR
     * @return Response
     */
    public function email_stage_0(
        ViewRenderer $head,
        #[RouteArgument('id')]
        int $id,
        CCR $ccR,
        CFR $cfR,
        CVR $cvR,
        ETR $etR,
        ICR $icR,
        QR $qR,
        PCR $pcR,
        SOCR $socR,
        QCR $qcR,
        UIR $uiR,
    ): Response {
        $mailer_helper = new MailerHelper($this->sR, $this->session, $this->translator, $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        if (!$mailer_helper->mailer_configured()) {
            $this->flashMessage('warning', $this->translator->translate('email.not.configured'));
            return $this->webService->getRedirectResponse('quote/index');
        }
        $quote_entity = $this->quote($id, $qR, true);
        if ($quote_entity) {
            $quote_id = $quote_entity->getId();
            $quote = $qR->repoQuoteUnLoadedquery((string) $quote_id);
            if ($quote) {
                // Get all custom fields
                $custom_fields = [];
                $custom_tables = [
                    'client_custom' => 'client',
                    'inv_custom' => 'invoice',
                    'payment_custom' => 'payment',
                    'quote_custom' => 'quote',
                    'salesorder_custom' => 'salesorder',
                    // TODO 'user_custom' => 'user',
                ];
                foreach (array_keys($custom_tables) as $table) {
                    $custom_fields[$table] = $cfR->repoTablequery($table);
                }
                if ($template_helper->select_email_quote_template() == '') {
                    $this->flashMessage('warning', $this->translator->translate('quote.email.templates.not.configured'));
                    return $this->webService->getRedirectResponse('setting/tab_index');
                }
                $setting_status_email_template = $etR->repoEmailTemplatequery($template_helper->select_email_quote_template())
                                               ?: null;
                null === $setting_status_email_template ? $this->flashMessage(
                    'info',
                    $this->translator->translate('default.email.template') . '=>' .
                                                  $this->translator->translate('not.set'),
                ) : '';

                empty($template_helper->select_pdf_quote_template()) ? $this->flashMessage(
                    'info',
                    $this->translator->translate('default.pdf.template') . '=>' .
                                                  $this->translator->translate('not.set'),
                ) : '';
                $parameters = [
                    'head' => $head,
                    'actionName' => 'quote/email_stage_2',
                    'actionArguments' => ['id' => $quote_id],
                    'alert' => $this->alert(),
                    'autoTemplate' => null !== $setting_status_email_template
                                           ? $this->get_inject_email_template_array($setting_status_email_template)
                                           : [],
                    'settingStatusPdfTemplate' => $template_helper->select_pdf_quote_template(),
                    'email_templates' => $etR->repoEmailTemplateType('quote'),
                    'dropdownTitlesOfEmailTemplates' => $this->email_templates($etR),
                    'userInv' => $uiR->repoUserInvUserIdcount($quote->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($quote->getUser_id()) : null,
                    'quote' => $quote,
                    'pdfTemplates' => $this->email_get_quote_templates('pdf'),
                    'templateTags' => $this->viewRenderer->renderPartialAsString('//invoice/emailtemplate/template-tags', [
                        'custom_fields' => $custom_fields,
                        'template_tags_inv' => '',
                        'template_tags_quote' => $this->viewRenderer->renderPartialAsString('//invoice/emailtemplate/template-tags-quote', [
                            'custom_fields_quote_custom' => $custom_fields['quote_custom'],
                        ]),
                    ]),
                    'form' => new MailerQuoteForm(),
                    'custom_fields' => $custom_fields,
                ];
                return $this->viewRenderer->render('mailer_quote', $parameters);
            } // quote
            return $this->webService->getRedirectResponse('quote/index');
        } // quote_entity
        return $this->webService->getRedirectResponse('quote/index');
    }

    /**
     * @param EmailTemplate $email_template
     * @return array
     */
    public function get_inject_email_template_array(EmailTemplate $email_template): array
    {
        return [
            'body' => Json::htmlEncode($email_template->getEmail_template_body()),
            'subject' => $email_template->getEmail_template_subject() ?? '',
            'from_name' => $email_template->getEmail_template_from_name() ?? '',
            'from_email' => $email_template->getEmail_template_from_email() ?? '',
            'cc' => $email_template->getEmail_template_cc() ?? '',
            'bcc' => $email_template->getEmail_template_bcc() ?? '',
            'pdf_template' => $email_template->getEmail_template_pdf_template() ?? '',
        ];
    }

    /**
     * @param ETR $etR
     *
     * @return (string|null)[]
     *
     * @psalm-return array<''|int, null|string>
     */
    public function email_templates(ETR $etR): array
    {
        $email_templates = $etR->repoEmailTemplateType('quote');
        $data = [];
        /** @var EmailTemplate $email_template */
        foreach ($email_templates as $email_template) {
            $data[] = $email_template->getEmail_template_title();
        }
        return $data;
    }

    /**
     * @param string|null $quote_id
     * @param array $from
     * @param string $to
     * @param string $subject
     * @param string $email_body
     * @param string $cc
     * @param string $bcc
     * @param array $attachFiles
     * @param CR $cR
     * @param CCR $ccR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param IAR $iaR
     * @param ICR $icR
     * @param QIAR $qiaR
     * @param QIR $qiR
     * @param IR $iR
     * @param QTRR $qtrR
     * @param PCR $pcR
     * @param SOCR $socR
     * @param QR $qR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param UIR $uiR
     * @param ViewRenderer $viewrenderer
     * @return bool
     */
    public function email_stage_1(
        string|null $quote_id,
        array $from,
        // $to can only have one email address
        string $to,
        string $subject,
        string $email_body,
        string $cc,
        string $bcc,
        array $attachFiles,
        CR $cR,
        CCR $ccR,
        CFR $cfR,
        CVR $cvR,
        IAR $iaR,
        ICR $icR,
        QIAR $qiaR,
        QIR $qiR,
        IR $iR,
        QTRR $qtrR,
        PCR $pcR,
        SOCR $socR,
        QR $qR,
        QAR $qaR,
        QCR $qcR,
        SOR $soR,
        UIR $uiR,
        ViewRenderer $viewrenderer,
    ): bool {
        // All custom repositories, including icR have to be initialised.
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        $mailer_helper = new MailerHelper($this->sR, $this->session, $this->translator, $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        if (null !== $quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
            $quote_custom_values = $this->quote_custom_values($quote_id, $qcR);
            $quote_entity = $qR->repoCount($quote_id) > 0 ? $qR->repoQuoteUnLoadedquery($quote_id) : null;
            if ($quote_entity) {
                $stream = false;
                /** @var string $pdf_template_target_path */
                $pdf_template_target_path = $this->pdf_helper->generate_quote_pdf($quote_id, $quote_entity->getUser_id(), $stream, true, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $viewrenderer);
                if ($pdf_template_target_path) {
                    $mail_message = $template_helper->parse_template($quote_id, false, $email_body, $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR);
                    $mail_subject = $template_helper->parse_template($quote_id, false, $subject, $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR);
                    $mail_cc = $template_helper->parse_template($quote_id, false, $cc, $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR);
                    $mail_bcc = $template_helper->parse_template($quote_id, false, $bcc, $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR);
                    // from[0] is the from_email and from[1] is the from_name
                    /**
                     * @var string $from[0]
                     * @var string $from[1]
                     */
                    $mail_from =
                        [$template_helper->parse_template($quote_id, false, $from[0], $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR),
                            $template_helper->parse_template($quote_id, false, $from[1], $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR)];
                    // mail_from[0] is the from_email and mail_from[1] is the from_name
                    return $mailer_helper->yii_mailer_send(
                        $mail_from[0],
                        $mail_from[1],
                        $to,
                        $mail_subject,
                        $mail_message,
                        $mail_cc,
                        $mail_bcc,
                        $attachFiles,
                        $pdf_template_target_path,
                        $uiR,
                    );
                } // pdf_template_target_path
            } // quote_entity
            return false;
        } // quote_id
        return false;
    }

    /**
     * @param Request $request
     * @param int $quote_id
     * @param CR $cR
     * @param CCR $ccR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param GR $gR
     * @param IAR $iaR
     * @param QIAR $qiaR
     * @param ICR $icR
     * @param QIR $qiR
     * @param IR $iR
     * @param QTRR $qtrR
     * @param PCR $pcR
     * @param SOCR $socR
     * @param QR $qR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param SOR $soR
     * @param UIR $uiR
     * @return Response
     */
    public function email_stage_2(
        Request $request,
        #[RouteArgument('id')]
        int $quote_id,
        CR $cR,
        CCR $ccR,
        CFR $cfR,
        CVR $cvR,
        GR $gR,
        IAR $iaR,
        QIAR $qiaR,
        ICR $icR,
        QIR $qiR,
        IR $iR,
        QTRR $qtrR,
        PCR $pcR,
        SOCR $socR,
        QR $qR,
        QAR $qaR,
        QCR $qcR,
        SOR $soR,
        UIR $uiR,
    ): Response {
        if ($quote_id) {
            $mailer_helper = new MailerHelper($this->sR, $this->session, $this->translator, $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['btn_cancel'] = 0;
                if (!$mailer_helper->mailer_configured()) {
                    $this->flashMessage('warning', $this->translator->translate('email.not.configured'));
                    return $this->webService->getRedirectResponse('quote/index');
                }

                /**
                 * @var array $body['MailerQuoteForm']
                 */
                $to = (string) $body['MailerQuoteForm']['to_email'] ?: '';
                if (empty($to)) {
                    return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                        '//invoice/setting/quote_message',
                        ['heading' => '','message' => $this->translator->translate('email.to.address.missing'),'url' => 'quote/view','id' => $quote_id],
                    ));
                }

                /**
                 * @var array $from
                 */
                $from = [
                    $body['MailerQuoteForm']['from_email'] ?? '',
                    $body['MailerQuoteForm']['from_name'] ?? '',
                ];


                if (empty($from[0])) {
                    return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                        '//invoice/setting/quote_message',
                        ['heading' => '','message' => $this->translator->translate('email.to.address.missing'),'url' => 'quote/view','id' => $quote_id],
                    ));
                }

                /**
                 * @var string $subject
                 */
                $subject = $body['MailerQuoteForm']['subject'] ?? '';
                /**  @var string $body */
                $email_body = (string) $body['MailerQuoteForm']['body'];

                /**
                 * @var string $cc
                 */
                $cc = $body['MailerQuoteForm']['cc'] ?? '';
                /**
                 * @var string $bcc
                 */
                $bcc = $body['MailerQuoteForm']['bcc'] ?? '';

                $attachFiles = $request->getUploadedFiles();

                $this->generate_quote_number_if_applicable((string) $quote_id, $qR, $this->sR, $gR);
                // Custom fields are automatically included on the quote
                if ($this->email_stage_1((string) $quote_id, $from, $to, $subject, $email_body, $cc, $bcc, $attachFiles, $cR, $ccR, $cfR, $cvR, $iaR, $icR, $qiaR, $qiR, $iR, $qtrR, $pcR, $socR, $qR, $qaR, $qcR, $soR, $uiR, $this->viewRenderer)) {
                    $this->sR->quote_mark_sent((string) $quote_id, $qR);
                    $this->flashMessage('success', $this->translator->translate('email.successfully.sent'));
                    return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
                }
            }
        } // quote_id
        $this->flashMessage('danger', $this->translator->translate('email.not.sent.successfully'));
        return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
    }

    /**
     * @param string $quote_id
     * @param QR $qR
     * @param SR $sR
     * @param GR $gR
     */
    public function generate_quote_number_if_applicable(string $quote_id, QR $qR, SR $sR, GR $gR): void
    {
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if (!empty($quote) && ($quote->getStatus_id() == 1) && ($quote->getNumber() == '')) {
            // Generate new quote number if applicable
            if ($sR->getSetting('generate_quote_number_for_draft') == 0) {
                $quote_number = (string) $qR->get_quote_number($quote->getGroup_id(), $gR);
                // Set new quote number and save
                $quote->setNumber($quote_number);
                $qR->save($quote);
            }
        }
    }

    // users with viewInv permission access this function

    /**
     * @param Request $request
     * @param QAR $qaR
     * @param QR $qR
     * @param UCR $ucR
     * @param UIR $uiR
     * @param int $page
     * @param int $status
     * @throws NoClientsAssignedToUserException
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function guest(
        Request $request,
        QAR $qaR,
        QR $qR,
        UCR $ucR,
        UIR $uiR,
        #[RouteArgument('page')]
        int $page = 1,
        #[RouteArgument('status')]
        int $status = 0,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $pageMixed = $query_params['page'] ?? $page;
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $pageMixed > 0 ? (int) $pageMixed : 1;
        /**
         * @var string|null $query_params['sort']
         */
        $sortString = $query_params['sort'] ?? '-id';
        $urlCreator = new UrlCreator($this->url_generator);
        $order = OrderHelper::stringToArray($sortString);
        $urlCreator->__invoke([], $order);
        $sort = Sort::only(['status_id','number','date_created','date_expires','id','client_id'])->withOrderString($sortString);

        // Get the current user and determine from (Related logic: see Settings...User Account) whether they have been given
        // either guest or admin rights. These rights are unrelated to rbac and serve as a second
        // 'line of defense' to support role based admin control.

        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->userService->getUser();
        if ($user) {
            // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount((string) $user->getId()) > 0
                     ? $uiR->repoUserInvUserIdquery((string) $user->getId())
                     : null);
            if ($userinv) {
                // Determine what clients have been allocated to this user (Related logic: see Settings...User Account)
                // by looking at UserClient table

                // eg. If the user is a guest-accountant, they will have been allocated certain clients
                // A user-quest-accountant will be allocated a series of clients
                // A user-guest-client will be allocated their client number by the administrator so that
                // they can view their quotes when they log in
                $user_clients = $ucR->get_assigned_to_user((string) $user->getId());
                if (!empty($user_clients)) {
                    /**
                     * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $quotes
                     */
                    $quotes = $this->quotes_status_with_sort_guest($qR, $status, $user_clients, $sort);
                    if (isset($query_params['filterQuoteNumber']) && !empty($query_params['filterQuoteNumber'])) {
                        $quotes = $qR->filterQuoteNumber((string) $query_params['filterQuoteNumber']);
                    }
                    if (isset($query_params['filterQuoteAmountTotal']) && !empty($query_params['filterQuoteAmountTotal'])) {
                        $quotes = $qR->filterQuoteAmountTotal((string) $query_params['filterQuoteAmountTotal']);
                    }
                    if ((isset($query_params['filterQuoteNumber']) && !empty($query_params['filterQuoteNumber'])) &&
                       (isset($query_params['filterQuoteAmountTotal']) && !empty($query_params['filterQuoteAmountTotal']))) {
                        $quotes = $qR->filterQuoteNumberAndQuoteAmountTotal((string) $query_params['filterQuoteNumber'], (float) $query_params['filterQuoteAmountTotal']);
                    }
                    $paginator = (new DataOffsetPaginator($quotes))
                    ->withPageSize($this->sR->positiveListLimit())
                    ->withCurrentPage($currentPageNeverZero)
                    ->withSort($sort)
                    ->withToken(PageToken::next((string) $pageMixed));
                    $quote_statuses = $qR->getStatuses($this->translator);
                    $parameters = [
                        'alert' => $this->alert(),
                        'qR' => $qR,
                        'qaR' => $qaR,
                        'quotes' => $quotes,
                        // guests will not have access to the pageListLimiter
                        'editInv' => $this->userService->hasPermission('editInv'),
                        'grid_summary' => $this->sR->grid_summary(
                            $paginator,
                            $this->translator,
                            (int) $this->sR->getSetting('default_list_limit'),
                            $this->translator->translate('quotes'),
                            $qR->getSpecificStatusArrayLabel((string) $status),
                        ),
                        'defaultPageSizeOffsetPaginator' => $this->sR->getSetting('default_list_limit')
                                                        ? (int) $this->sR->getSetting('default_list_limit') : 1,
                        'quoteStatuses' => $quote_statuses,
                        'max' => (int) $this->sR->getSetting('default_list_limit'),
                        'page' => (string) $pageMixed,
                        'paginator' => $paginator,
                        'sortOrder' => $sortString,
                        'status' => $status,
                        'urlCreator' => $urlCreator,
                    ];
                    return $this->viewRenderer->render('guest', $parameters);
                } // empty user client
                throw new NoClientsAssignedToUserException($this->translator);
            } // userinv
        } //user
        return $this->webService->getNotFoundResponse();
    }

    // Only users with editInv permission can access this index. Refer to config/routes accesschecker.

    /**
     * @param Request $request
     * @param QAR $qaR
     * @param QR $quoteRepo
     * @param CR $clientRepo
     * @param GR $groupRepo
     * @param SOR $soR
     * @param sR $sR
     * @param UCR $ucR
     * @param string $_language
     * @param string $page
     * @param string $status
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function index(
        Request $request,
        QAR $qaR,
        QR $quoteRepo,
        CR $clientRepo,
        GR $groupRepo,
        SOR $soR,
        SR $sR,
        UCR $ucR,
        #[RouteArgument('_language')]
        string $_language,
        #[RouteArgument('page')]
        string $page = '1',
        #[RouteArgument('status')]
        string $status = '0',
    ): \Yiisoft\DataResponse\DataResponse|Response {
        // build the quote
        $quote = new Quote();
        $quoteForm = new QuoteForm($quote);
        $bootstrap5ModalQuote = new Bootstrap5ModalQuote(
            $this->translator,
            $this->viewRenderer,
            $clientRepo,
            $groupRepo,
            $sR,
            $ucR,
            $quoteForm,
        );
        // If the language dropdown changes
        $this->session->set('_language', $_language);
        $active_clients = $ucR->getClients_with_user_accounts();
        if (!$active_clients == []) {
            $clients = $clientRepo->repoUserClient($active_clients);
            $optionsDataActive = [];
            /**
             * @var \App\Invoice\Entity\Client $client
             */
            foreach ($clients as $client) {
                $client_id = $client->getClient_id();
                if (null !== $client_id) {
                    $optionsDataActive[$client_id] = $client->getClient_full_name();
                }
            }
            $query_params = $request->getQueryParams();
            /**
             * @var string $query_params['page']
             */
            $page = $query_params['page'] ?? $page;
            /** @psalm-var positive-int $currentPageNeverZero */
            $currentPageNeverZero = (int) $page > 0 ? (int) $page : 1;
            //status 0 => 'all';
            $status = (int) $status;
            /** @var string $query_params['sort'] */
            $sortString = $query_params['sort'] ?? '-id';
            $urlCreator = new UrlCreator($this->url_generator);
            $order = OrderHelper::stringToArray($sortString);
            $urlCreator->__invoke([], $order);
            $sort = Sort::only(['id','status_id','number','date_created','date_expires','client_id'])
                        // (Related logic: see vendor\yiisoft\data\src\Reader\Sort
                        // - => 'desc'  so -id => default descending on id
                        // Show the latest quotes first => -id
                        ->withOrder($order);
            /**
             * @psalm-var \Yiisoft\Data\Reader\ReadableDataInterface<array-key, array<array-key, mixed>|object>&\Yiisoft\Data\Reader\LimitableDataInterface&\Yiisoft\Data\Reader\OffsetableDataInterface&\Yiisoft\Data\Reader\CountableDataInterface $quotes
             */
            $quotes = $this->quotes_status_with_sort($quoteRepo, $status, $sort);
            if (isset($query_params['filterQuoteNumber']) && !empty($query_params['filterQuoteNumber'])) {
                $quotes = $quoteRepo->filterQuoteNumber((string) $query_params['filterQuoteNumber']);
            }
            if (isset($query_params['filterQuoteAmountTotal']) && !empty($query_params['filterQuoteAmountTotal'])) {
                $quotes = $quoteRepo->filterQuoteAmountTotal((string) $query_params['filterQuoteAmountTotal']);
            }
            if ((isset($query_params['filterQuoteNumber']) && !empty($query_params['filterQuoteNumber'])) &&
               (isset($query_params['filterQuoteAmountTotal']) && !empty($query_params['filterQuoteAmountTotal']))) {
                $quotes = $quoteRepo->filterQuoteNumberAndQuoteAmountTotal((string) $query_params['filterQuoteNumber'], (float) $query_params['filterQuoteAmountTotal']);
            }
            $paginator = (new DataOffsetPaginator($quotes))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withSort($sort)
            ->withToken(PageToken::next($page));
            $quote_statuses = $quoteRepo->getStatuses($this->translator);
            $parameters = [
                'status' => $status,
                'decimalPlaces' => (int) $this->sR->getSetting('tax_rate_decimal_places'),
                'paginator' => $paginator,
                'sortOrder' => $query_params['sort'] ?? '',
                'alert' => $this->alert(),
                'clientCount' => $clientRepo->count(),
                'optionsDataClientsDropdownFilter' => $this->optionsDataClients($quoteRepo),
                'grid_summary' => $sR->grid_summary(
                    $paginator,
                    $this->translator,
                    (int) $sR->getSetting('default_list_limit'),
                    $this->translator->translate('quotes'),
                    $quoteRepo->getSpecificStatusArrayLabel((string) $status),
                ),
                'defaultPageSizeOffsetPaginator' => $this->sR->getSetting('default_list_limit')
                                                        ? (int) $this->sR->getSetting('default_list_limit') : 1,
                'defaultQuoteGroup' => null !== ($gR = $groupRepo->repoGroupquery($this->sR->getSetting('default_quote_group')))
                                            ? (strlen($groupName = $gR->getName() ?? '') > 0 ? $groupName
                                                                                             : $this->sR->getSetting('i.not_set'))
                                            : $this->sR->getSetting('i.not_set'),
                'quoteStatuses' => $quote_statuses,
                'max' => (int) $sR->getSetting('default_list_limit'),
                'qR' => $quoteRepo,
                'qaR' => $qaR,
                'soR' => $soR,
                'modal_add_quote' => $bootstrap5ModalQuote->renderPartialLayoutWithFormAsString('quote', []),
                'urlCreator' => $urlCreator,
            ];
            return $this->viewRenderer->render('index', $parameters);
        }
        $this->flashMessage('info', $this->translator->translate('user.client.active.no'));
        return $this->webService->getRedirectResponse('client/index');
    }

    /**
     * @param string $items
     * @param FormHydrator $formHydrator
     * @param string $quote_id
     * @param int $order
     * @param PR $pR
     * @param QIR $qir
     * @param QIAR $qiar
     * @param TRR $trr
     * @param UNR $unR
     */
    public function items(
        string $items,
        FormHydrator $formHydrator,
        string $quote_id,
        int $order,
        PR $pR,
        QIR $qir,
        QIAR $qiar,
        TRR $trr,
        UNR $unR,
    ): void {
        /** @var array $item */
        foreach (Json::decode($items) as $item) {
            if ($item['item_name'] && (empty($item['item_id']) || !isset($item['item_id']))) {
                $quoteItem = new QuoteItem();
                $ajax_content = new QuoteItemForm($quoteItem, $quote_id);
                $quoteitem = [];
                $quoteitem['name'] = (string) $item['item_name'];
                $quoteitem['quote_id'] = (string) $item['quote_id'];
                $quoteitem['tax_rate_id'] = (string) $item['item_tax_rate_id'];
                $quoteitem['product_id'] = (string) $item['item_product_id'];
                //product_id used later to get description and name of product.
                $quoteitem['date_added'] = new \DateTimeImmutable();
                $quoteitem['quantity'] = (float) ($item['item_quantity'] ? $this->number_helper->standardize_amount($item['item_quantity']) : (float) 0);
                $quoteitem['price'] = (float) ($item['item_price'] ? $this->number_helper->standardize_amount($item['item_price']) : (float) 0);
                $quoteitem['discount_amount'] = (float) (($item['item_discount_amount']) ? $this->number_helper->standardize_amount($item['item_discount_amount']) : (float) 0);
                $quoteitem['order'] = $order;
                $quoteitem['product_unit'] = $unR->singular_or_plural_name((string) $item['item_product_unit_id'], (int) $item['item_quantity']);
                $quoteitem['product_unit_id'] = (string) ($item['item_product_unit_id'] ?: null);
                unset($item['item_id']);
                ($formHydrator->populate($ajax_content, $quoteitem) && $ajax_content->isValid()) ?
                $this->quote_item_service->addQuoteItem($quoteItem, $quoteitem, $quote_id, $pR, $qiar, new QIAS($qiar), $unR, $trr, $this->translator) : false;
                $order++;
            }
            // Evaluate current items
            if ($item['item_name'] && (!empty($item['item_id']) || isset($item['item_id']))) {
                $unedited = $qir->repoQuoteItemquery((string) $item['item_id']);
                if ($unedited) {
                    $ajax_content = new QuoteItemForm($unedited, $quote_id);
                    $quoteitem = [];
                    $quoteitem['name'] = (string) $item['item_name'];
                    $quoteitem['quote_id'] = (string) $item['quote_id'];
                    $quoteitem['tax_rate_id'] = (string) $item['item_tax_rate_id'] ?: null;
                    $quoteitem['product_id'] = (string) ($item['item_product_id'] ?: null);
                    //product_id used later to get description and name of product.
                    $quoteitem['date_added'] = new \DateTimeImmutable();
                    $quoteitem['quantity'] = (float) ($item['item_quantity'] ? $this->number_helper->standardize_amount($item['item_quantity']) : (float) 0);
                    $quoteitem['price'] = (float) ($item['item_price'] ? $this->number_helper->standardize_amount($item['item_price']) : (float) 0);
                    $quoteitem['discount_amount'] = (float) ($item['item_discount_amount'] ? $this->number_helper->standardize_amount($item['item_discount_amount']) : (float) 0);
                    $quoteitem['order'] = $order;
                    $quoteitem['product_unit'] = $unR->singular_or_plural_name((string) $item['item_product_unit_id'], (int) $item['item_quantity']);
                    $quoteitem['product_unit_id'] = (int) ($item['item_product_unit_id'] ?: null);
                    unset($item['item_id']);
                    ($formHydrator->populate($ajax_content, $quoteitem) && $ajax_content->isValid()) ?
                    $this->quote_item_service->saveQuoteItem($unedited, $quoteitem, $quote_id, $pR, $unR, $this->translator) : false;
                } //unedited
            } // if item
        } // item
    }


    // jquery function currently not used
    // Data parsed from quote.js:$(document).on('click', '#client_change_confirm', function () {

    /**
     * @param Request $request
     * @param CR $cR
     * @param SR $sR
     */
    public function modal_change_client(Request $request, CR $cR, SR $sR): \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        $client = $cR->repoClientquery((string) $body['client_id']);
        $parameters = [
            'success' => 1,
            // Set a client id on quote/view.php so that details can be saved later.
            'pre_save_client_id' => $body['client_id'],
            'client_address_1' => ($client->getClient_address_1() ?? '') . '<br>',
            'client_address_2' => ($client->getClient_address_2() ?? '') . '<br>',
            'client_townline' => ($client->getClient_city() ?? '') . '<br>' . ($client->getClient_state() ?? '') . '<br>' . ($client->getClient_zip() ?? '') . '<br>',
            'client_country' => $client->getClient_country() ?? '',
            'client_phone' => $this->translator->translate('phone') . '&nbsp;' . ($client->getClient_phone() ?? ''),
            'client_mobile' => $this->translator->translate('mobile') . '&nbsp;' . ($client->getClient_mobile() ?? ''),
            'client_fax' => $this->translator->translate('fax') . '&nbsp;' . ($client->getClient_fax() ?? ''),
            'client_email' => $this->translator->translate('email') . '&nbsp;' . (string) Html::link($client->getClient_email()),
            // Reset the a href id="after_client_change_url" link to the new client url
            'after_client_change_url' => 'client/view/' . (string) $body['client_id'],
            'after_client_change_name' => $client->getClient_name(),
        ];
        // return parameters to quote.js:client_change_confirm ajax success function for processing
        return $this->factory->createResponse(Json::encode($parameters));
    }

    // Called from quote.js quote_to_pdf_confirm_with_custom_fields

    /**
     * @param int $include
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param QIR $qiR
     * @param QIAR $qiaR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param SR $sR
     * @param UIR $uiR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function pdf(#[RouteArgument('include')] int $include, CR $cR, CVR $cvR, CFR $cfR, GR $gR, QAR $qaR, QCR $qcR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR): \Yiisoft\DataResponse\DataResponse|Response
    {
        // include is a value of 0 or 1 passed from quote.js function quote_to_pdf_with(out)_custom_fields indicating whether the user
        // wants custom fields included on the quote or not.
        $quote_id = (string) $this->session->get('quote_id');
        $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
        if ($quote_amount) {
            $custom = (($include === 1) ? true : false);
            $quote_custom_values = $this->quote_custom_values((string) $this->session->get('quote_id'), $qcR);
            // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
            $pdfhelper = new PdfHelper($sR, $this->session);
            // The quote will be streamed ie. shown, and not archived
            $stream = true;
            // If we are required to mark quotes as 'sent' when sent.
            if ($sR->getSetting('mark_quotes_sent_pdf') == 1) {
                $this->generate_quote_number_if_applicable($quote_id, $qR, $sR, $gR);
                $sR->quote_mark_sent($quote_id, $qR);
            }
            $quote = $qR->repoQuoteUnloadedquery($quote_id);
            if ($quote) {
                $pdfhelper->generate_quote_pdf($quote_id, $quote->getUser_id(), $stream, $custom, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $this->viewRenderer);
                $parameters = ($include == '1' ? ['success' => 1] : ['success' => 0]);
                return $this->factory->createResponse(Json::encode($parameters));
            } // $inv
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        } // quote_amount
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param int $quote_id
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param QIR $qiR
     * @param QIAR $qiaR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param SR $sR
     * @param UIR $uiR
     */
    public function pdf_dashboard_include_cf(#[RouteArgument('id')] int $quote_id, CR $cR, CVR $cvR, CFR $cfR, GR $gR, QAR $qaR, QCR $qcR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR): void
    {
        if ($quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount((string) $quote_id) > 0) ? $qaR->repoQuotequery((string) $quote_id) : null);
            if ($quote_amount) {
                $quote_custom_values = $this->quote_custom_values((string) $quote_id, $qcR);
                // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($sR, $this->session);
                // The quote will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark quotes as 'sent' when sent.
                if ($sR->getSetting('mark_quotes_sent_pdf') == 1) {
                    $this->generate_quote_number_if_applicable((string) $quote_id, $qR, $sR, $gR);
                    $sR->quote_mark_sent((string) $quote_id, $qR);
                }
                $quote = $qR->repoQuoteUnloadedquery((string) $quote_id);
                if ($quote) {
                    $pdfhelper->generate_quote_pdf((string) $quote_id, $quote->getUser_id(), $stream, true, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $this->viewRenderer);
                }
            }
        } //quote_id
    }

    /**
     * @param int $quote_id
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param QIR $qiR
     * @param QIAR $qiaR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param SR $sR
     * @param UIR $uiR
     */
    public function pdf_dashboard_exclude_cf(#[RouteArgument('id')] int $quote_id, CR $cR, CVR $cvR, CFR $cfR, GR $gR, QAR $qaR, QCR $qcR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR): void
    {
        if ($quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount((string) $quote_id) > 0) ? $qaR->repoQuotequery((string) $quote_id) : null);
            if ($quote_amount) {
                $quote_custom_values = $this->quote_custom_values((string) $quote_id, $qcR);
                // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($sR, $this->session);
                // The quote will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark quotes as 'sent' when sent.
                if ($sR->getSetting('mark_quotes_sent_pdf') == 1) {
                    $this->generate_quote_number_if_applicable((string) $quote_id, $qR, $sR, $gR);
                    $sR->quote_mark_sent((string) $quote_id, $qR);
                }
                $quote = $qR->repoQuoteUnloadedquery((string) $quote_id);
                if ($quote) {
                    $pdfhelper->generate_quote_pdf((string) $quote_id, $quote->getUser_id(), $stream, false, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $this->viewRenderer);
                }
            }
        } // quote_id
    }

    /**
     * @param int $id
     * @param QuoteRepository $quoteRepo
     * @param bool $unloaded
     * @return Quote|null
     */
    private function quote(
        int $id,
        QR $quoteRepo,
        bool $unloaded = false,
    ): Quote|null {
        if ($id) {
            return $unloaded ? $quoteRepo->repoQuoteUnLoadedquery((string) $id) : $quoteRepo->repoQuoteLoadedquery((string) $id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function quotes(QR $quoteRepo, int $status): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $quoteRepo->findAllWithStatus($status);
    }

    /**
     * @param QuoteRepository $quoteRepo
     * @param int $status
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    private function quotes_status_with_sort(QR $quoteRepo, int $status, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        return $quoteRepo->findAllWithStatus($status)
                            ->withSort($sort);
    }

    /**
     * @param QR $qR
     * @param int $status
     * @param array $user_clients
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    private function quotes_status_with_sort_guest(QR $qR, int $status, array $user_clients, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface
    {
        return $qR->repoGuest_Clients_Sent_Viewed_Approved_Rejected_Cancelled($status, $user_clients)
                     ->withSort($sort);
    }

    /**
     * @param string $quote_id
     * @param qcR $qcR
     * @return array
     */
    public function quote_custom_values(string $quote_id, QCR $qcR): array
    {
        // Get all the custom fields that have been registered with this quote on creation, retrieve existing values via repo, and populate
        // custom_field_form_values array
        $custom_field_form_values = [];
        if ($qcR->repoQuoteCount($quote_id) > 0) {
            $quote_custom_fields = $qcR->repoFields($quote_id);
            /**
             * @var string $key
             * @var string $val
             */
            foreach ($quote_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }

    /**
     * @param int $id
     * @param QIR $quoteitemRepository
     * @return QuoteItem|null
     */
    private function quote_item(int $id, QIR $quoteitemRepository): QuoteItem|null
    {
        if ($id) {
            $quoteitem = $quoteitemRepository->repoQuoteItemquery((string) $id);
            if (null !== $quoteitem) {
                return $quoteitem;
            }
            return null;
        }
        return null;
    }

    // Data fed from quote.js->$(document).on('click', '#quote_to_invoice_confirm', function () {

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CFR $cfR
     * @param GR $gR
     * @param IIAR $iiaR
     * @param InvItemAmountservice $iiaS
     * @param PR $pR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param QIR $qiR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param TRR $trR
     * @param UNR $unR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     *
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function quote_to_invoice_confirm(
        Request $request,
        FormHydrator $formHydrator,
        CFR $cfR,
        GR $gR,
        IIAR $iiaR,
        InvItemAmountService $iiaS,
        PR $pR,
        QAR $qaR,
        QCR $qcR,
        QIR $qiR,
        QR $qR,
        QTRR $qtrR,
        TRR $trR,
        UNR $unR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $body = $request->getQueryParams();
        $quote_id = (string) $body['quote_id'];
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if ($quote) {
            $ajax_body = [
                'client_id' => $body['client_id'],
                'group_id' => $body['group_id'],
                'status_id' => 1,
                'quote_id' => $quote->getId(),
                'is_read_only' => 0,
                'date_created' => (new \DateTimeImmutable('now'))->format('Y-m-d'),
                'password' => $body['password'] ?? '',
                'number' => $gR->generate_number((int) $body['group_id']),
                'discount_amount' => (float) $quote->getDiscount_amount(),
                'discount_percent' => (float) $quote->getDiscount_percent(),
                'url_key' => $quote->getUrl_key(),
                'payment_method' => 0,
                'terms' => '',
                'creditinvoice_parent_id' => '',
            ];
            $inv = new Inv();
            $form = new InvForm($inv);
            if ($formHydrator->populateAndValidate($form, $ajax_body) &&
                    // Quote has not been copied before:  inv_id = 0
                    ($quote->getInv_id() === (string) 0)
            ) {
                /**
                 * @var string $ajax_body['client_id']
                 */
                $client_id = $ajax_body['client_id'];
                $user_client = $ucR->repoUserquery($client_id);
                $user_client_count = $ucR->repoUserquerycount($client_id);
                if (null !== $user_client && $user_client_count == 1) {
                    // Only one user account per client
                    $user_id = $user_client->getUser_id();
                    $user = $uR->findById($user_id);
                    if (null !== $user) {
                        $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                        if (null !== $user_inv && $user_inv->getActive()) {
                            $this->inv_service->saveInv($user, $inv, $ajax_body, $this->sR, $gR);
                            $inv_id = $inv->getId();
                            if (null !== $inv_id) {
                                // Transfer each quote_item to inv_item and the corresponding quote_item_amount to inv_item_amount for each item
                                $this->quote_to_invoice_quote_items($quote_id, $inv_id, $iiaR, $iiaS, $pR, $qiR, $trR, $formHydrator, $this->sR, $unR);
                                $this->quote_to_invoice_quote_tax_rates($quote_id, $inv_id, $qtrR, $formHydrator);
                                $this->quote_to_invoice_quote_custom($quote_id, $inv_id, $qcR, $cfR, $formHydrator);
                                $this->quote_to_invoice_quote_amount($quote_id, $inv_id, $qaR, $formHydrator);
                                // Update the quotes inv_id.
                                $quote->setInv_id($inv_id);
                                $qR->save($quote);
                                $parameters = [
                                    'success' => 1,
                                    'flash_message' => $this->translator->translate('quote.copied.to.invoice'),
                                ];
                                return $this->factory->createResponse(Json::encode($parameters));
                            } //null!==$inv_id
                        } // null!==$user_inv && $user_inv->getActive()
                    } // null!==$user
                } // null!==$user_client && $user_client_count==1
            } else {
                $parameters = [
                    'success' => 0,
                    'flash_message' => $this->translator->translate('quote.not.copied.to.invoice'),
                ];
                //return response to quote.js to reload page at location
                return $this->factory->createResponse(Json::encode($parameters));
            }
        } // quote
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CFR $cfR
     * @param GR $gR
     * @param soIAS $soiaS
     * @param PR $pR
     * @param QAR $qaR
     * @param soAR $soaR
     * @param QCR $qcR
     * @param soIAR $soiaR
     * @param QIR $qiR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param TRR $trR
     * @param UNR $unR
     * @param UCR $ucR
     * @param UR $uR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function quote_to_so_confirm(
        Request $request,
        FormHydrator $formHydrator,
        CFR $cfR,
        GR $gR,
        soIAS $soiaS,
        PR $pR,
        QAR $qaR,
        soAR $soaR,
        QCR $qcR,
        soIAR $soiaR,
        QIR $qiR,
        QR $qR,
        QTRR $qtrR,
        TRR $trR,
        UNR $unR,
        UCR $ucR,
        UR $uR,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        // $body data received from user in ...resources\views\invoice\quote\modal_quote_to_so
        // ...src\Invoice\Asset\rebuild-1.13\js\ $(document).on('click', '#quote_to_so_confirm', function () {var url = $(location).attr('origin') + "/invoice/quote/quote_to_so_confirm";
        // These parameters inserted into url
        // A route is necessary for it to pass. see config/common/routes/routes.php  'quote/quote_to_so_confirm'
        $body = $request->getQueryParams();
        $quote_id = (string) $body['quote_id'];
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if ($quote) {
            $so_body = [
                'quote_id' => $quote_id,
                'inv_id' => null,
                'client_id' => $body['client_id'],
                'group_id' => $body['group_id'],
                'client_po_number' => $body['po'],
                'client_po_person' => $body['person'],
                'status_id' => 1,
                'number' => $gR->generate_number((int) $body['group_id']),
                'discount_amount' => (float) $quote->getDiscount_amount(),
                'discount_percent' => (float) $quote->getDiscount_percent(),
                // The quote's url will be the same for the so allowing for a trace
                'url_key' => $quote->getUrl_key(),
                'password' => $body['password'] ?? '',
                'notes' => '',
            ];
            $new_so = new SoEntity();
            $form = new SoForm($new_so);
            if ($formHydrator->populateAndValidate($form, $so_body) && ($quote->getSo_id() === (string) 0)) {
                /**
                 * @var string $so_body['client_id']
                 */
                $client_id = $so_body['client_id'];
                $user_client = $ucR->repoUserquery($client_id);
                $user_client_count = $ucR->repoUserquerycount($client_id);
                if (null !== $user_client && $user_client_count == 1) {
                    // Only one user account per client
                    $user_id = $user_client->getUser_id();
                    $user = $uR->findById($user_id);
                    if (null !== $user) {
                        $this->so_service->addSo($user, $new_so, $so_body);
                        // Ensure that the quote has a specific po and therefore cannot be copied again.
                        $new_so_id = $new_so->getId();
                        // Transfer each quote_item to so_item and the corresponding so_item_amount to so_item_amount for each item
                        if (null !== $new_so_id) {
                            $this->quote_to_so_quote_items($quote_id, $new_so_id, $soiaR, $soiaS, $pR, $qiR, $trR, $unR, $formHydrator);
                            $this->quote_to_so_quote_tax_rates($quote_id, $new_so_id, $qtrR, $formHydrator);
                            $this->quote_to_so_quote_custom($quote_id, $new_so_id, $qcR, $cfR, $formHydrator);
                            $this->quote_to_so_quote_amount($quote_id, $new_so_id, $qaR, $soaR);
                            // Set the quote's sales order id so that it cannot be copied in the future
                            $quote->setSo_id($new_so_id);
                            $qR->save($quote);
                            $parameters = [
                                'success' => 1,
                                'flash_message' => $this->translator->translate('quote.sales.order.created.from.quote'),
                            ];
                            //return response to quote.js to reload page at location
                            return $this->factory->createResponse(Json::encode($parameters));
                        } // null!==$new_so_id
                    }  // null!==$user
                } // null!==$user_client && $user_client_count==1
            } else {
                $parameters = [
                    'success' => 0,
                    'flash_message' => $this->translator->translate('quote.sales.order.not.created.from.quote'),
                ];
                //return response to quote.js to reload page at location
                return $this->factory->createResponse(Json::encode($parameters));
            }
        } // original
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param string $quote_id
     * @param string $inv_id
     * @param IIAR $iiaR
     * @param InvItemAmountService $iiaS
     * @param PR $pR
     * @param QIR $qiR
     * @param TRR $trR
     * @param FormHydrator $formHydrator
     * @param UNR $unR
     */
    private function quote_to_invoice_quote_items(string $quote_id, string $inv_id, IIAR $iiaR, InvItemAmountService $iiaS, PR $pR, QIR $qiR, TRR $trR, FormHydrator $formHydrator, SR $sR, UNR $unR): void
    {
        // Get all items that belong to the quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        /** @var QuoteItem $quote_item */
        foreach ($items as $quote_item) {
            $inv_item = [
                'inv_id' => $inv_id,
                'tax_rate_id' => $quote_item->getTax_rate_id(),
                'product_id' => $quote_item->getProduct_id(),
                'task_id' => '',
                'name' => $quote_item->getName(),
                'description' => $quote_item->getDescription(),
                'quantity' => $quote_item->getQuantity(),
                'price' => $quote_item->getPrice(),
                'discount_amount' => $quote_item->getDiscount_amount(),
                'order' => $quote_item->getOrder(),
                'is_recurring' => 0,
                'product_unit' => $quote_item->getProduct_unit(),
                'product_unit_id' => $quote_item->getProduct_unit_id(),
                // Recurring date
                'date' => '',
            ];
            // Create an equivalent invoice item for the quote item
            $invItem = new InvItem();
            $form = new InvItemForm($invItem, (int) $inv_id);
            if ($formHydrator->populateAndValidate($form, $inv_item)) {
                $this->inv_item_service->addInvItem_product($invItem, $inv_item, $inv_id, $pR, $trR, $iiaS, $iiaR, $sR, $unR);
            }
        } // items
    }

    /**
     * @param string $quote_id
     * @param string|null $inv_id
     * @param QTRR $qtrR
     * @param FormHydrator $formHydrator
     */
    private function quote_to_invoice_quote_tax_rates(string $quote_id, string|null $inv_id, QTRR $qtrR, FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate) {
            $inv_tax_rate = [
                'inv_id' => (string) $inv_id,
                'tax_rate_id' => $quote_tax_rate->getTax_rate_id(),
                'include_item_tax' => $quote_tax_rate->getInclude_item_tax(),
                'inv_tax_rate_amount' => $quote_tax_rate->getQuote_tax_rate_amount(),
            ];
            $entity = new InvTaxRate();
            $form = new InvTaxRateForm($entity);
            if ($formHydrator->populateAndValidate($form, $inv_tax_rate)) {
                $this->inv_tax_rate_service->saveInvTaxRate($entity, $inv_tax_rate);
            }
        } // foreach
    }

    /**
     * @param string $quote_id
     * @param string|null $so_id
     * @param QTRR $qtrR
     * @param FormHydrator $formHydrator
     */
    private function quote_to_so_quote_tax_rates(string $quote_id, string|null $so_id, QTRR $qtrR, FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate) {
            $so_tax_rate = [
                'so_id' => (string) $so_id,
                'tax_rate_id' => $quote_tax_rate->getTax_rate_id(),
                'include_item_tax' => $quote_tax_rate->getInclude_item_tax(),
                'so_tax_rate_amount' => $quote_tax_rate->getQuote_tax_rate_amount(),
            ];
            $entity = new SoTaxRate();
            $form = new SoTaxRateForm($entity);
            if ($formHydrator->populateAndValidate($form, $so_tax_rate)) {
                $this->so_tax_rate_service->saveSoTaxRate($entity, $so_tax_rate);
            }
        } // foreach
    }

    /**
     * @param string $quote_id
     * @param string|null $inv_id
     * @param QCR $qcR
     * @param CFR $cfR
     * @param FormHydrator $formHydrator
     */
    private function quote_to_invoice_quote_custom(
        string $quote_id,
        string|null $inv_id,
        QCR $qcR,
        CFR $cfR,
        FormHydrator $formHydrator,
    ): void {
        $quote_customs = $qcR->repoFields($quote_id);
        // For each quote custom field, build a new custom field for 'inv_custom' using the custom_field_id to find details
        /** @var QuoteCustom $quote_custom */
        foreach ($quote_customs as $quote_custom) {
            // For each quote custom field, build a new custom field for 'inv_custom'
            // using the custom_field_id to find details
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $cfR->repoCustomFieldquery($quote_custom->getCustom_field_id());
            if ($cfR->repoTableAndLabelCountquery('inv_custom', (string) $existing_custom_field->getLabel()) !== 0) {
                // Build an identitcal custom field for the invoice
                $custom_field = new CustomField();
                $custom_field->setTable('inv_custom');
                $custom_field->setLabel((string) $existing_custom_field->getLabel());
                $custom_field->setType($existing_custom_field->getType());
                $custom_field->setLocation((int) $existing_custom_field->getLocation());
                $custom_field->setOrder((int) $existing_custom_field->getOrder());
                $cfR->save($custom_field);
                // Build the inv_custom field record
                $inv_custom = [
                    'inv_id' => $inv_id,
                    'custom_field_id' => $custom_field->getId(),
                    'value' => $quote_custom->getValue(),
                ];
                $entity = new InvCustom();
                $form = new InvCustomForm($entity);
                if ($formHydrator->populateAndValidate($form, $inv_custom)) {
                    $this->inv_custom_service->saveInvCustom($entity, $inv_custom);
                }
            } // existing_custom_field
        } // foreach
    }

    /**
     * @param string $quote_id
     * @param string|null $so_id
     * @param QCR $qcR
     * @param CFR $cfR
     * @param FormHydrator $formHydrator
     */
    private function quote_to_so_quote_custom(
        string $quote_id,
        string|null $so_id,
        QCR $qcR,
        CFR $cfR,
        FormHydrator $formHydrator,
    ): void {
        $quote_customs = $qcR->repoFields($quote_id);
        // For each quote custom field, build a new custom field for 'inv_custom' using the custom_field_id to find details
        /** @var QuoteCustom $quote_custom */
        foreach ($quote_customs as $quote_custom) {
            // For each quote custom field, build a new custom field for 'so_custom'
            // using the custom_field_id to find details
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $cfR->repoCustomFieldquery($quote_custom->getCustom_field_id());
            if ($cfR->repoTableAndLabelCountquery('inv_custom', (string) $existing_custom_field->getLabel()) !== 0) {
                // Build an identitcal custom field for the po
                $custom_field = new CustomField();
                $custom_field->setTable('so_custom');
                $custom_field->setLabel((string) $existing_custom_field->getLabel());
                $custom_field->setType($existing_custom_field->getType());
                $custom_field->setLocation((int) $existing_custom_field->getLocation());
                $custom_field->setOrder((int) $existing_custom_field->getOrder());
                $cfR->save($custom_field);
                // Build the so_custom field record
                $so_custom = [
                    'so_id' => $so_id,
                    'custom_field_id' => $custom_field->getId(),
                    'value' => $quote_custom->getValue(),
                ];
                $entity = new SoCustom();
                $form = new SoCustomForm($entity);
                if ($formHydrator->populateAndValidate($form, $so_custom)) {
                    $this->so_custom_service->saveSoCustom($entity, $so_custom);
                }
            }   // existing_custom_field
        } // foreach
    }

    /**
     * @param string $quote_id
     * @param string|null $inv_id
     * @param QAR $qaR
     * @param FormHydrator $formHydrator
     */
    private function quote_to_invoice_quote_amount(string $quote_id, string|null $inv_id, QAR $qaR, FormHydrator $formHydrator): void
    {
        $quote_amount = $qaR->repoQuotequery($quote_id);
        $inv_amount = [];
        if ($quote_amount) {
            $inv_amount = [
                'inv_id' => $inv_id,
                'sign' => 1,
                'item_subtotal' => $quote_amount->getItem_subtotal(),
                'item_tax_total' => $quote_amount->getItem_tax_total(),
                'tax_total' => $quote_amount->getTax_total(),
                'total' => $quote_amount->getTotal(),
                'paid' => 0.00,
                'balance' => $quote_amount->getTotal(),
            ];
        }
        $entity = new InvAmount();
        $form = new InvAmountForm($entity);
        if ($formHydrator->populateAndValidate($form, $inv_amount)) {
            $this->inv_amount_service->saveInvAmount($entity, $inv_amount);
        }
    }

    /**
     * @param string $quote_id
     * @param string|null $copy_id
     */
    private function quote_to_quote_quote_amount(string $quote_id, string|null $copy_id): void
    {
        $this->quote_amount_service->initializeCopyQuoteAmount(new QuoteAmount(), $quote_id, $copy_id);
    }

    /**
     * @param string $quote_id
     * @param string|null $copy_id
     * @param QAR $qaR
     * @param soAR $soaR
     */
    private function quote_to_so_quote_amount(string $quote_id, string|null $copy_id, QAR $qaR, soAR $soaR): void
    {
        $this->so_amount_service->initializeCopyQuoteAmount(new SoAmount(), $qaR, $soaR, $quote_id, $copy_id);
    }

    // Data fed from quote.js->$(document).on('click', '#quote_to_quote_confirm', function () {

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param GR $gR
     * @param QIAS $qiaS
     * @param PR $pR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param QIAR $qiaR
     * @param QIR $qiR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param TRR $trR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @param UNR $unR
     */
    public function quote_to_quote_confirm(
        Request $request,
        FormHydrator $formHydrator,
        GR $gR,
        QIAS $qiaS,
        PR $pR,
        QAR $qaR,
        QCR $qcR,
        QIAR $qiaR,
        QIR $qiR,
        QR $qR,
        QTRR $qtrR,
        TRR $trR,
        UR $uR,
        UCR $ucR,
        UIR $uiR,
        UNR $unR,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $data_quote_js = $request->getQueryParams();
        $quote_id = (string) $data_quote_js['quote_id'];
        $original = $qR->repoQuoteUnloadedquery($quote_id);
        if ($original) {
            $group_id = $original->getGroup_id();
            $quote_body = [
                'inv_id' => null,
                'client_id' => $data_quote_js['client_id'],
                'group_id' => $group_id,
                'status_id' => 1,
                'number' => $gR->generate_number((int) $group_id),
                'discount_amount' => (float) $original->getDiscount_amount(),
                'discount_percent' => (float) $original->getDiscount_percent(),
                'url_key' => '',
                'password' => '',
                'notes' => '',
            ];
            $copy = new Quote();
            $form = new QuoteForm($copy);
            if ($formHydrator->populateAndValidate($form, $quote_body)) {
                /**
                 * @var string $quote_body['client_id']
                 */
                $client_id = $quote_body['client_id'];
                $user_client = $ucR->repoUserquery($client_id);
                $user_client_count = $ucR->repoUserquerycount($client_id);
                if (null !== $user_client && $user_client_count == 1) {
                    // Only one user account per client
                    $user_id = $user_client->getUser_id();
                    $user = $uR->findById($user_id);
                    if (null !== $user) {
                        $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                        if (null !== $user_inv && $user_inv->getActive()) {
                            $this->quote_service->saveQuote($user, $copy, $quote_body, $this->sR, $gR);
                            // Transfer each quote_item to quote_item and the corresponding quote_item_amount to quote_item_amount for each item
                            $copy_id = $copy->getId();
                            if (null !== $copy_id) {
                                $this->quote_to_quote_quote_items($quote_id, $copy_id, $qiaR, $qiaS, $pR, $qiR, $trR, $unR, $formHydrator);
                                $this->quote_to_quote_quote_tax_rates($quote_id, $copy_id, $qtrR, $formHydrator);
                                $this->quote_to_quote_quote_custom($quote_id, $copy_id, $qcR, $formHydrator);
                                $this->quote_to_quote_quote_amount($quote_id, $copy_id);
                                $qR->save($copy);
                                $parameters = [
                                    'success' => 1,
                                    'flash_message' => $this->translator->translate('quote.copied.to.quote'),
                                ];
                                //return response to quote.js to reload page at location
                                return $this->factory->createResponse(Json::encode($parameters));
                            } // null!==$copy_id
                        } // null!==$user_inv && $user_inv->getActive()
                    } // null!== $user
                } // null!==$user_client && $user_client_count==1
            } // $formHydrator->populateAndValidate($form, $body)
        } else {
            $parameters = [
                'success' => 0,
            ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param string $quote_id
     * @param string|null $copy_id
     * @param QCR $qcR
     * @param FormHydrator $formHydrator
     */
    private function quote_to_quote_quote_custom(string $quote_id, string|null $copy_id, QCR $qcR, FormHydrator $formHydrator): void
    {
        $quote_customs = $qcR->repoFields($quote_id);
        /** @var QuoteCustom $quote_custom */
        foreach ($quote_customs as $quote_custom) {
            $copy_custom = [
                'quote_id' => $copy_id,
                'custom_field_id' => $quote_custom->getCustom_field_id(),
                'value' => $quote_custom->getValue(),
            ];
            $entity = new QuoteCustom();
            $form = new QuoteCustomForm($entity);
            if ($formHydrator->populateAndValidate($form, $copy_custom)) {
                $this->quote_custom_service->saveQuoteCustom($entity, $copy_custom);
            }
        }
    }

    /**
     * @param string $quote_id
     * @param string $copy_id
     * @param QIAR $qiaR
     * @param QIAS $qiaS
     * @param PR $pR
     * @param QIR $qiR
     * @param TRR $trR
     * @param UNR $unR
     * @param FormHydrator $formHydrator
     */
    private function quote_to_quote_quote_items(string $quote_id, string $copy_id, QIAR $qiaR, QIAS $qiaS, PR $pR, QIR $qiR, TRR $trR, UNR $unR, FormHydrator $formHydrator): void
    {
        // Get all items that belong to the original quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        /** @var QuoteItem $quote_item */
        foreach ($items as $quote_item) {
            $copy_item = [
                'quote_id' => $copy_id,
                'tax_rate_id' => $quote_item->getTax_rate_id(),
                'product_id' => $quote_item->getProduct_id(),
                'task_id' => '',
                'name' => $quote_item->getName(),
                'description' => $quote_item->getDescription(),
                'quantity' => $quote_item->getQuantity(),
                'price' => $quote_item->getPrice(),
                'discount_amount' => $quote_item->getDiscount_amount(),
                'order' => $quote_item->getOrder(),
                'is_recurring' => 0,
                'product_unit' => $quote_item->getProduct_unit(),
                'product_unit_id' => $quote_item->getProduct_unit_id(),
                // Recurring date
                'date' => '',
            ];
            // Create an equivalent invoice item for the quote item
            $copyItem = new QuoteItem();
            $form = new QuoteItemForm($copyItem, $copy_id);
            if ($formHydrator->populateAndValidate($form, $copy_item)) {
                $this->quote_item_service->addQuoteItem($copyItem, $copy_item, $copy_id, $pR, $qiaR, $qiaS, $unR, $trR, $this->translator);
            }
        } // items as quote_item
    }

    private function quote_to_so_quote_items(string $quote_id, string $so_id, soIAR $soiaR, soIAS $soiaS, PR $pR, QIR $qiR, TRR $trR, UNR $unR, FormHydrator $formHydrator): void
    {
        // Note: The $soiaR variable will be used to see if there are pre-existing amounts later towards the end of this function
        // Get all items that belong to the original quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        /** @var QuoteItem $quote_item */
        foreach ($items as $quote_item) {
            $so_item = [
                'so_id' => $so_id,
                'peppol_po_itemid' => '',
                'tax_rate_id' => $quote_item->getTax_rate_id(),
                'product_id' => $quote_item->getProduct_id(),
                // There are currently no tasks provided in a quote and tasks and products are mutually exclusive
                'task_id' => '',
                'name' => $quote_item->getName(),
                'description' => $quote_item->getDescription(),
                'quantity' => $quote_item->getQuantity(),
                'price' => $quote_item->getPrice(),
                'discount_amount' => $quote_item->getDiscount_amount(),
                'order' => $quote_item->getOrder(),
                'is_recurring' => 0,
                'product_unit' => $quote_item->getProduct_unit(),
                'product_unit_id' => $quote_item->getProduct_unit_id(),
                // Recurring date
                'date' => '',
            ];
            // Create an equivalent purchase order item for the quote item
            $soItem = new SoItem();
            $form = new SoItemForm($soItem);
            if ($formHydrator->populateAndValidate($form, $so_item)) {
                $this->so_item_service->addSoItem($soItem, $so_item, $so_id, $pR, $soiaR, $soiaS, $unR, $trR);
            }
        } // items as quote_item
    }

    /**
     * @param string $quote_id
     * @param string|null $copy_id
     * @param QTRR $qtrR
     * @param FormHydrator $formHydrator
     */
    private function quote_to_quote_quote_tax_rates(string $quote_id, string|null $copy_id, QTRR $qtrR, FormHydrator $formHydrator): void
    {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate) {
            $copy_tax_rate = [
                'quote_id' => $copy_id,
                'tax_rate_id' => $quote_tax_rate->getTax_rate_id(),
                'include_item_tax' => $quote_tax_rate->getInclude_item_tax(),
                'quote_tax_rate_amount' => $quote_tax_rate->getQuote_tax_rate_amount(),
            ];
            $entity = new QuoteTaxRate();
            $form = new QuoteTaxRateForm($entity);
            if ($formHydrator->populateAndValidate($form, $copy_tax_rate)) {
                $this->quote_tax_rate_service->saveQuoteTaxRate($entity, $copy_tax_rate);
            }
        }
    }

    /**
     * @param int $id
     * @param QTRR $quotetaxrateRepository
     * @return QuoteTaxRate|null
     */
    private function quotetaxrate(int $id, QTRR $quotetaxrateRepository): QuoteTaxRate|null
    {
        if ($id) {
            $quotetaxrate = $quotetaxrateRepository->repoQuoteTaxRatequery((string) $id);
            if (null !== $quotetaxrate) {
                return $quotetaxrate;
            }
            return null;
        }
        return null;
    }

    /**
     * @param array $files
     * @return mixed
     */
    private function remove_extension(array $files): mixed
    {
        /**
         * @var string $file
         */
        foreach ($files as $key => $file) {
            $files[$key] = str_replace('.php', '', $file);
        }

        return $files;
    }

    // quote/view => '#btn_save_quote_custom_fields' => quote_custom_field.js => /invoice/quote/save_custom";

    /**
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param QCR $qcR
     */
    public function save_custom(FormHydrator $formHydrator, Request $request, QCR $qcR): \Yiisoft\DataResponse\DataResponse
    {
        $parameters = [
            'success' => 0,
        ];
        $js_data = $request->getQueryParams();
        $quote_id = (int) $js_data['quote_id'];
        $custom_field_body = [
            'custom' => $js_data['custom'] ?: '',
        ];
        $this->custom_fields($formHydrator, $custom_field_body, $quote_id, $qcR);
        $parameters['success'] = 1;
        return $this->factory->createResponse(Json::encode($parameters));
    }

    // '#quote_tax_submit' => quote.js

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     */
    public function save_quote_tax_rate(Request $request, FormHydrator $formHydrator): \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        $ajax_body = [
            'quote_id' => $body['quote_id'],
            'tax_rate_id' => $body['tax_rate_id'],
            'include_item_tax' => $body['include_item_tax'],
            'quote_tax_rate_amount' => 0.00,
        ];
        $quoteTaxRate = new QuoteTaxRate();
        $ajax_content = new QuoteTaxRateForm($quoteTaxRate);
        if ($formHydrator->populate($ajax_content, $ajax_body) && $ajax_content->isValid()) {
            $this->quote_tax_rate_service->saveQuoteTaxRate($quoteTaxRate, $ajax_body);
            $parameters = [
                'success' => 1,
                'flash_message' => $this->translator->translate('quote.tax.rate.saved'),
            ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));
        }
        $parameters = [
            'success' => 0,
            'flash_message' => $this->translator->translate('quote.tax.rate.incomplete.fields'),
        ];
        //return response to quote.js to reload page at location
        return $this->factory->createResponse(Json::encode($parameters));
    }

    // When you click on Send Mail whilst in the view, you will get mailer_quote view showing with the url_key at the bottom
    // Use this url_key to test what the customer will experience eg. invoice/quote/url_key/{url_key}
    // config/routes accesschecker ensures client has viewInv permission

    public function url_key(#[RouteArgument('url_key')] string $urlKey, CurrentUser $currentUser, CFR $cfR, QAR $qaR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, UIR $uiR, UCR $ucR, PMR $pmR): Response
    {
        // If there is no quote with such a url_key, issue a not found response
        if ($urlKey === '') {
            return $this->webService->getNotFoundResponse();
        }

        // If there is a quote with the url key ... continue or else issue not found response
        if ($qR->repoUrl_key_guest_count($urlKey) < 1) {
            return $this->webService->getNotFoundResponse();
        }

        // If this quote has a status id that falls into the category of (just)sent, viewed(in the past), approved(in the past) then continue
        $quote = $qR->repoUrl_key_guest_loaded($urlKey);
        $quote_tax_rates = null;
        if ($quote) {
            $quote_id = $quote->getId();
            if (null !== $quote_id) {
                if ($qtrR->repoCount($quote_id) > 0) {
                    $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
                }
            }
            // If the quote status is sent 2, viewed 3, or approved_with 4, or approved_without 5 or rejected 6
            if (in_array($quote->getStatus_id(), [2,3,4,5,6])) {
                $user_id = $quote->getUser_id();
                if ($uiR->repoUserInvUserIdcount($user_id) === 1) {
                    // After signup the user was included in the userinv using Settings...User Account...+
                    $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                    // The client has been assigned to the user id using Setting...User Account...Assigned Clients
                    $user_client = $ucR->repoUserClientqueryCount($user_id, $quote->getClient_id()) === 1 ? true : false;
                    if ($user_inv && $user_client) {
                        // If the userinv is a Guest => type = 1 ie. NOT an administrator =>type = 0
                        // So if the user has a type of 1 they are a guest.
                        if ($user_inv->getType() == 1) {
                            if ($quote->getStatus_id() === 2) {
                                // The quote has just been sent so change its status otherwise leave its status alone
                                $quote->setStatus_id(3);
                            }
                            $qR->save($quote);
                            $custom_fields = [
                                'invoice' => $cfR->repoTablequery('inv_custom'),
                                'client' => $cfR->repoTablequery('client_custom'),
                                //'user' => $cfR->repoTablequery('user_custom'),
                            ];

                            if (null !== $quote_id) {
                                $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
                                if ($quote_amount) {
                                    $parameters = [
                                        'renderTemplate' => $this->viewRenderer->renderPartialAsString('//invoice/template/quote/public/' . ($this->sR->getSetting('public_quote_template') ?: 'Quote_Web'), [
                                            'isGuest' => $currentUser->isGuest(),
                                            'alert' => $this->alert(),
                                            'quote' => $quote,
                                            'qiaR' => $qiaR,
                                            'quote_amount' => $quote_amount,
                                            'items' => $qiR->repoQuotequery($quote_id),
                                            // Get all the quote tax rates that have been setup for this quote
                                            'quote_tax_rates' => $quote_tax_rates,
                                            'quote_url_key' => $urlKey,
                                            'flash_message' => $this->flashMessage('info', ''),
                                            //'attachments' => $attachments,
                                            'custom_fields' => $custom_fields,
                                            'has_expired' => new \DateTimeImmutable('now') > $quote->getDate_expires() ? true : false,
                                            'client' => $quote->getClient(),
                                            // Get the details of the user of this quote
                                            'userInv' => $uiR->repoUserInvUserIdcount($user_id) > 0 ? $uiR->repoUserInvUserIdquery($user_id) : null,
                                            'modal_purchase_order_number' => $this->viewRenderer->renderPartialAsString('//invoice/quote/modal_purchase_order_number', ['urlKey' => $urlKey]),
                                        ]),
                                    ];
                                    return $this->viewRenderer->render('url_key', $parameters);
                                } // if quote_amount
                            } // if there is a quote id
                        } // user_inv->getType
                    } // user_inv
                } // $uiR
            } // if in_array
        } // if quote
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param int $id
     * @param string $_language
     * @param CFR $cfR
     * @param CVR $cvR
     * @param PIR $piR
     * @param PR $pR
     * @param QAR $qaR
     * @param QIAR $qiaR
     * @param QIR $qiR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param TRR $trR
     * @param FR $fR
     * @param UNR $uR
     * @param CR $cR
     * @param GR $gR
     * @param QCR $qcR
     * @param SOR $soR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        #[RouteArgument('id')]
        int $id,
        #[RouteArgument('_language')]
        string $_language,
        CFR $cfR,
        CVR $cvR,
        PIR $piR,
        PR $pR,
        QAR $qaR,
        QIAR $qiaR,
        QIR $qiR,
        QR $qR,
        QTRR $qtrR,
        TRR $trR,
        FR $fR,
        UNR $uR,
        CR $cR,
        GR $gR,
        QCR $qcR,
        SOR $soR,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $quote = $this->quote($id, $qR, false);
        if (null !== $quote) {
            $quote_id = $quote->getId();
            if (null !== $quote_id) {
                $this->session->set('quote_id', $quote_id);
                $this->number_helper->calculate_quote((string) $this->session->get('quote_id'), $qiR, $qiaR, $qtrR, $qaR, $qR);
                $quote_tax_rates = (($qtrR->repoCount((string) $this->session->get('quote_id')) > 0) ? $qtrR->repoQuotequery((string) $this->session->get('quote_id')) : null);
                $sales_order_number = '';
                if ($quote->getSo_id()) {
                    $so = $soR->repoSalesOrderUnloadedquery($quote->getSo_id());
                    $sales_order_number = $so ? ($so->getNumber() ?? '') : '';
                }
                $quote_amount = (($qaR->repoQuoteAmountCount((string) $this->session->get('quote_id')) > 0) ? $qaR->repoQuotequery((string) $this->session->get('quote_id')) : null);
                if ($quote_amount) {
                    $quote_custom_values = $this->quote_custom_values((string) $this->session->get('quote_id'), $qcR);
                    $parameters = [
                        'body' => $this->body($quote),
                        'alert' => $this->alert(),
                        // Hide buttons on the view if a 'viewInv' user does not have 'editInv' permission
                        'invEdit' => $this->userService->hasPermission('editInv') ? true : false,
                        // if the quote amount total is greater than zero show the buttons eg. Send email
                        'quote_amount_total' => $quote_amount->getTotal(),
                        'sales_order_number' => $sales_order_number,
                        'clientHelper' => new ClientHelper($this->sR),
                        'countryHelper' => new CountryHelper(),
                        'dateHelper' => new DateHelper($this->sR),
                        'numberHelper' => $this->number_helper,
                        'quoteForm' => new QuoteForm($quote),
                        'add_quote_item' => $this->viewRenderer->renderPartialAsString('//invoice/quoteitem/_item_form', [
                            'actionName' => 'quoteitem/add',
                            'actionArguments' => ['_language' => $_language],
                            'errors' => [],
                            'form' => new QuoteItemForm(new QuoteItem(), $quote_id),
                            'quote_id' => $this->quote($id, $qR, true),
                            'taxRates' => $trR->findAllPreloaded(),
                            'products' => $pR->findAllPreloaded(),
                            'units' => $uR->findAllPreloaded(),
                            'numberHelper' => new NumberHelper($this->sR),
                        ]),
                        // Get all the fields that have been setup for this SPECIFIC quote in quote_custom.
                        'fields' => $qcR->repoFields((string) $this->session->get('quote_id')),
                        // Get the standard extra custom fields built for EVERY quote.
                        'customFields' => $cfR->repoTablequery('quote_custom'),
                        'customValues' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                        'cvH' => new CVH($this->sR),
                        'quoteCustomValues' => $quote_custom_values,
                        'quoteStatuses' => $qR->getStatuses($this->translator),
                        'quote' => $quote,
                        'partial_item_table' => $this->viewRenderer->renderPartialAsString('//invoice/quote/partial_item_table', [
                            'included' => $this->translator->translate('item.tax.included'),
                            'excluded' => $this->translator->translate('item.tax.excluded'),
                            'invEdit' => $this->userService->hasPermission('editInv') ? true : false,
                            'piR' => $piR,
                            'products' => $pR->findAllPreloaded(),
                            'quoteItems' => $qiR->repoQuotequery((string) $this->session->get('quote_id')),
                            'qiaR' => $qiaR,
                            'quoteTaxRates' => $quote_tax_rates,
                            'quoteAmount' => $quote_amount,
                            'quote' => $quote,
                            'language' => $_language,
                            'taxRates' => $trR->findAllPreloaded(),
                            'units' => $uR->findAllPreloaded(),
                        ]),
                        'modal_choose_items' => $this->viewRenderer->renderPartialAsString(
                            '//invoice/product/modal_product_lookups_quote',
                            [
                                'families' => $fR->findAllPreloaded(),
                                'default_item_tax_rate' => $this->sR->getSetting('default_item_tax_rate') !== '' ?: 0,
                                'filter_product' => '',
                                'filter_family' => '',
                                'reset_table' => '',
                                'products' => $pR->findAllPreloaded(),
                                'partial_product_table_modal' => $this->viewRenderer->renderPartialAsString('//invoice/product/_partial_product_table_modal', [
                                    'products' => $pR->findAllPreloaded(),
                                ]),
                            ],
                        ),
                        'modal_add_quote_tax' => $this->viewRenderer->renderPartialAsString(
                            '//invoice/quote/modal_add_quote_tax',
                            [
                                'taxRates' => $trR->findAllPreloaded(),
                            ],
                        ),
                        'modal_copy_quote' => $this->viewRenderer->renderPartialAsString('//invoice/quote/modal_copy_quote', [ 's' => $this->sR,
                            'quote' => $qR->repoQuoteLoadedquery((string) $this->session->get('quote_id')),
                            'clients' => $cR->findAllPreloaded(),
                            'groups' => $gR->findAllPreloaded(),
                        ]),
                        'modal_delete_quote' => $this->viewRenderer->renderPartialAsString(
                            '//invoice/quote/modal_delete_quote',
                            [
                                'actionName' => 'quote/delete',
                                'actionArguments' => ['_language' => $_language, 'id' => $this->session->get('quote_id')],
                            ],
                        ),
                        'modal_delete_items' => $this->viewRenderer->renderPartialAsString('//invoice/quote/modal_delete_item', [
                            'partial_item_table_modal' => $this->viewRenderer->renderPartialAsString('//invoice/quoteitem/_partial_item_table_modal', [
                                'quoteItems' => $qiR->repoQuotequery((string) $this->session->get('quote_id')),
                                'numberHelper' => new NumberHelper($this->sR),
                            ]),
                        ]),
                        'modal_quote_to_invoice' => $this->viewRenderer->renderPartialAsString('//invoice/quote/modal_quote_to_invoice', [
                            'quote' => $quote,
                            'groups' => $gR->findAllPreloaded(),
                        ]),
                        'modal_quote_to_so' => $this->viewRenderer->renderPartialAsString('//invoice/quote/modal_quote_to_so', [
                            'quote' => $quote,
                            'groups' => $gR->findAllPreloaded(),
                        ]),
                        'modal_quote_to_pdf' => $this->viewRenderer->renderPartialAsString('//invoice/quote/modal_quote_to_pdf', [
                            'quote' => $quote,
                        ]),
                        'view_custom_fields' => $this->viewRenderer->renderPartialAsString('//invoice/quote/view_custom_fields', [
                            'custom_fields' => $cfR->repoTablequery('quote_custom'),
                            'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                            'quote_custom_values' => $quote_custom_values,
                            'cvH' => new CVH($this->sR),
                            'cvR' => $cvR,
                            'quoteCustomForm' => new QuoteCustomForm(new QuoteCustom()),
                        ]),
                    ];
                    return $this->viewRenderer->render('_view', $parameters);
                } // quote_amount
                $this->flashMessage('info', 'no quote tax');
            } // null!= $quote_id
        } //quote
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param Quote $quote
     * @param int $client_id
     * @param CR $clientRepo
     * @param ContractRepo $contractRepo
     * @param DLR $delRepo
     * @param GR $groupRepo
     * @param QR $quoteRepo
     * @param UCR $ucR
     * @return array
     */
    private function editOptionsData(
        Quote $quote,
        int $client_id,
        CR $clientRepo,
        ContractRepo $contractRepo,
        DLR $delRepo,
        GR $groupRepo,
        QR $quoteRepo,
        UCR $ucR,
    ): array {
        $contracts = $contractRepo->repoClient($quote->getClient_id());
        $optionsDataContract = [];
        /**
         * @var Contract $contract
         */
        foreach ($contracts as $contract) {
            $id = $contract->getId();
            $contractLine = [];
            if (null !== $id) {
                if (null !== $contract->getName()) {
                    $contractLine[] = $contract->getName();
                }
                if (null !== $contract->getReference()) {
                    $contractLine[] = $contract->getReference();
                }
                $optionsDataContract[$id] = implode(',', $contractLine);
            }
        }

        $dLocs = $delRepo->repoClientquery((string) $client_id);
        $optionsDataDeliveryLocations = [];
        /**
         * @var DeliveryLocation $dLoc
         */
        foreach ($dLocs as $dLoc) {
            $dLocId = $dLoc->getId();
            $address = [];
            if (null !== $dLocId) {
                if (null !== $dLoc->getAddress_1()) {
                    $address[] = $dLoc->getAddress_1();
                }
                if (null !== $dLoc->getAddress_2()) {
                    $address[] = $dLoc->getAddress_2();
                }
                if (null !== $dLoc->getCity()) {
                    $address[] = $dLoc->getCity();
                }
                if (null !== $dLoc->getZip()) {
                    $address[] = $dLoc->getZip();
                }
                $optionsDataDeliveryLocations[$dLocId] = implode(',', $address);
            }
        }
        $optionsDataGroup = [];
        /**
         * @var Group $group
         */
        foreach ($groupRepo->findAllPreloaded() as $group) {
            $optionsDataGroup[$group->getId()] = $group->getName();
        }

        $optionsDataQuoteStatus = [];
        /**
         * @var string $key
         * @var array $status
         */
        foreach ($quoteRepo->getStatuses($this->translator) as $key => $status) {
            $optionsDataQuoteStatus[$key] = (string) $status['label'];
        }
        return $optionsData = [
            'client' => $clientRepo->optionsData($ucR),
            'contract' => $optionsDataContract,
            'deliveryLocation' => $optionsDataDeliveryLocations,
            'group' => $optionsDataGroup,
            'quoteStatus' => $optionsDataQuoteStatus,
        ];
    }

    /**
     * @param QR $qR
     * @return array
     */
    public function optionsDataClients(QR $qR): array
    {
        $optionsDataClients = [];
        // Get all the invoices that have been made out to clients with user accounts
        $quotes = $qR->findAllPreloaded();
        /**
         * @var Quote $quote
         */
        foreach ($quotes as $quote) {
            $client = $quote->getClient();
            if (null !== $client) {
                if (strlen($client->getClient_full_name()) > 0) {
                    $fullName = $client->getClient_full_name();
                    $optionsDataClients[$fullName] = !empty($fullName) ? $fullName : '';
                }
            }
        }
        return $optionsDataClients;
    }
}
