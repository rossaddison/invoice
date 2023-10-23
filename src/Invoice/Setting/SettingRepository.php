<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

use App\Invoice\Entity\Setting;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Libraries\Lang;
use App\Invoice\Quote\QuoteRepository as QR;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;
use Yiisoft\Html\Html;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\Cycle\Data\Writer\EntityWriter;
use Yiisoft\Yii\Runner\Http\HttpApplicationRunner;

/**
 * @template TEntity of Setting
 * @extends Select\Repository<TEntity>
 */
final class SettingRepository extends Select\Repository
{
    private EntityWriter $entityWriter;
    
    public array $settings = [];
    
    private SessionInterface $session;
    private TranslatorInterface $translator;        
    /**
     * 
     * @param Select<TEntity> $select 
     * @param EntityWriter $entityWriter
     * @param SessionInterface $session
     */
    public function __construct(Select $select, EntityWriter $entityWriter, SessionInterface $session, TranslatorInterface $translator)
    {
        $this->entityWriter = $entityWriter;
        $this->session = $session;
        $this->translator = $translator;
        parent::__construct($select);
    }
    
    /**
     * Get settings without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }
    
    /**
     * 
     * @param string $setting_key
     * @return int
     */
    public function repoCount(string $setting_key) : int {
        $count = $this->select()
                      ->where(['setting_key' => $setting_key])                                
                      ->count();
        return $count; 
    }
            
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param Setting|null $setting
     * @throws Throwable 
     * @return void
     */
    public function save(Setting|null $setting): void
    {
        if (null!==$setting) {
            if ($setting->getSetting_key() === 'default_language') {
                $this->session->set('_language', $setting->getSetting_value());            
            }
            $this->entityWriter->write([$setting]);        
        }
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param Setting|null $setting
     * @throws Throwable 
     * @return void
     */
    public function delete(Setting|null $setting): void
    {
        $this->entityWriter->delete([$setting]);
    }

    /**
     * 
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id', 'setting_key', 'setting_value'])
                ->withOrder(['setting_key' => 'asc'])
        );
    }
    
    /**
     * 
     * @param string $setting_id
     * @return Setting|null
     */
    public function repoSettingquery(string $setting_id): Setting|null
    {
        $query = $this
            ->select()
            ->where(['id' => $setting_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $setting_key
     * @return Setting|null
     */
    public function withKey(string $setting_key): Setting|null
    {
        $query = $this
            ->select()
            ->where(['setting_key' => $setting_key]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * 
     * @param string $setting_value
     * @return Setting|null
     */
    public function withValue(string $setting_value): Setting|null
    {
        $query = $this
            ->select()
            ->where(['setting_value' => $setting_value]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * Build settings array
     * 
     * @return void
     */
    public function load_settings(): void
    {
        $all_settings = $this->findAllPreloaded();
        /** @var Setting $setting */
        foreach ($all_settings as $setting) {
            /** @var string $this->settings[$setting->getSetting_key()] */
            $this->settings[$setting->getSetting_key()] = $setting->getSetting_value();
        }        
    }
    
    /**
     * 
     * @param string $key
     * @return string
     */
    public function get_setting(string $key) : string
    {
        // Build settings array
        $this->load_settings();
        /** @var string $this->settings[$key] */
        if (array_key_exists($key, $this->settings)) {
            $setting = $this->settings[$key];
        } else {
            // Add any newly added keys from payment_gateway array
            $setting = $this->add_setting_key($key);
        }
        return $setting;
    }
    
    /**
     * Save the new setting key and make it available
     * 
     * @param string $key
     * @return string
     */
    public function add_setting_key(string $key) : string {
           // Add the new setting key
            // eg. if a new payment gateway has been added
            // to the Payment Gateway array save all the new keys
            // and values of the array
            $new_setting = new Setting();
            $new_setting->setSetting_key($key);
            // Default value is ''
            $new_setting->setSetting_value('');
            $this->save($new_setting);
            $this->load_settings();
            /** @var string $this->settings[$key] */
            $setting = $this->settings[$key];
            return $setting;
    }
    
    /**
     * 
     * @param string $key
     * @return string
     */
    public function setting(string $key) : string
    {
         $this->load_settings();
        /** @var string $this->settings[$key] */
        $setting = $this->settings[$key];
        return $setting;
    }    
    
    /**
     * 
     * @param string $key
     * @param string $value
     * @return void
     */
    public function set_setting(string $key, string $value): void
    {
        $this->settings[$key] = $value;
    }
    
    /**
     * 
     * @param string $base_dir
     * @param int $level
     * @return array
     */
    public function expandDirectoriesMatrix(string $base_dir, int $level): array {
        $directories = [];
        foreach(scandir($base_dir) as $file) {
            if($file == '.' || $file == '..') continue;
            $dir = $base_dir.DIRECTORY_SEPARATOR.$file;
            if(is_dir($dir)) {
            $directories[]= array(
                    'level' => $level,
                    'name' => $file,
                    'path' => $dir             
                );
            }
        }
        return $directories;
    } 
    
    /**
     * 
     * @param mixed $value1
     * @param mixed $value2
     * @param string $operator
     * @param bool $checked
     * @return void
     */
    public function check_select(mixed $value1, mixed $value2, string $operator = '==', bool $checked = false) : void
    {
        $select = $checked ? 'checked="checked"' : 'selected="selected"';

        // Instant-validate if $value1 is a bool value
        if (is_bool($value1) && $value2 === null) {
            echo $value1 ? $select : '';
        }

        switch ($operator) {
            case '==':
                $echo_selected = $value1 == $value2 ? true : false;
                break;
            case '!=':
                $echo_selected = $value1 != $value2 ? true : false;
                break;
            case 'e':
                $echo_selected = empty($value1) ? true : false;
                break;
            case '!e':
                $echo_selected = empty($value1) ? true : false;
                break;
            default:
                $echo_selected = $value1 ? true : false;
                break;
        }

        echo $echo_selected ? $select : '';
    }
    
   /**
     * @return (mixed|string)[]
     *
     * @psalm-return array{esmtp_scheme: mixed, esmtp_host: mixed, esmtp_port: mixed, use_send_mail: string}
     */
    public function config_params() : array {     
        $config = $this->get_config_params();
        $params = $config->get('params');
        /**
         * @var array $params['symfony/mailer']
         * @var string $params['yiisoft/mailer']['useSendmail']
         * @var string $params['symfony/mailer']['esmtpTransport']['scheme']
         * @var string $params['symfony/mailer']['esmtpTransport']['host']
         * @var string $params['symfony/mailer']['esmtpTransport']['port']
         */
        $config_array = [
            'esmtp_scheme' =>$params['symfony/mailer']['esmtpTransport']['scheme'],
            'esmtp_host'=>$params['symfony/mailer']['esmtpTransport']['host'],
            'esmtp_port'=>$params['symfony/mailer']['esmtpTransport']['port'],
            'use_send_mail'=>$params['yiisoft/mailer']['useSendmail'] == 1 ? $this->trans('true') : $this->trans('false'),           
        ];
        return $config_array;
    }
    
    /**
     * @return array
     */
    public function get_config_company_details() : array {
        $config = $this->get_config_params();
        $params = $config->get('params');
        /**
         * @var array $params['company']
         * @var string $params['company']['name']
         * @var string $params['company']['address_1']
         * @var string $params['company']['address_2']
         * @var string $params['company']['zip']
         * @var string $params['company']['city']         
         * @var string $params['company']['state']
         * @var string $params['company']['country']
         * @var string $params['company']['vat_id']
         * @var string $params['company']['tax_code']
         * @var string $params['company']['tax_currency'],
         * @var string $params['company']['phone']
         * @var string $params['company']['fax']
         * @var string $params['company']['iso_3166_country_identification_code']
         * @var string $params['company']['iso_3166_country_identification_list_id']
         */
        $company_array = [
            'name' => $params['company']['name'],
            'address_1' => $params['company']['address_1'],
            'address_2' => $params['company']['address_2'],
            'zip' => $params['company']['zip'],
            'city' => $params['company']['city'],
            'state' => $params['company']['state'],
            'country' => $params['company']['country'],
            'vat_id' => $params['company']['vat_id'],
            'tax_code' => $params['company']['tax_code'],
            'tax_currency' => $params['company']['tax_currency'],
            'phone' => $params['company']['phone'],
            'fax' => $params['company']['fax'],
            'iso_3166_country_identification_code' => $params['company']['iso_3166_country_identification_code'],
            'iso_3166_country_identification_list_id' => $params['company']['iso_3166_country_identification_list_id']
        ];
        return $company_array;
    }
    
    /**
     * @return string
     */
    public function getTaxCurrencyFromConfigDetails() : string
    {
        /*
         *  @var array $this->get_config_company_details()
         */
        $company_details = $this->get_config_company_details();
        /** @var string $company_details['tax_currency'] */ 
        return $company_details['tax_currency'];
    }
    
    /**
     * Used in EmailTemplateController add and edit functions which use the form 
     * to merge with 'froms' FromDropDownController emails
     *
     * @return array
     */
    public function getConfigMailerEmails() : array {
      $config = $this->get_config_params();
      $params = $config->get('params');

      // Currently two adminEmail and senderEmail
      $mailer_emails_array = (array)$params['mailer'];
      return $mailer_emails_array;
    }
    
    /**
     * 
     * @return string
     */
    public function getConfigSenderEmail() : string {
      $mailer_emails = $this->getConfigMailerEmails();
      /**
       * @var string $mailer_emails['senderEmail']
       */
      return $mailer_emails['senderEmail'];      
    }
    
    /**
     * 
     * @return string
     */
    public function getConfigAdminEmail() : string {
      $mailer_emails = $this->getConfigMailerEmails();
      /**
       * @var string $mailer_emails['adminEmail']
       */
      return $mailer_emails['adminEmail'];      
    }
        
    /**
     * @see config/params.php 
     * @return int
     */
    public function getSchemaProvidersMode() : int 
    {
        $config = $this->get_config_params();
        $params = $config->get('params');
        
        $yii_cycle_array = (array)$params['yiisoft/yii-cycle'];
        $schema_providers_array = (array)$yii_cycle_array['schema-providers'];
        $php_file_array = (array)$schema_providers_array['Yiisoft\\Yii\\Cycle\\Schema\\Provider\\PhpFileSchemaProvider'];
        return (int)$php_file_array['mode'];
    }       
    
    /**
     * 
     * @return array
     */
    public function get_config_peppol() : array {
        $config = $this->get_config_params();
        $params = $config->get('params');
        /**
         * @var array $params['peppol']['invoice']
         * @var array $params['peppol']['invoice']['AccountingSupplierParty']
         * @var array $params['peppol']['invoice']['AccountingSupplierParty']['Party']
         * @var array $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PartyIdentification']['ID']
         * @var string $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PartyIdentification']['ID']['value']
         * @var string $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PartyIdentification']['ID']['schemeID']
         * @var array $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PostalAddress']
         * @var array $params['peppol']['invoice']['AccountingSupplierParty']['Party']['Contact']
         * @var array $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PartyTaxScheme']         
         * @var array $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PartyLegalEntity'] 
         * @var array $params['peppol']['invoice']['AccountingSupplierParty']['Party']['EndPointID']  
         * @var string $params['peppol']['invoice']['TaxCurrencyCode']
         * @var string $params['peppol']['invoice']['PaymentMeans'] 
         * @var string $params['peppol']['invoice']['DocumentCurrencyCode'] 
         */
        $peppol_flat_array = [
            'SupplierPartyIdentificationId' => $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PartyIdentification']['ID']['value'],
            'SupplierPartyIdentificationSchemeId' => $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PartyIdentification']['ID']['schemeID'],
            'SupplierPartyIdentificationPostalAddress' => $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PostalAddress'],
            'Contact' => $params['peppol']['invoice']['AccountingSupplierParty']['Party']['Contact'],
            'PartyTaxScheme' => $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PartyTaxScheme'], 
            'PartyLegalEntity' => $params['peppol']['invoice']['AccountingSupplierParty']['Party']['PartyLegalEntity'],
            'EndPointID' => $params['peppol']['invoice']['AccountingSupplierParty']['Party']['EndPointID'],
            'PaymentMeans' => $params['peppol']['invoice']['PaymentMeans'],
            'TaxCurrencyCode' => $params['peppol']['invoice']['TaxCurrencyCode']
        ];
        return $peppol_flat_array;
    }
    
    /**
     * 
     * @return ConfigInterface
     */
    public function get_config_params() : ConfigInterface {
        $rootPath = dirname(dirname(dirname(__DIR__)));
        $http_runner = new HttpApplicationRunner(
                //$rootPath
                $rootPath,
                //$debug 
                false, 
                //$checkEvents
                false,
                //$environment
                null,
                //$bootstrapGroup
                'bootstrap-web',
                //$eventsGroup
                'events-web',
                //$diGroup
                'di-web',
                //$diProvidersGroup
                'di-providers-web',
                //$diDelegatesGroup
                'di-delegates-web',
                //$diTagsGroup
                'di-tags-web',
                //$paramsGroup
                'params-web',
                //$nestedParamsGroups
                ['params'],
                //$nestedEventsGroups
                ['events'],
        );
        $params = $http_runner->getConfig();
        return $params;
    }
    
    /**
     * @return string[]
     *
     * @psalm-return array{English: 'en_GB', French: 'fr_FR', German: 'de_DE', Japan: 'jp_JP', Italian: 'it_IT', Spanish: 'es_ES'}
     */
    public function amazon_languages() : array {
        $languages = [
            'English' => 'en_GB',
            'French' => 'fr_FR',
            'German' => 'de_DE',
            'Japan' => 'jp_JP',
            'Italian' => 'it_IT',
            'Spanish' => 'es_ES',
        ];
        return $languages;
    }
    
    /**
     * 
     * @return array
     */
    public function amazon_regions() : array {
        $regions = [
            'North America' => 'na',
            'Japan' => 'jp',
            'Europe' => 'eu',
        ];
        return $regions;
     }
    
    /**
     * 
     * @return array
     */
    public function locale_language_array() : array {
        // locale => src/Invoice/Language/{language folder name}    
        $language_list = [
            'af'=>'Afrikaans',
            'ar'=>'Arabic',
            'az'=>'Azerbaijani',
            'fr'=>'French',
            'de'=>'German',
            'en'=>'English',
            'es'=>'Spanish',
            'id'=>'Indonesian',            
            'ja'=>'Japanese',
            'nl'=>'Dutch',
            'ru'=>'Russian',
            'sk'=>'Slovakian',            
            'uk'=>'Ukrainian',
            'uz'=>'Uzbek',
            'vi'=>'Vietnamese',
            // Use camelcase here => remove the space between Chinese and Simplified and in the original folder otherwise it will not be 
            // retrieved
            'zh'=>'ChineseSimplified',                
        ];
        return $language_list;
    }
    
    /**
     * 
     * @param string $session_language
     * @return void
     */
    public function save_session_locale_to_cldr(string $session_language) : void {
        if ($this->repoCount('cldr') > 0) {
            $cldr = $this->withKey('cldr');
            if ($cldr instanceof Setting) {
                $cldr->setSetting_value($session_language);
                $this->save($cldr);
            }
        } else {
            $cldr = new Setting();
            $cldr->setSetting_key('cldr');
            $cldr->setSetting_value($session_language);
            $this->save($cldr);
        }
    }
    
    // The default_language setting is a setting of last resort and should be set infrequently 
    // If the locale is set, and it exists in the above language array, then use it in preference to the default_language
    // Return: string eg. "English"
    
    /**
     * 
     * @return string
     */
    public function get_folder_language() : string {
        // Prioritise the use of the locale dropdown since it will always be set. config/params/locales
        $sess_lang = (string)$this->session->get('_language'); 
        // The print language is set under the get_print_language function in pdfHelper which uses the clients language as priority
        $print_lang = (string)$this->session->get('print_language');
        // Use the print language if it is not empty over the locale language
        if (empty($print_lang)) {
            return (!empty($sess_lang) && (array_key_exists($sess_lang, $this->locale_language_array()))) 
                             ? (string)$this->locale_language_array()[$sess_lang] 
            : ($this->get_setting('default_language') ?: "English");         
        } else {
            return $print_lang;
        }   
    }       
    
    /**
     * @return array
     */
    public function load_language_folder(): array
    {   
        $folder_language = $this->get_folder_language();           
        $lang = new Lang();
        $lang->load('ip',$folder_language);
        $lang->load('gateway',$folder_language);
        $lang->load('custom',$folder_language);
        $lang->load('merchant',$folder_language);
        $lang->load('form_validation',$folder_language);
        $languages = $lang->_language;
        return $languages;
    }  
    
    /**
     * 
     * @param string $words
     * @return string
     */
    public function trans(string $words) : string
    {
        $language_folder = $this->load_language_folder();
        /** 
         * @var string $value 
         * @var string $key
         */
        foreach ($language_folder as $key => $value){
             if ($words === $key){
                  return $value;                                    
             }
        }
        return '';
    }
    
    /**
     * 
     * @param string $invoice_id
     * @param IR $iR
     * @return void
     */
    public function invoice_mark_viewed(string $invoice_id, IR $iR): void
    {
        $invoice = $iR->repoInvUnloadedquery($invoice_id);
        if ($invoice) {
            //mark as viewed if status is 2                                    
            if (($iR->repoCount($invoice_id)>0) && $invoice->getStatus_id()===2){
                //set the invoice to viewed status ie 3
                $invoice->setStatus_id(3);
                $iR->save($invoice);
            }

            //set the invoice to 'read only' only once it has been viewed according to 'Other settings' 
            //2 sent, 3 viewed, 4 paid,
            if ($this->get_setting('read_only_toggle') == 3)
            {
                $invoice = $iR->repoInvUnloadedquery($invoice_id);
                if ($invoice) {
                    $invoice->setIs_read_only(true);
                    $iR->save($invoice);
                }
            }
        }    
    }
    
   /**
    * 
    * @param string $quote_id
    * @param QR $qR
    * @return void
    */
    public function quote_mark_viewed(string $quote_id, QR $qR) : void
    {
        $quote = $qR->repoQuoteStatusquery($quote_id,2);
        if ($quote) {
            //mark as viewed if status is 2
            if ($qR->repoCount($quote_id)>0){
                //set the quote to viewed status ie 3
                $quote->setStatus_id(3);
                $qR->save($quote);
            }
        }    
    }
    
    /**
     * @param null|string $invoice_id
     */
    public function invoice_mark_sent(string|null $invoice_id, IR $iR) : void
    {
            if (null!==$invoice_id) {
            $invoice = $iR->repoInvUnloadedquery($invoice_id);
            if ($invoice) {
                //draft->sent->view->paid
                //set the invoice to sent ie. 2                                    
                if ($invoice->getStatus_id() === 1){
                    $invoice->setStatus_id(2);
                }
                //set the invoice to read only ie. not updateable, if invoice_status_id is 2
                if (null!==$this->withKey('read_only_toggle')) {
                    if ($this->withKey('read_only_toggle')?->getSetting_value() === '2')
                    {
                        $invoice->setIs_read_only(true);            
                    }
                }
                $iR->save($invoice);
                
            }
        }    
    }
    
    /**
     * 
     * @param string|null $quote_id
     * @param QR $qR
     * @return void
     */
    public function quote_mark_sent(string|null $quote_id, QR $qR) : void
    {
        // Quote exists and has a status of 1 ie. draft
        if ($qR->repoQuoteStatuscount($quote_id, 1) > 0) {
            $quote = $qR->repoQuoteStatusquery($quote_id,1);
            if ($quote){
                 $quote->setStatus_id(2);
                 $qR->save($quote);
            }
        }   
    }
    
    public function getTermsAndConditions() : array {
        $array = [
            // I have not accepted the terms
            $this->translator->translate('invoice.term.1'),
            // I have accepted the terms
            $this->translator->translate('invoice.term.2'),
        ];
        return $array;
    }
    
    
    // Add to src/Invoice
    public static function getPlaceholderRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR.'Uploads'.DIRECTORY_SEPARATOR;
    } 
    
    public static function getAssetholderRelativeUrl(): string
    {        
        return DIRECTORY_SEPARATOR.'Asset'.DIRECTORY_SEPARATOR;
    }
    
    public static function getCustomerfolderRelativeUrl(): string
    {        
        return DIRECTORY_SEPARATOR.'Customer_files';
    }
    
    public static function getPemFileFolder(): string {
        return DIRECTORY_SEPARATOR.'Pem_unique_folder';
    }
    
    public static function getGoogleTranslateJsonFileFolder(): string {
        return DIRECTORY_SEPARATOR.'Google_translate_unique_folder';
    }
    
    public static function getCompanyPrivateLogosRelativefolderUrl(): string
    {        
        return DIRECTORY_SEPARATOR.'Company_private_logos';
    }
    
    public static function getTempPeppolfolderRelativeUrl(): string
    {        
        return DIRECTORY_SEPARATOR.'Temp'.DIRECTORY_SEPARATOR.'Peppol'.DIRECTORY_SEPARATOR;
    }
    
    public static function getTempZugferdfolderRelativeUrl(): string
    {        
        return DIRECTORY_SEPARATOR.'Temp'.DIRECTORY_SEPARATOR.'Zugferd'.DIRECTORY_SEPARATOR;
    }
    
    
    public static function getTemplateholderRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR.'Invoice_templates'.DIRECTORY_SEPARATOR.'Pdf'.DIRECTORY_SEPARATOR;
    }        
    
    // Append to uploads folder
    public static function getUploadsArchiveholderRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR.'Archive';
    }
    
    // Append to uploads folder
    public static function getUploadsCustomerFilesRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR.'Customer_files';
    }
    
    // Append to uploads folder
    public static function getUploadsProductImagesRelativeUrl(): string
    {
        return DIRECTORY_SEPARATOR.'ProductImages';
    }
    
    // Append to uploads folder
    public static function getAttachmentsCustomerFilesRelativeUrl(): string
    {
        return 'src'.DIRECTORY_SEPARATOR.'Invoice'.DIRECTORY_SEPARATOR.'Uploads'.DIRECTORY_SEPARATOR.'Customer_files'.DIRECTORY_SEPARATOR;
    }
    
    /**
     * 
     * @param mixed $amount
     * @return string
     */
    public function format_currency(mixed $amount): string
    {
        $this->load_settings();
        $currency_symbol =$this->get_setting('currency_symbol');
        $currency_symbol_placement = $this->get_setting('currency_symbol_placement');
        $thousands_separator = $this->get_setting('thousands_separator');
        $decimal_point = $this->get_setting('decimal_point');

        if ($currency_symbol_placement == 'before') {
            return $currency_symbol . number_format((float)$amount, ($decimal_point) ? 2 : 0, $decimal_point, $thousands_separator);
        } elseif ($currency_symbol_placement == 'afterspace') {
            return number_format((float)$amount, ($decimal_point) ? 2 : 0, $decimal_point, $thousands_separator) . '&nbsp;' . $currency_symbol;
        } else {
            return number_format((float)$amount, ($decimal_point) ? 2 : 0, $decimal_point, $thousands_separator) . $currency_symbol;
        }
    }
    
    //show the decimal point representation character whether a comma, a dot, or something else with maximum of 2 decimal points after the point
    
    /**
     * @param float|null $amount
     * @return string|null
     */
    
    public function format_amount(float|null $amount = null): string|null
    {
        $this->load_settings();    
        if ($amount) {
            $thousands_separator = $this->get_setting('thousands_separator');
            $decimal_point = $this->get_setting('decimal_point');
            //force the rounding of amounts to 2 decimal points if the decimal point setting is filled.
            return number_format($amount, ($decimal_point) ? 2 : 0, $decimal_point, $thousands_separator);
        }
        return null;
    }
    
    /**
     * @param float $amount
     * @return string
     */    
    public function standardize_amount(float $amount): string
    {
        $this->load_settings();
        $thousands_separator = $this->get_setting('thousands_separator');
        $decimal_point = $this->get_setting('decimal_point');
        $amt = str_replace($thousands_separator, '', (string)$amount);
        $final_amt = str_replace($decimal_point, '.', $amt);
        return $final_amt;
    }
    
    
    /**
     * @return Aliases
     */
    public function get_img() : Aliases
    {
        $aliases = new Aliases(['@base' => dirname(dirname(dirname(__DIR__))), 
                                '@img' => dirname(dirname(dirname(__DIR__)))
                                          .DIRECTORY_SEPARATOR. 'src'
                                          .DIRECTORY_SEPARATOR. 'Invoice'
                                          .DIRECTORY_SEPARATOR. 'Asset'
                                          .DIRECTORY_SEPARATOR. 'core'
                                          .DIRECTORY_SEPARATOR. 'img',
                               ]);
        return $aliases;
    }
    
    /**
     * 
     * @param string $type
     * @return array
     */
    public function get_invoice_templates(string $type = 'pdf') : array
    {
        $aliases = new Aliases(['@base' => dirname(dirname(dirname(__DIR__))), 
                                '@pdf' => '@base/resources/views/invoice/template/invoice/pdf',
                                '@public' =>'@base/resources/views/invoice/template/invoice/public'
                               ]);
        $templates = [];
        if ($type == 'pdf') { 
            $templates = array_diff(scandir($aliases->get('@pdf'),SCANDIR_SORT_ASCENDING), array('..', '.'));
        } elseif ($type == 'public') {
            $templates = array_diff(scandir($aliases->get('@public'),SCANDIR_SORT_ASCENDING), array('..', '.'));
        }        
        return $this->remove_extension($templates);
    }
    
    /**
     * 
     * @param string $type
     * @return array
     */
    public function get_quote_templates(string $type = 'pdf') : array
    {
         $aliases = new Aliases(['@base' => dirname(dirname(dirname(__DIR__))), 
                                 '@pdf' => '@base/resources/views/invoice/template/quote/pdf',
                                 '@public' =>'@base/resources/views/invoice/template/quote/public'
                               ]);
        $templates = [];
        if ($type == 'pdf') { 
            $templates = array_diff(scandir($aliases->get('@pdf'),SCANDIR_SORT_ASCENDING), array('..', '.'));
        } elseif ($type == 'public') {
            $templates = array_diff(scandir($aliases->get('@public'),SCANDIR_SORT_ASCENDING), array('..', '.'));
        }
        return $this->remove_extension($templates);
    }
    
    /**
     * @return Aliases
     */
    public function get_invoice_archived_folder_aliases(): Aliases {
        $aliases = new Aliases(['@base' => dirname(dirname(dirname(__DIR__))), 
                                '@archive_invoice' => '@base/src/Invoice/Uploads'.$this->getUploadsArchiveholderRelativeUrl().'/Invoice/'
        ]);
        return $aliases;
    }
    
    /**
     * @return Aliases
     */
    public function get_customer_files_folder_aliases(): Aliases {
        $aliases = new Aliases(['@base' => dirname(dirname(dirname(__DIR__))), 
                                '@customer_files' => '@base/src/Invoice/Uploads'.$this->getUploadsCustomerFilesRelativeUrl(),
                                '@public' => '@base/public'
        ]);
        return $aliases;
    }
    
    /**
     * @return Aliases
     */
    public function get_company_private_logos_folder_aliases(): Aliases {
        $aliases = new Aliases(['@base' => dirname(dirname(dirname(__DIR__))), 
                                '@company_private_logos' => '@base/src/Invoice/Uploads'.$this->getCompanyPrivateLogosRelativefolderUrl(),
                                '@public' => '@base/public'
        ]);
        return $aliases;
    }
    
    /**
     * @return Aliases
     */
    public function get_google_translate_json_file_aliases(): Aliases {
        $aliases = new Aliases(['@base' => dirname(dirname(dirname(__DIR__))), 
                                '@google_translate_json_file_folder' => '@base/src/Invoice'.$this->getGoogleTranslateJsonFileFolder()
        ]);
        return $aliases;
    }
    
    /**
     * @return Aliases
     */
    public function get_productimages_files_folder_aliases(): Aliases {
        $aliases = new Aliases(['@base' => dirname(dirname(dirname(__DIR__))), 
                                // Internal folder not normally used for storage
                                '@productimages_files' => '@base/src/Invoice/Uploads'.$this->getUploadsProductImagesRelativeUrl(),
                                '@public' => '@base/public',
                                
                                // Web accessible external folder normally used  
                                '@public_product_images' => '@public/products'
        ]);
        return $aliases;
    }
    
    /**
     * 
     * @param string $invoice_number
     * @return array
     */
    public function get_invoice_archived_files_with_filter(string $invoice_number): array
    {        
        $aliases = $this->get_invoice_archived_folder_aliases();
        $filehelper = new FileHelper();
        // TODO Use PathPattern to create *.pdf and '*_'.$invoice_number.'.pdf' pattern
        $filter = (new PathMatcher())
                   ->doNotCheckFilesystem()
                   ->only($invoice_number.'.pdf');        
        $files = $filehelper::findFiles($aliases->get('@archive_invoice'), ['recursive'=>false,'filter'=>$filter]);                
        return $files;
    }
    
    /**
     * @return Aliases
     */
    public function get_amazon_pem_file_folder_aliases(): Aliases {
        $aliases = new Aliases(['@base' => dirname(dirname(dirname(__DIR__))), 
                                '@pem_file_unique_folder' => '@base/src/Invoice'.$this->getPemFileFolder(),
        ]);
        return $aliases;
    }
    
    /**
     * 
     * @param array $files
     * @return array
     */
    private function remove_extension(array $files) : array
    {
        /**
         * @var string $file
         */
        foreach ($files as $key => $file) {
            $files[$key] = str_replace('.php', '', $file);
        }

        return $files;
    }
    
    // php 8.0 compatible gateways for omnipay 3.2
    // Working with ...src/Invoice/Language/English/gateway_lang.php
    // label must correspond to ...src/Language/English/gateway_lang.php
    
    /**
     * 
     * @return array
     */
    public function payment_gateways() : array 
    {
        $payment_gateways = array(
            'AuthorizeNet_AIM' => array(
                'apiLoginId' => array(
                    'type' => 'text',
                    'label' => 'Api Login Id',
                ),
                'transactionKey' => array(
                    'type' => 'text',
                    'label' => 'Transaction Key',
                ),
                'testMode' => array(
                    'type' => 'checkbox',
                    'label' => 'Test Mode',
                ),
                'developerMode' => array(
                    'type' => 'checkbox',
                    'label' => 'Developer Mode',
                ),
                'version' => array(
                    'type' => 'checkbox',
                    'label' => 'Omnipay Version'                    
                )
                //'liveEndpoint' => array(
                //    'type' => 'text',
                //    'label' => 'Live Endpoint',
                //),
                //'developerEndpoint' => array(
                //    'type' => 'text',
                //    'label' => 'Developer Endpoint',
                //),
            ),
            'AuthorizeNet_SIM' => array(
                'apiLoginId' => array(
                    'type' => 'text',
                    'label' => 'Api Login Id',
                ),
                'transactionKey' => array(
                    'type' => 'text',
                    'label' => 'Transaction Key',
                ),
                'testMode' => array(
                    'type' => 'checkbox',
                    'label' => 'Test Mode',
                ),
                'developerMode' => array(
                    'type' => 'checkbox',
                    'label' => 'Developer Mode',
                ),
                'version' => array(
                    'type' => 'checkbox',
                    'label' => 'Omnipay Version'                    
                )
                //'liveEndpoint' => array(
                //    'type' => 'text',
                //    'label' => 'Live Endpoint',
                //),
                //'developerEndpoint' => array(
                //    'type' => 'text',
                //    'label' => 'Developer Endpoint',
                //),
                //'hashSecret' => array(
                //    'type' => 'text',
                //    'label' => 'Hash Secret',
                //),
            ),
            'PayPal_Express' => array(
                'username' => array(
                    'type' => 'text',
                    'label' => 'Username',
                ),
                'password' => array(
                    'type' => 'password',
                    'label' => 'Password',
                ),
                'signature' => array(
                    'type' => 'password',
                    'label' => 'Signature',
                ),
                'testMode' => array(
                    'type' => 'checkbox',
                    'label' => 'Test Mode',
                ),
                'version' => array(
                    'type' => 'checkbox',
                    'label' => 'Omnipay Version'                    
                )
                //'solutionType' => array(
                //    'type' => 'text',
                //    'label' => 'Solution Type',
                //),
                //'landingPage' => array(
                //    'type' => 'text',
                //    'label' => 'Landing Page',
                //),
                //'brandName' => array(
                //    'type' => 'text',
                //    'label' => 'Brand Name',
                //),
                //'headerImageUrl' => array(
                //    'type' => 'text',
                //    'label' => 'Header Image Url',
                //),
                //'logoImageUrl' => array(
                //    'type' => 'text',
                //    'label' => 'Logo Image Url',
                //),
                //'borderColor' => array(
                //    'type' => 'text',
                //    'label' => 'Border Color',
                //),
            ),
            'PayPal_Pro' => array(
                'username' => array(
                    'type' => 'text',
                    'label' => 'Username',
                ),
                'password' => array(
                    'type' => 'password',
                    'label' => 'Password',
                ),
                'signature' => array(
                    'type' => 'text',
                    'label' => 'Signature',
                ),
                'testMode' => array(
                    'type' => 'checkbox',
                    'label' => 'Test Mode',
                ),
                'version' => array(
                    'type' => 'checkbox',
                    'label' => 'Omnipay Version'                    
                )
            ),
            // Below are listed online dashboard tested PCI COMPLIANT Payment Gateways 
            'Amazon_Pay' => array(
                'publicKeyId' => array(
                    'type' => 'password',
                    'label' => 'Public Key ID',
                ),
                'merchantId' => array(
                    'type' => 'password',
                    'label' => 'Merchant ID',
                ),
                'clientId' => array(
                    'type' => 'password',
                    'label' => 'Client ID',
                ),
                'clientSecret' => array(
                    'type' => 'password',
                    'label' => 'Client Secret',
                ),
                'returnUrl' => array(
                    'type' => 'text',
                    'label' => 'Return Url',
                ),
                'storeId' => array(
                    'type' => 'password',
                    'label' => 'Store Id'
                ),
                'version' => array(
                    'type' => 'checkbox',
                    'label' => 'Omnipay Version'                    
                ),                
                'sandbox' => array(
                    'type' => 'checkbox',
                    'label' => 'Sandbox'                    
                )
            ),
            'Stripe' => array(
                'apiKey' => array(
                    'type' => 'password',
                    'label' => 'Api Key',
                ),
                // @see src/Invoice/Language/English/gateway_lang
                // Not server-side ie. client-side                
                'publishableKey' => array(
                    'type' => 'password',
                    'label' => 'Publishable Key',
                ),
                // server-side @see https://dashboard.stripe.com/test/dashboard
                'secretKey' => array(
                    'type' => 'password',
                    'label' => 'Secret Key',
                ),
                'version' => array(
                    'type' => 'checkbox',
                    'label' => 'Omnipay Version'                    
                ),
                // 'sandbox' => array(
                //    'type' => 'checkbox',
                //    'label' => 'Sandbox'                    
                //)
            ),
            'StoreCove' => array(
                'apiKey' => array(
                    'type' => 'password',
                    'label' => 'Api Key',
                )
            ),
            // https://sandbox.braintreegateway.com/merchants
            'Braintree' => array(
                'privateKey' => array(
                    'type' => 'password',
                    'label' => 'Api Key',
                ),
                'publicKey' => array(
                    'type' => 'password',
                    'label' => 'Public Key',
                ),
                'merchantId' => array(
                    'type' => 'password',
                    'label' => 'Merchant Id',
                ),
                'version' => array(
                    'type' => 'checkbox',
                    'label' => 'Omnipay Version'                    
                ),
                'sandbox' => array(
                    'type' => 'checkbox',
                    'label' => 'Sandbox'                    
                ),
            ),
            // March 2023 
            //https://developer.paypal.com/docs/checkout/advanced/
            // Eligibility: Australia, Canada, France, 
            //              Germany, Italy, Spain,
            //              United States, United Kingdom
            // https://developer.paypal.com/sdk/js/configuration/
            //'PayPal_Checkout' => array(                
            //    'clientId' => array(
            //        'type' => 'password',
            //        'label' => 'Client Id',
            //    ),
            //    'clientSecret' => array(
            //        'type' => 'password',
            //        'label' => 'Client Secret',
            //    ),
            //    'returnUrl' => array(
            //        'type' => 'text',
            //        'label' => 'Return Url',
            //    ),
            //    'version' => array(
            //        'type' => 'checkbox',
            //        'label' => 'Omnipay Version'                    
            //    ),
            //    'sandbox' => array(
            //        'type' => 'checkbox',
            //        'label' => 'Sandbox'                    
            //    ),
            //    'webhookId' => array(
            //        'type' => 'text',
            //        'label' => 'Webhook Id',
            //    )                
            //), 
        );
        return $payment_gateways;
    }
    
    // Return the Upper case first with underscore gateway keys
    /**
     * @return (int|string)[]
     *
     * @psalm-return list<array-key>
     */
    public function payment_gateways_enabled_DriverList(): array {
        $available_drivers = [];
        $gateways = $this->payment_gateways();
        foreach ($gateways as $driver => $_fields) {
            $d = strtolower((string)$driver);
            if ($this->get_setting('gateway_' . $d . '_enabled') === '1') {
                $available_drivers[] = $driver;                
            }
        }
        return $available_drivers;
    }
    
    // Sandbox Url Array
    /**
     * @return string[]
     *
     * @psalm-return array{stripe: 'https://dashboard.stripe.com', amazon_pay: 'https://sellercentral-europe.amazon.com/external-payments/sandbox/home', braintree: 'https://sandbox.braintreegateway.com/login'}
     */
    public function sandbox_url_array() : array {
        $sandbox_array = [
            'stripe' => 'https://dashboard.stripe.com',
            'amazon_pay' => 'https://sellercentral-europe.amazon.com/external-payments/sandbox/home',
            'braintree' => 'https://sandbox.braintreegateway.com/login',
        ];
        return $sandbox_array;
    }
    
     /**
     * Lang
     *
     * Fetches a language variable
     *
     * @param string $in_line The language line
     *
     * @return string
     *
     * @psalm-param ''|'monday'|'sunday' $in_line
     */
    public function lang(string $in_line = '') 
    {
           $line = $this->trans($in_line);
           return $line;
    }
    
    public function tooltip_array() : array {
        $tooltip = [
         // General Settings   
        'bcc_mails_to_admin'=>[
          'why'=>'A blind carbon copy email, unseen to the recipient of the email, is sent to the administrator.',
          'where'=>' Helpers/MailerHelper yii_mailer_send function.'
        ],       
        'cron_key'=>[
          'why'=>'A cron job is used on the server to automatically email recurring invoices to clients.',
          'where'=>'This will be setup later.'
        ],
        'currency_code_from_to'=>[
          'why'=>'If the sender\'s elected invoice\'s document\'s currency is different to their (the sender\'s) country\'s currency you will have to enter an exchange rate relevant to today here.',
          'where'=>'src/Invoice/Helpers/Peppol/PeppolHelper/generate_invoice_peppol_ubl_xml_temp_file function'
        ],
        'currency_symbol' => [
          'why'=>'Used in NumberHelper/format_amount.',
          'where'=>'views/invoice/inv/partial_item_table, views/invoice/quote/partial_item_table, views/invoice/invitem/_item_edit_task and _item_edit_product'
        ],
        'currency_symbol_placement' => [
          'why'=>'NumberHelper/format_amount. ',
          'where'=>'views/invoice/inv/partial_item_table, views/invoice/quote/partial_item_table, views/invoice/invitem/_item_edit_task and _item_edit_product'
        ],   
        'currency_code' => [
          'why'=>'Used in PaymentInformationController and the dropdown array is constructed in src/Invoice/Helpers/CurrencyHelper',
          'where'=>'PaymentInformationController and CurrencyHelper'
        ],    
        'custom_title'=>[
          'why'=>'This custom designed title appears in the top left corner of the current browser tab.',
          'where'=>'layout/invoice'
        ],
        'date_tax_point'=>[
          'why'=>'Necessary for calculating VAT submissions to Receivers of Revenue',
          'where'=>'Refer to src\Invoice\Inv\InvService function set_tax_point. Variables used: 14 days, Date Supplied (Date Delivered), Date Created'   
        ],
        'default_email_template' => [
          'why'=>'Build your first template using Settings...Email Template. Your first email to the customer will use this template. '.
                 'Typically you will include various fields from the database in this template by dragging and dropping them when you build this template. ' .
                 'Normally you will create three templates ie. Normal, Overdue, and Paid. ' .
                 'The Normal Invoice Template that you create will be linked to the setting email_invoice_template. ' .
                 'The Paid Invoice Template that you create will be linked to the setting email_invoice_template_paid. ' .
                 'The Overdue Invoice Template that you create will be linked to the setting email_invoice_template_overdue. ' .
                 'Depending on the status of the invoice, the TemplateHelper matches the appropriate email template to the status of the invoice. ',
          'where'=>'src/Invoice/Helpers/TemplateHelper/select_email_invoice_template'
        ],    
        'date_format' => [
          'why'=>'This is used exclusively in DateHelper functions.',
          'where'=>'App/Invoice/Helpers/DateHelper.php'
        ],    
        'default_country' => [
          'why'=>'If a user, or client, do not have a country linked to them, this is the default country used',
          'where'=>'ClientController/Edit and UserInvController'
        ],
        'default_language' => [
          'why'=>'This is the language used if the session print language or the locale dropdown language are not set',
          'where'=>'setting/get_folder_language'
        ],    
        'default_list_limit' => [
          'why'=>'This value is used with the Paginator to limit the number of records viewed',
          'where'=>'ClientController/Edit'
        ],    
        'default_invoice_group'=>[
          'why'=>'When a new invoice or quote is created, the package uses invoice groups to determine the next invoice or quote number,'.
                 'and how it should be structured. The package comes with two default invoice groups namely Invoice Default and Quote Default. '. 
                 'Both groups will generate simple incremental IDs starting at the number 1, but the Quote Default will be prefixed with QUO. '.
                 'An example of an identifier tag might be eg. {{{year}}}-{{{month}}}-{{{day}}}-{{{ID}}}'.
                 'The ID tab must be included in all identifiers, preferably towards the end of the identifier.',
          'where'=>'views\invoice\group\_form.'
        ],
        'default_terms'=> [
            'why'=>'You can enter the default terms here for any invoice.',
            'where'=>' views\invoice\inv\_form'
        ],
        // Note: Appears as 'public_invoice_template' under settings table but as 'default_invoice_template' for language purposes =>ip_lang.php
        'default_public_template'=>[
            'why'=>'This is the HTML template that the client will see online prior to payment. The template has a pay-now button. The client must log in having been assigned observer role status in order to see this html invoice template. Different HTML Templates can be created in this folder and chosen in this dropdown.',
            'where'=>'views/invoice/template/invoice/public/Invoice_Web.php (subsequent to client gateway selection from inv/view) and also InvController/url_key function that receives the url_key and gateway query parameters in the Url from inv/view. This HTML template holds the pay-now button with the chosen gateway (passed from inv/view) which at this point cannot be changed. If the payment is successful the template and therefore the pay-now button will reflect as paid.'
        ],           
        'disable_quickactions'=>[
          'why'=>'This setting is used in the dashboard.',
          'where'=>'views/invoice/dashboard/index.php and also in InvoiceController/dashboard function', 
        ],   
        'disable_sidebar'=>[
          'why'=>'Enable or disable sidebar.',
          'where'=>'views/layout/invoice and also in InvoiceController/install_default_settings_on_first_run'
        ],  
        'email_send_method'=>[
            'why'=>'Symfony mailer is now the default mailer. '.
            'What is ESMTP? In response to the rampant spam problem on the internet, '.
            'an extension of SMTP was released in 1995: extended SMTP (ESMTP for short). '. 
            'It adds additional commands to the protocol in 8-bit ASCII code, enabling many '.
            'new functions to save bandwidth and protect servers. These include, for example: '.
            'Authentication of the sender, SSL encryption of e-mails, Possibility of attaching multimedia files to e-mails '.
            'Restrictions on the size of e-mails according to server specifications, '.
            'Simultaneous transmission to several recipients, '.
            'Standardised error messages in case of undeliverability',
            'where'=>'src/Invoice/Helpers/MailerHelper/mailer_configured function.'
        ],
        'email_pdf_attachment'=>[
            'why'=>'When an email is sent to a customer/client, the relevant invoice is automatically archived at'.
            ' src/Invoice/Uploads/Archive/Invoice. '.
            'Send this archived pdf to the customer along with any attachments when using the button ' .
            'Options...Send on the view/invoice.' . 
            'This setting is enabled by default under the InvoiceController',
            'where'=>'src/Invoice/Helpers/MailerHelper/yii_mailer_send function variable email_attachment_with_pdf_template. ' .
            'Run with view/invoice Options...Send  using MailerInvForm'
        ],
        'enable_vat_registration'=>[
            'why' => 'VAT uses line item tax and applying Invoice Taxes (whether before line item or after line tax) are disabled. Hence the tax_total field in the InvAmount Entity will always equal zero if VAT is used. ' .
                     'A new nullable field ... belongs_to_vat_invoice...has been introduced in the InvItem entity to allow for companies making this transition. '  ,
            'where' => 'This setting is used in resources/views/invoice/inv/view.php',
        ],
        'first_day_of_week' => [
          'why'=>'This is used in the javascript function on views/layout/invoice.php along with the datehelper datepicker function.',
          'where'=>'views/layout/invoice.php'
        ],    
        'generate_invoice_number_for_draft'=>[
            'why'=>'Automatically generate an Invoice Number by means of the Group Identifier. '.
            'When an invoice is first created, it is placed in Draft status by default. Sending an invoice by email will automatically change the status from Draft to Sent. Clients cannot view any invoices when they are in Draft status. ',
            'where'=>'InvController/generate_inv_get_number and InvRepository/get_inv_number'            
        ],
        'generate_quote_number_for_draft'=>[
            'why'=>'Automatically generate a Quote Number by means of the Group Identifier.',
            'where'=>'QuoteController/generate_quote_number_if_applicable and QuoteRepository/get_quote_number and GroupRepository/generate_number.'
        ],
        'google_translate_json_filename'=>[
            'why'=>'GeneratorController includes a function google_translate_lang. '.            
            'This function takes the English ip_lang array or gateway_lang located in src/Invoice/Language/English and translates it into the chosen locale (Settings...View...Google Translate) outputting it to resources/views/generator/output_overwrite'. "\r\n".
            '---Step--1: Download https://curl.haxx.se/ca/cacert.pem into active c:\wamp64\bin\php\php8.1.12 folder'."\r\n".
            '---Step--2: Select your project that you created under https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?supportedpurview=project'."\r\n".
            '---Step--3: Click on Actions icon and select Manage Keys'."\r\n".
            '---Step--4: Add Key'."\r\n".
            '---Step--5: Choose the Json File option and Download the file to src/Invoice/Google_translate_unique_folder'."\r\n".
            '---Step--6: You will have to enable the Cloud Translation API and provide your billing details. You will be charged 0 currency. '."\r\n".
            '---Step--7: Move the file from views/generator/output_overwrite to eg. src/Invoice/Language/{your language}',
            'where'=>'GeneratorController/google_translate_lang',
            //'menu' => true,
        ],
        'google_translate_en_app_php'=>[
            'why'=>'If you are wanting to translate resources/messages/en/app.php make sure you have loaded a copy in the ../Language/English folder.',
            'where'=>'GeneratorController/google_translate_lang',
            
        ],       
        'google_translate_locale'=>[
            'why'=>'To save time manually translating an ip_lang file using Google Translate Online, the Google Translate API https://github.com/googleapis/google-cloud-php-translate can be used to translate to your chosen locale. eg. es / Spanish',
            'where'=>'GeneratorController/google_translate_lang'
        ],
        'include_delivery_period'=>[
            'why'=>'A group of business terms providing information on the invoice period. Also called delivery period. If the group is used, the invoiceing period start date and/or end date must be used. ',
            'where'=>'src/Invoice/Delivery/DeliveryController'
        ],    
        'include_zugferd' => [
            'why'=>'ZUGFeRD stands for Zentraler User Guide des Forums elektronische Rechnung Deutschland ' .
                   'It is a uniform standard for the electronic transmission of invoice data in Germany. ' .
                   'The aim of the standard is to harmonise the exchange of information between companies and with public authorities. ' .
                   'With the standard, the information contained in invoices can be read and processed automatically. ' .
                   'This enables both you and the recipients of your documents to automatically transfer the invoice data to third-party systems with little effort. ' . 
                   'With the help of the standard, the entire content of the invoice can be transferred to an ERP system. ',
            'where'=> 'src/Invoice/Libraries and src/Invoice/Helpers/ZugFerdHelper' 
        ],     
        'install_test_data' => [
          'why'=>'This is used by Generator..Reset Data and Generator..Remove Data during the testing of data',
          'where'=>'invoice/test_data_reset and invoice/test_data_remove'
        ], 
        'invoice_default_payment_method'=>[
            'why'=>'Default: 1 => None, 2 => Cash, 3 => Cheque, 4 => Card/Direct Debit - Succeeded ' .
                 '5 => Card/Direct Debit - Processing 6 => Card/Direct Debit - Customer Ready.',
            'where'=>'InvoiceController/install_default_settings_on_first_run and '.
                   'InvController/create_confirm function which assigns the default of 1 to all invoices when created. ' .
                   'See src/Invoice/Asset/rebuild-1.13/js/inv.js #inv_create_confirm function and '.
                   'resources/views/invoice/inv/modal_create_inv.php as well.'            
        ],
        'invoices_due_after'=>[
          'why'=>'The number of days after the original invoice date when invoices become due for payment.',
          'where'=>'InvRepository/get_date_due and Entity/Inv/setDate_due().'
        ], 
        'invoice_overview_period'=>[
          'why'=>'This setting is used on the dashboard so that the invoices that are shown will either be this-month, last-month, this-quarter, last-quarter, this-year, or last-year',
          'where'=>'views/invoice/dashboard/index.php and also in InvoiceController/dashboard function', 
        ],  
        'login_logo'=>[
          'why'=>'',
          'where'=>''
        ],    
        'mark_invoices_sent_pdf'=>[
            'why'=>'If the invoice is downloaded it will be marked as sent.',
            'where'=>'InvController/pdf and InvController/email_stage_2 when viewing the invoice.'
        ],
        'mark_invoices_sent_copy'=>[
            'why'=>'Clients do not have access to draft invoices. Mark a copied invoice as sent so that the client can view it. Caution: Used for testing purposes only. '.
                   'By default copied invoices are marked as draft and therefore can not be viewed by the client online. '.
                   'They can only be viewed by the client once they have been sent by email or marked as sent manually in the Invoice Edit section under Inv/View/Options Dropdown Button. '  ,
            'where'=> 'InvController/inv_to_inv'
        ],    
        'monospace_amounts'=>[
          'why'=>'Evenly spaced characters for better presentation.',
          'where'=>'views/layout/invoice.php and views/layout/guest.php'
        ],
        'mpdf_ltr' => [
          'why'=>'Settings for https://mpdf.github.io/',
          'where'=>'src/Invoice/Helpers/MpdfHelper.php function initialize_pdf',  
        ],    
        'number_format'=>[
          'why'=>'When the number format is chosen, the decimal point, and thousands_separator settings have to be derived from the number_format array located in SettingsRepository using the tab_index_number_format function in the SettingController',
          'where'=>'SettingController/tab_index_number_format'
        ],  
        'open_reports_in_new_tab'=>[
          'why'=>'Open reports up in a new tab. Featured in eg. Reports...invoice_aging_index.php',  
          'where'=>' eg. views/invoice/invoice_aging_index.php'
        ],
        'pdf_archive_inv'=> [
            'why'=>'Pdf\'s that are generated can be archived under a folder called Archive situated in the Uploads folder.',
            'where'=>'pdfHelper pdfCreate function'
        ],  
        'pdf_watermark'=>[
            'why'=>'eg. If an invoice is paid, a watermark with the word paid will appear across it. The same applies to overdue invoices.',
            'where'=>'src/Invoice/Helpers/MpdfHelper/initialize_pdf function.'
        ], 
        'pdf_invoice_template'=>[
            'why'=>'Clients can download pdfs online if logged in and given observer status. This represents the normal template. ie. if an invoice is neither paid or overdue and is used alongside the paid and overdue template.',
            'where'=>'src/Invoice/Helpers/TemplateHelper/select_pdf_invoice_template function.'
        ],
        'pdf_invoice_template_paid'=>[
            'why'=>'Clients can download pdfs online if logged in and given observer status. This represents the paid template. ie. if an invoice is paid and is used alongside the normal and overdue template.',
            'where'=>'src/Invoice/Helpers/TemplateHelper/select_pdf_invoice_template function.'
        ],        
        'pdf_invoice_template_overdue'=>[
            'why'=>'Clients can download pdfs online if logged in and given observer status. This represents the overdue template. ie. if an invoice is overdue and is used alongside the normal and paid template.',
            'where'=>'src/Invoice/Helpers/TemplateHelper/select_pdf_invoice_template function.'
        ],
        'pdf_stream_inv'=>[
            'why'=>'To stream is to present in the browser normally as xml, html, or a pdf. Not to stream is to print to a file. Hence the use of the The Google sign located under settings ... Views ... Invoices... ',
            'where'=>'resources/views/invoice/setting/views/partial_settings_invoices with InvController/email_stage_1 variable $stream ... pdfHelper->generate_inv_pdf ... mpdfHelper->pdfCreate', 
        ],
        'quote_overview_period'=>[
            'why'=>'This setting is used on the dashboard so that the quotes that are shown will either be this-month, last-month, this-quarter, last-quarter, this-year, or last-year',
            'where'=>'views/invoice/dashboard/index.php and also in InvoiceController/dashboard function'
        ],
        'read_only_toggle' => [
            'why'=>'To prevent an invoice from being edited ie. is read only. By default set to read only if sent. ',
            'where'=> 'Sent: src/Invoice/Setting/SettingRepository/invoice_mark_sent with InvController (several places) '.
                      'View: src/Invoice/Setting/SettingRepository/invoice_mark_viewed InvController/url_key (when users view their invoices online) '.  
                      'Paid: src/Invoice/Helpers/NumberHelper/inv_balance_zero_set_to_read_only_if_fully_paid. ', 
        ],
        'stand_in_code' => [
            'why' => 'If a tax point date cannot be determined because a Delivery Period has been setup and there is no Date Supplied (ie. Actual Delivery Date) and no subsequent Date Issued, this code mutually excludes the tax point date value on an e-invoice. If you are using Accrual Based Vat Accouning use 3 Issue date or most likely 35 Supply date, if you are using Cash Based Vat Accounting use 432. The tax point date must be excluded from an e-invoice if Delivery Periods are used. ',
            'where' => 'src/Invoice/Inv/InvService/BothInv function and set_tax_point function. It is not included in AddInv and SaveInv since these two functions are deprecated.',
            'href' => 'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/cbc-DescriptionCode/'
        ],
        'storecove_country' => [
            'why' => 'The first step in sending an invoice is to create a sender. This sender is called a \'LegalEntity\'. LegalEntities can both send and receive, but for now we will focus on their sending role. Although the LegalEntity we are creating now can contain dummy data, you should carefully choose the LegalEntity’s country, because this will be important for the contents of the invoice.',
            'where' => 'src/Invoice/Helpers/StoreCove/StoreCoveHelper',            
        ],
        'storecove_sender_identifier' => [
            'why' => 'Legal Identifiers - A legal identifier identifies the legal entity from a legal perspective. It can be a local chambre of commerce number, or a DUNS, GLN, etc. However, in many countries the tax identifier is also the legal identifiers. In that case you don’t need to set this up separately. '.
                      'Tax Identifiers - A tax identifier identifies the legal entity from a tax perspective. In the EU, all tax identifiers are VAT numbers and are prefixed with the ISO3166-2 country code, e.g. "IT12345678901". In India, the tax identifier is issued by the state in which the LegalEntity resides. '.
                      'It’s first two digits are always the numercial code of the state that issued it.',
            'where' => 'src/Invoice/Helpers/StoreCove/StoreCoveHelper function maximum_pre_json_php_object_for_an_invoice()',            
        ],
        'storecove_sender_identifier_basis' => [
            'why' => 'Before selecting here, check that it is available in the sender identifier list. If not available, the available identifier will be chosen.',
            'where' => 'src/Invoice/Helpers/StoreCove/StoreCoveHelper function maximum_pre_json_php_object_for_an_invoice()',            
        ],       
        'tax_rate_decimal_places'=>[
          'why'=>'TODO: Currency decimal places vary per country. The decimal column of the TaxRate table, tax_rate_percent column has to be adjusted during runtime using the ALTER COMMAND sql statement preferably in a FRAGMENT',
          'where'=>'SettingController/tab_index_change_decimal_column'
        ],
        'time_zone' => [
          'why'=>'This is used in the DateHelper function datetime_zone_style which is used in TaskForm to get an accurate Finish Date for a Task.' .'/n'
               . 'It is also used in paymentinformation/amazon_signature to get a region from a time zone.' ,
          'where'=>'setting/get_folder_language'
        ],    
        ];
        return $tooltip;
    }
   
    /**
     * @param string $setting
     * @param bool $debug_mode
     * @return string
     */
    public function where(string $setting, bool $debug_mode = true) : string {
        
        $tooltip = $this->tooltip_array();
        $why = '';
        $where = '';
        $reference = '';
        $menu = false;
        /** 
         * @var array $value 
         * @var string $key
         */
        foreach ($tooltip as $key => $value) {
            if ($key === $setting) {
                /** 
                 * @var string $_value 
                 * @var string $_key
                 */
                foreach ($value as $_key => $_value) {
                    if ($_key === 'why') {
                        $why = $_value;
                    }
                    if ($_key === 'where') {
                        $where = $_value;
                    }
                    if ($_key === 'reference') {
                        $reference = $_value;
                    }
                    if ($_key === 'menu') {
                        $menu = $_value;
                    }
                }
            }
        }
        $information = (!$menu ? 'data-bs-toggle = "tooltip" data-bs-placement= "bottom" '.' '.'title = "' : ''). $why .' and is used in '.$where.'"';
        $build = $debug_mode ? $information : '';
        return $build;
    }
        
    /**
     * @param string $setting
     * @param bool $debug_mode
     * @return string
     */
    public function href(string $setting, bool $debug_mode = true) : string {
        $tooltip = $this->tooltip_array();
        $href = '';
        /** 
         * @var array $value 
         * @var string $key
         */
        foreach ($tooltip as $key => $value) {
            if ($key === $setting) {
                /** 
                 * @var string $_value 
                 * @var string $_key
                 */
                foreach ($value as $_key => $_value) {
                    if ($_key === 'href') {
                        $href = $_value;
                    }
                }
            }
        }
        $build = $debug_mode ? $href : '';
        return $build;
    }
    
    /**
     * @return string[][]
     *
     * @psalm-return array{number_format_us_uk: array{label: 'number_format_us_uk', decimal_point: '.', thousands_separator: ','}, number_format_european: array{label: 'number_format_european', decimal_point: ',', thousands_separator: '.'}, number_format_iso80k1_point: array{label: 'number_format_iso80k1_point', decimal_point: '.', thousands_separator: ' '}, number_format_iso80k1_comma: array{label: 'number_format_iso80k1_comma', decimal_point: ',', thousands_separator: ' '}, number_format_compact_point: array{label: 'number_format_compact_point', decimal_point: '.', thousands_separator: ''}, number_format_compact_comma: array{label: 'number_format_compact_comma', decimal_point: ',', thousands_separator: ''}}
     */
    public function number_formats() : array {
           /*
            | -------------------------------------------------------------------
            | Number formats
            | -------------------------------------------------------------------
            | This is a list of available number formats that are used by
            | the settings:
            |
            | US/UK format...................... 1,000,000.00
            | European format................... 1.000.000,00
            | ISO 80000-1 with decimal point.... 1 000 000.00
            | ISO 80000-1 with decimal comma.... 1 000 000,00
            | Compact with decimal point........   1000000.00
            | Compact with decimal comma........   1000000,00
            |
            */

            $number_formats = [            
            'number_format_us_uk' =>
                [
                    'label' => 'number_format_us_uk',
                    'decimal_point' => '.',
                    'thousands_separator' => ',',
                ],
            'number_format_european' =>
                [
                    'label' => 'number_format_european',
                    'decimal_point' => ',',
                    'thousands_separator' => '.',
                ],
            'number_format_iso80k1_point' =>
                [
                    'label' => 'number_format_iso80k1_point',
                    'decimal_point' => '.',
                    'thousands_separator' => ' ',
                ],
            'number_format_iso80k1_comma' =>
                [
                    'label' => 'number_format_iso80k1_comma',
                    'decimal_point' => ',',
                    'thousands_separator' => ' ',
                ],
            'number_format_compact_point' =>
                [
                    'label' => 'number_format_compact_point',
                    'decimal_point' => '.',
                    'thousands_separator' => '',
                ],
            'number_format_compact_comma' =>
                [
                    'label' => 'number_format_compact_comma',
                    'decimal_point' => ',',
                    'thousands_separator' => '',
                ],
            ];
            return $number_formats;
    }
    
    /**
     * @param string $period
     *
     * @return (\DateTimeImmutable|mixed)[]
     *
     * @psalm-return array{upper: \DateTimeImmutable, lower: \DateTimeImmutable,...}
     */
    public function range(string $period) : array {
        $range = [];
        switch ($period) {
                    case 'this-month':
                        $range['upper'] = new \DateTimeImmutable('now');
                        $range['lower'] = (new \DateTimeImmutable('now'))->sub(\DateInterval::createFromDateString('1 month'));
                        break;
                    case 'last-month':
                        $range['upper'] = (new \DateTimeImmutable('now'))->sub(\DateInterval::createFromDateString('1 month'));
                        $range['lower'] = (new \DateTimeImmutable('now'))->sub(\DateInterval::createFromDateString('2 months'));
                        break;
                    case 'this-quarter':
                        $range['upper'] = new \DateTimeImmutable('now');
                        $range['lower'] = (new \DateTimeImmutable('now'))->sub(\DateInterval::createFromDateString('3 months'));
                        break;
                    case 'last-quarter':
                        $range['upper'] = (new \DateTimeImmutable('now'))->sub(\DateInterval::createFromDateString('3 months'));
                        $range['lower'] = (new \DateTimeImmutable('now'))->sub(\DateInterval::createFromDateString('6 months'));
                        break;
                    case 'this-year':
                        $range['upper'] = new \DateTimeImmutable('now');
                        $range['lower'] = (new \DateTimeImmutable('now'))->sub(\DateInterval::createFromDateString('12 months'));
                        break;
                    case 'last-year':
                        $range['upper'] = (new \DateTimeImmutable('now'))->sub(\DateInterval::createFromDateString('12 months'));
                        $range['lower'] = (new \DateTimeImmutable('now'))->sub(\DateInterval::createFromDateString('24 months'));
                        break;
                    default:
                        $range['upper'] = new \DateTimeImmutable('now');
                        $range['lower'] = (new \DateTimeImmutable('now'))->sub(\DateInterval::createFromDateString('1 month'));
                        break;
        }
        return $range;
    }
    
    //https://www.php.net/manual/en/features.file-upload.errors.php
    
    /**
     * 
     * @param int $code
     * @return string
     */
    public function codeToMessage(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            case UPLOAD_ERR_OK:
                $message = "There is no error, the file uploaded with success";
                break;
            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }
    
    /**
     * @return array
     */
    public function get_payment_term_array(TranslatorInterface $translator): array 
    {
        $array = [
            $translator->translate('invoice.payment.term'),
            $translator->translate('invoice.payment.term.0.days'),
            $translator->translate('invoice.payment.term.net.15.days'),
            $translator->translate('invoice.payment.term.net.30.days'),
            $translator->translate('invoice.payment.term.net.60.days'),
            $translator->translate('invoice.payment.term.net.90.days'),
            $translator->translate('invoice.payment.term.net.120.days'),
            $translator->translate('invoice.payment.term.eom.15.days'),
            $translator->translate('invoice.payment.term.eom.30.days'),
            $translator->translate('invoice.payment.term.eom.60.days'),
            $translator->translate('invoice.payment.term.eom.90.days'),
            $translator->translate('invoice.payment.term.eom.120.days'),
            $translator->translate('invoice.payment.term.mfi.15'),
            $translator->translate('invoice.payment.term.general'),
            $translator->translate('invoice.payment.term.polite'),
            $translator->translate('invoice.payment.term.pia'),
        ];
        return $array;
    }
    
    /**
     * @return string[]
     *
     * @psalm-return list{'af', 'ar', 'az', 'be', 'bg', 'bs', 'ca', 'cs', 'da', 'de', 'el', 'es', 'et', 'fa', 'fi', 'fr', 'he', 'hr', 'hu', 'hy', 'id', 'it', 'ja', 'ka', 'kk', 'ko', 'kz', 'lt', 'lv', 'ms', 'nb-NO', 'nl', 'pl', 'pt', 'pt-BR', 'ro', 'ru', 'sk', 'sl', 'sr', 'sr-Latn', 'sv', 'tg', 'th', 'tr', 'uk', 'uz', 'vi', 'zh-CN', 'zh-TW'}
     */
    public function locales(): array {
        $locales = [
            'af', 'ar', 'az', 
            'be', 'bg', 'bs', 
            'ca', 'cs', 
            'da', 'de', 
            'el', 'es', 'et', 
            'fa', 'fi', 'fr', 
            'he', 'hr', 'hu', 'hy', 
            'id', 'it', 
            'ja', 
            'ka', 'kk', 'ko', 'kz', 
            'lt', 'lv', 
            'ms', 
            'nb-NO', 'nl', 
            'pl', 'pt', 'pt-BR', 
            'ro', 'ru', 
            'sk', 'sl', 'sr', 'sr-Latn', 'sv', 
            'tg', 'th', 'tr', 
            'uk', 'uz', 
            'vi', 
            'zh-CN', 
            'zh-TW'
        ];
        return $locales;
    }
    
    // Record the debug_mode in a setting.
    public function debug_mode(bool $debug_mode = true) : void {
        if (($debug_mode == true) && ($this->get_setting('debug_mode') === '0' || null==$this->get_setting('debug_mode'))) {
            $debug_mode = new Setting();
            $debug_mode->setSetting_key('debug_mode');
            $debug_mode->setSetting_value('1');
            $this->save($debug_mode);            
        }
        if (($debug_mode == false) && ($this->get_setting('debug_mode') === '1' || null==$this->get_setting('debug_mode'))) {
            $debug_mode = new Setting();
            $debug_mode->setSetting_key('debug_mode');
            $debug_mode->setSetting_value('0');
            $this->save($debug_mode);
        }        
    }
    
    /**
     * @return string
     */
    public function isDebugMode(int $key) : string {
        // If the default has changed from true to false in the layout/main.php return false otherwise stick to default
        // Do not return the file location if not in debug mode
        if (($this->get_setting('debug_mode') === '0')) {
           return ''; 
        }
        // Return the file location if in debug_mode
        if (($this->get_setting('debug_mode') === '1')) {
           return $this->debug_mode_file_location($key);
        }
        return '';        
    }
    
    /**
     * @param int $key
     * @return string
     */
    public function debug_mode_file_location(int $key) : string {
        $layout = '..resources/views/layout/';
        $common = '..resources/views/invoice/';
        $array = [//0
                  $layout.'invoice', 
                  //1
                  $common.'inv/view',
                  //2
                  $common.'invitem/_item_form_product',
                  //3
                  $common.'invitem/_item_form_task',
                  //4
                  $common.'inv/view_custom_fields',
                  //5
                  $common.'inv/partial_inv_attachments',
                  //6
                  $common.'inv/partial_inv_delivery_location',
                  //7
                  $common.'inv/partial_item_table',
                  //8
                  $common.'product/views/partial_product_image',
                  //9
                  $common.'product/views/partial_product_gallery'
        ];
        return $array[$key];
    }
    
    /**
     * @return string
     */
    public function public_logo() : string {
        if (!empty($this->get_setting('public_logo_png_prefix'))) {
            return $this->get_setting('public_logo_png_prefix');
        } else {
            // If no logo has been set use the default file 'logo.png' provided in the public directory
            $logo_prefix = new Setting();
            $logo_prefix->setSetting_key('public_logo_png_prefix');
            $logo_prefix->setSetting_value('logo');
            $this->save($logo_prefix);
        }
        return $this->get_setting('public_logo_png_prefix');
    }
    
    /**
     * 
     * @param OffsetPaginator $paginator
     * @param TranslatorInterface $translator
     * @param int $max
     * @param string $entity_plural
     * @param string $status_string
     * @return string
     */
    public function grid_summary(OffsetPaginator $paginator, TranslatorInterface $translator, int $max, string $entity_plural, string $status_string) : string {        
        $pageSize = $paginator->getCurrentPageSize();
        if ($pageSize > 0) {
          return Html::tag('b',
            sprintf($translator->translate('invoice.invoice.showing.of').
                    $translator->translate('invoice.invoice.max'). 
                    ' '.$max. ' '. 
                    $entity_plural.
                    $translator->translate('invoice.invoice.per.page.total').
                    $entity_plural.': '.
                    $paginator->getTotalItems() , $pageSize, $paginator->getTotalItems()). ' ',
            ['class' => 'card-header bg-warning text-black']
          ). (!empty($status_string) ? 
          Html::tag('b', $status_string,['class'=>'card-header bg-info text-black']) : '');
        }
        return '';
    }
 }