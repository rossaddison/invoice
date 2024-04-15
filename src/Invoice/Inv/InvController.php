<?php
declare(strict_types=1);

namespace App\Invoice\Inv;

// Entity's
use App\Invoice\Entity\Client;
use App\Invoice\Entity\Contract;
use App\Invoice\Entity\Delivery;
use App\Invoice\Entity\DeliveryLocation;
use App\Invoice\Entity\EmailTemplate;
use App\Invoice\Entity\Group;
use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\Entity\InvAllowanceCharge;
use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvAmount;
use App\Invoice\Entity\InvCustom;
use App\Invoice\Entity\InvTaxRate;
use App\Invoice\Entity\PaymentMethod;
use App\Invoice\Entity\PostalAddress;
use App\Invoice\Entity\Setting;
use App\Invoice\Entity\Sumex;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Entity\Upload;
// Services
// Inv
use App\User\UserService;
use App\User\User;
use App\Invoice\DeliveryLocation\DeliveryLocationService as DLS;
use App\Invoice\Inv\InvService;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeService;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeService;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\InvTaxRate\InvTaxRateService;
use App\Invoice\InvCustom\InvCustomService;
// Forms Inv
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeForm;
use App\Invoice\Inv\InvAttachmentsForm;
use App\Invoice\Inv\InvForm;
use App\Invoice\Inv\MailerInvForm;
use App\Invoice\InvCustom\InvCustomForm;
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvTaxRate\InvTaxRateForm;
// Repositories
use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\ClientPeppol\ClientPeppolRepository as cpR;
use App\Invoice\Contract\ContractRepository as ContractRepo;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Delivery\DeliveryRepository as DelRepo;
use App\Invoice\DeliveryParty\DeliveryPartyRepository as DelPartyRepo;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Payment\PaymentRepository as PYMR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
use App\Invoice\PostalAddress\PostalAddressRepository as paR;
use App\Invoice\ProductImage\ProductImageRepository as PIR;
use App\Invoice\ProductProperty\ProductPropertyRepository as ppR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Project\ProjectRepository as PRJCTR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SOCR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Sumex\SumexRepository as SumexR;
use App\Invoice\Task\TaskRepository as TASKR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UNR;
use App\Invoice\UnitPeppol\UnitPeppolRepository as unpR;
use App\Invoice\Upload\UploadRepository as UPR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserClient\Exception\NoClientsAssignedToUserException;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;
use App\Service\WebControllerService;
// App Helpers
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\MailerHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\PdfHelper;
use App\Invoice\Helpers\Peppol\PeppolHelper;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\Helpers\StoreCove\StoreCoveHelper;
use App\Invoice\Helpers\TemplateHelper;
use App\Widget\Bootstrap5ModalInv;
use App\Widget\Bootstrap5ModalTranslatorMessageWithoutAction;
// Libraries
use App\Invoice\Libraries\Crypt;
// Yii
use Yiisoft\Data\Paginator\OffsetPaginator as DataOffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Html\Html;
use Yiisoft\Json\Json;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\ViewRenderer;
// Psr\Http
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class InvController {
    private Crypt $crypt;
    private DateHelper $date_helper;
    private DataResponseFactoryInterface $factory;
    private Flash $flash;
    private InvAmountService $inv_amount_service;
    private InvCustomService $inv_custom_service;
    private InvService $inv_service;
    private InvAllowanceChargeService $inv_allowance_charge_service;
    private InvItemService $inv_item_service;
    private InvItemAllowanceChargeService $aciis;
    private IIAS $inv_item_amount_service;
    private InvTaxRateService $inv_tax_rate_service;
    private LoggerInterface $logger;
    private MailerInterface $mailer;
    private NumberHelper $number_helper;
    private PdfHelper $pdf_helper;
    private SessionInterface $session;
    private SR $sR;
    private TranslatorInterface $translator;
    private UrlGenerator $url_generator;
    private UserService $user_service;
    private ViewRenderer $view_renderer;
    private WebControllerService $web_service;
    /**
     * @param Crypt $crypt
     * @param DataResponseFactoryInterface $factory
     * @param InvAllowanceChargeService $inv_allowance_charge_service
     * @param InvAmountService $inv_amount_service
     * @param InvService $inv_service
     * @param InvCustomService $inv_custom_service
     * @param InvItemService $inv_item_service
     * @param InvItemAllowanceChargeService $aciis
     * @param IIAS $inv_item_amount_service
     * @param InvTaxRateService $inv_tax_rate_service
     * @param LoggerInterface $logger
     * @param MailerInterface $mailer
     * @param SessionInterface $session
     * @param SR $sR
     * @param TranslatorInterface $translator
     * @param UserService $user_service
     * @param UrlGenerator $url_generator
     * @param ViewRenderer $view_renderer
     * @param WebControllerService $web_service
     */
    public function __construct(
        Crypt $crypt,
        DataResponseFactoryInterface $factory,
        InvAllowanceChargeService $inv_allowance_charge_service,
        InvAmountService $inv_amount_service,
        InvService $inv_service,
        InvCustomService $inv_custom_service,
        InvItemService $inv_item_service,
        InvItemAllowanceChargeService $aciis,
        IIAS $inv_item_amount_service,
        InvTaxRateService $inv_tax_rate_service,
        LoggerInterface $logger,
        MailerInterface $mailer,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $user_service,
        UrlGenerator $url_generator,
        ViewRenderer $view_renderer,
        WebControllerService $web_service,
    ) {
        $this->crypt = $crypt;
        $this->date_helper = new DateHelper($sR);
        $this->factory = $factory;
        $this->flash = new Flash($session);
        $this->inv_allowance_charge_service = $inv_allowance_charge_service;
        $this->inv_amount_service = $inv_amount_service;
        $this->inv_service = $inv_service;
        $this->inv_custom_service = $inv_custom_service;
        $this->inv_item_service = $inv_item_service;
        $this->aciis = $aciis;
        $this->inv_item_amount_service = $inv_item_amount_service;
        $this->inv_tax_rate_service = $inv_tax_rate_service;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->number_helper = new NumberHelper($sR);
        $this->pdf_helper = new PdfHelper($sR, $session);
        $this->session = $session;
        $this->sR = $sR;
        $this->translator = $translator;
        $this->user_service = $user_service;
        $this->url_generator = $url_generator;
        $this->view_renderer = $view_renderer;
        if ($this->user_service->hasPermission('viewInv') && !$this->user_service->hasPermission('editInv')) {
            $this->view_renderer = $view_renderer->withControllerName('invoice')
                    ->withLayout('@views/layout/guest.php');
        }
        if ($this->user_service->hasPermission('viewInv') && $this->user_service->hasPermission('editInv')) {
            $this->view_renderer = $view_renderer->withControllerName('invoice')
                    ->withLayout('@views/layout/invoice.php');
        }
        $this->web_service = $web_service;
    }
    
    /**
     * @param string $client_id
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return User|null
     */
    private function active_user(string $client_id, UR $uR, UCR $ucR, UIR $uiR) : User|null {
      $user_client = $ucR->repoUserquery($client_id);
      if (null!==$user_client) {
            $user_client_count = $ucR->repoUserquerycount($client_id);
            if ($user_client_count==1) {
              $user_id = $user_client->getUser_id();
              $user = $uR->findById($user_id);
              if (null!==$user) {
                $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                if (null!==$user_inv && $user_inv->getActive()) {
                  return $user;
                }
              }
            }
      }
      return null;
    }
    
    /**
     * @return string
     */
    private function alert(): string {
      return $this->view_renderer->renderPartialAsString('/invoice/layout/alert',
      [ 
        'flash' => $this->flash,
      ]);
    }

    /**
     * @param Request $request
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function archive(Request $request): \Yiisoft\DataResponse\DataResponse {
        // TODO filter system: Currently the filter is disabled on the archive view.
        $invoice_archive = [];
        $flash_message = '';
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                /**
                 * @var string $value
                 */
                foreach ($body as $key => $value) {
                    if (((string) $key === 'invoice_number')) {
                        $invoice_archive = $this->sR->get_invoice_archived_files_with_filter($value);
                        $flash_message = $value;
                    }
                }
            }
        } else {
            $invoice_archive = $this->sR->get_invoice_archived_files_with_filter('');
            $flash_message = '';
        }
        $parameters = [
            'partial_inv_archive' => $this->view_renderer->renderPartialAsString('/invoice/inv/partial_inv_archive',
                    [
                        'invoices_archive' => $invoice_archive
                    ]),
            'alert' => $this->alert(), 
            'body' => $request->getParsedBody(),
        ];
        return $this->view_renderer->render('/invoice/inv/archive', $parameters);
    }

    /**
     * @param string $tmp
     * @param string $target
     * @param int $client_id
     * @param string $url_key
     * @param string $fileName
     * @param UPR $uPR
     * @return bool
     */
    private function attachment_move_to(string $tmp, string $target, int $client_id, string $url_key, string $fileName, UPR $uPR
    ): bool {
        $file_exists = file_exists($target);
        // The file does not exist yet in the target path but it exists in the tmp folder on the server
        if (!$file_exists) {
            // Record the details of this upload
            // (@see https://www.php.net/manual/en/function.is-uploaded-file.php)
            // Returns true if the file named by filename was uploaded via HTTP POST.
            // This is useful to help ensure that a malicious user hasn't tried to trick
            // the script into working on files upon which it should not be working--for instance, /etc/passwd.
            // This sort of check is especially important if there is any chance that anything
            // done with uploaded files could reveal their contents to the user, or even to other users on the same
            // system. For proper working, the function is_uploaded_file() needs an argument like
            // $_FILES['userfile']['tmp_name'], - the name of the uploaded file on the client's machine
            // $_FILES['userfile']['name'] does not work.
            if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $target)) {
                $track_file = new Upload();
                $track_file->setClient_id($client_id);
                $track_file->setUrl_key($url_key);
                $track_file->setFile_name_original($fileName);
                $track_file->setFile_name_new($url_key . '_' . $fileName);
                $track_file->setUploaded_date(new \DateTimeImmutable());
                $uPR->save($track_file);
                return true;
            } else {
                $this->flash_message('warning', $this->translator->translate('invoice.invoice.possible.file.upload.attack') . $tmp);
                return false;
            }
        } else {
            $this->flash_message('warning', $this->translator->translate('i.error_duplicate_file'));
            return false;
        }
    }

    /**
     * @param int $inv_id
     * @return string
     */
    private function attachment_not_writable(int $inv_id): string {
        return $this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                        [
                            'heading' => $this->translator->translate('i.errors'),
                            'message' => $this->translator->translate('i.path') . 
                                         $this->translator->translate('i.is_not_writable'),
                            'url' => 'inv/view', 'id' => $inv_id
                        ]);
    }

    /**
     * @param int $inv_id
     * @return string
     */
    private function attachment_successfully_created(int $inv_id): string {
        return $this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                        [
                            'heading' => '', 
                            'message' => $this->translator->translate('i.record_successfully_created'),
                            'url' => 'inv/view', 'id' => $inv_id]);
    }

    /**
     * @param int $inv_id
     * @return string
     */
    private function attachment_no_file_uploaded(int $inv_id): string {
        return $this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
        [
            'heading' => $this->translator->translate('invoice.errors'), 
            'message' => $this->translator->translate('invoice.no.file.uploaded'),
            'url' => 'inv/view', 'id' => $inv_id
        ]);
    }

    /**
     * 
     * @param int $inv_id
     * @param IR $iR
     * @param UPR $uPR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function attachment( #[RouteArgument('id')] int $inv_id, IR $iR, UPR $uPR): \Yiisoft\DataResponse\DataResponse|Response {
        $aliases = $this->sR->get_customer_files_folder_aliases();
        $targetPath = $aliases->get('@customer_files');
        if ($inv_id) {
            if (!is_writable($targetPath)) {
                return $this->factory->createResponse($this->attachment_not_writable($inv_id));
            }
            $invoice = $iR->repoInvLoadedquery((string)$inv_id) ?: null;
            if ($invoice instanceof Inv) {
                $client_id = $invoice->getClient()?->getClient_id();
                if (null!==$client_id) {
                    $url_key = $invoice->getUrl_key();
                    if (!empty($_FILES)) {
                        // @see https://github.com/vimeo/psalm/issues/5458

                        /** @var array $_FILES['InvAttachmentsForm'] */
                        /** @var string $_FILES['InvAttachmentsForm']['tmp_name']['attachFile'] */
                        $temporary_file = $_FILES['InvAttachmentsForm']['tmp_name']['attachFile'];
                        /** @var string $_FILES['InvAttachmentsForm']['name']['attachFile'] */
                        $original_file_name = preg_replace('/\s+/', '_', $_FILES['InvAttachmentsForm']['name']['attachFile']);
                        $target_path_with_filename = $targetPath . '/' . $url_key . '_' . $original_file_name;
                        if ($this->attachment_move_to($temporary_file, $target_path_with_filename, $client_id, $url_key, $original_file_name, $uPR)) {
                            return $this->factory->createResponse($this->attachment_successfully_created($inv_id));
                        } else {
                            return $this->factory->createResponse($this->attachment_no_file_uploaded($inv_id));
                        }
                    } else {
                        return $this->factory->createResponse($this->attachment_no_file_uploaded($inv_id));
                    }
                } // $client_id
            } // $invoice
            return $this->web_service->getRedirectResponse('inv/index');
        } //null!==$inv_id
        return $this->web_service->getRedirectResponse('inv/index');
    }
        
    /**
     * Used to display values in the view function
     * @param Inv $inv
     * @return array
     */
    private function body(Inv $inv): array {
      $body = [
        'number' => $inv->getNumber(),
        'id' => $inv->getId(),
        //relations
        'user_id' => $inv->getUser()?->getId(),
        'group_id' => $inv->getGroup()?->getId(),
        'client_id' => $inv->getClient()?->getClient_id(),
        //nullable
        'quote_id' => $inv->getQuote_id(),
        'so_id' => $inv->getSo_id(),
        'contract_id' => $inv->getContract_id(),
        'date_created' => $inv->getDate_created(),
        'date_supplied' => $inv->getDate_supplied(),
        'date_tax_point' => $inv->getDate_tax_point(),
        'date_modified' => $inv->getDate_modified(),
        'date_due' => $inv->getDate_due(),
        // where to deliver
        'delivery_location_id' => $inv->getDelivery_location_id(),
        // has been delivered to delivery location
        'delivery_id' => $inv->getDelivery_id(),
        'status_id' => $inv->getStatus_id(),
        'is_read_only' => $inv->getIs_read_only(),
        'creditinvoice_parent_id' => $inv->getCreditinvoice_parent_id(),
        'discount_amount' => $inv->getDiscount_amount(),
        'discount_percent' => $inv->getDiscount_percent(),
        'url_key' => $inv->getUrl_key(),
        'password' => $inv->getPassword(),
        'payment_method' => $inv->getPayment_method(),
        'terms' => $inv->getTerms(),
        'note' => $inv->getNote(),
        'document_description' => $inv->getDocumentDescription(),
      ];
      return $body;
    }

   
    public function add(Request $request,
                        #[RouteArgument('origin')] string $origin,
                        FormHydrator $formHydrator,
                        CR $clientRepository,
                        GR $gR,
                        SumexR $sumexR,
                        TRR $trR,
                        UR $uR,
                        UCR $ucR,  
                        UIR $uiR
    ) : \Yiisoft\DataResponse\DataResponse|Response
    {
        $inv = new Inv();
        $errors = [];
        $form = new InvForm($inv);
        $bootstrap5ModalInv = new Bootstrap5ModalInv(
                                $this->translator, 
                                $this->view_renderer, 
                                $clientRepository, 
                                $gR, 
                                $this->sR,
                                $ucR,
                                $form
                                );
                           
        $layoutWithForm = $bootstrap5ModalInv->renderPartialLayoutWithFormAsString($origin, $errors);
        $layoutParameters = [];
        $parametersNonModalForm = [];
        // do not use a modal from main menu selection and dashboard selection
        if (($origin == 'main') || ($origin == 'dashboard')) {
            $parametersNonModalForm = [
                'form' => $bootstrap5ModalInv->getFormParameters(),
                'return_url_action' => 'add'      
            ];
        }
        // use a modal from the invoice view
        if ($origin == 'inv') {
            $layoutParameters = [
                // use type to id the quote\modal_layout.php eg.  ->options(['id' => 'modal-add-'.$type,
                'type' => 'inv',
                'form' => $layoutWithForm,
                'return_url_action' => 'add'
            ];
        }
        // otherwise it will be a client number
        // use a modal from the client view
        if (($origin <> 'main') && ($origin <> 'inv') && ($origin <> 'dashboard')) {
            $layoutParameters = [
                'type' => 'client',
                'form' => $layoutWithForm,
                'return_url_action' => 'add'
            ];
        }
        // An invoice can originate and be added from the following pages:
        // 1. Main Menu e.g /invoice
        // 2. Client Menu e.g. /invoice/client/view/25
        // 3. Invoice Menu e.g. /invoice/inv
        // 4. Dashboard e.g. /invoice/dashboard
        // Use the RouteArgument's origin argument to return to correct origin
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            /**
             * @psalm-suppress PossiblyInvalidArgument $body 
             */
            if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                // Only clients that were assigned to user accounts were made available in dropdown 
                // therefore use the 'user client' user id
                /**
                 * @var string $body['client_id']
                 */
                $client_id = $body['client_id'];
                $user_client = $ucR->repoUserquery($client_id);
                if (null!==$user_client && null!==$user_client->getClient()) {
                  $client_first_name = $user_client->getClient()?->getClient_name();
                  $client_surname = $user_client->getClient()?->getClient_surname();
                  $client_fullname = (null!==($client_first_name) 
                                   ? $client_first_name 
                                   : '').
                                   ' '. 
                                   (null!==($client_surname) ? $client_surname : '');
                } else {
                    $this->flash_message('danger',  ($clientRepository->repoClientquery($client_id))->getClient_full_name(). ': '. $this->translator->translate('invoice.invoice.user.client.no.account'));
                }
                // Ensure that the client has only one (paying) user account otherwise reject this invoice
                // @see UserClientRepository function get_not_assigned_to_user which ensures that only
                // clients that have   NOT   been assigned to a user account are presented in the dropdown box for available clients
                // So this line is an extra measure to ensure that the invoice is being made out to the correct payer
                // ie. not more than one user is associated with the client.
                $user = $this->active_user($client_id, $uR, $ucR, $uiR);
                if (null!==$user) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body 
                     */
                    $saved_model = $this->inv_service->saveInv($user, $inv, $body, $this->sR, $gR);
                    /**
                     * The InvAmount entity is created automatically during the above saveInv
                     * @see src\Invoice\Entity\Inv ... New InvAmount();
                     */
                    $model_id = $saved_model->getId();
                    if (null!==$model_id) {
                      $this->default_taxes($inv, $trR, $formHydrator);
                      // if Settings...Views...Invoices...Sumex...Yes => Generate sumex patient details extension table
                      // This table can be filled in via Invoice...View...Options...Edit...Sumex
                      $this->sumex_add_record($sumexR, (int) $model_id);
                      // Inform the user of generated invoice number for draft setting
                      $this->flash_message('info', $this->sR->get_setting('generate_invoice_number_for_draft') === '1' 
                      ? $this->translator->translate('i.generate_invoice_number_for_draft') . '=>' . $this->translator->translate('i.yes') 
                      : $this->translator->translate('i.generate_invoice_number_for_draft') . '=>' . $this->translator->translate('i.no') );
                      $this->sR->get_setting('mark_invoices_sent_copy') === '1'   
                      ? $this->flash_message('danger', $this->translator->translate('invoice.mark.sent.copy.on')) 
                      : '';
                    } //$model_id
                    $this->flash_message('success', $this->translator->translate('i.record_successfully_created'));
                    if (($origin == 'main') || ($origin == 'inv')) {
                        return $this->web_service->getRedirectResponse('inv/index');
                    }
                    if ($origin == 'dashboard') {
                        return $this->web_service->getRedirectResponse('invoice/dashboard');
                    }
                    // otherwise return to client
                    return $this->web_service->getRedirectResponse('client/view', ['id' => $origin]);
                }    
            }
            $errors = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
        } // POST
        // show the form without a modal when using the main menu
        if (($origin == 'main') || ($origin == 'dashboard')) {
            // update the errors array with latest errors
            $bootstrap5ModalInv->renderPartialLayoutWithFormAsString($origin, $errors);
            // do not use the layout just get the formParameters
            $parameters = $bootstrap5ModalInv->getFormParameters();
            /**
             * @psalm-suppress MixedArgumentTypeCoercion $parameters
             */
            return $this->view_renderer->render('/invoice/inv/modal_add_inv_form', $parameters);
        }
        // show the form inside a modal when engaging with a view
        if ($origin == 'inv') {
            return $this->view_renderer->render('/invoice/inv/modal_layout', [
                // use type to id the inv\modal_layout.php eg.  ->options(['id' => 'modal-add-'.$type,
                'type' => 'inv',
                'form' => $bootstrap5ModalInv->renderPartialLayoutWithFormAsString($origin, $errors),
                'return_url_action' => 'add'
            ]);
        }
        // Otherwise return to client
        if (($origin <> 'main') && ($origin <> 'inv') && ($origin <> 'dashboard')) {
            return $this->view_renderer->render('/invoice/inv/modal_layout', [
                'type' => 'client',
                'form' => $bootstrap5ModalInv->renderPartialLayoutWithFormAsString($origin, $errors),
                'return_url_action' => 'add'
            ]);
        }
        return $this->web_service->getNotFoundResponse();
    }    
    
    // Reverse an invoice with a credit invoice /debtor/client/customer credit note
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CR $clientRepository
     * @param GR $gR
     * @param SumexR $sumexR
     * @param TRR $trR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return Response
     */
    public function credit(Request $request, 
        FormHydrator $formHydrator,
        CR $clientRepository,
        GR $gR,
        SumexR $sumexR,
        TRR $trR,
        UR $uR,
        UCR $ucR,  
        UIR $uiR
    ) : Response
    {
        $inv = new Inv();
        $form = new InvForm($inv);
        $invAmount = new InvAmount();
        $defaultGroupId = $this->sR->get_setting('default_invoice_group');
        $optionsGroupData = [];
        $groups = $gR->findAllPreloaded();
        /**
         * @var \App\Invoice\Entity\Group
         */
        foreach ($groups as $group) {
            $optionsGroupData[$group->getId()] = $group->getName();
        }
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'action' => ['inv/credit', ['']],
            'errors' => [],
            'form' => $form,
            'clients' => $clientRepository->optionsData($ucR),
            'groups' => $optionsGroupData,
            'defaultGroupId' => $defaultGroupId,
            'urlKey' => Random::string(32)
        ];
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            /**
             * @psalm-suppress PossiblyInvalidArgument $body 
             */
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                // Only clients that were assigned to user accounts were made available in dropdown 
                // therefore use the 'user client' user id
                /**
                 * @var string $body['client_id']
                 */
                $client_id = $body['client_id'];
                $user_client = $ucR->repoUserquery($client_id);
                if (null!==$user_client && null!==$user_client->getClient()) {
                  $client_first_name = $user_client->getClient()?->getClient_name();
                  $client_surname = $user_client->getClient()?->getClient_surname();
                  $client_fullname = (null!==($client_first_name) 
                                   ? $client_first_name 
                                   : '').
                                   ' '. 
                                   (null!==($client_surname) ? $client_surname : '');
                } else {
                    $this->flash_message('warning', $this->translator->translate('invoice.invoice.user.client.no.account'));
                }
                // Ensure that the client has only one (paying) user account otherwise reject this invoice
                // @see UserClientRepository function get_not_assigned_to_user which ensures that only
                // clients that have   NOT   been assigned to a user account are presented in the dropdown box for available clients
                // So this line is an extra measure to ensure that the invoice is being made out to the correct payer
                // ie. not more than one user is associated with the client.
                $user = $this->active_user($client_id, $uR, $ucR, $uiR);
                if (null!==$user) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body 
                     */
                    $saved_model = $this->inv_service->saveInv($user, $inv, $body, $this->sR, $gR);
                    $model_id = $saved_model->getId();
                    if (null!==$model_id) {
                      $this->inv_amount_service->initializeInvAmount($invAmount, $model_id);
                      $this->default_taxes($inv, $trR, $formHydrator);
                      // if Settings...Views...Invoices...Sumex...Yes => Generate sumex patient details extension table
                      // This table can be filled in via Invoice...View...Options...Edit...Sumex
                      $this->sumex_add_record($sumexR, (int) $model_id);
                      // Inform the user of generated invoice number for draft setting
                      $this->flash_message('info', $this->sR->get_setting('generate_invoice_number_for_draft') === '1' 
                      ? $this->translator->translate('i.generate_invoice_number_for_draft') . '=>' . $this->translator->translate('i.yes') 
                      : $this->translator->translate('i.generate_invoice_number_for_draft') . '=>' . $this->translator->translate('i.no') );
                    } //$model_id
                    return $this->web_service->getRedirectResponse('inv/index');
                  } //null!==$user
                  // In the event of the database being manually edited (highly unlikely) present this warning anyway
                  $message = '';
                  if (!empty($client_fullname)) {
                    $message = $this->translator->translate('invoice.user.inv.more.than.one.assigned').' '.(string)$client_fullname;
                    $this->flash_message('warning', $message);
                  }
                  return $this->web_service->getRedirectResponse('inv/index');
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
            $parameters['form'] = $form;
        }
        return $this->view_renderer->render('/invoice/inv/_form_create_confirm', $parameters);
    }
    
    /**
     * @see src/Invoice/Asset/rebuild1.13/js/inv.js function $(document).on('click', '#create-credit-confirm', function () 
     * @see resources/views/invoice/inv/modal_create_credit
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param IR $iR
     * @param GR $gR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param UCR $ucR
     * @param UIR $uiR
     * @param UR $uR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function create_credit_confirm(Request $request, FormHydrator $formHydrator, IR $iR, GR $gR, IIR $iiR, IIAR $iiaR, UCR $ucR, UIR $uiR, UR $uR): \Yiisoft\DataResponse\DataResponse {
      $body = $request->getQueryParams();
      $basis_inv = $iR->repoInvLoadedquery((string) $body['inv_id']);     
      if (null!== $basis_inv) {
        $basis_inv_id = (string) $body['inv_id'];
        // Set the basis_inv to read-only;
        $basis_inv->setIs_read_only(true);
        $ajax_body = [
            'client_id' => $body['client_id'],
            'group_id' => $body['group_id'],
            'user_id' => $body['user_id'],
            'creditinvoice_parent_id' => (string) $body['inv_id'],
            'status_id' => $basis_inv->getStatus_id(),
            'is_read_only' => false,
            'number' => $gR->generate_number((int) $body['group_id'], true),
            'discount_amount' => null,
            'discount_percent' => null,
            'url_key' => '',
            'password' => $body['password'],
            'payment_method' => 0,
            'terms' => '',
            'delivery_location_id' => $basis_inv->getDelivery_location_id()
        ];
        // Save the basis invoice
        $iR->save($basis_inv);
        $new_inv = new Inv();
        $form = new InvForm($new_inv);
        if ($formHydrator->populate($form, $ajax_body) && $form->isValid()) {
          /**
           * @var string $ajax_body['client_id']
           */
          $client_id = $ajax_body['client_id'];
          $user = $this->active_user($client_id, $uR, $ucR, $uiR);
          if (null!==$user) {  
              $saved_inv = $this->inv_service->saveInv($user, $new_inv, $ajax_body, $this->sR, $gR);
              $saved_inv_id = $saved_inv->getId();
              if (null!==$saved_inv_id) {
                $this->inv_item_service->initializeCreditInvItems((int) $basis_inv_id, $saved_inv_id , $iiR, $iiaR, $this->sR);
                $this->inv_amount_service->initializeCreditInvAmount(new InvAmount(), (int) $basis_inv_id, $saved_inv_id);
                $this->inv_tax_rate_service->initializeCreditInvTaxRate((int) $basis_inv_id, $saved_inv_id);
                $parameters = [
                    'success' => 1,
                    'flash_message' => $this->translator->translate('invoice.invoice.credit.note.creation.successful')
                ];
                //return response to inv.js to reload page at location
                return $this->factory->createResponse(Json::encode($parameters));
              } //null!== $saved_inv
          } //null!==$user
        } // ajax
      } //null!==$basis_inv
      return $this->factory->createResponse(Json::encode([
          'success' => 0, 
          'message' => $this->translator->translate('invoice.invoice.credit.note.creation.unsuccessful')
      ]));
    } 
    /**
     *
     * @param Inv $inv
     * @param TRR $trR
     * @param FormHydrator $formHydrator
     * @return void
     */
    public function default_taxes(Inv $inv, TRR $trR, FormHydrator $formHydrator): void {
        if ($trR->repoCountAll() > 0) {
            $taxrates = $trR->findAllPreloaded();
            /** @var TaxRate $taxrate */
            foreach ($taxrates as $taxrate) {
                $taxrate->getTax_rate_default() == 1 ? $this->default_tax_inv($taxrate, $inv, $formHydrator) : '';
            }
        }
    }

    /**
     *
     * @param TaxRate $taxrate
     * @param Inv $inv
     * @param FormHydrator $formHydrator
     * @return void
     */
    public function default_tax_inv(TaxRate $taxrate, Inv $inv, FormHydrator $formHydrator): void {
        $invTaxRate = new InvTaxRate();
        $invTaxRateForm = new InvTaxRateForm($invTaxRate);
        $inv_tax_rate = [];
        $inv_tax_rate['inv_id'] = $inv->getId();
        $inv_tax_rate['tax_rate_id'] = $taxrate->getTax_rate_id();
        $inv_tax_rate['include_item_tax'] = 0;
        $inv_tax_rate['inv_tax_rate_amount'] = 0;
        ($formHydrator->populate($invTaxRateForm, $inv_tax_rate) && $invTaxRateForm->isValid()) ?
                        $this->inv_tax_rate_service->saveInvTaxRate(new InvTaxRate(), $inv_tax_rate) : '';
    }

    /**
     * @param int $id
     * @param InvRepository $invRepo
     * @param ICR $icR
     * @param InvCustomService $icS
     * @param IIR $iiR
     * @param InvItemService $iiS
     * @param ITRR $itrR
     * @param InvTaxRateService $itrS
     * @param IAR $iaR
     * @param InvAmountService $iaS
     * @param DLR $dlR
     * @param DLS $dlS
     * @return Response
     */
    public function delete(#[RouteArgument('id')] int $id, InvRepository $invRepo,
            ICR $icR, InvCustomService $icS, IIR $iiR, InvItemService $iiS, ITRR $itrR,
            InvTaxRateService $itrS, IAR $iaR, InvAmountService $iaS, DLR $dlR, DLS $dlS): Response {
        try {
            $inv = $this->inv($id, $invRepo);
            if ($inv) {
                $this->inv_service->deleteInv($inv, $icR, $icS, $iiR, $iiS, $itrR, $itrS, $iaR, $iaS, $dlR, $dlS);
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                return $this->web_service->getRedirectResponse('inv/index');
            }
            return $this->web_service->getRedirectResponse('inv/index');
        } catch (\Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            unset($e);
            return $this->web_service->getRedirectResponse('inv/index');
        }
    }

    /**
     * @param int $id
     * @param IIR $iiR
     */
    public function delete_inv_item(#[RouteArgument('id')] int $id, IIR $iiR, ACIIR $aciiR, IIAR $iiaR): \Yiisoft\DataResponse\DataResponse|Response {
        try {
            $invItem = $this->inv_item($id, $iiR);
            if ($invItem) {
                // Do not allow the item to be deleted if the invoice
                // status is sent ie. 2
                if ($invItem->getInv()?->getStatus_id() !== 2) {
                    $aciis = $aciiR->repoInvItemquery((string) $invItem->getId());
                    /** @var InvItemAllowanceCharge $acii */
                    foreach ($aciis as $acii) {
                        $this->aciis->deleteInvItemAllowanceCharge($acii, $iiaR);
                    }
                    $this->inv_item_service->deleteInvItem($invItem);
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
                    return $this->web_service->getRedirectResponse('inv/view',['id'=> $invItem->getInv_id()]);
                } else {
                    $this->flash_message('warning', $this->translator->translate('invoice.invoice.delete.sent'));
                    return $this->web_service->getRedirectResponse('inv/view',['id'=> $invItem->getInv_id()]);
                }
            }
        } catch (\Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            unset($e);
        }
        $inv_id = (string) $this->session->get('inv_id');
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                                ['heading' => $this->translator->translate('i.invoice_items'), 'message' => $this->translator->translate('i.record_successfully_deleted'), 'url' => 'inv/view', 'id' => $inv_id]));
    }

    /**
     * @param int $id
     * @param ITRR $invtaxrateRepository
     */
    public function delete_inv_tax_rate(#[RouteArgument('id')] int $id, ITRR $invtaxrateRepository): \Yiisoft\DataResponse\DataResponse|Response {
        try {
            $inv_tax_rate = $this->invtaxrate($id, $invtaxrateRepository);
            $this->inv_tax_rate_service->deleteInvTaxRate($inv_tax_rate);
        } catch (\Exception $e) {
            $this->flash_message('danger', $e->getMessage());
            unset($e);
        }
        $inv_id = (string) $this->session->get('inv_id');
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                                ['heading' => $this->translator->translate('i.invoice_tax_rate'), 'message' => $this->translator->translate('i.record_successfully_deleted'), 'url' => 'inv/view', 'id' => $inv_id]));
    }
    
    private function disable_read_only_status_message() : void
    {
        if ($this->sR->get_setting('disable_read_only') == '') {
            $this->flash_message('warning', $this->translator->translate('invoice.security.disable.read.only.empty'));
        }
        if ($this->sR->get_setting('disable_read_only') == '1') {
            $this->flash_message('warning', $this->translator->translate('invoice.security.disable.read.only.warning'));
        }
    }        

    /**
     * Use: Download an attached, and currently uploaded file 
     * @param int $upload_id 
     * @param UPR $upR
     *
     * @return never
     */
    public function download_file(#[RouteArgument('upload_id')] int $upload_id, UPR $upR) {
        if ($upload_id) {
            $upload = $upR->repoUploadquery((string)$upload_id);
            if (null !== $upload) {
                $aliases = $this->sR->get_customer_files_folder_aliases();
                $targetPath = $aliases->get('@customer_files');
                $original_file_name = $upload->getFile_name_original();
                $url_key = $upload->getUrl_key();
                $target_path_with_filename = $targetPath . '/' . $url_key . '_' . $original_file_name;
                $path_parts = pathinfo($target_path_with_filename);
                $file_ext = $path_parts['extension'] ?? '';
                if (file_exists($target_path_with_filename)) {
                    $file_size = filesize($target_path_with_filename);
                    $allowed_content_type_array = $upR->getContentTypes();
                    // Check extension against allowed content file types @see UploadRepository getContentTypes
                    $save_ctype = isset($allowed_content_type_array[$file_ext]);
                    /** @var string $ctype */
                    $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] : $upR->getContentTypeDefaultOctetStream();
                    // https://www.php.net/manual/en/function.header.php
                    // Remember that header() must be called before any actual output is sent, either by normal HTML tags,
                    // blank lines in a file, or from PHP.
                    header("Expires: -1");
                    header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
                    header("Content-Disposition: attachment; filename=\"$original_file_name\"");
                    header("Content-Type: " . $ctype);
                    header("Content-Length: " . $file_size);
                    echo file_get_contents($target_path_with_filename, true);
                    exit;
                } //if file_exists
                exit;
            } //null!==upload
            exit;
        } //null!==$upload_id
        exit;
    }

    /**
     * @param string $invoice
     * @return void
     */
    public function download(#[RouteArgument('invoice')] string $invoice): void {
        $aliases = $this->sR->get_invoice_archived_folder_aliases();
        if ($invoice) {
            header('Content-type: application/pdf');
            header('Content-Disposition: attachment; filename="' . urldecode($invoice) . '"');
            readfile($aliases->get('@archive_invoice') . DIRECTORY_SEPARATOR . urldecode($invoice));
        }
    }
    
    private function editInputAttributesUrlKey(InvForm $form) : array
    {
        $inputAttributesUrlKey = [
                'class' => 'form-control',
                'readonly' => 'readonly',
                'value' => Html::encode($form->getUrl_key())
        ];
        // do not display the url key if it is a draft invoice otherwise display the url key
        if ($form->getStatus_id() == 1) {
            $inputAttributesUrlKey['hidden'] = 'hidden';            
        } else {
            $inputAttributesUrlKey['placeholder'] = $this->translator->translate('i.url_key');
        }           
        return $inputAttributesUrlKey;
    }   
    
    /**
     * @param InvForm $form
     * @return array
     */
    private function editInputAttributesPaymentMethod(InvForm $form) : array
    {
        $inputAttributesPaymentMethod = [];
        if ($form->getIsReadOnly() == true && $form->getStatus_id() == 4) {
            $inputAttributesPaymentMethod = [
                'class' => 'form-control',
                'disabled' => 'disabled'
            ];
        }
        else {
            $inputAttributesPaymentMethod = [
                'class' => 'form-control',
                'value' => Html::encode($form->getPayment_method() ?? ($this->sR->get_setting('invoice_default_payment_method') ?: 1))
            ];
        }
        return $inputAttributesPaymentMethod;
    }        
    
    /**
     * @param PeppolArrays $peppol_array
     * @param Inv $inv
     * @param int $client_id
     * @param CR $clientRepo
     * @param ContractRepo $contractRepo
     * @param DelRepo $deliveryRepo
     * @param DLR $delRepo
     * @param GR $groupRepo
     * @param IR $invRepo
     * @param paR $paR
     * @param PMR $pmRepo
     * @param UCR $ucR
     * @return array
     */
    private function editOptionsData(
        PeppolArrays $peppol_array, 
        Inv $inv, 
        int $client_id,
        CR $clientRepo,    
        ContractRepo $contractRepo, 
        DelRepo $deliveryRepo, 
        DLR $delRepo,
        GR $groupRepo,
        IR $invRepo,    
        paR $paR,
        PMR $pmRepo,
        UCR $ucR) : array
    {
        $contracts = $contractRepo->repoClient($inv->getClient_id());
        $optionsDataContract = [];
        /**
         * @var Contract $contract
         */
        foreach ($contracts as $contract) {
            $id = $contract->getId();
            if (null!==$id) {
                $optionsDataContract[$id] = ($contract->getName() ?? '') . " " . ($contract->getReference() ?? '');
            }
        }
        $deliverys = $deliveryRepo->findAllPreloaded();    
        $optionsDataDelivery = [];
        /**
         * @var Delivery $delivery
         */
        foreach ($deliverys as $delivery)
        {
            $delivery_id = $delivery->getId();
            /**
             * @var \DateTimeImmutable $startDate
             */
            $startDate = $delivery->getStart_date();
            /**
             * @var \DateTimeImmutable $endDate
             */
            $endDate = $delivery->getEnd_date();
            if (null!=$delivery_id) {
                $optionsDataDelivery[$delivery_id] = 
                $startDate->format($this->date_helper->style()) 
                . ' ----- ' 
                . $endDate->format($this->date_helper->style()) 
                . ' ---- ' 
                . $this->sR->get_setting('stand_in_code') 
                . ' ---- ' 
                .  $peppol_array->getCurrent_stand_in_code_value($this->sR);
            }    
        }
        
        $dLocs = $delRepo->repoClientquery((string)$client_id);
        $optionsDataDeliveryLocations = [];
        /**
         * @var DeliveryLocation $dLoc
         */
        foreach ($dLocs as $dLoc) {
            $dLocId = $dLoc->getId();
            if (null!==$dLocId) {
                $optionsDataDeliveryLocations[$dLocId] = ($dLoc->getAddress_1() ?? '') . ', ' . ($dLoc->getAddress_2() ?? '') . ', ' . ($dLoc->getCity() ?? '') . ', ' . ($dLoc->getZip() ?? '');
            }
        }
        $optionsDataGroup = [];
        /**
         * @var Group $group
         */
        foreach ($groupRepo->findAllPreloaded() as $group) {
            $optionsDataGroup[$group->getId()] = $group->getName();
        }
        
        $optionsDataPaymentMethod = [];
        /**
         * @var PaymentMethod $paymentMethod
         */
        foreach ($pmRepo->findAllPreloaded() as $paymentMethod) {
            $optionsDataPaymentMethod[$paymentMethod->getId()] = $paymentMethod->getName();
        }
        $optionsDataPaymentTerm = [];
        /**
         * @var string $key
         * @var string $value
         */
        foreach ($this->sR->get_payment_term_array($this->translator) as $key => $value) {
            $optionsDataPaymentTerm[$key] = $value;
        }
        $optionsDataPostalAddress = [];
        /**
         * @var PostalAddress $postalAddress
         */
        foreach ($paR->repoClientAll((string)$client_id) as $postalAddress) {
            $optionsDataPostalAddress[$postalAddress->getId()] = $postalAddress->getStreet_name() . ', ' . $postalAddress->getAdditional_street_name() . ', ' . $postalAddress->getBuilding_number() . ', ' . $postalAddress->getCity_name();
        }
        
        $optionsDataInvoiceStatus = [];
        /**
         * @var string $key
         * @var array $status
         */
        foreach ($invRepo->getStatuses($this->translator) as $key => $status) {
            $optionsDataInvoiceStatus[$key] =  (string)$status['label'];
        }
        return $optionsData = [
            'client' => $clientRepo->optionsData($ucR),
            'contract' => $optionsDataContract,
            'delivery' => $optionsDataDelivery,
            'deliveryLocation' => $optionsDataDeliveryLocations,
            'group' => $optionsDataGroup,
            'invoiceStatus' => $optionsDataInvoiceStatus,
            'paymentMethod' => $optionsDataPaymentMethod,
            'paymentTerm' => $optionsDataPaymentTerm,
            'postalAddress' => $optionsDataPostalAddress
        ];
    }

    /**
     * @param Request $request
     * @param int $id 
     * @param FormHydrator $formHydrator
     * @param IR $invRepo
     * @param CR $clientRepo
     * @param ContractRepo $contractRepo
     * @param DelRepo $deliveryRepo
     * @param DLR $delRepo
     * @param GR $groupRepo
     * @param PMR $pmRepo
     * @param UR $userRepo
     * @param IAR $iaR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param ICR $icR
     * @param paR $paR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function edit(
            Request $request, 
            #[RouteArgument('id')] int $id,
            FormHydrator $formHydrator,
            IR $invRepo,
            CR $clientRepo,
            ContractRepo $contractRepo,
            DelRepo $deliveryRepo,
            DLR $delRepo,
            GR $groupRepo,
            PMR $pmRepo,
            UR $userRepo,
            IAR $iaR,
            CFR $cfR,
            CVR $cvR,
            ICR $icR,
            paR $paR,
            UCR $ucR,
            UIR $uiR
    ): \Yiisoft\DataResponse\DataResponse|Response {

        $inv = $this->inv($id, $invRepo, true);
        if ($inv) {
            $form = new InvForm($inv);
            $invCustom = new InvCustom();
            $invCustomForm = new InvCustomForm($invCustom);
            $inv_id = $inv->getId();
            $client_id = $inv->getClient_id();
            $peppol_array = new PeppolArrays();
            $note_on_tax_point = '';
            $defaultGroupId = $this->sR->get_setting('default_invoice_group');
            if (($this->sR->get_setting('debug_mode') == '1') && ($this->user_service->hasPermission('editInv'))) {
                $note_on_tax_point = $this->view_renderer->renderPartialAsString('/invoice/info/taxpoint');
            }
            $parameters = [
                'action' => ['inv/edit', ['id' => $inv_id]],
                'note_on_tax_point' => $note_on_tax_point ?: '',
                'errors' => [],
                'form' => $form,
                'optionsData' => $this->editOptionsData($peppol_array, $inv, (int)$client_id, $clientRepo, 
                                                        $contractRepo, $deliveryRepo, $delRepo, 
                                                        $groupRepo, $invRepo, $paR, $pmRepo, $ucR),
                'editInputAttributesPaymentMethod' => $this->editInputAttributesPaymentMethod($form),
                'editInputAttributesUrlKey' => $this->editInputAttributesUrlKey($form),
                'defaultGroupId' => $defaultGroupId,
                'users' => $userRepo->findAllUsers(),
                'numberhelper' => $this->number_helper,
                'invs' => $invRepo->findAllPreloaded(),
                'cvH' => new CVH($this->sR),
                'custom_fields' => $cfR->repoTablequery('inv_custom'),
                // Applicable to normally building up permanent selection lists eg. dropdowns
                'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('inv_custom')),
                // There will initially be no custom_values attached to this invoice until they are filled in the field on the form
                'inv_custom_values' => $this->inv_custom_values($inv_id, $icR),
                'invCustomForm' => $invCustomForm,
                'inv' => $inv,
                // del or delivery location
                'del_count' => $delRepo->repoClientCount($inv->getClient_id()),
                'delivery_count' => (null !== $inv_id ? $deliveryRepo->repoCountInvoice($inv_id) : 0),
                'contract_count' => $contractRepo->repoClientCount($inv->getClient_id()),
                'postal_address_count' => $paR->repoClientCount($inv->getClient_id())
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                /**
                 * PossiblyInvalidArgument $body
                 */
                if (is_array($body)) {
                    // If the status has changed to 'paid', check that the balance on the invoice is zero
                    if (!$this->edit_check_status_reconciling_with_balance($iaR, (int) $inv_id) && $body['status_id'] === 4) {
                        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                        [
                            'heading' => $this->translator->translate('i.errors'), 
                            'message' => $this->translator->translate('i.error') . $this->translator->translate('invoice.balance.does.not.equal.zero'),
                                                    'url' => 'inv/view', 'id' => $inv_id]));
                    }
                    $returned_form = $this->edit_save_form_fields($body, $id, $formHydrator, $invRepo, $groupRepo, $userRepo, $ucR, $uiR);
                    $parameters['form'] = $returned_form;
                    if ($returned_form instanceof InvForm) {
                        if (!$returned_form->isValid()) {
                            $parameters['form'] = $returned_form;
                            $parameters['errors'] = $returned_form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                            return $this->view_renderer->render('/invoice/inv/_form_edit', $parameters);
                        }
                        $this->edit_save_custom_fields($body, $formHydrator, $icR, $inv_id);
                        $this->flash_message('success', $this->translator->translate('i.record_successfully_updated'));
                        return $this->web_service->getRedirectResponse('inv/view', ['id' => $inv_id]);
                    }
                } //$body
                return $this->web_service->getRedirectResponse('inv/index');
            }
            return $this->view_renderer->render('/invoice/inv/_form_edit', $parameters);
        } // if $inv_id
        return $this->web_service->getRedirectResponse('inv/index');
    }

    /**
     *
     * @param IAR $iaR
     * @param int $inv_id
     * @return bool
     */
    public function edit_check_status_reconciling_with_balance(IAR $iaR, int $inv_id): bool {
        $invoice_amount = $iaR->repoInvquery($inv_id);
        if (null !== $invoice_amount) {
            // If the invoice is fully paid up allow the status to change to 'paid'
            if ($invoice_amount->getBalance() == 0.00) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * @param array|object|null $body
     * @param int $id
     * @param FormHydrator $formHydrator
     * @param IR $invRepo
     * @param GR $groupRepo
     * @param IAR $iaR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return InvForm|null
     */
    public function edit_save_form_fields(array|object|null $body, int $id, FormHydrator $formHydrator, IR $invRepo, GR $groupRepo, UR $uR, UCR $ucR, UIR $uiR): InvForm|null {
        $inv = $this->inv($id, $invRepo, true);
        if ($inv) {
            $client_id = $inv->getClient_id();
            $user = $this->active_user($client_id, $uR, $ucR, $uiR);
            if (null!==$user) {
                $form = new InvForm($inv);
                if (null!==$body && is_array($body)) {
                    if ($formHydrator->populate($form, $body) && $form->isValid()) {
                      $this->inv_service->saveInv($user, $inv, $body, $this->sR, $groupRepo);
                    } 
                }
                return $form;          
            } // null !== $user
        }  // $inv  
        return null;
    }

    /**
     * @param array|object|null $parse
     * @param FormHydrator $formHydrator
     * @param ICR $icR
     * @param string|null $inv_id
     * @return void
     */
    public function edit_save_custom_fields(array|object|null $parse, FormHydrator $formHydrator, ICR $icR, string|null $inv_id): void {
        /**
         * @var array $parse['custom']
         */
        $custom = $parse['custom'] ?? [];
        /** @var string|array $value */
        foreach ($custom as $custom_field_id => $value) {
            if (($icR->repoInvCustomCount((string) $inv_id, (string) $custom_field_id)) == 0) {
                $inv_custom = new InvCustom();
                $inv_custom_input = [
                    'inv_id' => (int) $inv_id,
                    'custom_field_id' => (int) $custom_field_id,
                    'value' => is_array($value) ? serialize($value) : $value 
                ];
                $form = new InvCustomForm($inv_custom);
                if ($formHydrator->populate($form, $inv_custom_input) && $form->isValid()) {
                    $this->inv_custom_service->saveInvCustom($inv_custom, $inv_custom_input);
                }
            } else {
                $inv_custom = $icR->repoFormValuequery((string) $inv_id, (string) $custom_field_id);
                if ($inv_custom) {
                    $inv_custom_input = [
                        'inv_id' => (int) $inv_id,
                        'custom_field_id' => (int) $custom_field_id,
                        'value' => is_array($value) ? serialize($value) : $value 
                    ];
                    $form = new InvCustomForm($inv_custom);
                    if ($formHydrator->populate($form, $inv_custom_input) && $form->isValid()) {
                        $this->inv_custom_service->saveInvCustom($inv_custom, $inv_custom_input);
                    }
                } // inv_custom
            } // count
        } // custom
    }

    /**
     * @param string $type
     * @return array
     */
    public function email_get_invoice_templates(string $type = 'pdf'): array {
        return $this->sR->get_invoice_templates($type);
    }

    /**
     * @param ViewRenderer $head
     * @param int $id
     * @param CCR $ccR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param ETR $etR
     * @param ICR $icR
     * @param IR $iR
     * @param PCR $pcR
     * @param SOCR $socR
     * @param QCR $qcR
     * @param UIR $uiR
     * @return Response
     */
    public function email_stage_0(ViewRenderer $head,
            #[RouteArgument('id')] int $id, 
            CCR $ccR, CFR $cfR, CVR $cvR,
            ETR $etR,
            ICR $icR, IR $iR,
            PCR $pcR, SOCR $socR, QCR $qcR, UIR $uiR): Response {
        $mailer_helper = new MailerHelper($this->sR, $this->session, $this->translator, $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        if (!$mailer_helper->mailer_configured()) {
            $this->flash_message('warning', $this->translator->translate('i.email_not_configured'));
            return $this->web_service->getRedirectResponse('inv/index');
        }
        $inv = $this->inv($id, $iR, true);
        if ($inv instanceof Inv) {
            $inv_id = $inv->getId();
            $invoice = $iR->repoInvUnLoadedquery((string) $inv_id);
            if ($invoice instanceof Inv) {
                // Get all custom fields
                $custom_fields = [];
                $custom_tables = [
                    'client_custom' => 'client',
                    'inv_custom' => 'invoice',
                    'payment_custom' => 'payment',
                    'quote_custom' => 'quote',
                    'salesorder_custom' => 'salesorder'
                        // TODO
                        //'user_custom' => 'user',
                ];
                foreach (array_keys($custom_tables) as $table) {
                    $custom_fields[$table] = $cfR->repoTablequery($table);
                }
                if ($template_helper->select_email_invoice_template($invoice) == '') {
                    $this->flash_message('warning', $this->translator->translate('invoice.email.template.not.configured'));
                    return $this->web_service->getRedirectResponse('setting/tab_index');
                }
                $setting_status_email_template = $etR->repoEmailTemplatequery($template_helper->select_email_invoice_template($invoice)) ?: null;
                null === $setting_status_email_template ? $this->flash_message('info',
                                        $this->translator->translate('i.default_email_template') . '=>' .
                                        $this->translator->translate('i.not_set')) : '';
                empty($template_helper->select_pdf_invoice_template($invoice)) ? $this->flash_message('info',
                                        $this->translator->translate('i.default_pdf_template') . '=>' .
                                        $this->translator->translate('i.not_set')) : '';
                $parameters = [
                    'head' => $head,
                    'action' => ['inv/email_stage_2', ['id' => $inv_id]],
                    'alert' => $this->alert(),
                    // If email templates have been built under Setting...Email Template for Normal, Overdue, and Paid
                    // and Setting...View...Invoice...Invoice Templates have been linked to these built email templates
                    // then an email template should automatically appear on the mailer_invoice form by passing the
                    // status related email template to the get_inject_email_template_array function
                    'auto_template' => null !== $setting_status_email_template ? $this->get_inject_email_template_array($setting_status_email_template) : [],
                    //eg. If the invoice is overdue ie. status is 5, automatically select the 'overdue' pdf template
                    //which has 'overdue' text on it as a watermark
                    'setting_status_pdf_template' => $template_helper->select_pdf_invoice_template($invoice),
                    'email_templates' => $etR->repoEmailTemplateType('invoice'),
                    'dropdown_titles_of_email_templates' => $this->email_templates($etR),
                    'userinv' => $uiR->repoUserInvUserIdcount($invoice->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($invoice->getUser_id()) : null,
                    'invoice' => $invoice,
                    // All templates ie. overdue, paid, invoice
                    'pdf_templates' => $this->email_get_invoice_templates('pdf'),
                    'template_tags' => $this->view_renderer->renderPartialAsString('/invoice/emailtemplate/template-tags', [
                        'custom_fields' => $custom_fields,
                        'template_tags_quote' => '',
                        'template_tags_inv' => $this->view_renderer->renderPartialAsString('/invoice/emailtemplate/template-tags-inv', [
                            's' => $this->sR,
                            'custom_fields_inv_custom' => $custom_fields['inv_custom'],
                        ]),
                    ]),
                    'form' => new MailerInvForm(),
                    'custom_fields' => $custom_fields,
                ];
                return $this->view_renderer->render('/invoice/inv/mailer_invoice', $parameters);
            }// if invoice
            return $this->web_service->getRedirectResponse('inv/index');
        } // if $inv
        return $this->web_service->getRedirectResponse('inv/index');
    }

    /**
     *
     * @param EmailTemplate $email_template
     * @return array
     */
    public function get_inject_email_template_array(EmailTemplate $email_template): array {
        $email_template_array = [
            'body' => Json::htmlEncode($email_template->getEmail_template_body()),
            'subject' => $email_template->getEmail_template_subject() ?? '',
            'from_name' => $email_template->getEmail_template_from_name() ?? '',
            'from_email' => $email_template->getEmail_template_from_email() ?? '',
            'cc' => $email_template->getEmail_template_cc() ?? '',
            'bcc' => $email_template->getEmail_template_bcc() ?? '',
            'pdf_template' => null !== $email_template->getEmail_template_pdf_template() ? $email_template->getEmail_template_pdf_template() : '',
        ];
        return $email_template_array;
    }

    /**
     * @param ETR $etR
     *
     * @return (null|string)[]
     *
     * @psalm-return array<''|int, null|string>
     */
    public function email_templates(ETR $etR): array {
        $email_templates = $etR->repoEmailTemplateType('invoice');
        $data = [];
        /** @var EmailTemplate $email_template */
        foreach ($email_templates as $email_template) {
            if (null !== $email_template->getEmail_template_id()) {
                $data[] = $email_template->getEmail_template_title();
            }
        }
        return $data;
    }

    /**
     *
     * @param string|null $inv_id
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
     * @param IIAR $iiaR
     * @param IIR $iiR
     * @param IR $iR
     * @param ITRR $itrR
     * @param PCR $pcR
     * @param SOCR $socR
     * @param QR $qR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param SOR $soR
     * @param UIR $uiR
     * @param ViewRenderer $viewrenderer
     * @return bool
     */
    public function email_stage_1(string|null $inv_id,
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
            IIAR $iiaR,
            IIR $iiR,
            IR $iR,
            ITRR $itrR,
            PCR $pcR,
            SOCR $socR,
            QR $qR,
            QAR $qaR,
            QCR $qcR,
            SOR $soR,
            UIR $uiR,
            SumexR $sumexR,
            ViewRenderer $viewrenderer): bool {
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        $mailer_helper = new MailerHelper($this->sR, $this->session, $this->translator, $this->logger, $this->mailer,
                $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        if (null!==$inv_id) {
            $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ? $iaR->repoInvquery((int) $inv_id) : null);
            $inv_custom_values = $this->inv_custom_values($inv_id, $icR);
            $inv = $iR->repoCount($inv_id) > 0 ? $iR->repoInvUnLoadedquery($inv_id) : null;
            if ($inv) {
                // The Google sign under Invoices ... Pdf Settings
                // The initial recommendation for testing email sending is that this be set to off ie. 1 
                // so that a plain successful message can be output without interferance from a pdf
                $stream = ($this->sR->get_setting('pdf_stream_inv') == '1' ? true : false);
                $so = ($inv->getSo_id() ? $soR->repoSalesOrderLoadedquery($inv->getSo_id()) : null);
                // true => invoice ie. not quote
                // If $stream is false => pdfhelper->generate_inv_pdf => mpdfhelper->pdf_Create => filename returned
                $pdf_template_target_path = $this->pdf_helper->generate_inv_pdf($inv_id, $inv->getUser_id(), $stream, true, $so, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $sumexR, $viewrenderer);
                if ($pdf_template_target_path) {
                    $mail_message = $template_helper->parse_template($inv_id, true, $email_body, $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR);
                    $mail_subject = $template_helper->parse_template($inv_id, true, $subject, $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR);
                    $mail_cc = $template_helper->parse_template($inv_id, true, $cc, $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR);
                    $mail_bcc = $template_helper->parse_template($inv_id, true, $bcc, $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR);
                    // from[0] is the from_email and from[1] is the from_name
                    /**
                     * @var string $from[0]
                     * @var string $from[1]
                     */
                    $mail_from = array($template_helper->parse_template($inv_id, true, $from[0], $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR), $template_helper->parse_template($inv_id, true, $from[1], $cR, $cvR, $iR, $iaR, $qR, $qaR, $soR, $uiR));
                    //$message = (empty($mail_message) ? 'this is a message ' : $mail_message);
                    $message = $mail_message;
                    // mail_from[0] is the from_email and mail_from[1] is the from_name
                    return $mailer_helper->yii_mailer_send($mail_from[0], $mail_from[1],
                                    $to, $mail_subject, $message, $mail_cc, $mail_bcc,
                                    $attachFiles, $pdf_template_target_path, $uiR);
                } //is_string
            } //inv
            return false;
        } // inv_id
        return false;
    }

    // The views/invoice/inv/mailer_inv form is submitted

    /**
     * @param Request $request
     * @param int $inv_id
     * @param CR $cR
     * @param CCR $ccR
     * @param CFR $cfR
     * @param CVR $cvR
     * @param GR $gR
     * @param IAR $iaR
     * @param IIAR $iiaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IR $iR
     * @param ITRR $itrR
     * @param PCR $pcR
     * @param SOCR $socR
     * @param QR $qR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param SOR $soR
     * @param UIR $uiR
     * @return Response
     */
    public function email_stage_2(Request $request,
            #[RouteArgument('id')] int $inv_id,
            CR $cR, CCR $ccR, CFR $cfR, CVR $cvR,
            GR $gR,
            IAR $iaR, IIAR $iiaR, ICR $icR, IIR $iiR, IR $iR, ITRR $itrR,
            PCR $pcR, SOCR $socR, QR $qR, QAR $qaR, QCR $qcR, SOR $soR, UIR $uiR, SumexR $sumexR): Response {
        if ($inv_id) {
            $mailer_helper = new MailerHelper($this->sR, $this->session, $this->translator, $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['btn_cancel'] = 0;
                if (!$mailer_helper->mailer_configured()) {
                    $this->flash_message('warning', $this->translator->translate('i.email_not_configured'));
                    return $this->web_service->getRedirectResponse('inv/index');
                }
                /**
                 * @var string $to
                 */
                $to = $body['MailerInvForm']['to_email'] ?? '';
                if (empty($to)) {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                                            ['heading' => '', 'message' => $this->translator->translate('i.email_to_address_missing'), 'url' => 'inv/view', 'id' => $inv_id]));
                }
                /**
                 * @var array $from
                 */
                $from = [
                    $body['MailerInvForm']['from_email'] ?? '',
                    $body['MailerInvForm']['from_name'] ?? '',
                ];

                if (empty($from[0])) {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                                            ['heading' => '', 'message' => $this->translator->translate('i.email_to_address_missing'), 'url' => 'inv/view', 'id' => $inv_id]));
                }
                /** @var array $body['MailerInvForm'] */
                $subject = (string) $body['MailerInvForm']['subject'] ?: '';
                /**  @var string $email_body */
                $email_body = $body['MailerInvForm']['body'] ?? '';

                /**  @var string $cc */
                $cc = $body['MailerInvForm']['cc'] ?? '';
                /**  @var string $bcc */
                $bcc = $body['MailerInvForm']['bcc'] ?? '';

                $attachFiles = $request->getUploadedFiles();

                $this->generate_inv_number_if_applicable((string)$inv_id, $iR, $this->sR, $gR);

                // Custom fields are automatically included on the invoice
                if ($this->email_stage_1((string)$inv_id, $from, $to, $subject, $email_body, $cc, $bcc, $attachFiles,
                    $cR, $ccR, $cfR, $cvR,
                    $iaR, $icR, $iiaR, $iiR, $iR, $itrR,
                    $pcR, $socR,
                    $qR, $qaR, $qcR, $soR, $uiR, $sumexR, $this->view_renderer)) {
                    $invoice = $iR->repoInvUnloadedquery((string)$inv_id);
                    if ($invoice) {
                      //draft->sent->view->paid
                      //set the invoice to sent ie. 2                                    
                      $invoice->setStatus_id(2);
                      $iR->save($invoice);
                    }
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
// EMAIL SENT
                      ['heading' => '', 
                       'message' => $this->translator->translate('i.email_successfully_sent'),
                       'url' => 'inv/view', 
                       'id' => $inv_id]));
                } else {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
// EMAIL ... NOT ... SENT
                      ['heading' => '', 
                       'message' => $this->translator->translate('invoice.invoice.email.not.sent.successfully'),
                       'url' => 'inv/view', 
                       'id' => $inv_id]));
                } //$this->email_stage_1
            } //is_array(body)
            return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                                    ['heading' => '', 'message' => $this->translator->translate('invoice.invoice.email.not.sent.successfully'),
                                     'url' => 'inv/view', 'id' => $inv_id]));
        }
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                                ['heading' => '', 'message' => $this->translator->translate('i.email_not_sent'),
                                    'url' => 'inv/view', 'id' => $inv_id]));
    }

