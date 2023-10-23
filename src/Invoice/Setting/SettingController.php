<?php
declare(strict_types=1);

namespace App\Invoice\Setting;

// App
use App\Invoice\Entity\Setting;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\EmailTemplate\EmailTemplateRepository as ER;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Libraries\Crypt;
use App\Invoice\PaymentMethod\PaymentMethodRepository as PM;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\CurrencyHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\Helpers\StoreCove\StoreCoveArrays;
use App\Invoice\Libraries\Sumex;
use App\Invoice\TaxRate\TaxRateRepository as TR;
//use App\Invoice\Libraries\Sumex;
use App\Service\WebControllerService;
use App\User\UserService;
// Yii
use Yiisoft\Aliases\Aliases;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Files\FileHelper;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Security\Random;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Miscellaneous
use \DateTimeZone;

final class SettingController
{
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private SettingService $settingService;
    private Translator $translator;
    private UserService $userService;    
    private DataResponseFactoryInterface $factory;
    private Flash $flash;
    private Session $session;
    private SettingRepository $s;
    
    public function __construct(
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        SettingService $settingService,
        Translator $translator,
        UserService $userService,
        DataResponseFactoryInterface $factory,
        Session $session,
        SettingRepository $s,    
    ) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/setting')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->settingService = $settingService;
        $this->translator = $translator;
        $this->userService = $userService;
        $this->factory = $factory;
        $this->flash = new Flash($session);
        $this->session = $session;
        $this->s = $s;
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
    
    // The debug index is simply a list of the settings that are useful to change when debugging and appears in red    
    
    /**
     * @param CurrentRoute $currentRoute
     */
    public function debug_index(CurrentRoute $currentRoute): \Yiisoft\DataResponse\DataResponse
    {  
        $pageNum = (int)$currentRoute->getArgument('page', '1');
        $paginator = (new OffsetPaginator($this->settings($this->s)))
        ->withPageSize((int)$this->s->get_setting('default_list_limit'))
        ->withCurrentPage($pageNum);
        $canEdit = $this->rbac(); 
        $parameters = [
          'paginator' => $paginator,
          's'=>$this->s,
          'alert' => $this->alert(),
          'canEdit' => $canEdit,
          'settings' => $this->settings($this->s),
          'session'=>$this->session,
          'trans'=>$this->translator->translate('invoice.setting.translator.key'),
          'section'=>$this->translator->translate('invoice.setting.section'),
          'subsection'=>$this->translator->translate('invoice.setting.subsection'),
        ];
        return $this->viewRenderer->render('debug_index', $parameters);
    }
    
    // The tab_index is the index of settings showing in non debug mode
    
