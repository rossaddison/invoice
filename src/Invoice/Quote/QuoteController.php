<?php 
declare(strict_types=1); 

namespace App\Invoice\Quote;
// Entity's
use App\Invoice\Entity\CustomField;
use App\Invoice\Entity\EmailTemplate;
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
use App\Invoice\Quote\QuoteService;
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
use App\Invoice\Quote\MailerQuoteForm;
use App\Invoice\Quote\QuoteForm;
// Repositories
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\ClientCustom\ClientCustomRepository as CCR;
use App\Invoice\CustomValue\CustomValueRepository as CVR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository as DLR;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ETR;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\PaymentCustom\PaymentCustomRepository as PCR;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PMR;
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
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\User\UserRepository as UR;
// App Helpers
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
Use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\MailerHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\PdfHelper;
use App\Invoice\Helpers\TemplateHelper;
// Yii
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Html\Html;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\User\CurrentUser;
// Psr\Http
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class QuoteController
{
    private DataResponseFactoryInterface $factory;
    private Flash $flash;
    private NumberHelper $number_helper; 
    private InvAmountService $inv_amount_service;
    private InvService $inv_service;
    private InvCustomService $inv_custom_service;
    private InvItemService $inv_item_service;
    private InvItemAmountService $inv_item_amount_service;
    private InvTaxRateService $inv_tax_rate_service;
    private LoggerInterface $logger;    
    private MailerInterface $mailer;
    private PdfHelper $pdf_helper;
    private soAS $so_amount_service;
    private soCS $so_custom_service;
    private soIS $so_item_service;
    private soS $so_service;
    private soTRS $so_tax_rate_service;    
    private QuoteAmountService $quote_amount_service;
    private QuoteCustomService $quote_custom_service;
    private QuoteItemService $quote_item_service;    
    private QuoteService $quote_service;
    private QuoteTaxRateService $quote_tax_rate_service;
    private Session $session;
    private Translator $translator;
    private SR $sR;
    private UrlGenerator $url_generator;
    private UserService $user_service;
    private ViewRenderer $view_renderer;
    private WebControllerService $web_service;
    
    /**
     * 
     * @param DataResponseFactoryInterface $factory
     * @param InvAmountService $inv_amount_service
     * @param InvService $inv_service
     * @param InvCustomService $inv_custom_service
     * @param InvItemService $inv_item_service
     * @param InvItemAmountService $inv_item_amount_service
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
     * @param UserService $user_service
     * @param UrlGenerator $url_generator
     * @param ViewRenderer $view_renderer
     * @param WebControllerService $web_service
     */
    public function __construct(
        DataResponseFactoryInterface $factory,
        InvAmountService $inv_amount_service,
        InvService $inv_service,
        InvCustomService $inv_custom_service,
        InvItemService $inv_item_service,
        InvItemAmountService $inv_item_amount_service,
        InvTaxRateService $inv_tax_rate_service,
        LoggerInterface $logger,
        MailerInterface $mailer,
        soAS $so_amount_service,
        soCS $so_custom_service,
        soIS $so_item_service,
        soS $so_service,
        soTRS $so_tax_rate_service,    
        QuoteAmountService $quote_amount_service,
        QuoteCustomService $quote_custom_service,    
        QuoteItemService $quote_item_service,    
        QuoteService $quote_service,
        QuoteTaxRateService $quote_tax_rate_service,
        Session $session,
        SR $sR,
        Translator $translator,
        UserService $user_service,        
        UrlGenerator $url_generator,
        ViewRenderer $view_renderer,
        WebControllerService $web_service,                        
    )    
    {
        $this->factory = $factory;
        $this->flash = new Flash($session);
        $this->inv_amount_service = $inv_amount_service;
        $this->inv_service = $inv_service;
        $this->inv_custom_service = $inv_custom_service;
        $this->inv_item_service = $inv_item_service;
        $this->inv_item_amount_service = $inv_item_amount_service;
        $this->inv_tax_rate_service = $inv_tax_rate_service;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->number_helper = new NumberHelper($sR);
        $this->so_amount_service = $so_amount_service;
        $this->so_custom_service = $so_custom_service;
        $this->so_item_service = $so_item_service;
        $this->so_service = $so_service;
        $this->so_tax_rate_service = $so_tax_rate_service;
        $this->pdf_helper = new PdfHelper($sR, $session);
        $this->quote_amount_service = $quote_amount_service;
        $this->quote_custom_service = $quote_custom_service;
        $this->quote_item_service = $quote_item_service;        
        $this->quote_service = $quote_service;
        $this->quote_tax_rate_service = $quote_tax_rate_service;
        $this->session = $session;        
        $this->sR = $sR;
        $this->translator = $translator;
        $this->user_service = $user_service;
        $this->url_generator = $url_generator;
        $this->view_renderer = $view_renderer;
        if ($this->user_service->hasPermission('viewInv') && !$this->user_service->hasPermission('editInv')) {
            $this->view_renderer = $view_renderer->withControllerName('invoice')
                                                 ->withLayout('@views/invoice/layout/fullpage-loader.php')
                                                 ->withLayout('@views/layout/guest.php');
        }      
        if ($this->user_service->hasPermission('viewInv') && $this->user_service->hasPermission('editInv')) {
            $this->view_renderer = $view_renderer->withControllerName('invoice')
                                                 ->withLayout('@views/invoice/layout/fullpage-loader.php')
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
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function approve(Request $request, FormHydrator $formHydrator, CFR $cfR, 
                                           GR $gR, soIAS $soiaS, PR $pR, QAR $qaR, soAR $soaR, QCR $qcR,
                                           soIAR $soiaR, QIR $qiR, QR $qR, QTRR $qtrR, TRR $trR, UR $uR, UCR $ucR, UIR $uiR, UNR $unR) : \Yiisoft\DataResponse\DataResponse|Response {
        $body = $request->getQueryParams();
        $url_key = (string)$body['url_key'];
        $purchase_order_number = (string)$body['client_po_number'];
        $purchase_order_person = (string)$body['client_po_person'];
        if (!empty($url_key)) {
          if ($qR->repoUrl_key_guest_count($url_key) > 0) { 
            $quote = $qR->repoUrl_key_guest_loaded($url_key);
            if ($quote && null!==$quote->getId()) {
              $quote_id =  $quote->getId(); 
              $so_body = [                    
                'quote_id'=>$quote_id,
                'inv_id'=>0,
                'client_id'=>$quote->getClient_id(),
                'group_id'=>$this->sR->get_setting('default_sales_order_group'), 
                'status_id'=>4,
                'client_po_number'=>$purchase_order_number,
                'client_po_person'=>$purchase_order_person,
                'number'=>$gR->generate_number((int)$this->sR->get_setting('default_sales_order_group')),  
                'discount_amount'=>floatval($quote->getDiscount_amount()),
                'discount_percent'=>floatval($quote->getDiscount_percent()),
                // The quote's url will be the same for the po allowing for a trace 
                'url_key'=>$quote->getUrl_key(),
                'password'=> $quote->getPassword() ?? '',              
                'notes'=>$quote->getNotes()
              ];
              $this->flash_message('info', $this->translator->translate('invoice.salesorder.agree.to.terms'));  
              $form = new SoForm();
              $new_so = new SoEntity();
              if (($formHydrator->populate($form, $so_body) 
                && $form->isValid()) 
                && ($quote->getSo_id()===(string)0))
              {   
                $quote_id = $so_body['quote_id']; 
                $client_id = $so_body['client_id'];
                $user = $this->active_user($client_id, $uR, $ucR, $uiR);
                if (null!==$user) {
                  $this->so_service->addSo($user, $new_so, $form);            
                  // Ensure that the quote has a specific po and therefore cannot be copied again.
                  $new_so_id = $new_so->getId();
                  // Transfer each quote_item to so_item and the corresponding so_item_amount to so_item_amount for each item
                  if (null!==$new_so_id && $quote_id) {
                      $this->quote_to_so_quote_items($quote_id, $new_so_id, $soiaR, $soiaS, $pR, $qiR, $trR, $unR, $formHydrator);
                      $this->quote_to_so_quote_tax_rates($quote_id,$new_so_id,$qtrR, $formHydrator);
                      $this->quote_to_so_quote_custom($quote_id,$new_so_id, $qcR, $cfR, $formHydrator);
                      $this->quote_to_so_quote_amount($quote_id,$new_so_id, $qaR, $soaR);            
                      // Set the quote's sales order id so that it cannot be copied in the future
                      $quote->setSo_id($new_so_id);
                      // The quote has been approved with purchase order number
                      $quote->setStatus_id(4);
                      $qR->save($quote);
                      $parameters = ['success'=>1];
                      //return response to quote.js to reload page at location
                      return $this->factory->createResponse(Json::encode($parameters));
                  } // null!==$new_so_id
                } // null!==$user   
              } else {
                  $parameters = [
                     'success'=>0,
                  ];
                  //return response to quote.js to reload page at location
                  return $this->factory->createResponse(Json::encode($parameters));          
              }
            } // quote    
            return $this->web_service->getNotFoundResponse();
          } // if $qR
          return $this->web_service->getNotFoundResponse();
        } // null!==$url_key
        return $this->web_service->getNotFoundResponse();
    } // approve_with    
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param QR $qR
     * @return Response
     */
    public function reject(CurrentRoute $currentRoute, QR $qR) : Response {
        $url_key = $currentRoute->getArgument('url_key');
        if (null!==$url_key) {
            if ($qR->repoUrl_key_guest_count($url_key) > 0) { 
                $quote = $qR->repoUrl_key_guest_loaded($url_key);
                if ($quote) {
                    $quote_id = $quote->getId();
                    $quote->setStatus_id(5);
                    $qR->save($quote);
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_successful',
                    ['heading'=>$this->sR->trans('rejected'),'message'=>$this->sR->trans('record_successfully_updated'),'url'=>'quote/view','id'=>$quote_id]));  
                }    
                return $this->web_service->getNotFoundResponse();
            }
            return $this->web_service->getNotFoundResponse();
        }
        return $this->web_service->getNotFoundResponse();    
    }
    
    /**
     * 
     * @param Quote $quote
     * @return array
     */
    private function body(Quote $quote): array {
        $body = [
          'number'=>$quote->getNumber(),
            
          'id'=>$quote->getId(),
          'inv_id'=>$quote->getInv_id(),
          'so_id'=>$quote->getSo_id(),
            
          'user_id'=>$quote->getUser()?->getId(),
          'group_id'=>$quote->getGroup()?->getId(),
          'client_id'=>$quote->getClient()?->getClient_id(),          
         
          'date_created'=>$quote->getDate_created(),
          'date_modified'=>$quote->getDate_modified(),
          'date_expires'=>$quote->getDate_expires(),            
          
          'status_id'=>$quote->getStatus_id(),  
          
          'discount_amount'=>$quote->getDiscount_amount(),
          'discount_percent'=>$quote->getDiscount_percent(),
          'url_key'=>$quote->getUrl_key(),
          'password'=>$quote->getPassword(),
          'notes'=>$quote->getNotes(),  
        ];
        return $body;
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
    public function create_confirm(Request $request, FormHydrator $formHydrator, GR $gR, TRR $trR, UR $uR, UCR $ucR, UIR $uiR) : \Yiisoft\DataResponse\DataResponse
    {
        $body = $request->getQueryParams();
        $ajax_body = [
            'inv_id'=>null,
            'so_id'=>0,
            'client_id'=>(int)$body['client_id'],
            'group_id'=>(int)$body['quote_group_id'],
            'status_id'=>1,
            // Generate a number based on the GroupRepository Next id value and not on a newly generated quote_id 
            // if generate_quote_number_for_draft is set to 'yes' otherwise set to empty string ie. nothing.
            // Note: Clients cannot see draft quotes
            'number'=>$this->sR->get_setting('generate_quote_number_for_draft') === '1' ? $gR->generate_number((int)$body['quote_group_id'], true):'',
            'discount_amount'=>floatval(0),
            'discount_percent'=>floatval(0),
            'url_key'=>'',
            'password'=>$body['quote_password'],              
            'notes'=>'',
        ];
        $unsuccessful = $this->translator->translate('invoice.quote.creation.unsuccessful');
        $ajax_content = new QuoteForm();
        $quote = new Quote();
        if ($formHydrator->populate($ajax_content, $ajax_body) && $ajax_content->isValid()) {    
            $client_id = $ajax_body['client_id'];
            $user_client = $ucR->repoUserquery((string)$client_id);
            // Ensure that the client has only one (paying) user account otherwise reject this invoice
            // @see UserClientRepository function get_not_assigned_to_user which ensures that only
            // clients that have   NOT   been assigned to a user account are presented in the dropdown box for available clients
            // So this line is an extra measure to ensure that the invoice is being made out to the correct payer
            // ie. not more than one user is associated with the client.
            $user_client_count = $ucR->repoUserquerycount((string)$client_id);
            if (null!==$user_client && $user_client_count==1) {
              // Only one user account per client
              $user_id = $user_client->getUser_id();
              $user = $uR->findById($user_id);
              if (null!==$user) {
                $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                if (null!==$user_inv && $user_inv->getActive()) {
                  $saved_model = $this->quote_service->bothQuote($user, $quote, $ajax_content, $this->sR, $gR);
                  $model_id = $saved_model->getId();
                  if ($model_id) {
                    $this->quote_amount_service->initializeQuoteAmount(new QuoteAmount(), (int)$model_id);
                    $this->default_taxes($quote, $trR, $formHydrator);            
                    $parameters = ['success'=>1];
                    // Inform the user of generated invoice number for drat setting
                    $this->flash_message('info', 
                          $this->sR->get_setting('generate_quote_number_for_draft') === '1' 
                          ? $this->sR->trans('generate_quote_number_for_draft').'=>'.$this->sR->trans('yes') 
                          : $this->sR->trans('generate_quote_number_for_draft').'=>'.$this->sR->trans('no') );
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
              $this->flash_message('warning', $this->translator->translate('invoice.user.inv.more.than.one.assigned'));
            }
            return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => $unsuccessful]));
        } else {
            $parameters = [
               'success'=>0,
               'message' => $unsuccessful 
            ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        } 
    }
    
    /**
     * @param FormHydrator $formHydrator
     * @param array $array
     * @param int $quote_id
     * @param QCR $qcR
     * @return void
     */
    public function custom_fields(FormHydrator $formHydrator, array $array, int $quote_id, QCR $qcR) : void
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
            foreach ($db_array as $key => $value){
                if ($value !=='') { 
                    $ajax_custom = new QuoteCustomForm();
                    $quote_custom = [];
                    $quote_custom['quote_id']=$quote_id;
                    $quote_custom['custom_field_id']=$key;
                    $quote_custom['value']=$value; 
                    if ($qcR->repoQuoteCustomCount((string)$quote_id, $key) > 0) {
                       $model = $qcR->repoFormValuequery((string)$quote_id, $key);  
                    } else {
                       $model = new QuoteCustom(); 
                    }
                    if (null!==$model && $formHydrator->populate($ajax_custom, $quote_custom) && $ajax_custom->isValid()) {
                        $this->quote_custom_service->saveQuoteCustom($model, $ajax_custom);
                    }
                }             
            }
        }    
    }
    
    /**
     * 
     * @param Quote $quote
     * @param TRR $trR
     * @param FormHydrator $formHydrator
     * @return void
     */
    public function default_taxes(Quote $quote, TRR $trR, FormHydrator $formHydrator): void{
        if ($trR->repoCountAll() > 0) {
            $taxrates = $trR->findAllPreloaded();
            /** @var TaxRate $taxrate */
            foreach ($taxrates as $taxrate) {
                $taxrate->getTax_rate_default()  == 1 ? $this->default_tax_quote($taxrate, $quote, $formHydrator) : '';
            }
        }
    }
    
    /**
     * @param TaxRate|null $taxrate
     * @param Quote $quote
     * @param FormHydrator $formHydrator
     * @return void
     */
    public function default_tax_quote(TaxRate|null $taxrate, Quote $quote, FormHydrator $formHydrator) : void {
        $quote_tax_rate_form = new QuoteTaxRateForm();
        $quote_tax_rate = [];
        $quote_tax_rate['quote_id'] = $quote->getId();
        if (null!==$taxrate) {
            $quote_tax_rate['tax_rate_id'] = $taxrate->getTax_rate_id();
        } else {
            $quote_tax_rate['tax_rate_id'] = 1;
        }    
        $quote_tax_rate['include_item_tax'] = 0;
        $quote_tax_rate['quote_tax_rate_amount'] = 0;
        if ($formHydrator->populate($quote_tax_rate_form, $quote_tax_rate) && $quote_tax_rate_form->isValid()) { 
          $this->quote_tax_rate_service->saveQuoteTaxRate(new QuoteTaxRate(), $quote_tax_rate_form);
        }        
    }
    
   
    /**
     * 
     * @param CurrentRoute $currentRoute
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
    public function delete(CurrentRoute $currentRoute, QuoteRepository $quoteRepo, 
                           QCR $qcR, QuoteCustomService $qcS, QIR $qiR, QuoteItemService $qiS, QTRR $qtrR,
                           QuoteTaxRateService $qtrS, QAR $qaR, QuoteAmountService $qaS): Response {
        try {
            $quote = $this->quote($currentRoute, $quoteRepo);
            if ($quote) {
                $this->quote_service->deleteQuote($quote, $qcR, $qcS, $qiR, $qiS, $qtrR, $qtrS, $qaR, $qaS); 
                $this->flash_message('info','Deleted.');
                return $this->web_service->getRedirectResponse('quote/index'); 
            }
            return $this->web_service->getNotFoundResponse();
	} catch (\Exception $e) {
            unset($e);
            $this->flash_message('danger', 'Cannot delete.');
            return $this->web_service->getRedirectResponse('quote/index'); 
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     */
    public function delete_quote_item(CurrentRoute $currentRoute, QIR $qiR)
                                          : \Yiisoft\DataResponse\DataResponse {
        try {            
            $this->quote_item_service->deleteQuoteItem($this->quote_item($currentRoute,$qiR));
        } catch (\Exception $e) {
            unset($e);
            $this->flash_message('danger', $this->translator->translate('invoice.quote.item.cannot.delete'));
        }
        $quote_id = (string)$this->session->get('quote_id');
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_successful',
        ['heading'=>'','message'=>$this->sR->trans('record_successfully_deleted'),'url'=>'quote/view','id'=>$quote_id]));  
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QTRR $quotetaxrateRepository
     */
    public function delete_quote_tax_rate(CurrentRoute $currentRoute, QTRR $quotetaxrateRepository)
                                          : \Yiisoft\DataResponse\DataResponse {
        try {            
            $this->quote_tax_rate_service->deleteQuoteTaxRate($this->quotetaxrate($currentRoute,$quotetaxrateRepository));
        } catch (\Exception $e) {
            unset($e);
            $this->flash_message('danger', $this->translator->translate('invoice.quote.tax.rate.cannot.delete'));
        }
        $quote_id = (string)$this->session->get('quote_id');
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
        ['heading'=>$this->sR->trans('quote_tax_rate'),'message'=>$this->sR->trans('record_successfully_deleted'),'url'=>'quote/view','id'=>$quote_id]));  
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param QR $quoteRepo
     * @param IR $invRepo
     * @param CR $clientRepo
     * @param DLR $delRepo
     * @param GR $groupRepo
     * @param CFR $cfR
     * @param CVR $cvR
     * @param QCR $qcR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        QR $quoteRepo,                        
                        IR $invRepo,
                        CR $clientRepo,
                        DLR $delRepo,
                        GR $groupRepo,
                        CFR $cfR,
                        CVR $cvR,
                        QCR $qcR,
                        UR $uR,
                        UCR $ucR,
                        UIR $uiR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $quote = $this->quote($currentRoute, $quoteRepo, true);
        if (null!==$quote && !empty($quote->getId())) {
            $quote_id = $quote->getId(); 
            $action = ['quote/edit', ['id' => $quote_id]];
            $dels = $delRepo->repoClientquery($quote->getClient_id());
            $parameters = [
                'title' => '',
                'action' => $action,
                'errors' => [],
                'body' => $this->body($quote),
                'head'=>$head,
                's'=>$this->sR,
                'invs'=>$invRepo->findAllPreloaded(),
                'clients'=>$clientRepo->findAllPreloaded(),
                'dels'=>$dels,
                'groups'=>$groupRepo->findAllPreloaded(),
                'numberhelper' => new NumberHelper($this->sR),
                'quote_statuses'=> $quoteRepo->getStatuses($this->sR),            
                'cvH'=> new CVH($this->sR),
                'custom_fields'=>$cfR->repoTablequery('quote_custom'),
                // Applicable to normally building up permanent selection lists eg. dropdowns
                'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                // There will initially be no custom_values attached to this quote until they are filled in the field on the form
                'quote_custom_values' => null!==$quote_id ? $this->quote_custom_values($quote_id, $qcR) : null,
                'no_delivery_locations' => $delRepo->repoClientCount($quote->getClient_id()) > 0 ? '' : $this->flash_message('warning', $this->translator->translate('invoice.quote.delivery.location.none')),
                'quote' => $quote,
                'del_count'=> $delRepo->repoClientCount($quote->getClient_id()),
                'alert'=>$this->alert(),
            ];
            if ($request->getMethod() === Method::POST) {   
                $body = (array)$request->getParsedBody();
                $returned_form = $this->edit_save_form_fields($body, $currentRoute, $formHydrator, $quoteRepo, $groupRepo, $uR, $ucR, $uiR);
                $parameters['body'] = $body;
                $parameters['errors']=$returned_form->getValidationResult();
                $this->edit_save_custom_fields($body, $formHydrator, $qcR, $quote_id);            
                return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_successful',
                ['heading'=>'','message'=>
                    $this->sR->trans('record_successfully_updated'),
                    'url'=>'quote/view','id'=>$quote_id]));  
            }
            return $this->view_renderer->render('/invoice/quote/_form', $parameters);
        } // $quote
        return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * @param array $body
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param QR $quoteRepo
     * @param GR $gR
     * @param UR $uR
     * @param UCR $ucR
     * @param UIR $uiR
     * @return QuoteForm
     */
    public function edit_save_form_fields(array $body, CurrentRoute $currentRoute, FormHydrator $formHydrator, QR $quoteRepo, GR $gR, UR $uR, UCR $ucR, UIR $uiR) : QuoteForm {
      $form = new QuoteForm();
      // false => use Loaded query to engage relations
      $quote = $this->quote($currentRoute, $quoteRepo, false);
      if ($quote && $formHydrator->populate($form, $body) && $form->isValid()) {
        $client_id = $quote->getClient_id();
        $user = $this->active_user($client_id, $uR, $ucR, $uiR);
        if (null!==$user) {
          $this->quote_service->bothQuote($user, $quote, $form, $this->sR, $gR);
        }  
      }
      return $form;
    }
    
    /**
     * @param array|null|object $parse
     * @param null|string $quote_id
     */
    public function edit_save_custom_fields(array|object|null $parse, FormHydrator $formHydrator, QCR $qcR, string|null $quote_id): void {
        /** 
         * @var array $custom
         */
        $custom = $parse['custom'] ?? [];
        /** 
         * @var string $value 
         * @var int $custom_field_id
         * @var int $quote_id
         */
        foreach ($custom as $custom_field_id => $value) {
            if (($qcR->repoQuoteCustomCount((string)$quote_id, (string)$custom_field_id)) == 0) {
                $quote_custom = new QuoteCustom();
                $quote_custom_input = [
                    'quote_id'=>$quote_id,
                    'custom_field_id'=>$custom_field_id,
                    'value'=>$value
                ];
                $form = new QuoteCustomForm();
                if ($formHydrator->populate($form, $quote_custom_input) && $form->isValid())
                {
                    $this->quote_custom_service->saveQuoteCustom($quote_custom, $form);     
                }
            } else {
                $quote_custom = $qcR->repoFormValuequery((string)$quote_id, (string)$custom_field_id);
                if ($quote_custom) {
                    $quote_custom_input = [
                        'quote_id'=>$quote_id,
                        'custom_field_id'=>$custom_field_id,
                        'value'=>$value
                    ];
                    $form = new QuoteCustomForm();
                    if ($formHydrator->populate($form, $quote_custom_input) && $form->isValid())
                    {
                        $this->quote_custom_service->saveQuoteCustom($quote_custom, $form);     
                    }
                }
            } 
        }
    }
    
    /**
     * @psalm-param 'pdf' $type
     */
    public function email_get_quote_templates(string $type = 'pdf') : array
    {
        return $this->sR->get_quote_templates($type);
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
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
    public function email_stage_0(ViewRenderer $head, 
                                  CurrentRoute $currentRoute, 
                                  CCR $ccR, CFR $cfR, CVR $cvR, 
                                  ETR $etR, ICR $icR, 
                                  QR $qR, PCR $pcR, SOCR $socR, QCR $qcR, UIR $uiR) : Response {
        $mailer_helper = new MailerHelper($this->sR, $this->session, $this->translator,  $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        if (!$mailer_helper->mailer_configured()) {
            $this->flash_message('warning', $this->sR->trans('email_not_configured'));
            return $this->web_service->getRedirectResponse('quote/index');
        }
        $quote_entity = $this->quote($currentRoute, $qR, true);
        if ($quote_entity) {
            $quote_id = $quote_entity->getId();
            $quote = $qR->repoQuoteUnLoadedquery((string)$quote_id);
            if ($quote) {
                // Get all custom fields
                $custom_fields = [];
                $custom_tables = [
                    'client_custom' => 'client',
                    'inv_custom' => 'invoice',
                    'payment_custom' => 'payment',
                    'quote_custom' => 'quote',
                    'salesorder_custom' => 'salesorder'
                    // TODO 'user_custom' => 'user',
                ];
                foreach (array_keys($custom_tables) as $table) {
                    $custom_fields[$table] = $cfR->repoTablequery($table);
                }        
                if ($template_helper->select_email_quote_template() == '') {
                    $this->flash_message('warning', $this->translator->translate('invoice.quote.email.templates.not.configured'));
                    return $this->web_service->getRedirectResponse('setting/tab_index');
                }
                $setting_status_email_template =  $etR->repoEmailTemplatequery($template_helper->select_email_quote_template()) 
                                               ?: null;
                null===$setting_status_email_template ? $this->flash_message('info',
                                                  $this->sR->trans('default_email_template').'=>'.
                                                  $this->sR->trans('not_set')) : '';

                empty($template_helper->select_pdf_quote_template()) ? $this->flash_message('info',
                                                  $this->sR->trans('default_pdf_template').'=>'.
                                                  $this->sR->trans('not_set')) : '';
                $parameters = [
                    'head'=> $head,
                    'action' => ['quote/email_stage_2', ['id' => $quote_id]],
                    'alert'=>$this->alert(),
                    'auto_template' => null!== $setting_status_email_template 
                                           ? $this->get_inject_email_template_array($setting_status_email_template) 
                                           : [],
                    'setting_status_pdf_template' => $template_helper->select_pdf_quote_template(), 
                    'email_templates' => $etR->repoEmailTemplateType('quote'),
                    'dropdown_titles_of_email_templates' => $this->email_templates($etR),
                    'userinv' => $uiR->repoUserInvUserIdcount($quote->getUser_id()) > 0 ? $uiR->repoUserInvUserIdquery($quote->getUser_id()) : null,
                    'quote' => $quote,
                    'pdf_templates' => $this->email_get_quote_templates('pdf'),
                    'template_tags' => $this->view_renderer->renderPartialAsString('/invoice/emailtemplate/template-tags',[
                            'custom_fields'=> $custom_fields,                                         
                            'template_tags_inv'=>'',  
                            'template_tags_quote'=>$this->view_renderer->renderPartialAsString('/invoice/emailtemplate/template-tags-quote', [
                                's' => $this->sR,
                                'custom_fields_quote_custom'=>$custom_fields['quote_custom']
                            ]),
                    ]),
                    'form' => new MailerQuoteForm(),
                    'custom_fields' => $custom_fields,
                ];   
                return $this->view_renderer->render('/invoice/quote/mailer_quote', $parameters);
            } // quote    
            return $this->web_service->getRedirectResponse('quote/index');
        } // quote_entity
        return $this->web_service->getRedirectResponse('quote/index');
    }
    
    /**
     * @param EmailTemplate $email_template
     * @return array
     */
    public function get_inject_email_template_array(EmailTemplate $email_template) : array {
      $email_template_array = [
        'body' => Json::htmlEncode($email_template->getEmail_template_body()),
        'subject'=> $email_template->getEmail_template_subject() ?? '',
        'from_name'=> $email_template->getEmail_template_from_name() ?? '',
        'from_email'=> $email_template->getEmail_template_from_email() ?? '',
        'cc'=> $email_template->getEmail_template_cc() ?? '',
        'bcc'=> $email_template->getEmail_template_bcc() ?? '',
        'pdf_template'=> null!==$email_template->getEmail_template_pdf_template()? $email_template->getEmail_template_pdf_template(): '',
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
    public function email_templates(ETR $etR) : array {
        $email_templates = $etR->repoEmailTemplateType('quote');
        $data = [];
        /** @var EmailTemplate $email_template */
        foreach ($email_templates as $email_template) {
            $data[] = $email_template->getEmail_template_title();
        }
        return $data;
    }
    
    /**
     * 
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
        
    public function email_stage_1 (string|null $quote_id, 
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
                                  ViewRenderer $viewrenderer) : bool
    {
        // All custom repositories, including icR have to be initialised.
        $template_helper = new TemplateHelper($this->sR, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
        $mailer_helper = new MailerHelper($this->sR, $this->session, $this->translator,  $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);       
        if ($quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);        
            $quote_custom_values = $this->quote_custom_values($quote_id, $qcR);
            $quote_entity = $qR->repoCount($quote_id) > 0 ? $qR->repoQuoteUnLoadedquery($quote_id) : null;
            if ($quote_entity) {
                $stream = false;   
                /** @var string $pdf_template_target_path */
                $pdf_template_target_path = $this->pdf_helper->generate_quote_pdf($quote_id, $quote_entity->getUser_id(), $stream, true, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $viewrenderer); 
                if ($pdf_template_target_path) {
                    $mail_message = $template_helper->parse_template($quote_id, false, $email_body, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $soR, $uiR);
                    $mail_subject = $template_helper->parse_template($quote_id, false, $subject, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $soR, $uiR);
                    $mail_cc = $template_helper->parse_template($quote_id, false, $cc, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $soR, $uiR);
                    $mail_bcc = $template_helper->parse_template($quote_id, false, $bcc, $cR, $cvR, $iR, $iaR, $qR,  $qaR, $soR, $uiR);
                     // from[0] is the from_email and from[1] is the from_name    
                    /**
                     * @var string $from[0]
                     * @var string $from[1]
                     */
                    $mail_from = 
                        array($template_helper->parse_template($quote_id, false, $from[0], $cR, $cvR, $iR, $iaR, $qR,  $qaR, $soR, $uiR), 
                              $template_helper->parse_template($quote_id, false, $from[1], $cR, $cvR, $iR, $iaR, $qR,  $qaR, $soR, $uiR));
                    // mail_from[0] is the from_email and mail_from[1] is the from_name
                    return $mailer_helper->yii_mailer_send($mail_from[0], $mail_from[1], 
                                                           $to, $mail_subject, $mail_message, $mail_cc, $mail_bcc, $attachFiles, $pdf_template_target_path,
                                                           $uiR);
                } // pdf_template_target_path    
            } // quote_entity
            return false;
        } // quote_id    
        return false;
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
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
     * @param QR $qR
     * @param QAR $qaR
     * @param QCR $qcR
     * @param UIR $uiR
     * @return Response
     */
    
    public function email_stage_2(Request $request, 
                                  CurrentRoute $currentRoute, 
                                  CR $cR, CCR $ccR, CFR $cfR, CVR $cvR, 
                                  GR $gR, 
                                  IAR $iaR, QIAR $qiaR, ICR $icR, QIR $qiR, IR $iR, QTRR $qtrR, 
                                  PCR $pcR, SOCR $socR, QR $qR, QAR $qaR, QCR $qcR, SOR $soR, UIR $uiR) : Response 
    {
        $quote_id = $currentRoute->getArgument('id'); 
        if (null!==$quote_id) {
            $mailer_helper = new MailerHelper($this->sR, $this->session, $this->translator,  $this->logger, $this->mailer, $ccR, $qcR, $icR, $pcR, $socR, $cfR, $cvR);
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $body['btn_cancel'] = 0; 
                if (!$mailer_helper->mailer_configured()) {
                    $this->flash_message('warning', $this->sR->trans('email_not_configured'));
                    return $this->web_service->getRedirectResponse('quote/index');
                }
                
                /**
                 * @var array $body['MailerQuoteForm']
                 */
                $to = (string)$body['MailerQuoteForm']['to_email'] ?: '';
                if (empty($to)) {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_to_address_missing'),'url'=>'quote/view','id'=>$quote_id]));  
                }
                                
                /**
                 * @var array $from
                 */
                $from = [
                    $body['MailerQuoteForm']['from_email'] ?? '',
                    $body['MailerQuoteForm']['from_name'] ?? '',
                ];
                
                
                if (empty($from[0])) {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_to_address_missing'),'url'=>'quote/view','id'=>$quote_id]));  
                }

                /**  
                 * @var string $subject 
                 */
                $subject = $body['MailerQuoteForm']['subject'] ?? '';
                  /**  @var string $body */
                $email_body = (string)$body['MailerQuoteForm']['body'];

                if (strlen($email_body) !== strlen(strip_tags($email_body))) {
                    $email_body = htmlspecialchars_decode($email_body); 
                } else {
                    $email_body = htmlspecialchars_decode(nl2br($email_body));
                }
                
                /**  
                 * @var string $cc 
                 */
                $cc = $body['MailerQuoteForm']['cc'] ?? '';
                /**  
                 * @var string $bcc 
                 */
                $bcc = $body['MailerQuoteForm']['bcc'] ?? '';

                $attachFiles = $request->getUploadedFiles();

                $this->generate_quote_number_if_applicable($quote_id, $qR, $this->sR, $gR);
                // Custom fields are automatically included on the quote
                if ($this->email_stage_1($quote_id, $from, $to, $subject, $email_body, $cc, $bcc, $attachFiles, $cR, $ccR, $cfR,  $cvR, $iaR, $icR, $qiaR, $qiR, $iR, $qtrR, $pcR, $socR, $qR, $qaR, $qcR, $soR, $uiR, $this->view_renderer)) {
                    $this->sR->quote_mark_sent($quote_id, $qR);            
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/quote_message',
                    ['heading'=>'','message'=>$this->sR->trans('email_successfully_sent'),'url'=>'quote/view','id'=>$quote_id]));  
                } else {
                    return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->translator->translate('invoice.invoice.email.not.sent.successfully'),
                     'url'=>'quote/view','id'=>$quote_id]));  
                }
            } // is_array
            return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->translator->translate('invoice.invoice.email.not.sent.successfully'),
                     'url'=>'quote/view','id'=>$quote_id]));
        } // quote_id   
        return $this->factory->createResponse($this->view_renderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'','message'=>$this->translator->translate('invoice.invoice.email.not.sent.successfully'),
                     'url'=>'quote/view','id'=>$quote_id]));
    } 
    
    /**
     * @param string $quote_id
     * @param QR $qR
     * @param SR $sR
     * @param GR $gR
     * @return void
     */
    public function generate_quote_number_if_applicable(string $quote_id, QR $qR, SR $sR, GR $gR) : void
    {
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if (!empty($quote) && ($quote->getStatus_id() == 1) && ($quote->getNumber() == "")) {
                // Generate new quote number if applicable
                if ($sR->get_setting('generate_quote_number_for_draft') == 0) {
                    $quote_number = (string)$qR->get_quote_number($quote->getGroup_id(), $gR);
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
     * @param CurrentRoute $currentRoute
     * @param QR $qR
     * @param UCR $ucR
     * @param UIR $uiR
     */
    public function guest(Request $request, QAR $qaR, CurrentRoute $currentRoute,
                          QR $qR, UCR $ucR, UIR $uiR) : \Yiisoft\DataResponse\DataResponse|Response {
        $query_params = $request->getQueryParams();
        $pageNum = (int)$currentRoute->getArgument('page', '1');
         //status 0 => 'all';
        $status = (int)$currentRoute->getArgument('status', '0');
        /**
         * @var string|null $query_params['sort']
         */
        $sort_string = $query_params['sort'] ?? '-id';
        $sort = Sort::only(['status_id','number','date_created','date_expires','id','client_id'])->withOrderString($sort_string); 
                
        // Get the current user and determine from (@see Settings...User Account) whether they have been given 
        // either guest or admin rights. These rights are unrelated to rbac and serve as a second
        // 'line of defense' to support role based admin control.
         
        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->user_service->getUser();         
        if ($user) {
            // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount((string)$user->getId()) > 0 
                     ? $uiR->repoUserInvUserIdquery((string)$user->getId()) 
                     : null);
            if ($userinv) {
                // Determine what clients have been allocated to this user (@see Settings...User Account) 
                // by looking at UserClient table        

                // eg. If the user is a guest-accountant, they will have been allocated certain clients
                // A user-quest-accountant will be allocated a series of clients
                // A user-guest-client will be allocated their client number by the administrator so that
                // they can view their quotes when they log in
                $user_clients = $ucR->get_assigned_to_user((string)$user->getId());
                $quotes = $this->quotes_status_with_sort_guest($qR, $status, $user_clients, $sort);
                $paginator = (new OffsetPaginator($quotes))
                ->withPageSize((int)$this->sR->get_setting('default_list_limit'))
                ->withCurrentPage($pageNum);
                $quote_statuses = $qR->getStatuses($this->sR);
                $parameters = [            
                    'alert'=> $this->alert(),
                    'qaR'=> $qaR,
                    'quotes' => $quotes,
                    'grid_summary'=> $this->sR->grid_summary(
                         $paginator, 
                         $this->translator, 
                         (int)$this->sR->get_setting('default_list_limit'), 
                         $this->translator->translate('invoice.quotes'), 
                         $qR->getSpecificStatusArrayLabel((string)$status)
                    ),
                    'quote_statuses'=> $quote_statuses,            
                    'max'=> (int) $this->sR->get_setting('default_list_limit'),
                    'page'=> $pageNum,
                    'paginator'=> $paginator,
                    's'=> $this->sR,
                    'sortOrder' => $sort_string, 
                    'status'=> $status,
                ];    
                return $this->view_renderer->render('/invoice/quote/guest', $parameters);  
            } // userinv
            return $this->web_service->getNotFoundResponse();
        } //user
        return $this->web_service->getNotFoundResponse();
    }
        
    // Only users with editInv permission can access this index. Refer to config/routes accesschecker.
    
    /**
     * @param Request $request
     * @param QAR $qaR
     * @param QR $quoteRepo
     * @param CR $clientRepo
     * @param GR $groupRepo
     * @param SOR $soR
     * @param CurrentRoute $currentRoute
     * @param sR $sR
     * @param UCR $ucR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(Request $request, QAR $qaR, QR $quoteRepo, CR $clientRepo, GR $groupRepo, SOR $soR, CurrentRoute $currentRoute, sR $sR, UCR $ucR): \Yiisoft\DataResponse\DataResponse
    {
        // If the language dropdown changes
        $this->session->set('_language', $currentRoute->getArgument('_language'));
        $active_clients = $ucR->getClients_with_user_accounts();
        $clients = $clientRepo->repoUserClient($active_clients);
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page','1');
        //status 0 => 'all';
        $status = (int)$currentRoute->getArgument('status','0');
        /** @psalm-suppress MixedAssignment $sort_string */
        $sort_string = $query_params['sort'] ?? '-id';
        $sort = Sort::only(['id','status_id','number','date_created','date_expires','client_id'])
                    // (@see vendor\yiisoft\data\src\Reader\Sort
                    // - => 'desc'  so -id => default descending on id
                    // Show the latest quotes first => -id
                    /** @psalm-suppress MixedArgument $sort_string */
                    ->withOrderString((string)$sort_string);
        $quotes = $this->quotes_status_with_sort($quoteRepo, $status, $sort); 
        $paginator = (new OffsetPaginator($quotes))
        ->withPageSize((int)$this->sR->get_setting('default_list_limit'))
        ->withCurrentPage($page)
        ->withNextPageToken((string) $page);    
        $quote_statuses = $quoteRepo->getStatuses($this->sR);
        $parameters = [
            'page' => $page,
            'status' => $status,
            'paginator' => $paginator,
            'sortOrder' => $query_params['sort'] ?? '', 
            'alert'=>$this->alert(),
            'client_count'=>$clientRepo->count(),
            'grid_summary'=> $sR->grid_summary(
                 $paginator, 
                 $this->translator, 
                 (int)$sR->get_setting('default_list_limit'), 
                 $this->translator->translate('invoice.quotes'), 
                 $quoteRepo->getSpecificStatusArrayLabel((string)$status)
            ),
            'quote_statuses'=> $quote_statuses,
            'max'=>(int)$sR->get_setting('default_list_limit'),
            'qaR'=>$qaR,
            'soR'=>$soR,
            'modal_create_quote'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_create_quote',[
              // Only make available clients that have linked user accounts => use user_client repository
              'clients'=>$clients,
              's'=>$this->sR,
              'invoice_groups'=>$groupRepo->findAllPreloaded(),
              'datehelper'=> new DateHelper($this->sR)
            ]),           
        ];  
        return $this->view_renderer->render('/invoice/quote/index', $parameters);  
    }
        
    /**
     * 
     * @param string $items
     * @param FormHydrator $formHydrator
     * @param string $quote_id
     * @param int $order
     * @param PR $pR
     * @param QIR $qir
     * @param QIAR $qiar
     * @param TRR $trr
     * @param UNR $unR
     * @return void
     */
    
    public function  items(string $items, FormHydrator $formHydrator, string $quote_id, int $order ,
                                     PR $pR, QIR $qir, QIAR $qiar, TRR $trr, UNR $unR) 
                                     : void { 
        /** @var array $item */
        foreach (Json::decode($items) as $item) {
            if ($item['item_name'] && (empty($item['item_id'])||!isset($item['item_id']))) {
                $ajax_content = new QuoteItemForm();
                $quoteitem = [];
                $quoteitem['name'] = (string)$item['item_name'];
                $quoteitem['quote_id']=(string)$item['quote_id'];
                $quoteitem['tax_rate_id']=(string)$item['item_tax_rate_id'];
                $quoteitem['product_id']=(string)($item['item_product_id']);
                //product_id used later to get description and name of product.
                $quoteitem['date_added']=new \DateTimeImmutable();
                $quoteitem['quantity']=(float)($item['item_quantity'] ? $this->number_helper->standardize_amount($item['item_quantity']) : floatval(0));
                $quoteitem['price']= (float)($item['item_price'] ? $this->number_helper->standardize_amount($item['item_price']) : floatval(0));
                $quoteitem['discount_amount']= (float)(($item['item_discount_amount']) ? $this->number_helper->standardize_amount($item['item_discount_amount']) : floatval(0));
                $quoteitem['order']= $order;
                $quoteitem['product_unit']=$unR->singular_or_plural_name((string)$item['item_product_unit_id'],(int)$item['item_quantity']);
                $quoteitem['product_unit_id']= (string)($item['item_product_unit_id'] ?: null);                
                unset($item['item_id']);
                ($formHydrator->populate($ajax_content, $quoteitem) && $ajax_content->isValid()) ? 
                $this->quote_item_service->addQuoteItem(new QuoteItem(), $ajax_content, $quote_id, $pR, $qiar, new QIAS($qiar),$unR, $trr) : false;                 
                $order++;      
            }
            // Evaluate current items
            if ($item['item_name'] && (!empty($item['item_id'])||isset($item['item_id']))) {
                $unedited = $qir->repoQuoteItemquery((string)$item['item_id']);  
                if ($unedited) {
                    $ajax_content = new QuoteItemForm();
                    $quoteitem = [];
                    $quoteitem['name'] = (string)$item['item_name'];
                    $quoteitem['quote_id']=(string)$item['quote_id'];
                    $quoteitem['tax_rate_id']=(string)$item['item_tax_rate_id'] ?: null;
                    $quoteitem['product_id']=(string)($item['item_product_id'] ?: null);
                    //product_id used later to get description and name of product.
                    $quoteitem['date_added']=new \DateTimeImmutable();
                    $quoteitem['quantity']=(float)($item['item_quantity'] ? $this->number_helper->standardize_amount($item['item_quantity']) : floatval(0));
                    $quoteitem['price']=(float)($item['item_price'] ? $this->number_helper->standardize_amount($item['item_price']) : floatval(0));
                    $quoteitem['discount_amount']= (float)($item['item_discount_amount'] ? $this->number_helper->standardize_amount($item['item_discount_amount']) : floatval(0));
                    $quoteitem['order']= $order;
                    $quoteitem['product_unit']=$unR->singular_or_plural_name((string)$item['item_product_unit_id'],(int)$item['item_quantity']);
                    $quoteitem['product_unit_id']= (int)($item['item_product_unit_id'] ? $item['item_product_unit_id'] : null);                
                    unset($item['item_id']);
                    ($formHydrator->populate($ajax_content, $quoteitem) && $ajax_content->isValid()) ? 
                    $this->quote_item_service->saveQuoteItem($unedited, $ajax_content, $quote_id, $pR, $unR) : false;             
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
        $client = $cR->repoClientquery((string)$body['client_id']);
        $parameters = [
            'success'=>1,
            // Set a client id on quote/view.php so that details can be saved later. 
            'pre_save_client_id'=>$body['client_id'],                
            'client_address_1'=>($client->getClient_address_1() ?? '').'<br>',
            'client_address_2'=>($client->getClient_address_2() ?? '').'<br>',
            'client_townline'=>($client->getClient_city() ?? '').'<br>'.($client->getClient_state() ?? '').'<br>'.($client->getClient_zip() ?? '').'<br>',
            'client_country'=>$client->getClient_country() ?? '',
            'client_phone'=> $sR->trans('phone').'&nbsp;'.($client->getClient_phone() ?? ''),
            'client_mobile'=>$sR->trans('mobile').'&nbsp;'.($client->getClient_mobile() ?? ''),
            'client_fax'=>$sR->trans('fax').'&nbsp;'.($client->getClient_fax() ?? ''),
            'client_email'=>$sR->trans('email').'&nbsp;'. Html::link($client->getClient_email()),                
            // Reset the a href id="after_client_change_url" link to the new client url
            'after_client_change_url'=>'/invoice/client/view/'.(string)$body['client_id'],
            'after_client_change_name'=>$client->getClient_name(),
        ];
        // return parameters to quote.js:client_change_confirm ajax success function for processing
        return $this->factory->createResponse(Json::encode($parameters));  
    }
    
    // Called from quote.js quote_to_pdf_confirm_with_custom_fields
    
    /**
     * @param CurrentRoute $currentRoute
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
     * @param Request $request
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function pdf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, QAR $qaR, QCR $qcR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR) : \Yiisoft\DataResponse\DataResponse|Response {
        // include is a value of 0 or 1 passed from quote.js function quote_to_pdf_with(out)_custom_fields indicating whether the user
        // wants custom fields included on the quote or not.
        $include = $currentRoute->getArgument('include');        
        $quote_id = (string)$this->session->get('quote_id');
        $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
        if ($quote_amount) {
            $custom = (($include===(string)1) ? true : false);
            $quote_custom_values = $this->quote_custom_values((string)$this->session->get('quote_id'),$qcR);
            // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
            $pdfhelper = new PdfHelper($sR, $this->session);
            // The quote will be streamed ie. shown, and not archived
            $stream = true;
            // If we are required to mark quotes as 'sent' when sent.
            if ($sR->get_setting('mark_quotes_sent_pdf') == 1) {
                $this->generate_quote_number_if_applicable($quote_id, $qR, $sR, $gR);
                $sR->quote_mark_sent($quote_id, $qR);
            }
            $quote = $qR->repoQuoteUnloadedquery($quote_id);        
            if ($quote) {
                $pdfhelper->generate_quote_pdf($quote_id, $quote->getUser_id(), $stream, $custom, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $this->view_renderer);        
                $parameters = ($include == '1' ? ['success'=>1] : ['success'=>0]);
                return $this->factory->createResponse(Json::encode($parameters));  
            } // $inv
            return $this->factory->createResponse(Json::encode(['success'=>0]));  
        } // quote_amount 
        return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
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
     * @return void
     */
    
    public function pdf_dashboard_include_cf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, QAR $qaR, QCR $qcR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR) : void {
        $quote_id = $currentRoute->getArgument('id');
        if ($quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
            if ($quote_amount) {
                $quote_custom_values = $this->quote_custom_values($quote_id,$qcR);
                // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($sR, $this->session);
                // The quote will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark quotes as 'sent' when sent.
                if ($sR->get_setting('mark_quotes_sent_pdf') == 1) {
                    $this->generate_quote_number_if_applicable($quote_id, $qR, $sR, $gR);
                    $sR->quote_mark_sent($quote_id, $qR);
                }
                $quote = $qR->repoQuoteUnloadedquery($quote_id);        
                if ($quote) {
                    $pdfhelper->generate_quote_pdf($quote_id, $quote->getUser_id(), $stream, true, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $this->view_renderer);        
                }    
            }    
        } //quote_id    
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
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
     * @return void
     */
    
    public function pdf_dashboard_exclude_cf(CurrentRoute $currentRoute, CR $cR, CVR $cvR, CFR $cfR, GR $gR, QAR $qaR, QCR $qcR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, SR $sR, UIR $uiR) : void {
        $quote_id = $currentRoute->getArgument('id');
        if ($quote_id) {
            $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
            if ($quote_amount) {
                $quote_custom_values = $this->quote_custom_values($quote_id,$qcR);
                // session is passed to the pdfHelper and will be used for the locale ie. $session->get('_language') or the print_language ie $session->get('print_language')
                $pdfhelper = new PdfHelper($sR, $this->session);
                // The quote will be streamed ie. shown, and not archived
                $stream = true;
                // If we are required to mark quotes as 'sent' when sent.
                if ($sR->get_setting('mark_quotes_sent_pdf') == 1) {
                    $this->generate_quote_number_if_applicable($quote_id, $qR, $sR, $gR);
                    $sR->quote_mark_sent($quote_id, $qR);
                }
                $quote = $qR->repoQuoteUnloadedquery($quote_id);        
                if ($quote) {
                    $pdfhelper->generate_quote_pdf($quote_id, $quote->getUser_id(), $stream, false, $quote_amount, $quote_custom_values, $cR, $cvR, $cfR, $qiR, $qiaR, $qR, $qtrR, $uiR, $this->view_renderer);      
                }    
            }    
        } // quote_id    
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QuoteRepository $quoteRepo
     * @param bool $unloaded
     * @return Quote|null
     */
    
    private function quote(CurrentRoute $currentRoute, 
                           QuoteRepository $quoteRepo, 
                           bool $unloaded = false): Quote|null 
    {
        $id = $currentRoute->getArgument('id');
        if (null!==$id) {
            $quote = ($unloaded ? $quoteRepo->repoQuoteUnLoadedquery($id) : $quoteRepo->repoQuoteLoadedquery($id));
            return $quote;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function quotes(QuoteRepository $quoteRepo, int $status): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $quotes = $quoteRepo->findAllWithStatus($status);  
        return $quotes;
    }
    
    /**
     * @param QuoteRepository $quoteRepo
     * @param int $status
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Quote>
     */
    private function quotes_status_with_sort(QuoteRepository $quoteRepo, int $status, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $quotes = $quoteRepo->findAllWithStatus($status)
                            ->withSort($sort);
        return $quotes;
    }
    
    /**
     * @param QR $qR
     * @param int $status
     * @param array $user_clients
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Quote>
     */
    private function quotes_status_with_sort_guest(QR $qR, int $status,  array $user_clients, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $quotes = $qR->repoGuest_Clients_Sent_Viewed_Approved_Rejected_Cancelled($status, $user_clients)
                     ->withSort($sort);
        return $quotes;
    }
    
    /**
     * 
     * @param string $quote_id
     * @param qcR $qcR
     * @return array
     */
    public function quote_custom_values(string $quote_id, qcR $qcR) : array
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
     * @param CurrentRoute $currentRoute
     * @param QIR $quoteitemRepository
     * @return QuoteItem|null
     */
    private function quote_item(CurrentRoute $currentRoute,QIR $quoteitemRepository): QuoteItem|null 
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $quoteitem = $quoteitemRepository->repoQuoteItemquery($id);
            if (null!==$quoteitem) {
                return $quoteitem;
            }
            return null;
        }
        return null;
    }
    
    // Data fed from quote.js->$(document).on('click', '#quote_to_invoice_confirm', function () {
    
    /**
     * 
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
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function quote_to_invoice_confirm(Request $request, FormHydrator $formHydrator, CFR $cfR, 
                                             GR $gR, IIAR $iiaR, InvItemAmountservice $iiaS, PR $pR, QAR $qaR, QCR $qcR,
                                             QIR $qiR,QR $qR, QTRR $qtrR, TRR $trR, UNR $unR, UR $uR, UCR $ucR, UIR $uiR) : \Yiisoft\DataResponse\DataResponse|Response
    {
        $body = $request->getQueryParams();
        $quote_id = (string)$body['quote_id'];
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if ($quote) {
            $ajax_body = [
                'client_id'=>$body['client_id'],
                'group_id'=>$body['group_id'],
                'status_id'=>1,
                'quote_id'=>$quote->getId(),
                'is_read_only'=>0,
                'password'=>$body['password'] ?? '',
                'number'=>$gR->generate_number((int)$body['group_id']),
                'discount_amount'=>floatval($quote->getDiscount_amount()),
                'discount_percent'=>floatval($quote->getDiscount_percent()),
                'url_key'=>$quote->getUrl_key(),
                'payment_method'=>0,
                'terms'=>'',
                'creditinvoice_parent_id'=>''
            ];
            $form = new InvForm();
            $inv = new Inv();
            if (($formHydrator->populate($form, $ajax_body) && $form->isValid()) &&
                    // Quote has not been copied before:  inv_id = 0
                    (($quote->getInv_id()===(string)0))
                ) {
               /**
                * @var string $ajax_body['client_id']
                */
                $client_id = $ajax_body['client_id'];
                $user_client = $ucR->repoUserquery($client_id);
                $user_client_count = $ucR->repoUserquerycount($client_id);
                if (null!==$user_client && $user_client_count==1) {
                  // Only one user account per client
                  $user_id = $user_client->getUser_id();
                  $user = $uR->findById($user_id);
                  if (null!==$user) {
                    $user_inv = $uiR->repoUserInvUserIdquery($user_id);
                    if (null!==$user_inv && $user_inv->getActive()) {
                      $this->inv_service->bothInv($user, $inv, $form, $this->sR, $gR);
                      $inv_id = $inv->getId();
                      if (null!==$inv_id) {
                          // Transfer each quote_item to inv_item and the corresponding quote_item_amount to inv_item_amount for each item
                          $this->quote_to_invoice_quote_items($quote_id,$inv_id,$iiaR,$iiaS,$pR,$qiR,$trR, $formHydrator, $this->sR, $unR);
                          $this->quote_to_invoice_quote_tax_rates($quote_id,$inv_id,$qtrR,$formHydrator);
                          $this->quote_to_invoice_quote_custom($quote_id,$inv_id,$qcR,$cfR,$formHydrator);
                          $this->quote_to_invoice_quote_amount($quote_id,$inv_id,$qaR,$formHydrator);
                          // Update the quotes inv_id.
                          $quote->setInv_id($inv_id);
                          $qR->save($quote);
                          $parameters = ['success'=>1];
                          //return response to quote.js to reload page at location
                          $this->flash_message('info',$this->translator->translate('invoice.quote.copied.to.invoice'));
                          return $this->factory->createResponse(Json::encode($parameters));          
                      } //null!==$inv_id
                    } // null!==$user_inv && $user_inv->getActive()
                  } // null!==$user
                } // null!==$user_client && $user_client_count==1                
              } else {
                  $parameters = [
                     'success'=>0,
                  ];
                  //return response to quote.js to reload page at location
                  return $this->factory->createResponse(Json::encode($parameters));          
              }
        } // quote
        return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * 
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
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function quote_to_so_confirm(Request $request, FormHydrator $formHydrator, CFR $cfR, 
                                           GR $gR, soIAS $soiaS, PR $pR, QAR $qaR, soAR $soaR, QCR $qcR,
                                           soIAR $soiaR, QIR $qiR, QR $qR, QTRR $qtrR, TRR $trR, UNR $unR, UCR $ucR, UR $uR) : \Yiisoft\DataResponse\DataResponse|Response
    {
        // $body data received from user in ...resources\views\invoice\quote\modal_quote_to_so
        // ...src\Invoice\Asset\rebuild-1.13\js\ $(document).on('click', '#quote_to_so_confirm', function () {var url = $(location).attr('origin') + "/invoice/quote/quote_to_so_confirm";
        // These parameters inserted into url
        // A route is necessary for it to pass. see config/common/routes/routes.php  'quote/quote_to_so_confirm'
        $body = $request->getQueryParams();
        $quote_id = (string)$body['quote_id'];
        $quote = $qR->repoQuoteUnloadedquery($quote_id);
        if ($quote) {
            $so_body = [                    
                'quote_id'=>$quote_id,
                'inv_id'=>null,
                'client_id'=>$body['client_id'],
                'group_id'=>$body['group_id'], 
                'client_po_number'=>$body['po'],
                'client_po_person'=>$body['person'],
                'status_id'=>1,
                'number'=>$gR->generate_number((int)$body['group_id']),  
                'discount_amount'=>floatval($quote->getDiscount_amount()),
                'discount_percent'=>floatval($quote->getDiscount_percent()),
                // The quote's url will be the same for the so allowing for a trace 
                'url_key'=>$quote->getUrl_key(),
                'password'=> $body['password'] ?? '',              
                'notes'=>'',                
            ];
            $form = new SoForm();
            $new_so = new SoEntity();
            if (($formHydrator->populate($form, $so_body) 
                && $form->isValid()) 
                && ($quote->getSo_id()===(string)0))
            {
            /**
             * @var string $so_body['client_id']
             */  
            $client_id = $so_body['client_id'];
            $user_client = $ucR->repoUserquery($client_id);
            $user_client_count = $ucR->repoUserquerycount($client_id);
            if (null!==$user_client && $user_client_count==1) {
              // Only one user account per client
              $user_id = $user_client->getUser_id();
              $user = $uR->findById($user_id);
              if (null!==$user) {
                $this->so_service->addSo($user, $new_so, $form);            
                // Ensure that the quote has a specific po and therefore cannot be copied again.
                $new_so_id = $new_so->getId();
                // Transfer each quote_item to so_item and the corresponding so_item_amount to so_item_amount for each item
                if (null!==$new_so_id) {
                    $this->quote_to_so_quote_items($quote_id, $new_so_id, $soiaR, $soiaS, $pR, $qiR, $trR, $unR, $formHydrator);
                    $this->quote_to_so_quote_tax_rates($quote_id,$new_so_id, $qtrR, $formHydrator);
                    $this->quote_to_so_quote_custom($quote_id, $new_so_id, $qcR, $cfR, $formHydrator);
                    $this->quote_to_so_quote_amount($quote_id, $new_so_id, $qaR, $soaR);            
                    // Set the quote's sales order id so that it cannot be copied in the future
                    $quote->setSo_id($new_so_id);
                    $qR->save($quote);
                    $parameters = ['success'=>1];
                    //return response to quote.js to reload page at location
                    return $this->factory->createResponse(Json::encode($parameters));
                  } // null!==$new_so_id
                }  // null!==$user  
              } // null!==$user_client && $user_client_count==1    
            } else {
                $parameters = [
                   'success'=>0,
                ];
                //return response to quote.js to reload page at location
                return $this->factory->createResponse(Json::encode($parameters));          
            }
        } // original    
        return $this->web_service->getNotFoundResponse();
    }
            
    /**
     * 
     * @param string $quote_id
     * @param string $inv_id
     * @param IIAR $iiaR
     * @param InvItemAmountService $iiaS
     * @param PR $pR
     * @param QIR $qiR
     * @param TRR $trR
     * @param FormHydrator $formHydrator
     * @param UNR $unR
     * @return void
     */
    private function quote_to_invoice_quote_items(string $quote_id, string $inv_id, IIAR $iiaR, InvItemAmountService $iiaS, PR $pR, QIR $qiR, TRR $trR, FormHydrator $formHydrator, SR $sR, UNR $unR): void {
        // Get all items that belong to the quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        /** @var QuoteItem $quote_item */
        foreach ($items as $quote_item) {
            $inv_item = [
                'inv_id'=>$inv_id,
                'tax_rate_id'=>$quote_item->getTax_rate_id(),
                'product_id'=>$quote_item->getProduct_id(),
                'task_id'=>'',
                'name'=>$quote_item->getName(),
                'description'=>$quote_item->getDescription(),
                'quantity'=>$quote_item->getQuantity(),
                'price'=>$quote_item->getPrice(),
                'discount_amount'=>$quote_item->getDiscount_amount(),
                'order'=>$quote_item->getOrder(),
                'is_recurring'=>0,
                'product_unit'=>$quote_item->getProduct_unit(),
                'product_unit_id'=>$quote_item->getProduct_unit_id(),
                // Recurring date
                'date'=>''
            ];
            // Create an equivalent invoice item for the quote item
            $invitem = new InvItem();
            $form = new InvItemForm();
            if ($formHydrator->populate($form, $inv_item) && $form->isValid()) {
              $this->inv_item_service->addInvItem_product($invitem, $form, $inv_id, $pR, $trR , $iiaS, $iiaR, $sR, $unR);
            }
        } // items
    }
    
    /**
     * @param string $quote_id
     * @param string|null $inv_id
     * @param QTRR $qtrR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function quote_to_invoice_quote_tax_rates(string $quote_id, string|null $inv_id, QTRR $qtrR, FormHydrator $formHydrator): void {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);        
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate){ 
            $inv_tax_rate = [
                'inv_id'=>(string)$inv_id,
                'tax_rate_id'=>$quote_tax_rate->getTax_rate_id(),
                'include_item_tax'=>$quote_tax_rate->getInclude_item_tax(),
                'inv_tax_rate_amount'=>$quote_tax_rate->getQuote_tax_rate_amount(),
            ];
            $entity = new InvTaxRate();
            $form = new InvTaxRateForm();
            if ($formHydrator->populate($form, $inv_tax_rate) && $form->isValid()
            ) {    
               $this->inv_tax_rate_service->saveInvTaxRate($entity,$form);
            }
        } // foreach        
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $so_id
     * @param QTRR $qtrR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function quote_to_so_quote_tax_rates(string $quote_id, string|null $so_id, QTRR $qtrR, FormHydrator $formHydrator): void {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);        
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate){ 
            $so_tax_rate = [
                'so_id'=>(string)$so_id,
                'tax_rate_id'=>$quote_tax_rate->getTax_rate_id(),
                'include_item_tax'=>$quote_tax_rate->getInclude_item_tax(),
                'so_tax_rate_amount'=>$quote_tax_rate->getQuote_tax_rate_amount(),
            ];
            $entity = new SoTaxRate();
            $form = new SoTaxRateForm();
            if ($formHydrator->populate($form, $so_tax_rate) && $form->isValid()
            ) {    
              $this->so_tax_rate_service->saveSoTaxRate($entity, $form);
            }
        } // foreach        
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $inv_id
     * @param QCR $qcR
     * @param CFR $cfR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function quote_to_invoice_quote_custom(string $quote_id, string|null $inv_id, 
                                                   QCR $qcR,                                                     
                                                   CFR $cfR, 
                                                   FormHydrator $formHydrator) : void {
        $quote_customs = $qcR->repoFields($quote_id);
        // For each quote custom field, build a new custom field for 'inv_custom' using the custom_field_id to find details
        /** @var QuoteCustom $quote_custom */
        foreach ($quote_customs as $quote_custom) {
            // For each quote custom field, build a new custom field for 'inv_custom' 
            // using the custom_field_id to find details
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $cfR->repoCustomFieldquery($quote_custom->getCustom_field_id());
            if ($cfR->repoTableAndLabelCountquery('inv_custom',(string)$existing_custom_field->getLabel()) !== 0) {
                // Build an identitcal custom field for the invoice
                $custom_field = new CustomField();
                $custom_field->setTable('inv_custom');
                $custom_field->setLabel((string)$existing_custom_field->getLabel());
                $custom_field->setType($existing_custom_field->getType());
                $custom_field->setLocation((int)$existing_custom_field->getLocation());
                $custom_field->setOrder((int)$existing_custom_field->getOrder());
                $cfR->save($custom_field);
                // Build the inv_custom field record
                $inv_custom = [
                    'inv_id'=>$inv_id,
                    'custom_field_id'=>$custom_field->getId(),
                    'value'=>$quote_custom->getValue(),
                ];
                $entity = new InvCustom();
                $form = new InvCustomForm();
                if ($formHydrator->populate($form, $inv_custom) && $form->isValid()) {    
                  $this->inv_custom_service->saveInvCustom($entity, $form);            
                }
            } // existing_custom_field    
        } // foreach        
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $so_id
     * @param QCR $qcR
     * @param CFR $cfR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function quote_to_so_quote_custom(string $quote_id, string|null $so_id, 
                                                   QCR $qcR,                                                     
                                                   CFR $cfR, 
                                                  FormHydrator $formHydrator) : void {
        $quote_customs = $qcR->repoFields($quote_id);
        // For each quote custom field, build a new custom field for 'inv_custom' using the custom_field_id to find details
        /** @var QuoteCustom $quote_custom */
        foreach ($quote_customs as $quote_custom) {
            // For each quote custom field, build a new custom field for 'so_custom' 
            // using the custom_field_id to find details
            /** @var CustomField $existing_custom_field */
            $existing_custom_field = $cfR->repoCustomFieldquery($quote_custom->getCustom_field_id());
            if ($cfR->repoTableAndLabelCountquery('inv_custom',(string)$existing_custom_field->getLabel()) !== 0) {
                // Build an identitcal custom field for the po
                $custom_field = new CustomField();
                $custom_field->setTable('so_custom');
                $custom_field->setLabel((string)$existing_custom_field->getLabel());
                $custom_field->setType($existing_custom_field->getType());
                $custom_field->setLocation((int)$existing_custom_field->getLocation());
                $custom_field->setOrder((int)$existing_custom_field->getOrder());
                $cfR->save($custom_field);
                // Build the so_custom field record
                $so_custom = [
                    'so_id'=>$so_id,
                    'custom_field_id'=>$custom_field->getId(),
                    'value'=>$quote_custom->getValue(),
                ];
                $entity = new SoCustom();
                $form = new SoCustomForm();
                if ($formHydrator->populate($form, $so_custom) && $form->isValid()) {    
                  $this->so_custom_service->saveSoCustom($entity, $form);            
                }
            }   // existing_custom_field    
        } // foreach        
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $inv_id
     * @param QAR $qaR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function quote_to_invoice_quote_amount(string $quote_id,string|null $inv_id, QAR $qaR, FormHydrator $formHydrator) : void {
        $quote_amount = $qaR->repoQuotequery($quote_id);
        $inv_amount = [];
        if ($quote_amount) {
            $inv_amount = [
                'inv_id'=>$inv_id,
                'sign'=>1,
                'item_subtotal'=>$quote_amount->getItem_subtotal(),
                'item_tax_total'=>$quote_amount->getItem_tax_total(),
                'tax_total'=>$quote_amount->getTax_total(),
                'total'=>$quote_amount->getTotal(),
                'paid'=>floatval(0.00),
                'balance'=>$quote_amount->getTotal(),
            ];
        }    
        $entity = new InvAmount();
        $form = new InvAmountForm();
        if ($formHydrator->populate($form, $inv_amount) && $form->isValid()) {    
          $this->inv_amount_service->saveInvAmount($entity, $form);            
        }
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $copy_id
     * @return void
     */
    private function quote_to_quote_quote_amount(string $quote_id,string|null $copy_id): void {
        $this->quote_amount_service->initializeCopyQuoteAmount(new QuoteAmount(), $quote_id, $copy_id);                
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $copy_id
     * @param QAR $qaR
     * @param soAR $soaR
     * @return void
     */
     private function quote_to_so_quote_amount(string $quote_id, string|null $copy_id, QAR $qaR, soAR $soaR): void {
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
    public function quote_to_quote_confirm(Request $request, FormHydrator $formHydrator, 
                                           GR $gR, QIAS $qiaS, PR $pR, QAR $qaR, QCR $qcR,
                                           QIAR $qiaR, QIR $qiR, QR $qR, QTRR $qtrR, TRR $trR, UR $uR, UCR $ucR, UIR $uiR, UNR $unR) : \Yiisoft\DataResponse\DataResponse|Response
    {
      $data_quote_js = $request->getQueryParams();
      $quote_id = (string)$data_quote_js['quote_id'];
      $original = $qR->repoQuoteUnloadedquery($quote_id);
      if ($original) {
        $group_id = $original->getGroup_id();
        $ajax_body = [
          'inv_id'=>null,
          'client_id'=>$data_quote_js['client_id'],
          'group_id'=>$group_id,
          'status_id'=>1,
          'number'=>$gR->generate_number((int)$group_id),  
          'discount_amount'=>floatval($original->getDiscount_amount()),
          'discount_percent'=>floatval($original->getDiscount_percent()),
          'url_key'=>'',
          'password'=>'',              
          'notes'=>'',
        ];
        $form = new QuoteForm();
        $copy = new Quote();
        if (($formHydrator->populate($form, $ajax_body) && $form->isValid())) {    
          /**
           * @var string $ajax_body['client_id']
           */
          $client_id = $ajax_body['client_id'];
          $user_client = $ucR->repoUserquery($client_id);
          $user_client_count = $ucR->repoUserquerycount($client_id);
          if (null!==$user_client && $user_client_count==1) {
            // Only one user account per client
            $user_id = $user_client->getUser_id();
            $user = $uR->findById($user_id);
            if (null!==$user) {
              $user_inv = $uiR->repoUserInvUserIdquery($user_id);
              if (null!==$user_inv && $user_inv->getActive()) {
                $this->quote_service->bothQuote($user, $copy, $form, $this->sR, $gR);            
                // Transfer each quote_item to quote_item and the corresponding quote_item_amount to quote_item_amount for each item
                $copy_id =$copy->getId();
                if (null!==$copy_id) {
                    $this->quote_to_quote_quote_items($quote_id,$copy_id, $qiaR, $qiaS, $pR,$qiR, $trR, $unR, $formHydrator);
                    $this->quote_to_quote_quote_tax_rates($quote_id,$copy_id,$qtrR, $formHydrator);
                    $this->quote_to_quote_quote_custom($quote_id,$copy_id,$qcR, $formHydrator);
                    $this->quote_to_quote_quote_amount($quote_id,$copy_id);            
                    $qR->save($copy);
                    $parameters = ['success'=>1];
                    //return response to quote.js to reload page at location
                    return $this->factory->createResponse(Json::encode($parameters));
                } // null!==$copy_id
              } // null!==$user_inv && $user_inv->getActive()
            } // null!== $user 
          } // null!==$user_client && $user_client_count==1
        } // $formHydrator->populate($form, $body) && $form->isValid())
      } else {
              $parameters = [
                 'success'=>0,
              ];
              //return response to quote.js to reload page at location
              return $this->factory->createResponse(Json::encode($parameters));          
      }    
      return $this->web_service->getNotFoundResponse();
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $copy_id
     * @param QCR $qcR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function quote_to_quote_quote_custom(string $quote_id, string|null $copy_id, QCR $qcR, FormHydrator $formHydrator): void {
        $quote_customs = $qcR->repoFields($quote_id);
        /** @var QuoteCustom $quote_custom */
        foreach ($quote_customs as $quote_custom) {
            $copy_custom = [
                'quote_id'=>$copy_id,
                'custom_field_id'=>$quote_custom->getCustom_field_id(),
                'value'=>$quote_custom->getValue(),
            ];
            $entity = new QuoteCustom();
            $form = new QuoteCustomForm();
            if ($formHydrator->populate($form, $copy_custom) && $form->isValid()) {    
              $this->quote_custom_service->saveQuoteCustom($entity, $form);            
            }
        }        
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string $copy_id
     * @param QIAR $qiaR
     * @param QIAS $qiaS
     * @param PR $pR
     * @param QIR $qiR
     * @param TRR $trR
     * @param UNR $unR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function quote_to_quote_quote_items(string $quote_id, string $copy_id, QIAR $qiaR, QIAS $qiaS, PR $pR, QIR $qiR, TRR $trR, UNR $unR, FormHydrator $formHydrator): void {
        // Get all items that belong to the original quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        /** @var QuoteItem $quote_item */
        foreach ($items as $quote_item) {
            $copy_item = [
                'quote_id'=>$copy_id,
                'tax_rate_id'=>$quote_item->getTax_rate_id(),
                'product_id'=>$quote_item->getProduct_id(),
                'task_id'=>'',
                'name'=>$quote_item->getName(),
                'description'=>$quote_item->getDescription(),
                'quantity'=>$quote_item->getQuantity(),
                'price'=>$quote_item->getPrice(),
                'discount_amount'=>$quote_item->getDiscount_amount(),
                'order'=>$quote_item->getOrder(),
                'is_recurring'=>0,
                'product_unit'=>$quote_item->getProduct_unit(),
                'product_unit_id'=>$quote_item->getProduct_unit_id(),
                // Recurring date
                'date'=>''
            ];
            // Create an equivalent invoice item for the quote item
            $copyitem = new QuoteItem();
            $form = new QuoteItemForm();
            if ($formHydrator->populate($form, $copy_item) && $form->isValid()) {
              $this->quote_item_service->addQuoteItem($copyitem, $form, $copy_id, $pR, $qiaR, $qiaS, $unR, $trR);
            }
        } // items as quote_item
    }
    
    private function quote_to_so_quote_items(string $quote_id, string $so_id, soIAR $soiaR, soIAS $soiaS, PR $pR, QIR $qiR, TRR $trR, UNR $unR, FormHydrator $formHydrator): void {
        // Note: The $soiaR variable will be used to see if there are pre-existing amounts later towards the end of this function
        // Get all items that belong to the original quote
        $items = $qiR->repoQuoteItemIdquery($quote_id);
        /** @var QuoteItem $quote_item */
        foreach ($items as $quote_item) {
            $so_item = [
                'so_id'=>$so_id,
                'peppol_po_itemid'=>'',
                'tax_rate_id'=>$quote_item->getTax_rate_id(),
                'product_id'=>$quote_item->getProduct_id(),
                // There are currently no tasks provided in a quote and tasks and products are mutually exclusive
                'task_id'=>'',
                'name'=>$quote_item->getName(),
                'description'=>$quote_item->getDescription(),
                'quantity'=>$quote_item->getQuantity(),
                'price'=>$quote_item->getPrice(),
                'discount_amount'=>$quote_item->getDiscount_amount(),
                'order'=>$quote_item->getOrder(),
                'is_recurring'=>0,
                'product_unit'=>$quote_item->getProduct_unit(),
                'product_unit_id'=>$quote_item->getProduct_unit_id(),
                // Recurring date
                'date'=>''
            ];
            // Create an equivalent purchase order item for the quote item
            $soitem = new SoItem();
            $form = new SoItemForm();
            if ($formHydrator->populate($form, $so_item) && $form->isValid()) {
              $this->so_item_service->addSoItem($soitem, $form, $so_id, $pR, $soiaR, $soiaS, $unR, $trR);
            }
        } // items as quote_item
    }
    
    /**
     * 
     * @param string $quote_id
     * @param string|null $copy_id
     * @param QTRR $qtrR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function quote_to_quote_quote_tax_rates(string $quote_id, string|null $copy_id, QTRR $qtrR, FormHydrator $formHydrator): void {
        // Get all tax rates that have been setup for the quote
        $quote_tax_rates = $qtrR->repoQuotequery($quote_id);  
        /** @var QuoteTaxRate $quote_tax_rate */
        foreach ($quote_tax_rates as $quote_tax_rate){
          $copy_tax_rate = [
              'quote_id'=>$copy_id,
              'tax_rate_id'=>$quote_tax_rate->getTax_rate_id(),
              'include_item_tax'=>$quote_tax_rate->getInclude_item_tax(),
              'amount'=>$quote_tax_rate->getQuote_tax_rate_amount(),
          ];
          $entity = new QuoteTaxRate();
          $form = new QuoteTaxRateForm();
          if ($formHydrator->populate($form, $copy_tax_rate) && $form->isValid()) {    
            $this->quote_tax_rate_service->saveQuoteTaxRate($entity, $form);
          }
        }        
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param QTRR $quotetaxrateRepository
     * @return QuoteTaxRate|null
     */    
    private function quotetaxrate(CurrentRoute $currentRoute, QTRR $quotetaxrateRepository): QuoteTaxRate|null 
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $quotetaxrate = $quotetaxrateRepository->repoQuoteTaxRatequery($id);
            if (null!==$quotetaxrate) {
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
    private function remove_extension(array $files) : mixed
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
    public function save_custom(FormHydrator $formHydrator, Request $request, QCR $qcR) : \Yiisoft\DataResponse\DataResponse
    {
            $parameters = [
                'success'=>0
            ]; 
            $js_data = $request->getQueryParams();        
            $quote_id = (int)$js_data['quote_id'];
            $custom_field_body = [            
                'custom'=>$js_data['custom'] ?: '',            
            ];
            $this->custom_fields($formHydrator, $custom_field_body,$quote_id, $qcR);
            $parameters['success'] = 1;            
            return $this->factory->createResponse(Json::encode($parameters)); 
    }
    
    // '#quote_tax_submit' => quote.js 
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     */
    public function save_quote_tax_rate(Request $request, FormHydrator $formHydrator)
                                        : \Yiisoft\DataResponse\DataResponse {       
        $body = $request->getQueryParams();
        $ajax_body = [
            'quote_id'=>$body['quote_id'],
            'tax_rate_id'=>$body['tax_rate_id'],
            'include_item_tax'=>$body['include_item_tax'],
            'quote_tax_rate_amount'=>floatval(0.00),
        ];
        $ajax_content = new QuoteTaxRateForm();
        if ($formHydrator->populate($ajax_content, $ajax_body) && $ajax_content->isValid()) {    
            $this->quote_tax_rate_service->saveQuoteTaxRate(new QuoteTaxRate(), $ajax_content);
            $parameters = [
                'success'=>1
            ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        } else {
            $parameters = [
               'success'=>0
             ];
            //return response to quote.js to reload page at location
            return $this->factory->createResponse(Json::encode($parameters));          
        }        
    }
    
    // When you click on Send Mail whilst in the view, you will get mailer_quote view showing with the url_key at the bottom
    // Use this url_key to test what the customer will experience eg. invoice/quote/url_key/{url_key}
    // config/routes accesschecker ensures client has viewInv permission
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param CurrentUser $currentUser
     * @param CFR $cfR
     * @param QAR $qaR
     * @param QIR $qiR
     * @param QIAR $qiaR
     * @param QR $qR
     * @param QTRR $qtrR
     * @param UIR $uiR
     * @param PMR $pmR
     * @return Response
     */
    public function url_key(CurrentRoute $currentRoute, CurrentUser $currentUser, CFR $cfR, QAR $qaR, QIR $qiR, QIAR $qiaR, QR $qR, QTRR $qtrR, UIR $uiR, UCR $ucR, PMR $pmR): Response 
    {
        // Get the url key from the browser
        $url_key = $currentRoute->getArgument('url_key');
        
        // If there is no quote with such a url_key, issue a not found response
        if ($url_key === null) {
            return $this->web_service->getNotFoundResponse();
        }
        
        // If there is a quote with the url key ... continue or else issue not found response
        if ($qR->repoUrl_key_guest_count($url_key) < 1) {
            return $this->web_service->getNotFoundResponse();
        }
        
        // If this quote has a status id that falls into the category of (just)sent, viewed(in the past), approved(in the past) then continue
        $quote = $qR->repoUrl_key_guest_loaded($url_key);
        $quote_tax_rates = null;
        if ($quote) {
            $quote_id = $quote->getId();
            if ($quote_id) {
                if ($qtrR->repoCount($quote_id) > 0)  {
                    $quote_tax_rates = $qtrR->repoQuotequery($quote_id);
                }    
            }    
            // If the quote status is sent 2, viewed 3, or approved_with 4, or approved_without 5 or rejected 6
            if (in_array($quote->getStatus_id(),[2,3,4,5,6])) {
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

                            //TODO 
                            // $attachments;
                            if ($quote_id) {
                                $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
                                if ($quote_amount) {
                                    $parameters = [            
                                        'render'=> $this->view_renderer->renderPartialAsString('/invoice/template/quote/public/' . ($this->sR->get_setting('public_quote_template') ?: 'Quote_Web'), [
                                            'isGuest' => $currentUser->isGuest(),
                                            // TODO logo
                                            'logo'=> '',
                                            'alert'=>$this->alert(),
                                            'quote' => $quote,
                                            'quote_item_amount'=>$qiaR,
                                            'quote_amount' => $quote_amount,
                                            'items' => $qiR->repoQuotequery($quote_id),
                                            // Get all the quote tax rates that have been setup for this quote
                                            'quote_tax_rates' => $quote_tax_rates,
                                            'quote_url_key' => $url_key,
                                            'flash_message' => $this->flash_message('info', ''),
                                            //'attachments' => $attachments,
                                            'custom_fields' => $custom_fields,
                                            'clienthelper' => new ClientHelper($this->sR),
                                            'datehelper' => new DateHelper($this->sR),
                                            'numberhelper' => new NumberHelper($this->sR),
                                            'has_expired' => new \DateTimeImmutable('now') > $quote->getDate_expires() ? true : false,
                                            's'=>$this->sR,
                                            'client'=>$quote->getClient(),
                                            // Get the details of the user of this quote
                                            'userinv'=> $uiR->repoUserInvUserIdcount($user_id) > 0 ? $uiR->repoUserInvUserIdquery($user_id) : null,                
                                            'modal_purchase_order_number' => $this->view_renderer->renderPartialAsString('/invoice/quote/modal_purchase_order_number',['url_key'=>$url_key])
                                        ]),        
                                    ];        
                                    return $this->view_renderer->render('/invoice/quote/url_key', $parameters);
                                } // if quote_amount    
                                return $this->web_service->getNotFoundResponse(); 
                            } // if there is a quote id 
                            return $this->web_service->getNotFoundResponse(); 
                        } // user_inv->getType
                        return $this->web_service->getNotFoundResponse(); 
                    } // user_inv
                    return $this->web_service->getNotFoundResponse(); 
                } // $uiR    
                return $this->web_service->getNotFoundResponse(); 
            } // if in_array
            return $this->web_service->getNotFoundResponse(); 
        } // if quote
        return $this->web_service->getNotFoundResponse(); 
    }
    
    /**
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param CFR $cfR
     * @param CVR $cvR
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
     */
    public function view(ViewRenderer $head, CurrentRoute $currentRoute, Request $request,
                         CFR $cfR, CVR $cvR, PR $pR, QAR $qaR, QIAR  $qiaR, QIR $qiR, QR $qR, QTRR $qtrR, TRR $trR, FR $fR,  UNR $uR, CR $cR, GR $gR, QCR $qcR, SOR $soR)
                         : \Yiisoft\DataResponse\DataResponse|Response {
        $quote = $this->quote($currentRoute, $qR, false);
        if ($quote) {
            $this->session->set('quote_id',$quote->getId());
            $this->number_helper->calculate_quote((string)$this->session->get('quote_id'), $qiR, $qiaR, $qtrR, $qaR, $qR); 
            $quote_tax_rates = (($qtrR->repoCount((string)$this->session->get('quote_id')) > 0) ? $qtrR->repoQuotequery((string)$this->session->get('quote_id')) : null); 
            $sales_order_number = '';
            if ($quote->getSo_id()) { 
                $so = $soR->repoSalesOrderUnloadedquery($quote->getSo_id());
                $sales_order_number = $so ? ($so->getNumber() ?: '') : '';
            }    
            if ($quote_tax_rates) {
                $quote_amount = (($qaR->repoQuoteAmountCount((string)$this->session->get('quote_id')) > 0) ? $qaR->repoQuotequery((string)$this->session->get('quote_id')) : null);
                if ($quote_amount) {
                    $quote_custom_values = $this->quote_custom_values((string)$this->session->get('quote_id'), $qcR);
                    $parameters = [
                        'title' => $this->sR->trans('view'),            
                        'body' => $this->body($quote),          
                        's'=>$this->sR,
                        'alert'=>$this->alert(),
                         // Hide buttons on the view if a 'viewInv' user does not have 'editInv' permission
                        'invEdit' => $this->user_service->hasPermission('editInv') ? true : false,       
                        // if the quote amount total is greater than zero show the buttons eg. Send email
                        'quote_amount_total' => $quote_amount->getTotal(), 
                        'sales_order_number' => $sales_order_number,
                        'add_quote_item'=>$this->view_renderer->renderPartialAsString('/invoice/quoteitem/_item_form',[
                                'action' => ['quoteitem/add',['_language' => $currentRoute->getArgument('_language')]],
                                'errors' => [],
                                'body' => $request->getParsedBody(),
                                's'=>$this->sR,
                                'head'=>$head,
                                'quote_id'=>$this->quote($currentRoute, $qR, true),
                                'tax_rates'=>$trR->findAllPreloaded(),
                                'products'=>$pR->findAllPreloaded(),
                                'units'=>$uR->findAllPreloaded(),
                                'numberhelper'=>new NumberHelper($this->sR)
                        ]),
                        // Get all the fields that have been setup for this SPECIFIC quote in quote_custom. 
                        'fields' => $qcR->repoFields((string)$this->session->get('quote_id')),
                        // Get the standard extra custom fields built for EVERY quote. 
                        'custom_fields'=>$cfR->repoTablequery('quote_custom'),
                        'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                        'cvH'=> new CVH($this->sR),
                        'quote_custom_values' => $quote_custom_values,
                        'quote_statuses'=> $qR->getStatuses($this->sR),  
                        'quote'=>$qR->repoQuoteLoadedquery((string)$this->session->get('quote_id')),   
                        'partial_item_table'=>$this->view_renderer->renderPartialAsString('/invoice/quote/partial_item_table',[
                            'invEdit' => $this->user_service->hasPermission('editInv') ? true : false,    
                            'numberhelper'=> new NumberHelper($this->sR),          
                            'products'=>$pR->findAllPreloaded(),
                            'quote_items'=>$qiR->repoQuotequery((string)$this->session->get('quote_id')),
                            'quote_item_amount'=>$qiaR,
                            'quote_tax_rates'=>$quote_tax_rates,
                            'quote_amount'=> $quote_amount,
                            'quote'=>$qR->repoQuoteLoadedquery((string)$this->session->get('quote_id')),  
                            'language'=> $currentRoute->getArgument('_language'),
                            's'=>$this->sR,
                            'tax_rates'=>$trR->findAllPreloaded(),
                            'units'=>$uR->findAllPreloaded(),
                        ]),
                        'modal_choose_items'=>$this->view_renderer->renderPartialAsString('/invoice/product/modal_product_lookups_quote',
                        [
                            's'=>$this->sR,
                            'families'=>$fR->findAllPreloaded(),
                            'default_item_tax_rate'=> $this->sR->get_setting('default_item_tax_rate') !== '' ?: 0,
                            'filter_product'=> '',            
                            'filter_family'=> '',
                            'reset_table'=> '',
                            'products'=>$pR->findAllPreloaded(),
                            'head'=> $head,
                        ]),
                        'modal_add_quote_tax'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_add_quote_tax',['s'=>$this->sR,'tax_rates'=>$trR->findAllPreloaded()]),
                        //'modalhelper'=> new ModalHelper($this->sR),
                        'modal_copy_quote'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_copy_quote',[ 's'=>$this->sR,
                            'quote'=>$qR->repoQuoteLoadedquery((string)$this->session->get('quote_id')),
                            'clients'=>$cR->findAllPreloaded(),                
                            'groups'=>$gR->findAllPreloaded(),
                        ]),
                        'modal_delete_quote'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_delete_quote',
                                ['action'=>['quote/delete', ['_language' => $currentRoute->getArgument('_language'), 'id' => $this->session->get('quote_id')]],
                                 's'=>$this->sR,   
                        ]),            
                        'modal_delete_items'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_delete_item',[
                                'partial_item_table_modal'=>$this->view_renderer->renderPartialAsString('/invoice/quoteitem/_partial_item_table_modal',[
                                    'quoteitems'=>$qiR->repoQuotequery((string)$this->session->get('quote_id')),
                                    's'=>$this->sR,
                                    'numberhelper'=>new NumberHelper($this->sR),
                                ]),
                                's'=>$this->sR,
                        ]),
                        'modal_quote_to_invoice'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_quote_to_invoice',[
                                 's'=>$this->sR,
                                 'quote'=> $quote,                        
                                 'groups'=>$gR->findAllPreloaded(),
                        ]),
                        'modal_quote_to_so'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_quote_to_so',[
                                 's'=>$this->sR,
                                 'quote'=> $quote,                        
                                 'groups'=>$gR->findAllPreloaded(),
                        ]),
                        'modal_quote_to_pdf'=>$this->view_renderer->renderPartialAsString('/invoice/quote/modal_quote_to_pdf',[
                                 's'=>$this->sR,
                                 'quote'=> $quote,                        
                        ]),
                        'view_custom_fields'=>$this->view_renderer->renderPartialAsString('/invoice/quote/view_custom_fields', [
                                 'custom_fields'=>$cfR->repoTablequery('quote_custom'),
                                 'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('quote_custom')),
                                 'quote_custom_values'=> $quote_custom_values,  
                                 'cvH'=> new CVH($this->sR),
                                 's'=>$this->sR,   
                        ]),        
                    ];
                    return $this->view_renderer->render('/invoice/quote/view', $parameters);
                } // quote_amount                   
                return $this->web_service->getNotFoundResponse();
            } //quote_tax_rates
        } //quote    
        return $this->web_service->getNotFoundResponse();
    }    
}