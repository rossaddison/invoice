<?php

declare(strict_types=1); 

namespace App\Invoice\Payment;

use App\Invoice\Client\ClientRepository;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\CustomValue\CustomValueRepository;
use App\Invoice\Entity\Payment;
use App\Invoice\Entity\PaymentCustom;
use App\Invoice\Entity\Inv;
use App\Invoice\Helpers\CustomValuesHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Merchant\MerchantRepository;
use App\Invoice\Payment\PaymentService;
use App\Invoice\Payment\PaymentRepository;
use App\Invoice\Payment\PaymentForm;
use App\Invoice\PaymentMethod\PaymentMethodRepository;
use App\Invoice\PaymentCustom\PaymentCustomRepository;
use App\Invoice\PaymentCustom\PaymentCustomForm;
use App\Invoice\PaymentCustom\PaymentCustomService;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvRepository;

use App\User\User;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

final class PaymentController
{
    private Session $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private PaymentService $paymentService;
    private PaymentCustomService $paymentCustomService;
    private TranslatorInterface $translator;
    private DataResponseFactoryInterface $factory;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        PaymentService $paymentService,
        PaymentCustomService $paymentCustomService,    
        TranslatorInterface $translator,
        DataResponseFactoryInterface $factory
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->webService = $webService;
        $this->userService = $userService;
        $this->paymentService = $paymentService;
        $this->paymentCustomService = $paymentCustomService;
        $this->translator = $translator;
        $this->factory = $factory;
        $this->viewRenderer = $viewRenderer;
        if ($this->userService->hasPermission('viewPayment') 
            && !$this->userService->hasPermission('editPayment')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/payment')
                                               ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewPayment') 
            && $this->userService->hasPermission('editPayment')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/payment')
                                               ->withLayout('@views/layout/invoice.php');
        }
    }
    
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ACIR $aciR
     * @param SettingRepository $settingRepository
     * @param InvRepository $invRepository
     * @param InvAmountRepository $iaR
     * @param PaymentMethodRepository $payment_methodRepository
     * @param PaymentCustomRepository $pcR
     * @param PaymentRepository $pmtR
     * @param CustomFieldRepository $cfR
     * @param CustomValueRepository $cvR
     * @param ClientRepository $cR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param ITRR $itrR
     * @return Response
     */
    public function add(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        ACIR $aciR,
                        SettingRepository $settingRepository,                        
                        InvRepository $invRepository,
                        InvAmountRepository $iaR,
                        PaymentMethodRepository $payment_methodRepository,
                        PaymentCustomRepository $pcR,
                        PaymentRepository $pmtR,
                        CustomFieldRepository $cfR,
                        CustomValueRepository $cvR,
                        ClientRepository $cR,
                        IIR $iiR,
                        IIAR $iiaR,
                        ITRR $itrR,                        
    ) : Response
    {
        $open = $invRepository->open();
        $datehelper = new DateHelper($settingRepository);
        $invRepository->open_count() == 0 ? $this->flash_message('danger', $this->translator->translate('invoice.payment.no.invoice.sent')) : '';
        $amounts = [];
        $invoice_payment_methods = [];
        /** @var Inv $open_invoice */
        foreach ($open as $open_invoice) {
            $open_invoice_id = $open_invoice->getId();
            if (null!==$open_invoice_id) {    
                $inv_amount = $iaR->repoInvquery((int)$open_invoice_id);            
                if (null!==$inv_amount) {
                    $amounts['invoice' . $open_invoice_id] = $settingRepository->format_amount($inv_amount->getBalance());
                }
                $invoice_payment_methods['invoice' . $open_invoice_id] = $open_invoice->getPayment_method();            
            }    
        }
        $number_helper = new NumberHelper($settingRepository);
        $parameters = [
            'action' => ['payment/add'],            
            'alert'=>$this->alert(),
            'body' => $request->getParsedBody(),
            'datehelper'=> $datehelper,
            'numberhelper'=> $number_helper,
            'clienthelper'=> new ClientHelper($settingRepository),
            'head'=>$head,
            'open_invs_count'=>$invRepository->open_count(),
            'open_invs'=>$open,
            's'=>$settingRepository,
            // jquery script at bottom of _from to load all amounts
            'amounts'=>Json::encode($amounts),
            'invoice_payment_methods'=>Json::encode($invoice_payment_methods),
            'payment_methods'=>$payment_methodRepository->count() > 0 ? $payment_methodRepository->findAllPreloaded() : null,
            'cR'=>$cR,
            'iaR'=>$iaR,
            'cvH'=> new CustomValuesHelper($settingRepository),
            'custom_fields'=>$cfR->repoTablequery('payment_custom'),
            // Applicable to normally building up permanent selection lists eg. dropdowns
            'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('payment_custom')),
            // There will initially be no custom_values attached to this payment until they are filled in the field on the form
            //'payment_custom_values' => $this->payment_custom_values($payment_id,$pcR),
            'payment_custom_values' => [],
            'edit'=>false
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $parameters['body'];                    
            // Default payment method is 1 => None
            
            // Retrieve form values
            if (is_array($body)) {
                        $inv_id = 0;
                        $payment_method_id = 1;
                        $payment_date = new \DateTime();
                        $amount = 0.00;
                        $note = '';
                        /** @var string $value */
                        foreach ($body as $key => $value) {
                            switch ($key) {
                                case 'inv_id':
                                    $inv_id = (int)$value;
                                    break;
                                case 'payment_date':                                    
                                    /** @var \DateTime $payment_date */
                                    $payment_date = $datehelper->get_or_set_with_style($value);
                                    break;
                                case 'amount':
                                    $amount = (float)$value;
                                    break;
                                case 'payment_method_id':
                                    $payment_method_id = (int)$value;
                                    break;                            
                                case 'note':
                                    $note = $value;
                                    break;                            
                            }
                    }
                    
                    $payment = new Payment();
                    $payment_method_id ? $payment->setPayment_method_id($payment_method_id) : '';
                    
                    $payment->setPayment_date($payment_date);
                    
                    $payment->setAmount($amount);
                    $payment->setNote($note);
                    $payment->setInv_id($inv_id); 
                    $pmtR->save($payment);
                    
                    // Once the payment has been saved, retrieve the payment id for the custom fields
                    $payment_id = $payment->getId();
                    
                    // Recalculate the invoice
                    $number_helper->calculate_inv((string)$inv_id, $aciR, $iiR, $iiaR, $itrR, $iaR, $invRepository, $pmtR);
                    $this->flash_message('info', $settingRepository->trans('record_successfully_created')); 
                                        
                    // Retrieve the custom array
                    /** @var array $custom */
                    $custom = $body['custom'];
                    /** 
                     * @var int $custom_field_id
                     * @var string $value
                     */
                    foreach ($custom as $custom_field_id => $value) {
                        $payment_custom = new PaymentCustom();
                        $payment_custom_input = [
                            'payment_id'=>(int)$payment_id,
                            'custom_field_id'=>$custom_field_id,
                            'value'=>$value
                        ];
                        $form = new PaymentCustomForm();
                        if ($formHydrator->populate($form, $payment_custom_input) 
                            && $form->isValid() 
                            && $this->add_custom_field($payment_id, $custom_field_id, $pcR)) {
                            try {
                              $this->paymentCustomService->savePaymentCustom($payment_custom, $form);
                            } catch (\Exception $e){
                                switch ($e->getCode()) {
                                    //catch integrity constraint on custom_field_id => 23000
                                    case 23000 :
                                       //$message = 'Incomplete fields.'. ' Payment: '.$payment->getId(). ' Custom field id: '.$custom_field_id.' Value: '.$value . var_dump($payment);
                                       $message = $payment_id; 
                                       break;
                                    default : 
                                       $message = 'Unknown error.';
                                       break;
                                }   
                                $this->flash_message('danger', $message . ' ' . $e->getCode());
                                unset($e);   
                            }
                        }
                    }
                    return $this->webService->getRedirectResponse('payment/index');
                    //$parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
                }
            return $this->viewRenderer->render('_form', $parameters); 
            } // is_array body
        return $this->webService->getRedirectResponse('payment/index');   
    }
    
    // If the custom field already exists return false
    
    /**
     * 
     * @param string $payment_id
     * @param int $custom_field_id
     * @param PaymentCustomRepository $pcR
     * @return bool
     */
    public function add_custom_field(string $payment_id, int $custom_field_id, PaymentCustomRepository $pcR): bool
    {
        return ($pcR->repoPaymentCustomCount($payment_id, (string)$custom_field_id) > 0 ? false : true);        
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
    
    /**
     * 
     * @param Payment $payment
     * @return array
     */
    private function body(Payment $payment): array {
        $body = [      
          'id'=>$payment->getId(),
          'payment_method_id'=>$payment->getPayment_method_id(),
          'payment_date'=>$payment->getPayment_date(),
          'amount'=>$payment->getAmount(),
          'note'=>$payment->getNote(),
          'inv_id'=>$payment->getInv_id()
        ];
        return $body;
    }
    
    /**
     * @param FormHydrator $formHydrator
     * @param (mixed|string)[] $array     
     * @param string $payment_id
     * @param PaymentCustomRepository $pcR
     * @psalm-param array{custom: ''|mixed} $array
     * @return void
     */
    public function custom_fields(FormHydrator $formHydrator, array $array, string $payment_id, PaymentCustomRepository $pcR) : void
    {   
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
                    $values[$matches[1]][] = $custom['value'] ;
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
                    // Reduce eg.  customview[4] to 4 
                    $key_value = preg_match('/\d+/', $key, $m) ? $m[0] : '';
                    $db_array[$key_value] = $value;
                }
            }
            foreach ($db_array as $key => $value){
               if ($value !=='') { 
                $from_custom = new PaymentCustomForm();
                $payment_custom = [];
                $payment_custom['payment_id']=$payment_id;
                $payment_custom['custom_field_id']=$key;
                $payment_custom['value']=$value; 
                $model = ($pcR->repoPaymentCustomCount($payment_id,$key) > 0 ? $pcR->repoFormValuequery($payment_id,$key) : new PaymentCustom());
                if (null!==$model && $formHydrator->populate($from_custom, $payment_custom) && $from_custom->isValid()) {  
                    $this->paymentCustomService->savePaymentCustom($model, $from_custom);                        
                } // if null                                   
               } // if value
            } // foreach db
        } // if !empty array             
    }  
    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param SettingRepository $settingRepository
     * @param InvRepository $invRepository
     * @param InvAmountRepository $iaR
     * @param PaymentRepository $pmtR
     * @param ACIR $aciR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param ITRR $itrR
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, 
                           SettingRepository $settingRepository,                        
                           InvRepository $invRepository,
                           InvAmountRepository $iaR,
                           PaymentRepository $pmtR,
                           ACIR $aciR,
                           IIR $iiR,
                           IIAR $iiaR,
                           ITRR $itrR,         
    ): Response {
        try {
        $number_helper = new NumberHelper($settingRepository);                
        $payment = $this->payment($currentRoute, $pmtR);
            if ($payment) {
                $inv_id = $payment->getInv()?->getId();
                // Error: Unprocessible Entity : If <form Method="POST" in payment/index line 70 used and
                // and 'if ($request->getMethod() === Method::POST) {' used here in association with this delete function.
                // config/route payment/delete has both GET and POST METHOD.
                $this->paymentService->deletePayment($payment);
                $number_helper->calculate_inv((string)$inv_id, $aciR, $iiR, $iiaR, $itrR, $iaR, $invRepository, $pmtR);
                $this->flash_message('danger', $this->translator->translate('invoice.payment.deleted'));
                return $this->webService->getRedirectResponse('payment/index');
            }
            return $this->webService->getRedirectResponse('payment/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flash_message('danger', $this->translator->translate('invoice.payment.cannot.delete'));
            return $this->webService->getRedirectResponse('payment/index');
        }
    }
        
    /**
     * 
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @param InvRepository $invRepository
     * @param InvAmountRepository $iaR
     * @param PaymentRepository $pmtR
     * @param PaymentMethodRepository $payment_methodRepository
     * @param PaymentCustomRepository $pcR
     * @param CustomFieldRepository $cfR
     * @param CustomValueRepository $cvR
     * @param ClientRepository $cR
     * @param IIR $iiR
     * @param IIAR $iiaR
     * @param ITRR $itrR
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        ACIR $aciR,
                        SettingRepository $settingRepository,                          
                        InvRepository $invRepository,
                        InvAmountRepository $iaR,                        
                        PaymentRepository $pmtR, 
                        PaymentMethodRepository $payment_methodRepository,
                        PaymentCustomRepository $pcR,            
                        CustomFieldRepository $cfR,
                        CustomValueRepository $cvR,
                        ClientRepository $cR,
                        IIR $iiR,
                        IIAR $iiaR,
                        ITRR $itrR,
    ): Response {  
        $payment = $this->payment($currentRoute, $pmtR);
        if ($payment) {
            $payment_id = $payment->getId();
            $open = $invRepository->open();
            $number_helper = new NumberHelper($settingRepository);
            $date_helper = new DateHelper($settingRepository);
            $parameters = [
                'title' => $settingRepository->trans('edit'),
                'action' => ['payment/edit', ['id' => $payment_id]],
                'alert'=>$this->alert(),
                'body' => $this->body($payment),
                'errors'=>[],
                'datehelper'=> $date_helper,
                'numberhelper'=> $number_helper,
                'clienthelper'=>new ClientHelper($settingRepository),
                'head'=>$head, 
                'open_invs'=>$open,            
                'open_invs_count'=>$invRepository->open_count(),
                'payment_methods'=>$payment_methodRepository->findAllPreloaded(),
                'cR'=>$cR,
                'iaR'=>$iaR,
                'cvH'=> new CustomValuesHelper($settingRepository),
                'custom_fields'=>$cfR->repoTablequery('payment_custom'),
                // Applicable to normally building up permanent selection lists eg. dropdowns
                'custom_values'=>$cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('payment_custom')),
                // There will initially be no custom_values attached to this payment until they are filled in the field on the form
                //'payment_custom_values' => $this->payment_custom_values($payment_id,$pcR),
                'payment_custom_values' => $this->payment_custom_values($payment_id, $pcR),
                'edit'=>true
           ];
           if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                /** @var array $body['custom'] */
                if (null!==$body && is_array($body)) {
                    /** @var array $custom */
                    $custom = $body['custom'];
                    $inv_id = (string)$body['inv_id'];
                    $pcR->repoPaymentCount($payment_id) > 0 ? $this->edit_save_custom_fields($custom, $formHydrator, $pcR, $payment_id) : '';
                    $this->edit_save_form_fields($body, $currentRoute, $formHydrator, $pmtR);
                    // Recalculate the invoice
                    $number_helper->calculate_inv($inv_id, $aciR, $iiR, $iiaR, $itrR, $iaR, $invRepository, $pmtR);
                    $this->flash_message('info', $settingRepository->trans('record_successfully_updated')); 
                    return $this->webService->getRedirectResponse('payment/index');
                }    
           }
           return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('payment/index');
    }
    
    /**
     * @param array|Payment|null $body
     */
    public function edit_save_form_fields(array|Payment|null $body, CurrentRoute $currentRoute, FormHydrator $formHydrator, PaymentRepository $pmtR) : void {
        $form = new PaymentForm();
        $payment = $this->payment($currentRoute, $pmtR);
        if ($payment && $formHydrator->populate($form, $body) && $form->isValid()) {
            $this->paymentService->editPayment($payment, $form);
        }
    }
    
    /**
     * @param array $custom
     * @param FormHydrator $formHydrator
     * @param PaymentCustomRepository $pcR
     * @param string $payment_id
     * @return void
     */
    public function edit_save_custom_fields(array $custom, FormHydrator $formHydrator, PaymentCustomRepository $pcR,string $payment_id): void {
      /** @var string $value */
      foreach ($custom as $custom_field_id => $value) {
          $payment_custom = $pcR->repoFormValuequery($payment_id, (string)$custom_field_id);
          if ($payment_custom) {
            $payment_custom_input = [
                'payment_id'=>(int)$payment_id,
                'custom_field_id'=>(int)$custom_field_id,
                'value'=>$value
            ];
            $form = new PaymentCustomForm();
            if ($formHydrator->populate($form, $payment_custom_input) && $form->isValid())
            {
              $this->paymentCustomService->editPaymentCustom($payment_custom, $form);     
            }
          }
      }
    }
    
    // This function is used in invoice/layout/guest
    
    /**
     * 
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $paymentRepository
     * @param SettingRepository $settingRepository
     * @param DateHelper $dateHelper
     * @param InvAmountRepository $iaR
     * @param UserClientRepository $ucR
     * @param UserInvRepository $uiR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function guest(Request $request,  
                          CurrentRoute $currentRoute,  
                          PaymentRepository $paymentRepository, 
                          SettingRepository $settingRepository, 
                          DateHelper $dateHelper,  
                          InvAmountRepository $iaR,
                          UserClientRepository $ucR,
                          UserInvRepository $uiR) : \Yiisoft\DataResponse\DataResponse|Response {
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page','1');
        // Clicking on the gridview's Inv_id column hyperlink generates 
        // the query_param called 'sort' 
        // Clicking on the paginator does not generate the query_param 'sort'
        /** @psalm-suppress MixedAssignment $sort_string */
        $sort = $query_params['sort'] ?? '-inv_id';
        $sort_by = Sort::only(['id','inv_id','payment_date'])
                // Sort the merchant responses in descending order
                ->withOrderString((string)$sort);
        // Retrieve the user from Yii-Demo's list of users in the User Table
        $user = $this->userService->getUser(); 
        if ($user instanceof User && null!==$user->getId()) {
            // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount((string)$user->getId()) > 0 
                     ? $uiR->repoUserInvUserIdquery((string)$user->getId()) 
                     : null);
            // Determine what clients have been allocated to this user (@see Settings...User Account) 
            // by looking at UserClient table        
            // eg. If the user is a guest-accountant, they will have been allocated certain clients
            // A user-quest-accountant will be allocated a series of clients
            // A user-guest-client will be allocated their client number by the administrator so that
            // they can view their invoices and make payment
            // Return an array of client ids associated with the current user
            if (null!== $userinv && null!==$user->getId()) {
                /** @psalm-suppress PossiblyNullArgument */
                $client_id_array = $ucR->get_assigned_to_user($user->getId());
            } else {
                $client_id_array = [];
            }
            $payments = $this->payments_with_sort_guest($paymentRepository, $client_id_array, $sort_by); 
            $paginator = (new OffsetPaginator($payments))
             ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
             ->withCurrentPage($page)
             ->withNextPageToken((string) $page);
            $canEdit = $this->userService->hasPermission('editPayment');
            $canView = $this->userService->hasPermission('viewPayment');
            $parameters = [
                'alert'=>$this->alert(),
                'canEdit'=>$canEdit,
                'canView'=>$canView,
                'grid_summary' => $settingRepository->grid_summary(
                    $paginator,
                    $this->translator,
                    (int) $settingRepository->get_setting('default_list_limit'),
                    $this->translator->translate('invoice.invoice.payments'),
                    ''
                ),                    
                'page'=>$page,
                'paginator' => $paginator,
                'sortOrder' => $query_params['sort'] ?? '', 
                'd'=>$dateHelper,
                'iaR'=>$iaR,
                'payments'=>$this->payments($paymentRepository),
                'max'=>(int)$settingRepository->get_setting('default_list_limit'),
            ];
            return $this->viewRenderer->render('index', $parameters);  
        } //if user 
        return $this->webService->getRedirectResponse('payment/index');
    }
    
     /**
      * 
      * @param Request $request
      * @param CurrentRoute $currentRoute
      * @param MerchantRepository $merchantRepository
      * @param SettingRepository $settingRepository
      * @param UserClientRepository $ucR
      * @param UserInvRepository $uiR
      * @param DateHelper $dateHelper
      * @return \Yiisoft\DataResponse\DataResponse|Response
      */
    public function guest_online_log(Request $request, CurrentRoute $currentRoute, 
                          MerchantRepository $merchantRepository, 
                          SettingRepository $settingRepository,
                          UserClientRepository $ucR,
                          UserInvRepository $uiR,
                          DateHelper $dateHelper): \Yiisoft\DataResponse\DataResponse|Response
    {   
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page','1');
        /** @psalm-suppress MixedAssignment $sort */
        $sort = $query_params['sort'] ?? '-inv_id';
        $sort_by = Sort::only(['inv_id','date', 'successful', 'driver'])
                // Sort the merchant responses in descending order
                ->withOrderString((string)$sort); 
        // Retrieve the user from Yii-Demo's list of users in the User Table
        /** @var User $user */
        $user = $this->userService->getUser();
        $user_id = $user->getId();
        if (null!==$user_id) {
            // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users
            $userinv = ($uiR->repoUserInvUserIdcount($user_id) > 0 
                     ? $uiR->repoUserInvUserIdquery($user_id) 
                     : null);
            $client_id_array = (null!== $userinv ? $ucR->get_assigned_to_user($user_id) : []);
            $merchants = $this->merchant_with_sort_guest($merchantRepository, $client_id_array, $sort_by); 
            $paginator = (new OffsetPaginator($merchants))
             ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
             ->withCurrentPage($page)
             ->withNextPageToken((string)$page);
            // No need for rbac here since the route accessChecker for payment/online_log
            // includes 'viewPayment' @see config/routes.php
            $parameters = [
                'alert'=>$this->alert(),
                'page'=>$page,
                'paginator' => $paginator,
                'sortOrder' => $query_params['sort'] ?? '', 
                'd'=>$dateHelper,
                'merchants'=>$this->merchants($merchantRepository),
                'max'=>(int)$settingRepository->get_setting('default_list_limit'),
            ];
            return $this->viewRenderer->render('online_log', $parameters);  
        }
        return $this->webService->getRedirectResponse('payment/index');
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $paymentRepository
     * @param SettingRepository $settingRepository
     * @param DateHelper $dateHelper
     * @param InvAmountRepository $iaR
     */
    public function index(Request $request,  
                          CurrentRoute $currentRoute,  
                          PaymentRepository $paymentRepository, 
                          SettingRepository $settingRepository, 
                          DateHelper $dateHelper,  
                          InvAmountRepository $iaR) : \Yiisoft\DataResponse\DataResponse {
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page','1');
        // Clicking on the gridview's Inv_id column hyperlink generates 
        // the query_param called 'sort' which is seen in the url
        // Clicking on the paginator does not generate the query_param 'sort'
        /** @psalm-suppress MixedAssignment $sort */
        $sort = $query_params['sort'] ?? '-inv_id';
        $sort_by = Sort::only(['id','inv_id','payment_date'])
                // Sort the merchant responses in descending order
                ->withOrderString((string)$sort); 
        $payments = $this->payments_with_sort($paymentRepository, $sort_by); 
        $paginator = (new OffsetPaginator($payments))
         ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
         ->withCurrentPage($page)
         ->withNextPageToken((string) $page);
        $canEdit = $this->userService->hasPermission('editPayment');
        $canView = $this->userService->hasPermission('viewPayment');
        $parameters = [
            'alert'=>$this->alert(),
            'canEdit'=>$canEdit,
            'canView'=>$canView,
            'grid_summary'=> $settingRepository->grid_summary($paginator, $this->translator, (int)$settingRepository->get_setting('default_list_limit'), $this->translator->translate('invoice.payments'), ''),
            'page'=>$page,
            'paginator' => $paginator,
            'sortOrder' => $query_params['sort'] ?? '', 
            'd'=>$dateHelper,
            'iaR'=>$iaR,
            'payments'=>$this->payments($paymentRepository),
            'max'=>(int)$settingRepository->get_setting('default_list_limit'),
        ];
        return $this->viewRenderer->render('index', $parameters);  
    }
    
    /**
     * @param MerchantRepository $merchantRepository
     *
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function merchants(MerchantRepository $merchantRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader 
    {
        $merchants = $merchantRepository->findAllPreloaded();        
        return $merchants;
    }
    
    /**
     * @param MerchantRepository $merchantRepository
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, \App\Invoice\Entity\Merchant>
     */
    private function merchant_with_sort(MerchantRepository $merchantRepository, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $merchants = $merchantRepository->findAllPreloaded()
                                        ->withSort($sort);
        return $merchants;
    }
    
    /**
     * @param MerchantRepository $merchantRepository
     * @param array $client_id_array
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, \App\Invoice\Entity\Merchant>
     */
    private function merchant_with_sort_guest(MerchantRepository $merchantRepository, array $client_id_array, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $merchant_responses = $merchantRepository->findOneUserManyClientsMerchantResponses($client_id_array)
                                                 ->withSort($sort);  
        return $merchant_responses;
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param MerchantRepository $merchantRepository
     * @param SettingRepository $settingRepository
     * @param DateHelper $dateHelper
     */
    public function online_log(Request $request, CurrentRoute $currentRoute,  
                          MerchantRepository $merchantRepository, 
                          SettingRepository $settingRepository, 
                          DateHelper $dateHelper): \Yiisoft\DataResponse\DataResponse
    {   
        $query_params = $request->getQueryParams();
        $page = (int)$currentRoute->getArgument('page','1');
        /** @psalm-suppress MixedAssignment $sort */
        $sort = $query_params['sort'] ?? '-inv_id';
        $sort_by = Sort::only(['inv_id','date', 'successful', 'driver'])
                // Sort the merchant responses in descending order
                ->withOrderString((string)$sort); 
        $merchants = $this->merchant_with_sort($merchantRepository, $sort_by); 
        $paginator = (new OffsetPaginator($merchants))
         ->withPageSize((int)$settingRepository->get_setting('default_list_limit'))
         ->withCurrentPage($page)
         ->withNextPageToken((string) $page);
        // No need for rbac here since the route accessChecker for payment/online_log
        // includes 'viewPayment' @see config/routes.php
        $parameters = [
            'alert'=>$this->alert(),
            'page'=>$page,
            'paginator' => $paginator,
            'sortOrder' => $query_params['sort'] ?? '', 
            'd'=>$dateHelper,
            'merchants'=>$this->merchants($merchantRepository),
            'max'=>(int)$settingRepository->get_setting('default_list_limit'),
        ];
        return $this->viewRenderer->render('online_log', $parameters);  
    }
    
    /**
     * @param PaymentRepository $paymentRepository
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Payment>
     */
    private function payments_with_sort(PaymentRepository $paymentRepository, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $payments = $paymentRepository->findAllPreloaded()
                                      ->withSort($sort);
        return $payments;
    }
    
    /**
     * @param PaymentRepository $paymentRepository
     * @param array $client_id_array
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Payment>
     */
    private function payments_with_sort_guest(PaymentRepository $paymentRepository, array $client_id_array, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $payments = $paymentRepository->findOneUserManyClientsPayments($client_id_array)
                                      ->withSort($sort);  
        return $payments;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $paymentRepository
     * @return Payment|null
     */
    private function payment(CurrentRoute $currentRoute, PaymentRepository $paymentRepository): Payment|null 
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $payment = $paymentRepository->repoPaymentquery($id);
            return $payment;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function payments(PaymentRepository $paymentRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader 
    {
        $payments = $paymentRepository->findAllPreloaded();        
        return $payments;
    }
    
    /**
     * @param string $payment_id
     * @param PaymentCustomRepository $pcR
     * @return array
     */
    private function payment_custom_values(string $payment_id, PaymentCustomRepository $pcR) : array
    {
        // Function edit: Get field's values for editing
        $custom_field_form_values = [];
        if ($pcR->repoPaymentCount($payment_id) > 0) {
          $payment_custom_fields = $pcR->repoFields($payment_id);
          
          /** 
           * @var string $key 
           * @var string $val
           */
          foreach ($payment_custom_fields as $key => $val) {
               $custom_field_form_values['custom[' .$key . ']'] = $val;
          }
        }
        return $custom_field_form_values;
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $viewPayment = $this->userService->hasPermission('viewPayment');
        if (!$viewPayment){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('payment/index');
        }
        return $viewPayment;
    }
    
    // payment/view => '#btn_save_payment_custom_fields' => payment_custom_field.js => /invoice/payment/save_custom";
    
    /**
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param PaymentCustomRepository $pcR
     */
    public function save_custom(FormHydrator $formHydrator, Request $request, PaymentCustomRepository $pcR) : \Yiisoft\DataResponse\DataResponse
    {
            $js_data = $request->getQueryParams();
            $payment_id = (string)$js_data['payment_id'];
            $custom_field_body = [            
                'custom'=>(array)$js_data['custom'] ?: '',            
            ];
            $this->custom_fields($formHydrator, $custom_field_body, $payment_id, $pcR);
            return $this->factory->createResponse(Json::encode(['success'=>1])); 
    }
    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param PaymentRepository $paymentRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, PaymentRepository $paymentRepository,
        SettingRepository $settingRepository
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $payment = $this->payment($currentRoute, $paymentRepository);
        if ($payment) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['payment/edit', ['id' => $payment->getId()]],
                'errors' => [],
                'body' => $this->body($payment),
                'payment' => $paymentRepository->repoPaymentquery($payment->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('payment/index');
    }
}