// email_stage_2

    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
      $this->flash->add($level, $message, true);
      return $this->flash;
    }

   /**
     * @see Route::get('/client_invoices[/page/{page:\d+}[/status/{status:\d+}]]') status and page are decimals
     * @param Request $request
     * @param IAR $iaR
     * @param IRR $irR
     * @param IR $iR
     * @param UCR $ucR
     * @param UIR $uiR
     * @param int $page
     * @param int $status 
     */
    public function guest(Request $request, IAR $iaR, IRR $irR, IR $iR, UCR $ucR, UIR $uiR, 
        #[RouteArgument('page')] $page = 1, #[RouteArgument('status')] $status = 0): \Yiisoft\DataResponse\DataResponse|Response {
        $query_params = $request->getQueryParams();
        /**
         * @var string $query_params['page']
         */
        $pageMixed = $query_params['page'] ?? $page;
        
        /**
         * @var string|null $query_params['sort']
         */
        $sort_string = $query_params['sort'] ?? '-id';
        $sort = Sort::only(['status_id', 'number', 'date_created', 'date_due', 'id', 'client_id'])->withOrderString($sort_string);

        // Get the current user and determine from (@see Settings...User Account) whether they have been given
        // either guest or admin rights. These rights are unrelated to rbac and serve as a second
        // 'line of defense' to support role based admin control.
        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->user_service->getUser();
        if ($user instanceof User && null !== $user->getId()) {
            $user_id = $user->getId();
            // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount((string) $user_id) > 0 ? $uiR->repoUserInvUserIdquery((string) $user_id) : null);
            
            if (null!==$userinv && null !== $user_id) {
                $userInvListLimit = $userinv->getListLimit();
                // Determine what clients have been allocated to this user (@see Settings...User Account)
                // by looking at UserClient table
                // eg. If the user is a guest-accountant, they will have been allocated certain clients
                // A user-quest-accountant will be allocated a series of clients
                // A user-guest-client will be allocated their client number by the administrator so that
                // they can view their invoices and make payment
                $user_clients = $ucR->get_assigned_to_user($user_id);
                if (!empty($user_clients)) {
                    $invs = $this->invs_status_with_sort_guest($iR, $status, $user_clients, $sort);
                    $preFilterInvs = $invs;
                    if (isset($query_params['filterInvNumber']) && !empty($query_params['filterInvNumber'])) {
                        $invs = $iR->filterInvNumber((string)$query_params['filterInvNumber']);
                    }
                    if (isset($query_params['filterInvAmountTotal']) && !empty($query_params['filterInvAmountTotal'])) {
                        $invs = $iR->filterInvAmountTotal((string)$query_params['filterInvAmountTotal']);
                    }
                    if ((isset($query_params['filterInvNumber']) && !empty($query_params['filterInvNumber'])) && 
                       (isset($query_params['filterInvAmountTotal']) && !empty($query_params['filterInvAmountTotal']))) {
                        $invs = $iR->filterInvNumberAndInvAmountTotal((string)$query_params['filterInvNumber'], (float)$query_params['filterInvAmountTotal']);
                    }
                    $paginator = (new DataOffsetPaginator($invs))
                        ->withPageSize(null!== $userInvListLimit ? $userInvListLimit : 10)
                        ->withCurrentPage((int)$pageMixed)
                        ->withToken(PageToken::next((string)$pageMixed));   
                    $inv_statuses = $iR->getStatuses($this->translator);
                    $label = $iR->getSpecificStatusArrayLabel((string)$status);
                    $parameters = [
                        'alert' => $this->alert(),
                        'decimal_places' => (int)$this->sR->get_setting('tax_rate_decimal_places'),
                        'optionsDataInvNumberDropDownFilter' => $this->optionsDataInvNumberGuestFilter($preFilterInvs),
                        'iaR' => $iaR,
                        'irR' => $irR,
                        'invs' => $invs,
                        'grid_summary' => $this->sR->grid_summary(
                            $paginator,
                            $this->translator,
                            null!== $userInvListLimit ? $userInvListLimit : 10,
                            $this->translator->translate('invoice.invoice.invoices'),
                            $label
                        ),
                        // the guest will not have access to the pageSizeLimiter
                        'viewInv' => $this->user_service->hasPermission('viewInv'),
                        // update userinv with the user's listlimit preference
                        'userinv' => $userinv,
                        'defaultPageSizeOffsetPaginator' => null!==$userinv->getListLimit() ? $userinv->getListLimit(): 10,
                        // numbered tiles between the arrrows                
                        'maxNavLinkCount' => 10,
                        'inv_statuses' => $inv_statuses,
                        'page' => (string) $pageMixed,
                        'paginator' => $paginator,
                        // Clicking on a grid column sort hyperlink will generate a url query_param eg. ?sort=
                        'sortOrder' => $query_params['sort'] ?? '',
                        'status' => $status,
                    ];
                    return $this->view_renderer->render('/invoice/inv/guest', $parameters);
                } // no clients assigned to this user    
                throw new NoClientsAssignedToUserException($this->translator);   
            } // $user_inv
            return $this->web_service->getNotFoundResponse();
        } // $user
        return $this->web_service->getNotFoundResponse();
    }

    /**
     *
     * @param int $include
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param SumexR $sumexR
     * @param UIR $uiR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function html(#[RouteArgument('include')] int $include, CR $cR, CVR $cvR, CFR $cfR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, SumexR $sumexR, UIR $uiR, SOR $soR): \Yiisoft\DataResponse\DataResponse {
        $inv_id = (string) $this->session->get('inv_id');
        $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ? $iaR->repoInvquery((int) $inv_id) : null);
        if ($inv_amount) {
            $custom = (($include === 1) ? true : false);
            $inv_custom_values = $this->inv_custom_values($inv_id, $icR);
            $inv = $iR->repoInvUnloadedquery($inv_id);
            if ($inv) {
                $so = ($inv->getSo_id() ? $soR->repoSalesOrderLoadedquery($inv->getSo_id()) : null);
                $html = $this->pdf_helper->generate_inv_html(
                        $inv_id,
                        $inv->getUser_id(),
                        $custom,
                        $so,
                        $inv_amount,
                        $inv_custom_values,
                        $cR,
                        $cvR,
                        $cfR,
                        $iiR,
                        $iiaR,
                        $inv,
                        $itrR,
                        $uiR,
                        $sumexR,
                        $this->view_renderer
                );
                return $this->factory->createResponse('<pre>' . Html::encode($html) . '</pre>');
            } // $inv
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        } // $inv_amount
        return $this->factory->createResponse(Json::encode(['success' => 0]));
    }

    /**
     * @param Request $request
     * @param IR $invRepo
     * @param IRR $irR
     * @param CR $clientRepo
     * @param GR $groupRepo
     * @param QR $qR
     * @param SOR $soR
     * @param DLR $dlR
     * @param UCR $ucR
     * @param string $_language
     * @param string $page
     * @param string $status
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function index(Request $request, IR $invRepo, IRR $irR, CR $clientRepo, GR $groupRepo, QR $qR, SOR $soR, DLR $dlR, UCR $ucR, #[RouteArgument('_language')] string $_language, #[RouteArgument('page')] string $page = '1', #[RouteArgument('status')] string $status = '0'): \Yiisoft\DataResponse\DataResponse|Response {
        // build the inv and hasOne InvAmount table
        $visible = $this->sR->get_setting('columns_all_visible');
        $inv = new Inv();
        $invForm = new InvForm($inv);
        $bootstrap5ModalInv = new Bootstrap5ModalInv(
            $this->translator, 
            $this->view_renderer, 
            $clientRepo, 
            $groupRepo,
            $this->sR,
            $ucR,
            $invForm
        );    
        // If the language dropdown changes
        $this->session->set('_language', $_language);
        // ensure that admin is aware when read-only functionality ie. invoice deletion prevention has changed
        $this->disable_read_only_status_message();
        $active_clients = $ucR->getClients_with_user_accounts();
        if ($active_clients) {
            $query_params = $request->getQueryParams();
            // All, Draft, Sent ... filter governed by routes eg. invoice.myhost/invoice/inv/page/1/status/1 => #[RouteArgument('page')] string $page etc 
            // Paginator ... governed by query params format eg. invoice.myhost/invoice/inv?page=1&pagesize=1 => $query_params
            
            /**
             * @var string $query_params['page']
             */
            $page = $query_params['page'] ?? $page;
            //status 0 => 'all';
            $status = (int) $status;
            /**
             * @var string|null $query_params['sort']
             */
            $sort_string = $query_params['sort'] ?? '-id';
            $order =  OrderHelper::stringToArray($sort_string);
            $sort = Sort::only(['id', 'status_id', 'number', 'date_created', 'date_due', 'client_id'])
                    // (@see vendor\yiisoft\data\src\Reader\Sort
                    // - => 'desc'  so -id => default descending on id
                    // Show the latest quotes first => -id
                    ->withOrder($order);
            $invs = $this->invs_status_with_sort($invRepo, $status, $sort);
            if (isset($query_params['filterInvNumber']) && !empty($query_params['filterInvNumber'])) {
                $invs = $invRepo->filterInvNumber((string)$query_params['filterInvNumber']);
            }
            if (isset($query_params['filterInvAmountTotal']) && !empty($query_params['filterInvAmountTotal'])) {
                $invs = $invRepo->filterInvAmountTotal((string)$query_params['filterInvAmountTotal']);
            }
            if ((isset($query_params['filterInvNumber']) && !empty($query_params['filterInvNumber'])) && 
               (isset($query_params['filterInvAmountTotal']) && !empty($query_params['filterInvAmountTotal']))) {
                $invs = $invRepo->filterInvNumberAndInvAmountTotal((string)$query_params['filterInvNumber'], (float)$query_params['filterInvAmountTotal']);
            }
            if (isset($query_params['filterClientGroup']) && !empty($query_params['filterClientGroup'])) {
                $invs = $invRepo->filterClientGroup((string)$query_params['filterClientGroup']);
            }
            if (isset($query_params['filterDateCreatedYearMonth']) && !empty($query_params['filterDateCreatedYearMonth']))
            {
                // Use the mySql format 'Y-m'
                $invs = $invRepo->filterDateCreatedLike('Y-m', (string)$query_params['filterDateCreatedYearMonth']);
            }
            $paginator = (new DataOffsetPaginator($invs))
                ->withPageSize((int) $this->sR->get_setting('default_list_limit'))
                ->withCurrentPage((int)$page)
                ->withSort($sort)    
                ->withToken(PageToken::next($page));
            $inv_statuses = $invRepo->getStatuses($this->translator);
            $label = $invRepo->getSpecificStatusArrayLabel((string) $status);
            $this->draft_flash($_language);
            $this->mark_sent_flash($_language);
            $parameters = [
                'paginator' => $paginator,
                'alert' => $this->alert(), 'client_count' => $clientRepo->count(),
                'decimal_places' => (int)$this->sR->get_setting('tax_rate_decimal_places'),
                'visible' => $visible == '0' ? false : true, 
                'optionsDataClientsDropdownFilter' => $this->optionsDataClientsFilter($invRepo),
                'optionsDataClientGroupDropDownFilter' => $this->optionsDataClientGroupFilter($clientRepo),
                'optionsDataInvNumberDropDownFilter' => $this->optionsDataInvNumberFilter($invRepo),
                'optionsDataYearMonthDropDownFilter' => $this->optionsDataYearMonthFilter(),
                'grid_summary' => $this->sR->grid_summary(
                    $paginator,
                    $this->translator,
                    (int) $this->sR->get_setting('default_list_limit'),
                    $this->translator->translate('invoice.invoice.invoices'),
                    $label
                ),
                'defaultPageSizeOffsetPaginator' => $this->sR->get_setting('default_list_limit')
                                                    ? (int)$this->sR->get_setting('default_list_limit') : 1,
                // numbered tiles between the arrrows                
                'maxNavLinkCount' => 10,
                'inv_statuses' => $inv_statuses,
                'max' => (int) $this->sR->get_setting('default_list_limit'),
                'page' => $page,
                'status' => $status,
                'qR' => $qR,
                'dlR' => $dlR,
                'soR' => $soR,
                // Use the invRepo to retrieve the Invoice Number of the invoice
                // that a credit note has been generated from
                'iR' => $invRepo,
                'irR' => $irR,
                'modal_add_inv' => $bootstrap5ModalInv->renderPartialLayoutWithFormAsString('inv', [])
            ];
            return $this->view_renderer->render('/invoice/inv/index', $parameters);
        } else {
            $this->flash_message('info',$this->translator->translate('invoice.user.client.active.no'));
            return $this->web_service->getRedirectResponse('client/index');  
        }
    }

    /**
     * @param IR $iR
     * @param int $status
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader<array-key, array<array-key, mixed>|object>
     */
    private function invs_status_with_sort(IR $iR, int $status, Sort $sort): \Yiisoft\Data\Reader\DataReaderInterface {
        $invs = $iR->findAllWithStatus($status)
                ->withSort($sort);
        return $invs;
    }

    /**
     * @param IR $iR
     * @param mixed $status
     * @param array $user_clients
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Inv>
     */
    private function invs_status_with_sort_guest(IR $iR, mixed $status, array $user_clients, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {
        $invs = $iR->repoGuest_Clients_Post_Draft((int)$status, $user_clients)
                ->withSort($sort);
        return $invs;
    }

    // Called from inv.js inv_to_pdf_confirm_with_custom_fields

    /**
     * @param int $include
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param SOR $soR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param SumexR $sumexR
     * @param UIR $uiR
     * @param Request $request
     */
    public function pdf(#[RouteArgument('include')] int $include, CR $cR, CVR $cvR, CFR $cfR, GR $gR, SOR $soR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, SumexR $sumexR, UIR $uiR): \Yiisoft\DataResponse\DataResponse {
        // include is a value of 0 or 1 passed from inv.js function inv_to_pdf_with(out)_custom_fields indicating whether the user
        // wants custom fields included on the inv or not.
        $inv_id = (string) $this->session->get('inv_id');
        $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ? $iaR->repoInvquery((int) $inv_id) : null);
        if ($inv_amount) {
            $custom = (($include === 1) ? true : false);
            $inv_custom_values = $this->inv_custom_values($inv_id, $icR);
            // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
            $pdfhelper = new PdfHelper($this->sR, $this->session);
            // The invoice will be streamed if set under Settings...View...Invoices...Pdf Settings
            $stream = ($this->sR->get_setting('pdf_stream_inv') == '0') ? false : true;
            // If we are required to mark invoices as 'sent' when sent.
            if ($this->sR->get_setting('mark_invoices_sent_pdf') == 1) {
                $this->generate_inv_number_if_applicable($inv_id, $iR, $this->sR, $gR);
                $this->sR->invoice_mark_sent($inv_id, $iR);
            }
            $inv = $iR->repoInvUnloadedquery($inv_id);
            if ($inv) {
                $so = !empty($inv->getSo_id()) ? $soR->repoSalesOrderUnloadedquery($inv->getSo_id()) : null;
                $pdfhelper->generate_inv_pdf($inv_id, $inv->getUser_id(), $stream, $custom, $so, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $sumexR, $this->view_renderer);
                return $this->pdf_archive_message();
            } // $inv
            return $this->factory->createResponse(Json::encode(['success' => 0]));
        } // $inv_amount
        return $this->factory->createResponse(Json::encode(['success' => 0]));
    }

    /**
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function pdf_archive_message(): \Yiisoft\DataResponse\DataResponse {
        if ($this->sR->get_setting('pdf_archive_inv') == '1') {
            return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/pdf_close',
                                    ['heading' => '', 'message' => $this->translator->translate('invoice.invoice.pdf.archived.yes')]));
        } else {
            return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/pdf_close',
                                    ['heading' => '', 'message' => $this->translator->translate('invoice.invoice.pdf.archived.no')]));
        }
    }

    /**
     * @param int $inv_id
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param UIR $uiR
     * @param SOR $soR
     * @param SumexR $sumexR
     * @return void
     */
    public function pdf_dashboard_include_cf(#[RouteArgument('id')] int $inv_id, CR $cR, CVR $cvR, CFR $cfR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, UIR $uiR, SOR $soR, SumexR $sumexR): void {
        if ($inv_id) {
            $inv_amount = (($iaR->repoInvAmountCount($inv_id) > 0) ? $iaR->repoInvquery($inv_id) : null);
            if ($inv_amount) {
                $inv_custom_values = $this->inv_custom_values((string)$inv_id, $icR);
                // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($this->sR, $this->session);
                // The invoice will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark invoices as 'sent' when sent.
                if ($this->sR->get_setting('mark_invoices_sent_pdf') == 1) {
                    $this->generate_inv_number_if_applicable((string)$inv_id, $iR, $this->sR, $gR);
                    $this->sR->invoice_mark_sent((string)$inv_id, $iR);
                }
                $inv = $iR->repoInvUnloadedquery((string)$inv_id);
                if ($inv) {
                    $so = (!empty($inv->getSo_id()) ? $soR->repoSalesOrderLoadedquery($inv->getSo_id()) : null);
                    $pdfhelper->generate_inv_pdf((string)$inv_id, $inv->getUser_id(), $stream, true, $so, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $sumexR, $this->view_renderer);
                } //inv
            } //$inv_amount
        } //$inv_id
    }

    /**
     * @param int $inv_id
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param UIR $uiR
     * @param SOR $soR
     * @param SumexR $sumexR
     * @return void
     */
    public function pdf_dashboard_exclude_cf(#[RouteArgument('id')] int $inv_id, CR $cR, CVR $cvR, CFR $cfR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, UIR $uiR, SOR $soR, SumexR $sumexR): void {
        if ($inv_id) {
            $inv_amount = (($iaR->repoInvAmountCount($inv_id) > 0) ? $iaR->repoInvquery($inv_id) : null);
            if ($inv_amount) {
                $inv_custom_values = $this->inv_custom_values((string)$inv_id, $icR);
                // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($this->sR, $this->session);
                // The invoice will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark invoices as 'sent' when sent.
                if ($this->sR->get_setting('mark_invoices_sent_pdf') == 1) {
                    $this->generate_inv_number_if_applicable((string)$inv_id, $iR, $this->sR, $gR);
                    $this->sR->invoice_mark_sent((string)$inv_id, $iR);
                }
                $inv = $iR->repoInvUnloadedquery((string)$inv_id);
                if ($inv) {
                    $so = (!empty($inv->getSo_id()) ? $soR->repoSalesOrderLoadedquery($inv->getSo_id()) : null);
                    $pdfhelper->generate_inv_pdf((string)$inv_id, $inv->getUser_id(), $stream, false, $so, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $sumexR, $this->view_renderer);
                } //inv
            } //inv_amount
        } // inv_id
    }

    /**
     * @param string $url_key
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param SumexR $sumexR
     * @param UIR $uiR
     * @param UPR $upR
     * @return mixed
     */
    public function pdf_download_include_cf(#[RouteArgument('url_key')] string $url_key, CR $cR, CVR $cvR, CFR $cfR, GR $gR, SOR $soR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, SumexR $sumexR, UIR $uiR, UPR $upR): mixed {
        if ($url_key) {
            // If the status is sent 2, viewed 3, or paid 4 and the url key exists
            if ($iR->repoUrl_key_guest_count($url_key) < 1) {
                return $this->web_service->getNotFoundResponse();
            }
            // Retrieve the inv_id
            $inv_guest = $iR->repoUrl_key_guest_count($url_key) ? $iR->repoUrl_key_guest_loaded($url_key) : null;
            if ($inv_guest) {
                $inv_id = $inv_guest->getId();
                $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ? $iaR->repoInvquery((int) $inv_id) : null);
                if ($inv_amount) {
                    $inv_custom_values = $this->inv_custom_values($inv_id, $icR);
                    // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                    $pdfhelper = new PdfHelper($this->sR, $this->session);
                    // The invoice will be not be streamed ie. shown (in a separate tab see setting), but will be downloaded
                    $stream = false;
                    $c_f = true;
                    // If we are required to mark invoices as 'sent' when sent.
                    if ($this->sR->get_setting('mark_invoices_sent_pdf') == 1) {
                        $this->generate_inv_number_if_applicable($inv_id, $iR, $this->sR, $gR);
                        $this->sR->invoice_mark_sent($inv_id, $iR);
                    }
                    $inv = $iR->repoInvUnloadedquery((string) $inv_id);
                    if ($inv) {
                        $so = (!empty($inv->getSo_id()) ? $soR->repoSalesOrderLoadedquery($inv->getSo_id()) : null);
                        // Because the invoice is not streamed an aliase of temporary folder file location is returned
                        /** @var string $temp_aliase */
                        $temp_aliase = $pdfhelper->generate_inv_pdf($inv_id, $inv->getUser_id(), $stream, $c_f, $so, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $sumexR, $this->view_renderer);
                        if ($temp_aliase) {
                            $path_parts = pathinfo($temp_aliase);
                            /**
                             * @var string $path_parts['extension']
                             */
                            $file_ext = $path_parts['extension'];
                            $original_file_name = $path_parts['basename'];
                            if (file_exists($temp_aliase)) {
                                $file_size = filesize($temp_aliase);
                                $allowed_content_type_array = $upR->getContentTypes();
                                // Check extension against allowed content file types @see UploadRepository getContentTypes
                                $save_ctype = isset($allowed_content_type_array[$file_ext]);
                                /**
                                 * @var string $ctype
                                 */
                                $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] : $upR->getContentTypeDefaultOctetStream();
                                // https://www.php.net/manual/en/function.header.php
                                // Remember that header() must be called before any actual output is sent, either by normal HTML tags,
                                // blank lines in a file, or from PHP.
                                header("Expires: -1");
                                header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
                                header("Content-Disposition: attachment; filename=\"$original_file_name\"");
                                header("Content-Type: " . $ctype);
                                header("Content-Length: " . $file_size);
                                echo file_get_contents($temp_aliase, true);
                                exit;
                            } // file_exists
                        } // is_string
                    } //inv
                } // inv_amount
            } // inv_guest
        } //url_key
        exit;
    }

    /**
     * @param string $urlKey
     * @param CR $cR
     * @param CVR $cvR
     * @param CFR $cfR
     * @param GR $gR
     * @param IAR $iaR
     * @param ICR $icR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param ITRR $itrR
     * @param SumexR $sumexR
     * @param UIR $uiR
     * @param UPR $upR
     * @return mixed
     */
    public function pdf_download_exclude_cf(#[RouteArgument('url_key')] string $urlKey, CR $cR, CVR $cvR, CFR $cfR, GR $gR, IAR $iaR, ICR $icR, IIR $iiR, IIAR $iiaR, IR $iR, ITRR $itrR, SOR $soR, SumexR $sumexR, UIR $uiR, UPR $upR): mixed {
        if ($urlKey) {
            // If the status is sent 2, viewed 3, or paid 4 and the url key exists
            if ($iR->repoUrl_key_guest_count($urlKey) < 1) {
                return $this->web_service->getNotFoundResponse();
            }
            // Retrieve the inv_id
            $inv_guest = $iR->repoUrl_key_guest_count($urlKey) ? $iR->repoUrl_key_guest_loaded($urlKey) : null;
            if ($inv_guest) {
                $inv_id = $inv_guest->getId();
                $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ? $iaR->repoInvquery((int) $inv_id) : null);
                if ($inv_amount) {
                    $inv_custom_values = $this->inv_custom_values($inv_id, $icR);
                    // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                    $pdfhelper = new PdfHelper($this->sR, $this->session);
                    // The invoice will be not be streamed ie. shown (in a separate tab see setting), but will be downloaded
                    $stream = false;
                    $c_f = false;
                    // If we are required to mark invoices as 'sent' when sent.
                    if ($this->sR->get_setting('mark_invoices_sent_pdf') == 1) {
                        $this->generate_inv_number_if_applicable($inv_id, $iR, $this->sR, $gR);
                        $this->sR->invoice_mark_sent($inv_id, $iR);
                    }
                    $inv = $iR->repoInvUnloadedquery((string) $inv_id);
                    if ($inv) {
                        $so = $soR->repoSalesOrderLoadedquery($inv->getSo_id());
                        // Because the invoice is not streamed an aliase of temporary folder file location is returned
                        /** @var string $temp_aliase */
                        $temp_aliase = $pdfhelper->generate_inv_pdf($inv_id, $inv->getUser_id(), $stream, $c_f, $so, $inv_amount, $inv_custom_values, $cR, $cvR, $cfR, $iiR, $iiaR, $iR, $itrR, $uiR, $sumexR, $this->view_renderer);
                        if ($temp_aliase) {
                            $path_parts = pathinfo($temp_aliase);
                            /**
                             * @var string $path_parts['extension']
                             */
                            $file_ext = $path_parts['extension'];
                            // Do not choose 'basename' because extension pdf not necessary ie. filename is basename without extension .pdf
                            $original_file_name = $path_parts['filename'];
                            if (file_exists($temp_aliase)) {
                                $file_size = filesize($temp_aliase);
                                $allowed_content_type_array = $upR->getContentTypes();
                                // Check extension against allowed content file types @see UploadRepository getContentTypes
                                $save_ctype = isset($allowed_content_type_array[$file_ext]);
                                /** @var string $ctype */
                                $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] : $upR->getContentTypeDefaultOctetStream();
                                // https://www.php.net/manual/en/function.header.php
                                // Remember that header() must be called before any actual output is sent, either by normal HTML tags,
                                // blank lines in a file, or from PHP.
                                header("Expires: -1");
                                header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
                                header("Content-Disposition: attachment; filename=\"$original_file_name\"");
                                header("Content-Type: " . $ctype);
                                header("Content-Length: " . $file_size);
                                echo file_get_contents($temp_aliase, true);
                                exit;
                            } // file_exists
                        } // $temp_aliase
                    } // $inv
                } // inv_amount
            } // inv_guest
        } // url_key
        exit;
    }

    ///**
    // * This function has not been tested
    // * @param IR $iR
    // * @param IAR $iaR
    // * @param SumexR $sumexR
    // * @param UIR $uiR
    // * @param int $inv_id
    // * @return void
    // */
    //public function generate_sumex_pdf(IR $iR, IAR $iaR, SumexR $sumexR, UIR $uiR, int $inv_id) : void {
    //    $this->pdf_helper->generate_inv_sumex($iR, $iaR, $sumexR, $uiR,
    //        $inv_id,
    //        $stream = true,
    //        // client
    //        false
    //    );
    //}
    // If the setting 'generate_inv_number_for_draft' has not been set, give the quote a basic number according to id, and not according to identifier format

    /**
     * @param string|null $inv_id
     * @param IR $iR
     * @param SR $sR
     * @param GR $gR
     * @return void
     */
    public function generate_inv_number_if_applicable(string|null $inv_id, IR $iR, SR $sR, GR $gR): void {
        if (null!==$inv_id) {
            $inv = $iR->repoInvUnloadedquery($inv_id);
            if ($inv) {
                $group_id = $inv->getGroup_id();
                if ($iR->repoCount($inv_id) > 0) {
                    if ($inv->getStatus_id() === 1 && $inv->getNumber() === "") {
                        // Generate new inv number if applicable
                        $inv->setNumber((string) $this->generate_inv_get_number($group_id, $sR, $iR, $gR));
                        $iR->save($inv);
                    }
                }
            }
        }
    }

    /**
     * @param string $group_id
     * @param SR $sR
     * @param IR $iR
     * @param GR $gR
     * @return mixed $inv_number
     */
    public function generate_inv_get_number(string $group_id, SR $sR, IR $iR, GR $gR): mixed {
        $inv_number = '';
        if ($sR->get_setting('generate_invoice_number_for_draft') == '0') {
            /** @var mixed $inv_number */
            $inv_number = $iR->get_inv_number($group_id, $gR);
        }
        return $inv_number;
    }

    /**
     * @param int $id
     * @param InvRepository $invRepo
     * @param bool $unloaded
     * @return Inv|null
     */
    private function inv(int $id, InvRepository $invRepo, bool $unloaded = false): Inv|null {
        if ($id) {
            $inv = ($unloaded ? $invRepo->repoInvUnLoadedquery((string)$id) : $invRepo->repoInvLoadedquery((string)$id));
            if (null !== $inv) {
                return $inv;
            }
            return null;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function invs(InvRepository $invRepo, int $status): \Yiisoft\Data\Cycle\Reader\EntityReader {
        $invs = $invRepo->findAllWithStatus($status);
        return $invs;
    }

    /**
     *
     * @param string|null $inv_id
     * @param icR $icR
     * @return array
     */
    public function inv_custom_values(string|null $inv_id, icR $icR): array {
        // Get all the custom fields that have been registered with this inv on creation, retrieve existing values via repo, and populate
        // custom_field_form_values array
        $custom_field_form_values = [];
        if (null!==$inv_id) {
            if ($icR->repoInvCount($inv_id) > 0) {
                $inv_custom_fields = $icR->repoFields($inv_id);
                /**
                 * @var int $key
                 * @var string $val
                 */
                foreach ($inv_custom_fields as $key => $val) {
                    $custom_field_form_values['custom[' . $key . ']'] = $val;
                }
            }
            return $custom_field_form_values;
        }
        return [];
    }

    /**
     * @param int $id
     * @param IIR $invitemRepository
     * @return InvItem|null
     */
    private function inv_item(int $id, IIR $invitemRepository): InvItem|null {
        if ($id) {
            $invitem = $invitemRepository->repoInvItemquery((string)$id) ?: null;
            if ($invitem === null) {
                return null;
            }
            return $invitem;
        }
        return null;
    }

    /**
     * @param string $copy_id
     */
    private function inv_to_inv_inv_amount(string $inv_id, string $copy_id): void {
        $this->inv_amount_service->initializeCopyInvAmount(new InvAmount(), (int) $inv_id, $copy_id);
    }

    // Data fed from inv.js->$(document).on('click', '#inv_to_inv_confirm', function () {

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param GR $gR
     * @param IIAS $iiaS
     * @param PR $pR
     * @param TASKR $taskR
     * @param ICR $icR
     * @param IIAR $iiaR
     * @param IIR $iiR
     * @param IR $iR
     * @param ITRR $itrR
     * @param TRR $trR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @param UNR $unR
     */
    public function inv_to_inv_confirm(Request $request, FormHydrator $formHydrator,
            GR $gR, IIAS $iiaS, PR $pR, TASKR $taskR, ICR $icR,
            IIAR $iiaR, IIR $iiR, IR $iR, ITRR $itrR, TRR $trR, UR $uR, UCR $ucR, UIR $uiR, UNR $unR): \Yiisoft\DataResponse\DataResponse|Response {
      $data_inv_js = $request->getQueryParams();
      $inv_id = (string) $data_inv_js['inv_id'];
      $original = $iR->repoInvUnloadedquery($inv_id);
      if ($original) {
        $group_id = $original->getGroup_id();
        $ajax_body = [
            'quote_id' => null,
            'client_id' => $data_inv_js['client_id'],
            'group_id' => $group_id,
            'status_id' => $this->sR->get_setting('mark_invoices_sent_copy') === '1' ? 2 : 1,
            'number' => $gR->generate_number((int) $group_id),
            'creditinvoice_parent_id' => null,
            'discount_amount' => floatval($original->getDiscount_amount()),
            'discount_percent' => floatval($original->getDiscount_percent()),
            'url_key' => '',
            'password' => '',
            'payment_method' => 1,
            'terms' => '',
        ];
        $copy = new Inv();
        $form = new InvForm($copy);
        if (($formHydrator->populate($form, $ajax_body) && $form->isValid())) {
          /**
           * @var string $ajax_body['client_id']
           */
          $client_id = $ajax_body['client_id'];
          $user = $this->active_user($client_id, $uR, $ucR, $uiR);
          if (null!==$user) {  
            $this->inv_service->saveInv($user, $copy, $ajax_body, $this->sR, $gR);
            // Transfer each inv_item to inv_item and the corresponding inv_item_amount to inv_item_amount for each item
            $copy_id = $copy->getId();
            if (null !== $copy_id) {
              $this->inv_to_inv_inv_items($inv_id, $copy_id, $iiaR, $iiaS, $pR, $taskR, $iiR, $trR, $formHydrator, $unR);
              $this->inv_to_inv_inv_tax_rates($inv_id, $copy_id, $itrR, $formHydrator);
              $this->inv_to_inv_inv_custom($inv_id, $copy_id, $icR, $formHydrator);
              $iR->save($copy);
              $parameters = ['success' => 1];
              //return response to inv.js to reload page at location
              $this->flash_message('info', $this->translator->translate('invoice.draft.guest'));
              return $this->factory->createResponse(Json::encode($parameters));
            }
          }
        } 
        $parameters = [
            'success' => 0,
        ];
        //return response to inv.js to reload page at location
        return $this->factory->createResponse(Json::encode($parameters));
      }
      // if original
      return $this->web_service->getNotFoundResponse();
    }

    /**
     * @param string $copy_id
     */
    private function inv_to_inv_inv_custom(string $inv_id, string $copy_id, ICR $icR, FormHydrator $formHydrator): void {
        $inv_customs = $icR->repoFields($inv_id);
        /**
         * @var InvCustom $inv_custom
         */
        foreach ($inv_customs as $inv_custom) {
            $copyCustom = [
                'inv_id' => $copy_id,
                'custom_field_id' => $inv_custom->getCustom_field_id(),
                'value' => $inv_custom->getValue(),
            ];
            $invCustom = new InvCustom();
            $form = new InvCustomForm($invCustom);
            if ($formHydrator->populate($form, $copyCustom) && $form->isValid()) {
                $this->inv_custom_service->saveInvCustom($invCustom, $copyCustom);
            }
        }
    }

    /**
     * @param string $copy_id
     */
    private function inv_to_inv_inv_items(string $inv_id, string $copy_id, IIAR $iiaR, IIAS $iiaS, PR $pR, TASKR $taskR, IIR $iiR, TRR $trR, FormHydrator $formHydrator, UNR $unR): void {
        // Get all items that belong to the original invoice
        $items = $iiR->repoInvItemIdquery($inv_id);
        /**
         * @var InvItem $inv_item
         */
        foreach ($items as $inv_item) {
            $copy_item = [
                'inv_id' => $copy_id,
                'tax_rate_id' => $inv_item->getTax_rate_id(),
                'product_id' => $inv_item->getProduct_id(),
                'task_id' => $inv_item->getTask_id(),
                'name' => $inv_item->getName(),
                'description' => $inv_item->getDescription(),
                'quantity' => $inv_item->getQuantity(),
                'price' => $inv_item->getPrice(),
                'discount_amount' => $inv_item->getDiscount_amount(),
                'order' => $inv_item->getOrder(),
                'is_recurring' => $inv_item->getIs_recurring(),
                'product_unit' => $inv_item->getProduct_unit(),
                'product_unit_id' => $inv_item->getProduct_unit_id(),
                // Recurring date
                'date' => ''
            ];

            // Create an equivalent invoice item for the invoice item
            $invItem = new InvItem();
            $form = new InvItemForm($invItem, (int)$inv_id);
            if ($formHydrator->populate($form, $copy_item) && $form->isValid()) {
                if (!empty($inv_item->getProduct_id()) && empty($inv_item->getTask_id())) {
                    // (InvItem $model, InvItemForm $form, string $inv_id,PR $pr, SR $s, UNR $unR)
                    $this->inv_item_service->addInvItem_product($invItem, $copy_item, $copy_id, $pR, $trR, $iiaS, $iiaR, $this->sR, $unR);
                } else {
                    $this->inv_item_service->addInvItem_task($invItem, $copy_item, $copy_id, $taskR, $trR, $iiaS, $iiaR, $this->sR);
                }
            } 
        } // foreach
    }

    /**
     * @param string $copy_id
     */
    private function inv_to_inv_inv_tax_rates(string $inv_id, string $copy_id, ITRR $itrR, FormHydrator $formHydrator): void {
        // Get all tax rates that have been setup for the invoice
        $inv_tax_rates = $itrR->repoInvquery($inv_id);
        /**
         * @var InvTaxRate $inv_tax_rate
         */
        foreach ($inv_tax_rates as $inv_tax_rate) {
            $copy_tax_rate = [
                'inv_id' => $copy_id,
                'tax_rate_id' => $inv_tax_rate->getTax_rate_id(),
                'include_item_tax' => $inv_tax_rate->getInclude_item_tax(),
                'amount' => $inv_tax_rate->getInv_tax_rate_amount(),
            ];
            $invTaxRate = new InvTaxRate();
            $form = new InvTaxRateForm($invTaxRate);
            if ($formHydrator->populate($form, $copy_tax_rate) && $form->isValid()) {
                $this->inv_tax_rate_service->saveInvTaxRate($invTaxRate, $copy_tax_rate);
            }
        }
    }

    /**
     * @param int $id
     * @param ITRR $invtaxrateRepository
     * @return InvTaxRate|null
     */
    private function invtaxrate(int $id, ITRR $invtaxrateRepository): InvTaxRate|null {
        if ($id) {
            $invtaxrate = $invtaxrateRepository->repoInvTaxRatequery((string)$id);
            if (null !== $invtaxrate) {
                return $invtaxrate;
            }
        }
        return null;
    }

    /**
     * @param array $files
     * @return mixed
     */
    private function remove_extension(array $files): mixed {
        /**
         * @var string $file
         */
        foreach ($files as $key => $file) {
            $files[$key] = str_replace('.php', '', $file);
        }
        return $files;
    }

    // inv/view => '#btn_save_inv_custom_fields' => inv_custom_field.js => /invoice/inv/save_custom";

    /**
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param ICR $icR
     */
    public function save_custom(FormHydrator $formHydrator, Request $request, ICR $icR): \Yiisoft\DataResponse\DataResponse {
        $parameters = [
            'success' => 0
        ];
        $js_data = $request->getQueryParams();
        $inv_id = (string) $js_data['inv_id'];
        $js_data_custom = (string) $js_data['custom'];
        $custom_field_body = [
            'custom' => $js_data_custom,
        ];
        $this->save_custom_fields($formHydrator, $custom_field_body, $inv_id, $icR);
        $parameters['success'] = 1;
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     *
     * @param FormHydrator $formHydrator
     * @param array $array
     * @param string $inv_id
     * @param ICR $icR
     * @return void
     */
    public function save_custom_fields(FormHydrator $formHydrator, array $array, string $inv_id, ICR $icR): void {
        if (!empty($array['custom'])) {
            $db_array = [];
            $values = [];
            /**
             * @var array $custom
             * @var string $custom['name']
             */
            foreach ($array['custom'] as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                    /**
                     * @var string $custom['value']
                     */
                    $values[$matches[1]][] = $custom['value'];
                } else {
                    /**
                     * @var string $custom['value']
                     */
                    $values[$custom['name']] = $custom['value'];
                }
            }
            /**
             * @var string $value
             */
            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    $key_value = preg_match('/\d+/', $key, $m) ? $m[0] : '';
                    $db_array[$key_value] = $value;
                }
            }
            foreach ($db_array as $key => $value) {
                if ($value !== '') {
                    $invCustom = new InvCustom();
                    $invCustomForm = new InvCustomForm($invCustom);
                    $inv_custom = [];
                    $inv_custom['inv_id'] = $inv_id;
                    $inv_custom['custom_field_id'] = $key;
                    $inv_custom['value'] = $value;
                    $model = ($icR->repoInvCustomCount($inv_id, $key) == 1 ? $icR->repoFormValuequery($inv_id, $key) : $invCustom);
                    if ($model && $formHydrator->populate($invCustomForm, $inv_custom) && $invCustomForm->isValid()) {
                      $this->inv_custom_service->saveInvCustom($model, $inv_custom);
                    }
                }
            } // foreach
        } //!empty array custom
    }

    /**
     * @see src/Invoice/Asset/rebuild-1.13/js/inv.js
     * @param Request $request
     * @param FormHydrator $formHydrator
     */
    public function save_inv_tax_rate(Request $request, FormHydrator $formHydrator): \Yiisoft\DataResponse\DataResponse {
        $body = $request->getQueryParams();
        $ajax_body = [
            'inv_id' => $body['inv_id'],
            'tax_rate_id' => $body['inv_tax_rate_id'],
            'include_item_tax' => $body['include_inv_item_tax'],
            'inv_tax_rate_amount' => floatval(0.00),
        ];
        $invTaxRate = new InvTaxRate();
        $form = new InvTaxRateForm($invTaxRate);
        if ($formHydrator->populate($form, $ajax_body) && $form->isValid()) {
            $this->inv_tax_rate_service->saveInvTaxRate($invTaxRate, $ajax_body);
            $parameters = [
                'success' => 1,
            ];
            //return response to inv.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));
        } else {
            $parameters = [
                'success' => 0
            ];
            //return response to inv.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));
        }
    }

    /**
     * @param SumexR $sumexR
     * @param int $inv_id
     * @return void
     */
    public function sumex_add_record(SumexR $sumexR, int $inv_id): void {
        $sumex_setting = $this->sR->get_setting('sumex');
        if ((int) $sumex_setting === 1) {
            $sumex = new Sumex();
            $sumex->setInvoice($inv_id);
            $sumex->setReason(0);
            $sumex->setDiagnosis('');
            $sumex->setObservations('');
            $sumex->setTreatmentstart((new \DateTime()));
            $sumex->setTreatmentend((new \DateTime()));
            $sumex->setCasedate((new \DateTime()));
            $sumexR->save($sumex);
        }
    }

    /**
     * @param string $urlKey
     * @param string $clientChosenGateway
     * @param string $_language
     * @param CurrentUser $currentUser
     * @param CFR $cfR
     * @param IAR $iaR
     * @param IIAR $iiaR
     * @param IIR $iiR
     * @param IR $iR
     * @param ITRR $itrR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @param PMR $pmR
     * @param UPR $upR
     * @return Response
     */
    public function url_key(#[RouteArgument('url_key')] string $urlKey, #[RouteArgument('gateway')] string $clientChosenGateway, #[RouteArgument('_language')] string $_language, CurrentUser $currentUser, CFR $cfR, IAR $iaR, IIAR $iiaR, IIR $iiR, IR $iR, ITRR $itrR, UR $uR, UCR $ucR, UIR $uiR, PMR $pmR, UPR $upR): Response {
        // if the current user is a guest it will return a null value
        if ($urlKey === '' || $currentUser->isGuest()) {
            return $this->web_service->getNotFoundResponse();
        }

        if ($clientChosenGateway === '') {
            return $this->web_service->getNotFoundResponse();
        }

        // If the status is sent 2, viewed 3, or paid 4 and the url key exists accept otherwise not found response
        if ($iR->repoUrl_key_guest_count($urlKey) < 1) {
            return $this->web_service->getNotFoundResponse();
        }

        $inv = $iR->repoUrl_key_guest_loaded($urlKey);
        if ($inv instanceof Inv) {
            $inv_id = $inv->getId();
            $client_id = $inv->getClient_id();
            $user = $this->active_user($client_id, $uR, $ucR, $uiR);
            if ($user) {
                $user_id = $user->getId();
                if (null!==$user_id) {
                  $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                  // If the user is not an administrator and the status is sent 2, now mark it as viewed
                  if (null!==$user_inv) {
                    if ($uiR->repoUserInvUserIdcount($user_id) === 1 && $user_inv->getType() !== 1 && $inv->getStatus_id() === 2) {
                        // Mark the invoice as viewed and check whether it should be marked as read only
                        // according to the read only toggle setting.
                        $this->sR->invoice_mark_viewed((string) $inv_id, $iR);
                    }
                    $iR->save($inv);                    
                    $payment_method = $inv->getPayment_method() !== 0 ? $pmR->repoPaymentMethodquery((string) $inv->getPayment_method()) : null;
                    $custom_fields = [
                        'invoice' => $cfR->repoTablequery('inv_custom'),
                        'client' => $cfR->repoTablequery('client_custom'),
                            // TODO 'user' => $cfR->repoTablequery('user_custom'),
                    ];

                    $attachments = $this->view_partial_inv_attachments($_language, $urlKey, (int) $client_id, $upR);
                    $inv_amount = (($iaR->repoInvAmountCount((int) $inv_id) > 0) ? $iaR->repoInvquery((int) $inv_id) : null);
                    if ($inv_amount) {
                        $is_overdue = ($inv_amount->getBalance() > 0 && ($inv->getDate_due()) < (new \DateTimeImmutable('now')) ? true : false);
                        $parameters = [
                            'render' => $this->view_renderer->renderPartialAsString('/invoice/template/invoice/public/' . ($this->sR->get_setting('public_invoice_template') ?: 'Invoice_Web'), [
                                'alert' => $this->alert(),
                                'aliases' => $this->sR->get_img(),
                                'attachments' => $attachments,
                                'balance' => ($inv_amount->getTotal() ?? 0.00) - ($inv_amount->getPaid() ?? 0.00),
                                // Gateway that the paying user has selected
                                'client_chosen_gateway' => $clientChosenGateway,
                                'clienthelper' => new ClientHelper($this->sR),
                                'client' => $inv->getClient(),
                                'custom_fields' => $custom_fields,
                                'download_pdf_non_sumex_action' => ['inv/download_pdf', ['url_key' => $urlKey]],
                                'download_pdf_sumex_action' => ['inv/download_pdf', ['url_key' => $urlKey]],
                                'inv' => $inv,
                                'inv_amount' => $inv_amount,
                                'inv_tax_rates' => ($inv_id > 0) && $itrR->repoCount($inv_id) > 0 ? $itrR->repoInvquery($inv_id) : null,
                                'inv_url_key' => $urlKey,
                                'inv_item_amount' => $iiaR,
                                'is_overdue' => $is_overdue,
                                'items' => ($inv_id > 0) ? $iiR->repoInvquery($inv_id) : new InvItem(),
                                'logo' => '',
                                'payment_method' => $payment_method,
                                'paymentTermsArray' => $this->sR->get_payment_term_array($this->translator),
                                'userinv' => $uiR->repoUserInvUserIdcount($user_id) > 0 ? $uiR->repoUserInvUserIdquery($user_id) : null,
                            ]),
                        ];
                        return $this->view_renderer->render('/invoice/inv/url_key', $parameters);
                    } // if inv_amount
                    $this->flash_message('warning', $this->translator->translate('invoice.invoice.amount.no'));
                    return $this->web_service->getNotFoundResponse();
                  } // null!== $user_inv  
                } // null!== $user_id  
            } // if user_inv
            $this->flash_message('danger', $this->translator->translate('invoice.invoice.client.not.allocated.to.user'));
            return $this->web_service->getNotFoundResponse();
        } // if inv
        $this->flash_message('danger', $this->translator->translate('invoice.invoice.not.found'));
        return $this->web_service->getNotFoundResponse();
    }

    /**
     * @param bool $read_only
     * @return bool
     */
    private function display_edit_delete_buttons(bool $read_only): bool {
        if (($read_only === false) && ($this->sR->get_setting('disable_read_only') === (string) 0)) {
            return true;
        }
        // Override the invoice's readonly
        if (($this->sR->get_setting('disable_read_only') === (string) 1)) {
            return true;
        }
        return false;
    }
    
    /**
     * @param array $enabled_gateways
     * @return void
     */
    private function flash_no_enabled_gateways(array $enabled_gateways, string $message) : void {
      if (empty(array_filter($enabled_gateways))) {
        $this->flash_message('warning', $message);
      }
    }
    
    /**
     * Check to make sure that the invoice's client has full peppol details setup before engaging peppol
     * @param string $client_id
     * @return bool
     */
    private function peppol_client_fully_setup(string $client_id, cpR $cpR) : bool 
    {
      $passed = false;
      if ($cpR->repoClientCount($client_id) == 1) {
        $cp = $cpR->repoClientPeppolLoadedquery($client_id);
        // check that each individual field has been completed otherwise raise a flash message
        if (null!==$cp) {
          if (empty($cp->getEndpointid())) {
            $this->flash_message('warning', '$cp->getEndpointid() '.$cp->getEndpointid());
          }
          if (empty($cp->getEndpointid_schemeid())) {
            $this->flash_message('warning', '$cp->getEndpointid_schemeid() '.$cp->getEndpointid_schemeid());
          }
          if (empty($cp->getIdentificationid())) {
            $this->flash_message('warning', '$cp->getIdentificationid() '.$cp->getIdentificationid());
          }
          if (empty($cp->getTaxschemecompanyid())) {
            $this->flash_message('warning', '$cp->getTaxschemecompanyid() '.$cp->getTaxschemecompanyid());
          }
          if (empty($cp->getTaxschemeid())) {
            $this->flash_message('warning', '$cp->getTaxschemeid() '.$cp->getTaxschemeid());
          }
          if (empty($cp->getLegal_entity_registration_name())) {
            $this->flash_message('warning', '$cp->getLegal_entity_registration_name() '.$cp->getLegal_entity_registration_name());
          }
          if (empty($cp->getLegal_entity_companyid())) {
            $this->flash_message('warning', '$cp->getLegal_entity_companyid() '.$cp->getLegal_entity_companyid());
          }
          if (empty($cp->getLegal_entity_companyid_schemeid())) {
            $this->flash_message('warning', '$cp->getLegal_entity_companyid_schemeid() '.$cp->getLegal_entity_companyid_schemeid());
          }
          if (empty($cp->getLegal_entity_company_legal_form())) {
            $this->flash_message('warning', '$cp->getLegal_entity_company_legal_form() '.$cp->getLegal_entity_company_legal_form());
          }
          if (empty($cp->getFinancial_institution_branchid())) {
            $this->flash_message('warning', '$cp->getFinancial_institution_branchid() '.$cp->getFinancial_institution_branchid());
          }
          if (empty($cp->getAccountingCost())) {
            $this->flash_message('warning', '$cp->getAccountingCost() '.$cp->getAccountingCost());
          }
          
          if (empty($cp->getSupplierAssignedAccountId())) {
            $this->flash_message('warning', '$cp->getSupplierAssignedAccountId() '.$cp->getSupplierAssignedAccountId());
          }
          
          if($cp->getEndpointid()
            && $cp->getEndpointid_schemeid()
            && $cp->getIdentificationid()
            && $cp->getIdentificationid_schemeid()
            && $cp->getTaxschemecompanyid()
            && $cp->getTaxschemeid()
            && $cp->getLegal_entity_registration_name()
            && $cp->getLegal_entity_companyid()
            && $cp->getLegal_entity_companyid_schemeid()
            && $cp->getLegal_entity_company_legal_form()
            && $cp->getFinancial_institution_branchid()
            && $cp->getAccountingCost()
            && $cp->getSupplierAssignedAccountId()) {
            $passed = true;       
          } else {
            $this->flash_message('warning', $this->translator->translate('invoice.peppol.client.check'));
            $passed = false;
          }
        } // null!==$cp   
      } // $cpR->repoClientCount($client_id) == 1
      return $passed;
    }  
    
    /**
     * Purpose: Generate OpenPeppol Ubl Invoice 3.0.15 XML file to 1. screen or 2. file
     * @param int $id
     * @param CurrentUser $currentUser
     * @param cpR $cpR
     * @param IAR $iaR
     * @param IIAR $iiaR
     * @param IIR $iiR
     * @param IR $invRepo
     * @param ContractRepo $contractRepo
     * @param DelRepo $delRepo
     * @param DelPartyRepo $delPartyRepo
     * @param DLR $dlR
     * @param paR $paR
     * @param SOR $soR
     * @param unpR $unpR
     * @param upR $upR
     * @param ACIR $aciR
     * @param ACIIR $aciiR
     * @param SOIR $soiR
     * @param TRR $trR
     * @return Response
     */
     public function peppol(#[RouteArgument('id')] int $id,
      CurrentUser $currentUser,
      cpR $cpR,
      IAR $iaR,
      IIAR $iiaR,
      IIR $iiR,
      IR $invRepo,
      ContractRepo $contractRepo,
      DelRepo $delRepo,
      DelPartyRepo $delPartyRepo,
      DLR $dlR,
      paR $paR,
      SOR $soR,
      unpR $unpR,
      UPR $upR,
      ACIR $aciR,
      ACIIR $aciiR,
      SOIR $soiR,
      TRR $trR
    ): Response {
      if ($currentUser->isGuest()) {
          return $this->web_service->getNotFoundResponse();
      }
      // Load the inv's HASONE relation 'invamount'
      if ($id) {
          $invoice = $invRepo->repoInvLoadInvAmountquery((string)$id);
          if ($invoice) {
              $client_id = $invoice->getClient()?->getClient_id();
              if (null !== $client_id) {
                  if ($this->peppol_client_fully_setup((string)$client_id, $cpR)) {
                    $delivery_location = $dlR->repoDeliveryLocationquery((string) $client_id);
                    if (null !== $delivery_location) {
                        $inv_amount = $invoice->getInvAmount();
                        $peppolhelper = new PeppolHelper(
                          $this->sR,
                          $iiaR,
                          $inv_amount,
                          $delivery_location,
                          $this->translator,
                          $this->sR->get_setting('currency_code_from'),
                          $this->sR->get_setting('currency_code_to'),
                          // one of 'from currency' converts to this of 'to currency':
                          $this->sR->get_setting('currency_from_to'),
                          // one of 'to currency' converts to this of 'from currency':
                          $this->sR->get_setting('currency_to_from'),
                        );
                        $uploads_temp_peppol_absolute_path_dot_xml = $peppolhelper->generate_invoice_peppol_ubl_xml_temp_file(
                          $soR,
                          $invoice,
                          $iaR,
                          $iiaR,
                          $iiR,
                          $contractRepo,
                          $delRepo,
                          $delPartyRepo,
                          $paR,
                          $cpR,
                          $unpR,
                          $upR,
                          $aciR,
                          $aciiR,
                          $soiR,
                          $trR
                        );
                        if ($this->sR->get_setting('peppol_xml_stream') == '1') {
                            $xml = $this->peppol_output($upR, $uploads_temp_peppol_absolute_path_dot_xml);
                            return $this->factory->createResponse('<pre>' . Html::encode($xml) . '</pre>');
                        } else {
                            echo $this->peppol_output($upR, $uploads_temp_peppol_absolute_path_dot_xml);
                            exit;
                        }
                    } // null!== $delivery_location
                    $this->flash_message('warning', $this->translator->translate('invoice.delivery.location.peppol.output'));
                  } // client_peppol fully setup
              } // null!== $client_id
          } // invoice
      } // null !==id
      exit;
    }
    
    /**
     * Purpose: Use the toggle button to 
     * stream Ubl invoice to screen or alternatively output to file 
     * 
     * View: resources/views/invoice/inv/view.php
     * @param int $id
     * @param CurrentUser $currentUser
     * @return Response
     */    
    public function peppol_stream_toggle(#[RouteArgument('id')] int $id, CurrentUser $currentUser,
      ) : Response  
    {
      if ($currentUser->isGuest()) {
            return $this->web_service->getNotFoundResponse();
      }
      if ($this->sR->repoCount('peppol_xml_stream') > 0) {
        $record = $this->sR->withKey('peppol_xml_stream');
        if ($this->sR->get_setting('peppol_xml_stream') === '1') {
          if ($record instanceof Setting) {
            $record->setSetting_value('0');
            $this->sR->save($record);
          }
        } else {
          if ($record instanceof Setting) {
            $record->setSetting_value('1');
            $this->sR->save($record);
          }  
        } // else
      } // $this->sR->repoCount
      $this->flash_message('info',$this->translator->translate('invoice.peppol.stream.toggle'));
      return $this->web_service->getRedirectResponse('inv/view',['id' => $id]); 
    } // peppol stream toggle
    
    /**
     * @see https://www.storecove.com/docs#_json_object
     * @see StoreCove API key stored under Online Payment keys under Settings...View...Online Payment
     * @param int $id
     * @param CurrentUser $currentUser
     * @param cpR $cpR
     * @param IIAR $iiaR
     * @param IR $invRepo
     * @param ContractRepo $contractRepo
     * @param DelRepo $delRepo
     * @param DelPartyRepo $delPartyRepo
     * @param DLR $dlR
     * @param paR $paR
     * @param ppR $ppR
     * @param unpR $unpR
     * @param SOR $soR
     * @param UPR $upR
     * @param ACIR $aciR
     * @param ACIIR $aciiR
     * @param SOIR $soiR
     * @param TRR $trR
     * @return Response
     */
    public function storecove(
            #[RouteArgument('id')] int $id, 
            CurrentUser $currentUser,
            cpR $cpR,
            IIAR $iiaR,
            IR $invRepo,
            ContractRepo $contractRepo,
            DelRepo $delRepo,
            DelPartyRepo $delPartyRepo,
            DLR $dlR,
            paR $paR,
            ppR $ppR,
            unpR $unpR,
            SOR $soR,
            UPR $upR,
            ACIR $aciR,
            ACIIR $aciiR,
            SOIR $soiR,
            TRR $trR
    ): Response {
        if ($currentUser->isGuest()) {
            return $this->web_service->getNotFoundResponse();
        }
        // Load the inv's HASONE relation 'invamount'
        if ($id) {
            $invoice = $invRepo->repoInvLoadInvAmountquery((string)$id);
            if ($invoice) {
                $client_id = $invoice->getClient()?->getClient_id();
                if (null !== $client_id) {
                    $delivery_location = $dlR->repoDeliveryLocationquery((string) $client_id);
                    if (null !== $delivery_location) {
                        $inv_amount = $invoice->getInvAmount();
                        $storecovehelper = new StoreCoveHelper(
                          $this->sR,
                          $iiaR,
                          $inv_amount,
                          $delivery_location,
                          $this->translator,
                          $this->sR->get_setting('currency_code_from'),
                          $this->sR->get_setting('currency_code_to'),
                          // one of 'from currency' converts to this of 'to currency':
                          $this->sR->get_setting('currency_from_to'),
                          // one of 'to currency' converts to this of 'from currency':
                          $this->sR->get_setting('currency_to_from'),
                          $this->crypt
                        );
                        $storecove_array = $storecovehelper->maximum_pre_json_php_object_for_an_invoice(
                            $soR,
                            $invoice,
                            $iiaR,
                            //$iiR,
                            $contractRepo,
                            $delRepo,
                            $delPartyRepo,
                            $paR,
                            $cpR,            
                            $ppR,
                            $unpR,
                            $upR,    
                            $aciR,
                            $aciiR,
                            $soiR,
                            $trR
                        );
                        echo Json::encode($storecove_array,     
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                        512);
                    }
                }
            }
        }        
        exit;
    }

    /**
     * @param upR $upR
     * @param string $uploads_temp_peppol_absolute_path_dot_xml
     * @return string
     */
    private function peppol_output(upR $upR, string $uploads_temp_peppol_absolute_path_dot_xml): string {
        $path_parts = pathinfo($uploads_temp_peppol_absolute_path_dot_xml);
        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        $file_ext = $path_parts['extension'];
        $original_file_name = $path_parts['filename'];
        if (file_exists($uploads_temp_peppol_absolute_path_dot_xml)) {
            $file_size = filesize($uploads_temp_peppol_absolute_path_dot_xml);
            // xml is included in the getContentTypes allowed array
            $allowed_content_type_array = $upR->getContentTypes();
            // Check current extension against allowed content file types @see UploadRepository getContentTypes
            $save_ctype = isset($allowed_content_type_array[$file_ext]);
            /** @var string $ctype */
            $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] : $upR->getContentTypeDefaultOctetStream();
            // https://www.php.net/manual/en/function.header.php
            // Remember that header() must be called before any actual output is sent, either by normal HTML tags,
            // blank lines in a file, or from PHP.
            header("Expires: -1");
            header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
            header("Content-Disposition: attachment; filename=\"$original_file_name\"");
            header("Content-Type: " . $ctype);
            header("Content-Length: " . $file_size);
            return file_get_contents($uploads_temp_peppol_absolute_path_dot_xml, true);
        }
        return '';
    }
    
    // The accesschecker in config/routes ensures that only users with viewInv permission can reach this

    /**
     * @param ViewRenderer $head
     * @param int $id
     * @param string $_language
     * @param CFR $cfR
     * @param CVR $cvR
     * @param PR $pR
     * @param PIR $piR
     * @param IAR $iaR
     * @param IIAR $iiaR
     * @param IIR $iiR
     * @param IR $iR
     * @param IRR $irR
     * @param ITRR $itrR
     * @param PMR $pmR
     * @param TRR $trR
     * @param FR $fR
     * @param UNR $uR
     * @param ACR $acR
     * @param ACIR $aciR
     * @param CR $cR
     * @param GR $gR
     * @param ICR $icR
     * @param PYMR $pymR
     * @param TASKR $taskR
     * @param PRJCTR $prjctR
     * @param UIR $uiR
     * @param UCR $ucR
     * @param UPR $upR
     * @param SumexR $sumexR
     * @param DLR $dlR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(ViewRenderer $head, #[RouteArgument('id')] int $id, #[RouteArgument('_language')] string $_language, 
            CFR $cfR, CVR $cvR, PR $pR, PIR $piR, IAR $iaR, IIAR $iiaR, IIR $iiR, IR $iR, IRR $irR, ITRR $itrR, PMR $pmR,
            TRR $trR, FR $fR, UNR $uR, ACR $acR, ACIR $aciR, ACIIR $aciiR, CR $cR, GR $gR, ICR $icR, PYMR $pymR, TASKR $taskR, PRJCTR $prjctR, UIR $uiR, UCR $ucR, UPR $upR, SOR $soR, SumexR $sumexR, DLR $dlR)
    : \Yiisoft\DataResponse\DataResponse|Response {
        $inv = $this->inv($id, $iR, false);
        $enabled_gateways = $this->sR->payment_gateways_enabled_DriverList();
        $this->flash_no_enabled_gateways($enabled_gateways, $this->translator->translate('invoice.payment.gateway.no'));
        if ($inv) {
            $sales_order_number = '';
            if ($inv->getSo_id()) {
                $so = $soR->repoSalesOrderUnloadedquery($inv->getSo_id());
                if ($so) {
                    $sales_order_number = $so->getNumber();
                }
            }
            $invoice = $inv->getId();
            $invAllowanceCharge = new InvAllowanceCharge();
            $invAllowanceChargeForm = new InvAllowanceChargeForm($invAllowanceCharge, (int)$invoice);
            $sumex = $sumexR->repoSumexInvoicequery((string) $invoice);
            $is_recurring = false;
            $read_only = $inv->getIs_read_only();
            $this->session->set('inv_id', $inv->getId());
            $this->number_helper->calculate_inv((string) $this->session->get('inv_id'), $aciR, $iiR, $iiaR, $itrR, $iaR, $iR, $pymR);
            $inv_amount = (($iaR->repoInvAmountCount((int) $inv->getId()) > 0) ? $iaR->repoInvquery((int) $this->session->get('inv_id')) : null);
            if ($inv_amount) {
                $inv_custom_values = $this->inv_custom_values((string) $this->session->get('inv_id'), $icR);
                $is_recurring = ($irR->repoCount((string) $this->session->get('inv_id')) > 0 ? true : false);
                $show_buttons = $this->display_edit_delete_buttons($read_only);
                // Each file attachment is recorded in Upload table with invoice's url_key, and client_id
                $url_key = $inv->getUrl_key();
                $client_id = $inv->getClient_id();
                $delivery_location_id = $inv->getDelivery_location_id();
                $bootstrap5ModalTranslatorMessageWithoutAction = new Bootstrap5ModalTranslatorMessageWithoutAction(
                        $this->view_renderer);
                $parameters = [
                    'sales_order_number' => $sales_order_number,
                    'title' => $this->translator->translate('i.view'),
                    'body' => $this->body($inv),
                    'inv' => $inv,
                    'alert' => $this->alert(),
                    'aciR' => $aciR,
                    // Determine if a 'viewInv' user has 'editInv' permission
                    'invEdit' => $this->user_service->hasPermission('editInv') ? true : false,
                    // Determine if a 'viewInv' user has 'viewPayment' permission
                    // This permission is necessary for a guest viewing a read-only view to go to the Pay now section
                    'paymentView' => $this->user_service->hasPermission('viewPayment') ? true : false,
                    'enabled_gateways' => $enabled_gateways,
                    'iaR' => $iaR,
                    'is_recurring' => $is_recurring,
                    'payment_methods' => $pmR->findAllPreloaded(),
                    // If a custom field exists for payments, use it/them on the payment form.
                    'payment_cf_exist' => $cfR->repoTableCountquery('payment_custom') > 0 ? true : false,
                    'read_only' => $read_only,
                    'show_buttons' => $show_buttons,
                    'sumex' => $sumex,
                    'payments' => $pymR->repoCount((string) $this->session->get('inv_id')) > 0 ? $pymR->repoInvquery((string) $this->session->get('inv_id')) : null,
                    'peppol_stream_toggle' => $this->sR->get_setting('peppol_xml_stream'),
                    // Sits above options section of invoice allowing the adding of a new row to the invoice
                    'add_inv_item_product' => $this->view_renderer->renderPartialAsString('/invoice/invitem/_item_form_product', [
                        'action' => ['invitem/add_product', ['_language' => $_language]],
                        'errors' => [],
                        'form' => new InvItemForm(new InvItem(), (int)$this->session->get('inv_id')),
                        'inv' => $iR->repoInvLoadedquery((string)$invoice),
                        'is_recurring' => ($irR->repoCount((string)$invoice) > 0) ? true : false,
                        'inv_id' => $this->session->get('inv_id'),
                        'inv_item_allowances_charges' => $aciiR->repoACIquery((string) $this->session->get('inv_id')),
                        'inv_item_allowances_charges_count' => $aciiR->repoInvcount((string) $this->session->get('inv_id')),
                        'tax_rates' => $trR->findAllPreloaded(),
                        // Tasks are excluded
                        'products' => $pR->findAllPreloaded(),
                        'units' => $uR->findAllPreloaded(),
                        'numberhelper' => $this->number_helper
                    ]),
                    'add_inv_item_task' => $this->view_renderer->renderPartialAsString('/invoice/invitem/_item_form_task', [
                        'action' => ['invitem/add_task', ['_language' => $_language]],
                        'errors' => [],
                        'form' => new InvItemForm(new InvItem(), (int)$this->session->get('inv_id')),
                        'inv' => $iR->repoInvLoadedquery((string) $this->session->get('inv_id')),
                        'is_recurring' => $is_recurring,
                        'inv_id' => (string) $this->session->get('inv_id'),
                        'tax_rates' => $trR->findAllPreloaded(),
                        // Only tasks with complete or status of 3 are made available for selection
                        'tasks' => $taskR->repoTaskStatusquery(3),
                        // Products are excluded
                        'units' => $uR->findAllPreloaded(),
                        'numberhelper' => $this->number_helper
                    ]),
                    // Get all the fields that have been setup for this SPECIFIC invoice in inv_custom.
                    'fields' => $icR->repoFields((string) $this->session->get('inv_id')),
                    // Get the standard extra custom fields built for EVERY invoice.
                    'custom_fields' => $cfR->repoTablequery('inv_custom'),
                    'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('inv_custom')),
                    'cvH' => new CVH($this->sR),
                    'inv_custom_values' => $inv_custom_values,
                    'inv_statuses' => $iR->getStatuses($this->translator),
                    'modal_choose_items' => $this->view_renderer->renderPartialAsString('/invoice/product/modal_product_lookups_inv',
                    [
                        'families' => $fR->findAllPreloaded(),
                        'default_item_tax_rate' => $this->sR->get_setting('default_item_tax_rate') !== '' ?: 0,
                        'filter_product' => '',
                        'filter_family' => '',
                        'reset_table' => '',
                        'products' => $pR->findAllPreloaded(),
                        'head' => $head,
                    ]),
                    'modal_choose_tasks' => $this->view_renderer->renderPartialAsString('/invoice/task/modal_task_lookups_inv',
                    [
                        'partial_task_table_modal' => $this->view_renderer->renderPartialAsString('/invoice/task/partial_task_table_modal', [
                            // Only tasks with complete or status of 3 are made available for selection
                            'tasks' => $taskR->repoTaskStatusquery(3),
                            'prjct' => $prjctR,
                            'datehelper' => $this->date_helper,
                            'numberhelper' => $this->number_helper,
                        ]),
                        'default_item_tax_rate' => $this->sR->get_setting('default_item_tax_rate') !== '' ?: 0,
                        'tasks' => $pR->findAllPreloaded(),
                        'head' => $head,
                    ]),
                    'modal_add_inv_tax' => $this->view_renderer->renderPartialAsString('/invoice/inv/modal_add_inv_tax', ['tax_rates' => $trR->findAllPreloaded()]),
                    'modal_add_allowance_charge' => $this->view_renderer->renderPartialAsString('/invoice/inv/modal_add_allowance_charge', 
                    [
                        'modal_add_allowance_charge_form' => $this->view_renderer->renderPartialAsString('/invoice/inv/modal_add_allowance_charge_form', 
                        [
                            'optionsDataAllowanceCharges' => $acR->optionsDataAllowanceCharges(),
                            'action' => ['invallowancecharge/add', ['inv_id' => (string) $this->session->get('inv_id')]],
                            'errors' => [],
                            'title' => $this->translator->translate('invoice.invoice.allowance.or.charge.add'),
                            'form' => $invAllowanceChargeForm 
                        ])
                    ]),
                    'modal_copy_inv' => $this->view_renderer->renderPartialAsString('/invoice/inv/modal_copy_inv', [
                        'inv' => $iR->repoInvLoadedquery((string) $this->session->get('inv_id')),
                        'clients' => $cR->repoUserClient($ucR->getClients_with_user_accounts()),
                        'groups' => $gR->findAllPreloaded(),
                    ]),
                    // Partial item table: Used to build items either products/tasks that make up the invoice
                    // Partial item table: Items and Grand Totals
                    'partial_item_table' => $this->view_partial_item_table($show_buttons, $id, $aciR, $aciiR, $pR, $piR, $taskR, $iiR, $iiaR, $iR, $trR, $uR,
                            $itrR, $inv_amount),
                    'modal_delete_inv' => $this->view_modal_delete_inv($_language),
                    'modal_delete_items' => $this->view_modal_delete_items($iiR),
                    'modal_change_client' => $this->view_modal_change_client($id, $cR, $iR),
                    'modal_inv_to_pdf' => $this->view_modal_inv_to_pdf($id, $iR),
                    'modal_inv_to_html' => $this->view_modal_inv_to_html($id, $iR),
                    'modal_create_recurring' => $this->view_modal_create_recurring($irR),
                    'modal_create_credit' => $this->view_modal_create_credit($id, $gR, $iR),
                    'view_custom_fields' => $this->view_custom_fields($cfR, $cvR, $inv_custom_values),
                    'partial_inv_attachments' => $this->view_partial_inv_attachments($_language, $url_key, (int) $client_id, $upR),
                    'partial_inv_delivery_location' => $this->view_partial_delivery_location($_language, $dlR, $delivery_location_id),
                    'modal_message_no_payment_method' => $bootstrap5ModalTranslatorMessageWithoutAction
                        ->renderPartialLayoutWithTranslatorMessageAsString(
                        $this->translator->translate('invoice.payment.method'),
                        $this->translator->translate('invoice.payment.information.payment.method.required'),
                        'inv'
                    )  
                ];
                return $this->view_renderer->render('/invoice/inv/view', $parameters);
            } // if $inv_amount
            return $this->web_service->getNotFoundResponse();
        } // if $inv
        return $this->web_service->getNotFoundResponse();
    }

    /**
     * @param CFR $cfR
     * @param CVR $cvR
     * @param array $inv_custom_values
     * @return string
     */
    private function view_custom_fields(CFR $cfR, CVR $cvR, array $inv_custom_values): string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/view_custom_fields', [
                    'custom_fields' => $cfR->repoTablequery('inv_custom'),
                    'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('inv_custom')),
                    'inv_custom_values' => $inv_custom_values,
                    'cvH' => new CVH($this->sR),
                    'invCustomForm' => new InvCustomForm(new InvCustom()),
        ]);
    }

    /**
     * @param int $id
     * @param CR $cR
     * @param IR $iR
     * @return string
     */
    private function view_modal_change_client(int $id, CR $cR, IR $iR): string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_change_client', [
                    'inv' => $this->inv($id, $iR, true),
                    'clients' => $cR->findAllPreloaded()
        ]);
    }

    /**
     * @param int $id
     * @param GR $gR
     * @param IR $iR
     * @return string
     */
    private function view_modal_create_credit(int $id, GR $gR, IR $iR): string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_create_credit', [
                    'invoice_groups' => $gR->repoCountAll() > 0 ? $gR->findAllPreloaded() : null,
                    'inv' => $this->inv($id, $iR, false),
                    'datehelper' => $this->date_helper
        ]);
    }

    /**
     * @param IRR $irR
     * @return string
     */
    private function view_modal_create_recurring(IRR $irR): string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_create_recurring', [
                    'recur_frequencies' => $irR->recur_frequencies(),
                    'datehelper' => $this->date_helper
        ]);
    }

    /**
     * @param string $_language
     * @return string
     */
    private function view_modal_delete_inv(string $_language): string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_delete_inv', ['action' => ['inv/delete', ['id' => $this->session->get('inv_id'), '_language' => $_language]],
        ]);
    }

    /**
     * @param IIR $iiR
     * @return string
     */
    private function view_modal_delete_items(IIR $iiR): string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_delete_item', [
                    'partial_item_table_modal' => $this->view_renderer->renderPartialAsString('/invoice/invitem/_partial_item_table_modal', [
                        'invitems' => $iiR->repoInvquery((string) $this->session->get('inv_id')),
                        'numberhelper' => $this->number_helper,
                    ]),
        ]);
    }

    /**
     * @param int $id
     * @param IR $iR
     * @return string
     */
    private function view_modal_inv_to_pdf(int $id, IR $iR): string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_inv_to_pdf', [
                    'inv' => $this->inv($id, $iR, true),
        ]);
    }

    /**
     * @param int $id
     * @param IR $iR
     * @return string
     */
    private function view_modal_inv_to_html(int $id, IR $iR): string {
        return $this->view_renderer->renderPartialAsString('/invoice/inv/modal_inv_to_html', [
                    'inv' => $this->inv($id, $iR, true),
        ]);
    }

    /**
     * @param string $_language
     * @param string $url_key
     * @param int $client_id
     * @param UPR $upR
     * @return string
     */
    private function view_partial_inv_attachments(string $_language, string $url_key, int $client_id, UPR $upR): string {
        $uploads = $upR->repoUploadUrlClientquery($url_key, $client_id);
        $dataReader = new DataOffsetPaginator($uploads);
        $invEdit = $this->user_service->hasPermission('editPayment');
        $invView = $this->user_service->hasPermission('viewPayment');
        return $this->view_renderer->renderPartialAsString('/invoice/inv/partial_inv_attachments', [
          'form' => new InvAttachmentsForm(),
          'invEdit' => $invEdit,
          'invView' => $invView,
          'partial_inv_attachments_list' => $this->view_renderer->renderPartialAsString('/invoice/inv/partial_inv_attachments_list', [
            'grid_summary' => $this->sR->grid_summary($dataReader, $this->translator, (int) $this->sR->get_setting('default_list_limit'), $this->translator->translate('invoice.invoice.attachment.list'), ''),
            'dataReader' => $dataReader,
            'invEdit' => $invEdit
          ]),
          'action' => ['inv/attachment', ['id' => $this->session->get('inv_id'), '_language' => $_language]]
        ]);
    }
    
    /**
     * @param string $_language
     * @param DLR $dlr
     * @param string $delivery_location_id
     * @return string
     */
    private function view_partial_delivery_location(string $_language, DLR $dlr, string $delivery_location_id) : string {
      if (!empty($delivery_location_id)) {
        $del = $dlr->repoDeliveryLocationquery($delivery_location_id);
        if (null!==$del) {
          return $this->view_renderer->renderPartialAsString('/invoice/inv/partial_inv_delivery_location',[
            'title' => $this->translator->translate('invoice.invoice.delivery.location'),
            'building_number' => $del->getBuildingNumber(),
            'address_1' => $del->getAddress_1(),
            'address_2' => $del->getAddress_2(),
            'city' => $del->getCity(),
            'state' => $del->getZip(),
            'country' => $del->getCountry(),
            'global_location_number' => $del->getGlobal_location_number(),
            'delivery_location_url' => $this->url_generator->generate('del/view',['_language' => $_language, 'id' => $delivery_location_id])
          ]);
        } //null!==$del   
      } else {
          return '';
      }
      return '';
    }

    /**
     * @param bool $show_buttons
     * @param int $id
     * @param ACIR $aciR
     * @param ACIIR $aciiR
     * @param PR $pR
     * @param PIR $piR
     * @param TaskR $taskR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param IR $iR
     * @param TRR $trR
     * @param UNR $uR
     * @param ITRR $itrR
     * @param InvAmount|null $inv_amount
     * @param bool $so_exists
     * @return string
     */
    private function view_partial_item_table(bool $show_buttons, int $id, ACIR $aciR, ACIIR $aciiR, PR $pR, PIR $piR, TaskR $taskR, IIR $iiR, IIAR $iiaR, IR $iR, TRR $trR, UNR $uR, ITRR $itrR, InvAmount|null $inv_amount): string {
        $inv = $this->inv($id, $iR, false);
        if ($inv) {
            $draft = ($inv->getStatus_id() == '1' ? true : false);
            $inv_tax_rates = (($itrR->repoCount((string) $this->session->get('inv_id')) > 0) ? $itrR->repoInvquery((string) $this->session->get('inv_id')) : null);
            // Allowances or Charges: DOCUMENT Level using $aciR
            $acis = $aciR->repoACIquery((string) $inv->getId());
            // Allowances or Charges: ITEM Level using $aciiR
            ////$inv_item_allowances_charges=$aciiR->repoACIquery((string)$inv->getId());
            ////$inv_item_allowances_charges_count=$aciiR->repoCount((string)$inv->getId());
            return $this->view_renderer->renderPartialAsString('/invoice/inv/partial_item_table', [
              'dl_acis' => $acis,
              'aciiR' => $aciiR,
              // Only make buttons available if status is draft
              'draft' => $draft,
              'piR' => $piR,
              'show_buttons' => $show_buttons,
              'numberhelper' => $this->number_helper,
              'products' => $pR->findAllPreloaded(),
              // Only tasks with complete or status of 3 are made available for selection
              'tasks' => $taskR->repoTaskStatusquery(3),
              'user_can_edit' => $this->user_service->hasPermission('editInv') ? true : false,
              'inv_items' => $iiR->repoInvquery((string) $this->session->get('inv_id')),
              'inv_item_amount' => $iiaR,
              'inv_tax_rates' => $inv_tax_rates,
              'inv_amount' => $inv_amount,
              'inv' => $iR->repoInvLoadedquery((string) $this->session->get('inv_id')),
              'tax_rates' => $trR->findAllPreloaded(),
              'units' => $uR->findAllPreloaded()
            ]);
        } // inv
        return '';
    }
    
    /**
     * Use: Toggle Button on Flash message reminder
     * @param string $_language
     * @return void
     */
    private function draft_flash(string $_language) : void {
      // Get the current draft setting
      $draft = $this->sR->get_setting('generate_invoice_number_for_draft');
      // Get the setting_id to allow for editing
      $setting = $this->sR->withKey('generate_invoice_number_for_draft');
      $setting_url = '';
      $setting_id = '';
      if (null!==$setting) {
        $setting_id = $setting->getSetting_id();
        // The route name has been simplified and differs from the action 'setting/inv_draft_has_number_switch'
        $setting_url = $this->url_generator->generate('setting/draft',['_language'=> $_language, 'setting_id'=>$setting_id]);
      }
      $level = $draft == '0' ? 'warning': 'info';
      $on_off = $draft == '0' ? 'off' : 'on';
      $message = ($this->translator->translate('invoice.invoice.draft.number.'
        .$on_off)) . str_repeat("&nbsp;", 2)
        .(!empty($setting_url) ? (string)Html::a(Html::tag('i','',['class'=>'fa fa-pencil']), $setting_url, ['class'=>'btn btn-primary']) 
        : '');
      $this->flash_message($level, $message);
    }
    
    /**
     * Purpose: Warning: Setting 'Mark invoices as sent when copy' should only be ON during development
     * Use: Toggle Button on Flash message reminder
     * @param string $_language
     * @return void
     */
    private function mark_sent_flash(string $_language) : void {
      // Get the current mark_invoice_sent_copy setting
      $mark_sent = $this->sR->get_setting('mark_invoices_sent_copy');
      // Get the setting_id to allow for editing
      $setting = $this->sR->withKey('mark_invoices_sent_copy');
      $setting_url = '';
      $setting_id = '';
      if (null!==$setting) {
        $setting_id = $setting->getSetting_id();
        $setting_url = $this->url_generator->generate('setting/mark_sent', ['_language' => $_language, 'setting_id' => $setting_id]);
      }
      $level = $mark_sent == '0' ? 'success': 'danger';
      $on_off = $mark_sent == '0' ? 'off' : 'on';
      /**
       * @link https://emojipedia.or/check-mark
       * @link https://emojipedia.org/cross-mark  ️
       */
      $message = ($mark_sent == '0' ? '✔' : '❌')  . ($this->translator->translate('invoice.invoice.mark.sent.'
        .$on_off)) . str_repeat("&nbsp;", 2)
        .(!empty($setting_url) ? (string)Html::a(Html::tag('i','',
                ['class'=>'fa fa-pencil']), 
                $setting_url, 
                ['class'=> $mark_sent == '0' ? 'btn btn-success' : 'btn btn-danger']) 
        : '');
      $this->flash_message($level, $message);
    } 
    
    /**
     * @param IR $iR
     * @return array
     */
    public function optionsDataClientsFilter(IR $iR) : array
    {
        $optionsDataClients = [];
        // Get all the invoices that have been made out to clients with user accounts
        $invs = $iR->findAllPreloaded();
        /**
         * @var Inv $inv
         */
        foreach ($invs as $inv) {
            $client = $inv->getClient();
            if (null!==$client) {
                if (strlen($client->getClient_full_name()) > 0) {
                    $fullName = $client->getClient_full_name();
                    $optionsDataClients[$fullName]  =  !empty($fullName) ? $fullName : '';
                }    
            }
        }
        return $optionsDataClients;
    }
    
    public function optionsDataYearMonthFilter() : array {
        $ym = []; 
        for ($y = 2024, $now = date('Y') + 10; $y <= $now; ++$y) {
            $months = ['01','02','03','04','05','06','07','08','09','10','11','12'];                     
            foreach ($months as $month) {
                $yearMonth = $y.'-'.$month;
                $ym[$yearMonth] = $yearMonth;
            }
        }
        return $ym;
    }
    
    public function optionsDataClientGroupFilter(CR $cR) : array {
        $clientGroup = [];
        $allClients = $cR->findAllPreloaded();
        /**
         * @var Client $client
         */
        foreach ($allClients as $client) {
            if (!in_array($client->getClient_group(), $clientGroup)) {
                /**
                 * @var string $client->getClient_group()
                 */
                $group = $client->getClient_group();
                if (null!==$group) {
                    $clientGroup[$group] = $group;
                }    
            }            
        }
        return $clientGroup;
    }
    
    /**
     * @param IR $iR
     * @return array
     */
    public function optionsDataInvNumberFilter(IR $iR) : array
    {
        $optionsDataInvNumbers = [];
        // Get all the invoices that have been made out to clients with user accounts
        $invs = $iR->findAllPreloaded();
        /**
         * @var Inv $inv
         */
        foreach ($invs as $inv) {
            $invNumber = $inv->getNumber();
            if (null!==$invNumber) {
                if (!in_array($invNumber, $optionsDataInvNumbers)) {
                    $optionsDataInvNumbers[$invNumber] = $invNumber;
                }
            }
        }
        return $optionsDataInvNumbers;
    }
    
    /**
     * Note function invs_status_with_sort_guest($iR, $status, $user_clients, $sort) has been used to generate $invs
     * @param \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface $invs
     * @return array
     */
    public function optionsDataInvNumberGuestFilter(\Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface $invs) : array
    {
        $optionsDataInvNumbers = [];
        /**
         * @var Inv $inv
         */
        foreach ($invs as $inv) {
            $invNumber = $inv->getNumber();
            if (null!==$invNumber) {
                if (!in_array($invNumber, $optionsDataInvNumbers)) {
                    $optionsDataInvNumbers[$invNumber] = $invNumber;
                }
            }
        }
        return $optionsDataInvNumbers;
    }
}