    /**
     * 
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ViewRenderer $head
     * @param ER $eR
     * @param GR $gR
     * @param PM $pm
     * @param SettingRepository $sR
     * @param TR $tR
     * @return Response
     */
    public function tab_index(Request $request, 
                              FormHydrator $formHydrator, 
                              ViewRenderer $head, 
                              ER $eR, 
                              GR $gR, 
                              PM $pm, 
                              SettingRepository $sR, 
                              TR $tR) : Response {
        
        $aliases = new Aliases(['@invoice' => dirname(__DIR__), 
                                '@language' => '@invoice/Language',
                                '@icon' => '@invoice/Uploads/Temp']);
        $datehelper = new DateHelper($this->s);
        $numberhelper = new NumberHelper($this->s);
        $countries = new CountryHelper();
        $crypt = new Crypt();
        $peppol_arrays = new PeppolArrays();
        $matrix = $this->s->expandDirectoriesMatrix($aliases->get('@language'), 0);
        /**
         * @psalm-suppress PossiblyInvalidArgument $matrix
         */
        $languages = ArrayHelper::map($matrix,'name','name');      
        $parameters = [
            'defat'=> $sR->withKey('default_language'),
            'action'=>['setting/tab_index'],
            'alert' => $this->alert(),
            's'=> $this->s,
            'head' => $head,
            'body'=> $request->getParsedBody(),
            'general'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_general',[
                's'=>$this->s,
                /**
                 * @psalm-suppress PossiblyInvalidArgument
                 */
                'languages'=> $languages,
                'first_days_of_weeks'=>['0' => $this->s->lang('sunday'), '1' => $this->s->lang('monday')],
                'date_formats'=>$datehelper->date_formats(),
                // Used in ClientForm
                'time_zones'=> DateTimeZone::listIdentifiers(),
                'countries'=>$countries->get_country_list((string)$this->session->get('_language')),
                'gateway_currency_codes'=>CurrencyHelper::all(),
                'number_formats'=>$this->s->number_formats(),
                'current_date'=>new \DateTime(),
                'icon'=>$aliases->get('@icon')
            ]),
            'invoices'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_invoices',[
                's'=>$this->s,
                'invoice_groups'=>$gR->findAllPreloaded(),
                'payment_methods'=>$pm->findAllPreloaded(),
                'public_invoice_templates'=>$this->s->get_invoice_templates('public'),
                'pdf_invoice_templates'=>$this->s->get_invoice_templates('pdf'),
                'email_templates_invoice'=>$eR->repoEmailTemplateType('invoice'),
                'roles' => Sumex::ROLES,
                'places' => Sumex::PLACES,
                'cantons' => Sumex::CANTONS,
            ]),
            'quotes'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_quotes',[
                's'=>$this->s,
                'invoice_groups'=>$gR->findAllPreloaded(),
                'public_quote_templates'=>$this->s->get_quote_templates('public'),
                'pdf_quote_templates'=>$this->s->get_quote_templates('pdf'),
                'email_templates_quote'=>$eR->repoEmailTemplateType('quote'),
            ]),
            'salesorders'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_client_purchase_orders',[
                's'=>$this->s,
                'invoice_groups'=>$gR->findAllPreloaded(),
            ]),
            'taxes'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_taxes',[
                's'=>$this->s,
                'tax_rates'=>$tR->findAllPreloaded(),
            ]),
            'email'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_email',[
                's'=>$this->s,
            ]),
            'google_translate'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_google_translate',[
                'locales'=>$this->s->locales(),
                's'=>$this->s,
            ]),
            'online_payment'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_online_payment',[
                's'=>$this->s,
                'gateway_drivers'=>$this->s->payment_gateways(),
                'gateway_currency_codes'=>CurrencyHelper::all(),
                'gateway_regions' => $this->s->amazon_regions(),
                'payment_methods'=>$pm->findAllPreloaded(),                
                'crypt'=> $crypt
            ]),
            'mpdf' => $this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_mpdf',[
                's'=>$this->s,
            ]),            
            'projects_tasks'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_projects_tasks',[
                's'=>$this->s,
            ]),            
            'vat_registered'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_vat_registered',[
                's'=>$this->s,
            ]),
            'peppol_electronic_invoicing'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_peppol',[
                's'=>$this->s,
                'config_tax_currency' => $this->s->get_config_peppol()['TaxCurrencyCode'] ?: $this->s->get_config_company_details()['tax_currency'],
                'gateway_currency_codes'=>CurrencyHelper::all(),
                // if delivery/invoice periods are used, a tax point date cannot be determined
                // because goods have not been delivered ie. no date supplied, and no invoice has been issued ie. no date issued/created after the goods have been delivered
                // A stand-in-code or description code 'stands in' or substitutes for how the tax point will be determined/calculated
                // If a stand-in-code exists, it is because a tax point cannot be determined
                // Therefore they are mutually exclusive. 
                // They cannot both exist at the same time.
                'stand_in_codes'=> $peppol_arrays->getUncl2005subset(),
                // use crypt to decode the store cove api key
                'crypt' => $crypt    
            ]),
            'storecove'=>$this->viewRenderer->renderPartialAsString('/invoice/setting/views/partial_settings_storecove',[
                'countries'=>$countries->get_country_list((string)$this->session->get('_language')),
                'sender_identifier_array'=>StoreCoveArrays::store_cove_sender_identifier_array(),
                's'=>$this->s,
            ]),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $parameters['body'];
            if (is_array($body)) {
                $settings = (array)$body['settings'];
                /** 
                 * @var string $key
                 * @var string $value
                 */
                foreach ($settings as $key => $value) {
                    $key === 'tax_rate_decimal_places' && (int)$value !== 2 ? $this->tab_index_change_decimal_column((int)$value) : '';
                    // Deal with existing keys after first installation
                    if ($sR->repoCount($key) > 0) {
                        if (strpos($key, 'field_is_password') !== false || strpos($key, 'field_is_amount') !== false) {
                            // Skip all meta fields
                            continue;
                        }                    
                        if (isset($settings[$key . '_field_is_password']) && empty($value)) {
                            // Password field, but empty value, let's skip it
                            continue;
                        }
                        if (isset($settings[$key . '_field_is_password']) && $value !=='') {
                            // Encrypt passwords but don't save empty passwords
                            $this->tab_index_settings_save($key, (string)$crypt->encode(trim($value)), $sR);
                        } elseif (isset($settings[$key . '_field_is_amount'])) {
                            // Format amount inputs
                            $this->tab_index_settings_save($key, (string)$numberhelper->standardize_amount($value), $sR);
                        } else {     
                            $this->tab_index_settings_save($key, $value, $sR);
                        }  

                        if ($key == 'number_format') {
                            // Set thousands_separator and decimal_point according to number_format
                            // Derive the 'decimal_point' and 'thousands_separator' setting from the chosen ..number format eg. 1000,000.00 if it has a value
                           $this->tab_index_number_format($value, $sR);
                        }   
                    }
                    else {
                       // The key does not exist because the repoCount is not greater than zero => add
                       // Note:
                       // The settings 'decimal_point' and 'thousands_separator' which are derived from number_format array
                       // and were installed on the first run in InvoiceController 
                       // will be derived automatically => their repoCount will be greater than zero and will not cause this to run
                       $this->tab_index_debug_mode_ensure_all_settings_included(true, $key, $value, $formHydrator);
                    }                
                }
                $this->flash_message('info', $this->s->trans('settings_successfully_saved'));
                return $this->webService->getRedirectResponse('setting/tab_index');
                }
            }
            return $this->viewRenderer->render('tab_index', $parameters);        
    }
    
    /**
     * 
     * @param string $key
     * @param string $value
     * @param SettingRepository $sR
     * @return void
     */
    public function tab_index_settings_save(string $key, string $value, SettingRepository $sR) : void {
        $setting = $sR->withKey($key);
        if ($setting) {
            $setting->setSetting_value($value);
            $sR->save($setting);
        }
    }
    
    /**
     * 
     * @param int $value
     * @return void
     */
    public function tab_index_change_decimal_column(int $value) : void {
        // Change the decimal column dynamically using cycle and the Fragment command. SyncTable has been commented out from config/params.php
        // TODO
    }
    
    /**
     * 
     * @param string $value
     * @param SettingRepository $sR
     * @return void
     */
    public function tab_index_number_format(string $value, SettingRepository $sR) : void {
        // Set thousands_separator and decimal_point according to number_format
        $number_formats = $sR->number_formats();
        if ($sR->repoCount('decimal_point') > 0) {
            $this->tab_index_settings_save('decimal_point',
                                            $number_formats[$value]['decimal_point'], 
                                            $sR);
        }
        if ($sR->repoCount('thousands_separator') > 0) {      
            $this->tab_index_settings_save('thousands_separator',
                                            $number_formats[$value]['thousands_separator'], 
                                            $sR);
        }     
    }
    
    // This procedure is used in the above procedure to ensure that all settings are being captured.
    /**
     * 
     * @param bool $bool
     * @param string $key
     * @param string $value
     * @param FormHydrator $formHydrator
     * @return void
     */
    public function tab_index_debug_mode_ensure_all_settings_included(bool $bool, string $key, string $value, FormHydrator $formHydrator): void {
        // The setting does not exist because repoCount is not greater than 0;
        if ($bool) {
            // Make sure the setting is available to be set in the database if there is no such like setting in the database
            $form = new SettingForm();
            $array = [
                'setting_key'=>$key,
                'setting_value'=>$value,
            ];
            if ($formHydrator->populate($form, $array) && $form->isValid()) {
                $this->settingService->saveSetting(new Setting(), $form);
            }
        }
    }
    
    /**
     * 
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $parameters = [
            'title' => $this->s->trans('add'),
            'action' => ['setting/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$this->s
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new SettingForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->settingService->saveSetting(new Setting(), $form);
                $this->flash_message('info', $this->s->trans('record_successfully_updated'));
                return $this->webService->getRedirectResponse('setting/debug_index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * Use: Toggle between draft invoice has 1. invoice number generated or 2. no Invoice number generated 
     * Route name: setting/draft route action setting/inv_draft_has_number_switch
     * @see /config/common/routes.php
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function inv_draft_has_number_switch(CurrentRoute $currentRoute): Response 
    {
      $setting = $this->setting($currentRoute, $this->s);
      if ($setting) {
          if ($setting->getSetting_value() == '0') {
             $setting->setSetting_value('1');
             $this->s->save($setting);
             return $this->webService->getRedirectResponse('inv/index');
          }
          if ($setting->getSetting_value() == '1') {
             $setting->setSetting_value('0');
             $this->s->save($setting);
             return $this->webService->getRedirectResponse('inv/index');
          }
      }
      return $this->webService->getRedirectResponse('inv/index');
    }
    
    /**
     * 
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute, 
             FormHydrator $formHydrator): Response 
    {
        $setting = $this->setting($currentRoute, $this->s);
        if ($setting) {
            $parameters = [
                'title' => $this->s->trans('edit'),
                'action' => ['setting/edit', ['setting_id' => $setting->getSetting_id()]],
                'errors' => [],
                'body' => [
                    'setting_key' => $setting->getSetting_key(),
                    'setting_value' => $setting->getSetting_value(),                
                ],
                's'=>$this->s,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new SettingForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->settingService->saveSetting($setting, $form);
                    $this->flash_message('info', $this->s->trans('record_successfully_updated'));
                    return $this->webService->getRedirectResponse('setting/debug_index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('setting/debug_index');
    }
    
    /**
     * @return true
     */
    public function true(): bool  {
       return true; 
    }
    
    /**
     * @return false
     */
    public function false(): bool {
        return false;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute): Response 
    {
        $setting = $this->setting($currentRoute,$this->s);
        if ($setting) {
            $this->flash_message('info','This record has been deleted.');
            $this->settingService->deleteSetting($setting);               
        }
        return $this->webService->getRedirectResponse('setting/debug_index');        
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
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function save_form(Request $request, CurrentRoute $currentRoute, 
             FormHydrator $formHydrator): Response 
    {
        $setting = $this->setting($currentRoute, $this->s);
        if ($setting) {
            $parameters = [
                'title' => $this->s->trans('edit'),
                'action' => ['setting/edit', ['setting_id' => $setting->getSetting_id()]],
                'errors' => [],
                'body' => [
                    'setting_key' => $setting->getSetting_key(),
                    'setting_value' => $setting->getSetting_value(),
                ],
                's'=>$this->s,
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new SettingForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    $this->settingService->saveSetting($setting, $form);
                    return $this->webService->getRedirectResponse('setting/index');
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            }
            return $this->viewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('setting/index');
    }
    
    /**
     * @param CurrentRoute $currentRoute
     */
    public function view(CurrentRoute $currentRoute)
        : \Yiisoft\DataResponse\DataResponse|Response {
        $setting = $this->setting($currentRoute, $this->s);
        if ($setting) {
            $parameters = [
                'title' => $this->s->trans('view'),
                'action' => ['setting/edit', ['setting_id' => $setting->getSetting_id()]],
                'errors' => [],
                'setting'=>$setting,
                's'=>$this->s,     
                'body' => [
                    'setting_id'=>$setting->getSetting_id(),
                    'setting_key'=>$setting->getSetting_key(),
                    'setting_value'=>$setting->getSetting_value(),
                ],            
            ];
            return $this->viewRenderer->render('__view', $parameters);
        }
        return $this->webService->getRedirectResponse('setting/index');
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('setting/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param SettingRepository $settingRepository
     * @return Setting|null
     */
    private function setting(CurrentRoute $currentRoute, 
                             SettingRepository $settingRepository): Setting|null 
    {
        $setting_id = $currentRoute->getArgument('setting_id');
        if (null!==$setting_id) {
            $setting = $settingRepository->repoSettingquery($setting_id);
            return $setting; 
        }
        return null;
    }
    
    //$settings = $this->settings();
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function settings(SettingRepository $settingRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader{
        $settings = $settingRepository->findAllPreloaded();
        return $settings;
    }
    
    /**
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function clear() : \Yiisoft\DataResponse\DataResponse
    {
        $directory = dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR. 'assets';
        try {
            $filehelper = new FileHelper;
            $filehelper->clearDirectory($directory);
            $this->flash_message('info', $this->translator->translate('invoice.setting.assets.cleared.at').$directory);
            return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/successful',
            ['heading'=>$this->translator->translate('invoice.successful'),'message'=>$this->translator->translate('invoice.setting.you.have.cleared.the.cache')])); 
        } catch (\Exception $e) {            
            $this->flash_message('warning', $this->translator->translate('invoice.setting.assets.were.not.cleared.at') .$directory. $this->translator->translate('invoice.setting.as.a.result.of').$e->getMessage());            
            return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/unsuccessful',
            ['heading'=>$this->translator->translate('invoice.unsuccessful'),'message'=> $this->translator->translate('invoice.setting.you.have.not.cleared.the.cache.due.to.a') . $e->getMessage(). $this->translator->translate('invoice.setting.error.on.the.public.assets.folder')])); 
        }
    }
    
    public function get_cron_key() : \Yiisoft\DataResponse\DataResponse
    {       
        $parameters = [
               'success'=>1,
               'cronkey'=>Random::string(32)
        ];
        return $this->factory->createResponse(Json::encode($parameters));     
    }
}
