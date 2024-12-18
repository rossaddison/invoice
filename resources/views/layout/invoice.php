<?php

declare(strict_types=1);

use App\Invoice\Asset\InvoiceAsset;
use App\Invoice\Asset\MonospaceAsset;
// DatePicker Assets available for dropdown locale/cldr selection
use App\Invoice\Asset\i18nAsset\af_ZA_Asset;
use App\Invoice\Asset\i18nAsset\ar_BH_Asset;
use App\Invoice\Asset\i18nAsset\az_Asset;
use App\Invoice\Asset\i18nAsset\de_DE_Asset;
use App\Invoice\Asset\i18nAsset\en_GB_Asset;
use App\Invoice\Asset\i18nAsset\es_ES_Asset;
use App\Invoice\Asset\i18nAsset\fil_PH_Asset;
use App\Invoice\Asset\i18nAsset\fr_FR_Asset;
use App\Invoice\Asset\i18nAsset\id_Asset;
use App\Invoice\Asset\i18nAsset\it_Asset;
use App\Invoice\Asset\i18nAsset\ja_Asset;
use App\Invoice\Asset\i18nAsset\nl_Asset;
use App\Invoice\Asset\i18nAsset\pl_Asset;
use App\Invoice\Asset\i18nAsset\pt_BR_Asset;
use App\Invoice\Asset\i18nAsset\ru_Asset;
use App\Invoice\Asset\i18nAsset\sk_Asset;
use App\Invoice\Asset\i18nAsset\uk_UA_Asset;
use App\Invoice\Asset\i18nAsset\uz_UZ_Asset;
use App\Invoice\Asset\i18nAsset\vi_VN_Asset;
use App\Invoice\Asset\i18nAsset\zh_CN_Asset;
use App\Invoice\Asset\i18nAsset\zh_TW_Asset;
use App\Invoice\Asset\i18nAsset\zu_ZA_Asset;
// PCI Compliant Payment Gateway Assets
use App\Invoice\Asset\pciAsset\stripe_v10_Asset;
use App\Invoice\Asset\pciAsset\amazon_pay_v2_4_Asset;
use App\Invoice\Asset\pciAsset\braintree_dropin_1_33_7_Asset;
use App\Asset\AppAsset;
use App\Widget\PerformanceMetrics;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Yii\Bootstrap5\Dropdown;
use Yiisoft\Yii\Bootstrap5\DropdownItem;
use Yiisoft\Yii\Bootstrap5\DropdownToggleVariant;
use Yiisoft\Yii\Bootstrap5\Nav;
use Yiisoft\Yii\Bootstrap5\NavBar;
use Yiisoft\Yii\Bootstrap5\Offcanvas;

/**
 * @see ...src\ViewInjection\LayoutViewInjection
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Config\Config $config
 * @var Yiisoft\Config\ConfigPaths $configPaths
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var bool $isGuest
 * @var bool $buildDatabase
 * @var bool $debugMode
 * @var string $csrf
 * @var string $companyLogoHeight 
 * @var string $companyLogoMargin
 * @var string $companyLogoWidth
 * @var string $content
 * @var string $javascriptJqueryDateHelper
 * @var string $logoPath 
 * @var string $read_write
 * @var string $scrutinizerRepository
 * 
 * @see ...src\ViewInjection\LayoutViewInjection.php
 * @var string $userLogin
 * 
 * @var string $xdebug
 */
$assetManager->register(AppAsset::class);
$assetManager->register(InvoiceAsset::class);
$assetManager->register(Yiisoft\Yii\Bootstrap5\Assets\BootstrapAsset::class);
$s->getSetting('monospace_amounts') == 1 ? $assetManager->register(MonospaceAsset::class) : '';
// '0' => PCI Compliant version
$s->getSetting('gateway_stripe_version') == '0' ? $assetManager->register(stripe_v10_Asset::class) : '';
$s->getSetting('gateway_amazon_pay_version') == '0' ? $assetManager->register(amazon_pay_v2_4_Asset::class) : '';
$s->getSetting('gateway_braintree_version') == '0' ? $assetManager->register(braintree_dropin_1_33_7_Asset::class) : '';
$vat = ($s->getSetting('enable_vat_registration') == '0');
// NOTE: $locale must correspond with SettingRepository/locale_language_array and
// ALSO: src/Invoice/Language/{folder_name}
switch ($currentRoute->getArgument('_language') ?? 'en') {
  /**
   * Note: case 'x' must follow config/web/params locale => ['locales' => [ 'x' => 'x-XX'] format i.e. use key and NOT value i.e. use 'x' and NOT 'x-XX' 
   * Note: If there is more than one official language in the country use the format x-YY for 'case' which should correspond with above locales array
   */  
  case 'af-ZA' : $assetManager->register(af_ZA_Asset::class);
    $locale = 'AfrikaansSouthAfrican';
    break;
  case 'ar-BH' : $assetManager->register(ar_BH_Asset::class);
    $locale = 'ArabicBahrainian';
    break;
  case 'az' : $assetManager->register(az_Asset::class);
    $locale = 'Azerbaijani';
    break;
  case 'de' : $assetManager->register(de_DE_Asset::class);
    $locale = 'German';
    break;
  case 'en' : $assetManager->register(en_GB_Asset::class);
    $locale = 'English';
    break;
  case 'fil' : $assetManager->register(fil_PH_Asset::class);
    $locale = 'Filipino';
    break;
  case 'fr' : $assetManager->register(fr_FR_Asset::class);
    $locale = 'French';
    break;
  case 'id' : $assetManager->register(id_Asset::class);
    $locale = 'Indonesian';
    break;
  case 'it' : $assetManager->register(it_Asset::class);
    $locale = 'Italian';
    break;
  case 'ja' : $assetManager->register(ja_Asset::class);
    $locale = 'Japanese';
    break;
  case 'nl' : $assetManager->register(nl_Asset::class);
    $locale = 'Dutch';
    break;
  case 'pl' : $assetManager->register(pl_Asset::class);
    $locale = 'Polish';
    break;
  case 'pt-BR' : $assetManager->register(pt_BR_Asset::class);
    $locale = 'PortugeseBrazilian';
    break; 
  case 'ru' : $assetManager->register(ru_Asset::class);
    $locale = 'Russian';
    break;
  case 'sk' : $assetManager->register(sk_Asset::class);
    $locale = 'Slovensky';
    break;
  case 'es' : $assetManager->register(es_ES_Asset::class);
    $locale = 'Spanish';
    break;
  case 'uk' : $assetManager->register(uk_UA_Asset::class);
    $locale = 'Ukrainian';
    break;
  case 'uz' : $assetManager->register(uz_UZ_Asset::class);
    $locale = 'Uzbek';
    break;
  case 'vi' : $assetManager->register(vi_VN_Asset::class);
    $locale = 'Vietnamese';
    break;
  case 'zh-CN' : $assetManager->register(zh_CN_Asset::class);
    $locale = 'ChineseSimplified';
    break;
  case 'zh-TW' : $assetManager->register(zh_TW_Asset::class);
    $locale = 'TiawaneseMandarin';
    break;      
  case 'zu-ZA' : $assetManager->register(zu_ZA_Asset::class);
    $locale = 'ZuluSouthAfrican';
    break;
  default : $assetManager->register(en_GB_Asset::class);
    $locale = 'English';
    break;
}

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());

$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

// Platform, Performance, and Clear Assets Cache, and links Menu will disappear if set to false;
/**
 * @see src\ViewInjection\LayoutViewInjection.php $debugMode
 */
$this->beginPage();
?>
<!DOCTYPE html>
<html class="h-100" lang="<?= $currentRoute->getArgument('_language') ?? 'en'; ?>">
    <head>
        <?= Meta::documentEncoding('utf-8') ?>
        <?= Meta::pragmaDirective('X-UA-Compatible', 'IE=edge') ?>
        <?= Meta::data('viewport', 'width=device-width, initial-scale=1') ?>
        <title>
            <?= $s->getSetting('custom_title') ?: 'Yii-Invoice'; ?>
        </title>
        <?php $this->head() ?>
    </head>
    <body>
        <?php
        Html::tag('Noscript', Html::tag('Div', $translator->translate('i.please_enable_js'), ['class' => 'alert alert-danger no-margin']));
        ?>
        
        <?php
        $this->beginBody();        
        $offcanvas = new Offcanvas();
        $offcanvas->title($s->getSetting('custom_title') ?: 'Yii-Invoice');
        
        echo NavBar::widget()
          // public folder represented by first forward slash ie. root
          ->brandImage($logoPath)              
          ->brandImageAttributes(['margin' => $companyLogoMargin, 'width' => $companyLogoWidth, 'height' => $companyLogoHeight])
          //->brandText(str_repeat('&nbsp;', 7).$brandLabel)      
          ->brandUrl($urlGenerator->generate('invoice/index'))
          ->withWidget(
            // If not full screen => 'burger icon ie. 3 horizontal lines' represents menu and
            // navbar moves in from left
            $offcanvas
          )
          ->begin();
        
        $currentPath = $currentRoute->getUri()?->getPath();
        if ((null!== $currentPath) && !$isGuest) {
            
            // Dashboard
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent(I::tag()->addClass('fa fa-dashboard'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link(
                    $translator->translate('i.dashboard'), 
                    $urlGenerator->generate('invoice/dashboard'), 
                    $debugMode, 
                    false, 
                    [
                        'style' => 'font-size: 1rem; color: cornflowerblue;'
                    ]
                ),
            )
            ->render();
            
            echo Dropdown::widget()
            ->addClass('navbar fs-4')        
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent('')        
            ->toggleSizeLarge(true)
            ->items(                
                // Assets Clear    
                DropdownItem::link(
                    $translator->translate('invoice.utility.assets.clear'), 
                    $urlGenerator->generate('setting/clear'), $debugMode, false, [
                        'data-bs-toggle' => 'tooltip',
                        'title' => 'Clear the assets cache which resides in /public/assets.', 
                        'style' => 'background-color: #ffcccb',
                        'hidden' => !$debugMode
                    ]
                ),
                // Vat exists? Show red or green background    
                DropdownItem::text(
                    $translator->translate('invoice.vat'), [], ['style' => $vat ? 'background-color: #ffcccb' : 'background-color: #90EE90']),
                // Debug Mode
                DropdownItem::text(
                    $translator->translate('invoice.debug'), [
                        'class' => 'nav fs-4', 
                        'data-bs-toggle' => 'tooltip', 
                        'title' => 'Set YII_DEBUG=false in .env file in root folder. Red background links and menus will disappear.', 
                        'style' => 'background-color: '. ($debugMode ? '#90EE90' : '#ffcccb'),
                        'hidden' => !$debugMode
                    ]
                ),
                // Locale    
                DropdownItem::text(
                    'Locale => ' . $locale, [
                        'class' => 'nav fs-4', 
                        'data-bs-toggle' => 'tooltip', 
                        'title' => 'Storage: session/runtime file.', 
                        'style' => 'background-color: #90EE90',
                        'hidden' => !$debugMode
                    ]
                ),
                // cldr    
                DropdownItem::text('cldr => ' . ($currentRoute->getArgument('_language') ?? '#'), [
                    'class' => 'nav fs-4', 
                    'data-bs-toggle' => 'tooltip', 
                    'title' => 'Storage: database', 
                    'style' => 'background-color: #ffffe0',
                    'hidden' => !$debugMode
                ]),
                // File Location    
                DropdownItem::text('File Location', [
                    'class' => 'nav fs-4', 
                    'data-bs-toggle' => 'tooltip', 
                    'title' => $s->debug_mode_file_location(0), 
                    'style' => 'background-color: #ffcccb',
                    'hidden' => !$debugMode,               
                ])    
            )        
            ->render();
            
            // FAQ's
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 2rem; color: cornflowerblue;',
                'hidden' => !$debugMode
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('invoice.faq'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link('Console Commands', $urlGenerator->generate('invoice/faq', ['topic' => 'consolecommands', 'selection' => ''])),
                DropdownItem::link($translator->translate('invoice.faq.taxpoint'), $urlGenerator->generate('invoice/faq', ['topic' => 'tp', 'selection' => ''])), 
                DropdownItem::link($translator->translate('invoice.faq.shared.hosting'), $urlGenerator->generate('invoice/faq', ['topic' => 'shared', 'selection' => ''])), 
                DropdownItem::link($translator->translate('invoice.faq.payment.provider'), $urlGenerator->generate('invoice/faq', ['topic' => 'paymentprovider', 'selection' => ''])), 
                DropdownItem::link($translator->translate('invoice.faq.php.info.all'), $urlGenerator->generate('invoice/phpinfo', ['selection' => '-1'])), 
                DropdownItem::link($translator->translate('invoice.faq.php.info.general'), $urlGenerator->generate('invoice/phpinfo', ['selection' => '1'])), 
                DropdownItem::link($translator->translate('invoice.faq.php.info.credits'), $urlGenerator->generate('invoice/phpinfo', ['selection' => '2'])), 
                DropdownItem::link($translator->translate('invoice.faq.php.info.configuration'), $urlGenerator->generate('invoice/phpinfo', ['selection' => '4'])), 
                DropdownItem::link($translator->translate('invoice.faq.php.info.modules'), $urlGenerator->generate('invoice/phpinfo', ['selection' => '8'])), 
                DropdownItem::link($translator->translate('invoice.faq.php.info.environment'), $urlGenerator->generate('invoice/phpinfo', ['selection' => '16'])), 
                DropdownItem::link($translator->translate('invoice.faq.php.info.variables'), $urlGenerator->generate('invoice/phpinfo', ['selection' => '32'])), 
                DropdownItem::link($translator->translate('invoice.faq.php.info.licence'), $urlGenerator->generate('invoice/phpinfo', ['selection' => '64'])),
            )
            ->render();
            
            // Translate
            echo Dropdown::widget()
            ->addClass('navbar fs4')        
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('i.language'),
                'url' => '#'
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent(I::tag()->addClass('bi bi-translate'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link('Afrikaans South African', $urlGenerator->generateFromCurrent(['_language' => 'af-ZA'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Arabic Bahrainian/ عربي', $urlGenerator->generateFromCurrent(['_language' => 'ar-BH'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Azerbaijani / Azərbaycan', $urlGenerator->generateFromCurrent(['_language' => 'az'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Chinese Simplified / 简体中文', $urlGenerator->generateFromCurrent(['_language' => 'zh-CN'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Tiawanese Mandarin / 简体中文', $urlGenerator->generateFromCurrent(['_language' => 'zh-TW'], fallbackRouteName: 'site/index')),
                DropdownItem::link('English', $urlGenerator->generateFromCurrent(['_language' => 'en'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Filipino / Filipino', $urlGenerator->generateFromCurrent(['_language' => 'fil'], fallbackRouteName: 'site/index')),
                DropdownItem::link('French / Français', $urlGenerator->generateFromCurrent(['_language' => 'fr'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Dutch / Nederlands', $urlGenerator->generateFromCurrent(['_language' => 'nl'], fallbackRouteName: 'site/index')),
                DropdownItem::link('German / Deutsch', $urlGenerator->generateFromCurrent(['_language' => 'de'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Indonesian / bahasa Indonesia', $urlGenerator->generateFromCurrent(['_language' => 'id'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Italian / Italiano', $urlGenerator->generateFromCurrent(['_language' => 'it'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Japanese / 日本', $urlGenerator->generateFromCurrent(['_language' => 'ja'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Polish / Polski', $urlGenerator->generateFromCurrent(['_language' => 'pl'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Portugese Brazilian / Português Brasileiro', $urlGenerator->generateFromCurrent(['_language' => 'pt-BR'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Russian / Русский', $urlGenerator->generateFromCurrent(['_language' => 'ru'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Slovakian / Slovenský', $urlGenerator->generateFromCurrent(['_language' => 'sk'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Spanish /  Española x', $urlGenerator->generateFromCurrent(['_language' => 'es'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Ukrainian / українська', $urlGenerator->generateFromCurrent(['_language' => 'uk'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Uzbek / o'."'".'zbek', $urlGenerator->generateFromCurrent(['_language' => 'uz'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Vietnamese / Tiếng Việt', $urlGenerator->generateFromCurrent(['_language' => 'vi'], fallbackRouteName: 'site/index')),
                DropdownItem::link('Zulu South African/ Zulu South African', $urlGenerator->generateFromCurrent(['_language' => 'zu-ZA'], fallbackRouteName: 'site/index')),
            )->render();   
            
            // Generator
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'background-color: #ffcccb',
                'hidden' => !$debugMode
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('invoice.generator'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('invoice.generator'), $urlGenerator->generate('generator/index')),
                DropdownItem::link($translator->translate('invoice.generator.relations'), $urlGenerator->generate('generatorrelation/index')),
                DropdownItem::link($translator->translate('invoice.generator.add'), $urlGenerator->generate('generator/add')),
                DropdownItem::link($translator->translate('invoice.generator.relations.add'), $urlGenerator->generate('generatorrelation/add')),
                DropdownItem::link($translator->translate('invoice.development.schema'), $urlGenerator->generate('generator/quick_view_schema')),
                // Using the saved locale dropdown setting under Settings ... Views ... Google Translate, translate one of the three files located in
                // ..resources/views/generator/templates_protected
                // Your Json file must be located in src/Invoice/google_translate_unique folder
                // Get your downloaded Json file from
                DropdownItem::link($translator->translate('invoice.generator.google.translate.gateway'), 
                                   $urlGenerator->generate('generator/google_translate_lang', ['type' => 'gateway'])),  
                DropdownItem::link($translator->translate('invoice.generator.google.translate.ip'), 
                                   $urlGenerator->generate('generator/google_translate_lang', ['type' => 'ip']), $debugMode, false, ['data-bs-toggle' => 'tooltip', 'title' => $s->where('google_translate_json_filename')]),
                DropdownItem::link($translator->translate('invoice.generator.google.translate.latest'), 
                                   $urlGenerator->generate('generator/google_translate_lang', ['type' => 'latest'])),
                DropdownItem::link($translator->translate('invoice.generator.google.translate.common'), 
                                   $urlGenerator->generate('generator/google_translate_lang', ['type' => 'common'])),
                DropdownItem::link($translator->translate('invoice.generator.google.translate.any'), 
                                   $urlGenerator->generate('generator/google_translate_lang', ['type' => 'any']), $debugMode, false, ['data-bs-toggle' => 'tooltip', 'title' => 'src\Invoice\Language\English\any_lang.php']),   
                DropdownItem::link($translator->translate('invoice.test.reset.setting'), 
                                   $urlGenerator->generate('invoice/setting_reset'), $debugMode, false, ['data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.test.reset.setting.tooltip')]),
                DropdownItem::link($translator->translate('invoice.test.reset'), 
                                   $urlGenerator->generate('invoice/test_data_reset'), $debugMode, false, ['data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.test.reset.tooltip')]),
                DropdownItem::link($translator->translate('invoice.test.remove'), 
                                   $urlGenerator->generate('invoice/test_data_remove'), $debugMode, false, ['data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.test.remove.tooltip')]), 
            )
            ->render();
            
            // Performance
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => $read_write ? 'background-color: #ffcccb' 
                                       : 'background-color: #90EE90',
                'data-bs-toggle'=>'tooltip',
                'title' => $read_write ? $translator->translate('invoice.performance.label.switch.on') 
                                       : $translator->translate('invoice.performance.label.switch.off'),
                'hidden' => !$debugMode
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('invoice.performance'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::text($translator->translate('invoice.platform.xdebug') . ' ' . $xdebug, ['data-bs-toggle' => 'tooltip', 'title' => 'Via Wampserver Menu: Icon..Php 8.1.8-->Php extensions-->xdebug 3.1.5(click)-->Allow php command prompt to restart automatically-->(click)Restart All Services-->No typing in or editing of a php.ini file!!']),
                DropdownItem::text('...config/common/params.php SyncTable currently not commented out and PhpFileSchemaProvider::MODE_READ_AND_WRITE...fast....MODE_WRITE_ONLY...slower'),
                DropdownItem::text('php.ini: opcache.memory_consumption (pref 128) = '. (ini_get('opcache.memory_consumption')), ['data-bs-toggle' => 'tooltip', 'title' => 'e.g. change manually in C:\wamp64\bin\php\php8.1.13\phpForApache.ini and restart all services.']),
                DropdownItem::text('php.ini: oopcache.interned_strings_buffer (pref 8) = '. (ini_get('opcache.interned_strings_buffer'))),
                DropdownItem::text('php.ini: opcache.max_accelerated_files (pref 4000) = '. (ini_get('opcache.max_accelerated_files'))),
                DropdownItem::text('php.ini: opcache.revalidate_freq (pref 60) = '. (ini_get('opcache.revalidate_freq'))),
                DropdownItem::text('php.ini: opcache.enable (pref 1) = ' . (ini_get('opcache.enable'))),
                DropdownItem::text('php.ini: opcache.enable_cli (pref 1) = ' .(ini_get('opcache.enable_cli'))),
                DropdownItem::text('php.ini: opcache.jit (pref see nothing) = '. (ini_get('opcache.jit'))),
                DropdownItem::text('php.ini: max_execution_time (pref 360) = '. (ini_get('max_execution_time'))),
                DropdownItem::text('.env: BUILD_DATABASE= (pref see nothing) = '. ($buildDatabase ? 'You have built the database using BUILD_DATABASE=true, now assign the environment varirable to nothing i.e. BUILD_DATABASE=' : '✅')),  
                DropdownItem::text('config.params: yiisoft/yii-debug: enabled , disable for improved performance'),
                DropdownItem::text('config.params: yiisoft/yii-debug-api: enabled, disable for improved performance'),
            )
            ->render();
            
            // Settings
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent(I::tag()->addClass('fa fa-cogs'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('i.view'), $urlGenerator->generate('setting/debug_index'), $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link($translator->translate('invoice.setting.add'), $urlGenerator->generate('setting/add'), $debugMode, false, ['style' => 'background-color: #ffcccb']),    
                DropdownItem::link($translator->translate('invoice.invoice.caution.delete.invoices'), $urlGenerator->generate('inv/flush'), $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link($translator->translate('i.view'), $urlGenerator->generate('setting/tab_index')),
                DropdownItem::link($translator->translate((($s->getSetting('install_test_data') == '1') && ($s->getSetting('use_test_data') == '1'))
                ? 'invoice.install.test.data' : 'invoice.install.test.data.goto.tab.index'), (($s->getSetting('install_test_data') == '1' && $s->getSetting('use_test_data') == '1') 
                ? $urlGenerator->generate('invoice/index') : $urlGenerator->generate('setting/tab_index')), ($s->getSetting('install_test_data') == '1' && $s->getSetting('use_test_data') == '1')),
                DropdownItem::link($translator->translate('i.email_template'), $urlGenerator->generate('emailtemplate/index')),
                DropdownItem::link($translator->translate('invoice.email.from.dropdown'), $urlGenerator->generate('from/index')),
                DropdownItem::link($translator->translate('invoice.email.log'), $urlGenerator->generate('invsentlog/index')),
                DropdownItem::link($translator->translate('i.custom_fields'), $urlGenerator->generate('customfield/index')),    
                DropdownItem::link($translator->translate('i.invoice_group'), $urlGenerator->generate('group/index')),
                DropdownItem::link($translator->translate('i.invoice_archive'), $urlGenerator->generate('inv/archive')),
                DropdownItem::link($translator->translate('i.payment_method'), $urlGenerator->generate('paymentmethod/index')),
                DropdownItem::link($translator->translate('i.invoice_tax_rate'), $urlGenerator->generate('taxrate/index')),
                DropdownItem::link($translator->translate('invoice.invoice.contract'), $urlGenerator->generate('contract/index')),
                DropdownItem::link($translator->translate('invoice.user.account'), $urlGenerator->generate('userinv/index')),
                DropdownItem::link($translator->translate('password.change'), $urlGenerator->generate('auth/change')),
                DropdownItem::link($translator->translate('invoice.user.api.list'), $urlGenerator->generate('user/index')),
                DropdownItem::link($translator->translate('invoice.setting.company'), $urlGenerator->generate('company/index')),
                DropdownItem::link($translator->translate('invoice.setting.company.private'), $urlGenerator->generate('companyprivate/index')),
                DropdownItem::link($translator->translate('invoice.setting.company.profile'), $urlGenerator->generate('profile/index')),    
            )
            ->render();
            
            // Platform
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'background-color: #ffcccb',
                'hidden' => !$debugMode
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('invoice.platform'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::text('WAMP'),
                DropdownItem::text($translator->translate('invoice.platform.editor') . ': Apache Netbeans IDE 23 64 bit'),
                DropdownItem::text($translator->translate('invoice.platform.server') . ': Wampserver 3.3.6 64 bit'),
                DropdownItem::text('Apache: 2.4.59 64 bit'),
                DropdownItem::text($translator->translate('invoice.platform.mySqlVersion') . ': 8.3.0 '),
                DropdownItem::text($translator->translate('invoice.platform.windowsVersion') . ': Windows 11 Pro Edition'),
                DropdownItem::text($translator->translate('invoice.platform.PhpVersion') . ': 8.3.0 (Compatable with PhpAdmin 5.2.1)'),
                DropdownItem::text($translator->translate('invoice.platform.PhpMyAdmin') . ': 5.2.1 (Compatable with php 8.2.0)'),
                DropdownItem::link($translator->translate('invoice.platform.PhpSupport'), 'https://php.net/supported-versions'), 
                DropdownItem::link($translator->translate('invoice.platform.update'), 'https://wampserver.aviatechno.net/'),
                DropdownItem::link('Bootstrap 5 Icons with Filter', 'https://icons.getbootstrap.com/'),
                DropdownItem::link('BootstrapBrain Free Wavelight Template', 'https://bootstrapbrain.com/template/free-bootstrap-5-multipurpose-one-page-template-wave/'),
                DropdownItem::link('Html to Markdown', 'https://convertsimple.com/convert-html-to-markdown/'),
                DropdownItem::link('European Invoicing', 'https://ec.europa.eu/digital-building-blocks/wikis/display/DIGITAL/Compliance+with+eInvoicing+standard'),
                DropdownItem::link('European Digital Testing', 'https://ec.europa.eu/digital-building-blocks/wikis/display/DIGITAL/eInvoicing+Conformance+Testing'),
                DropdownItem::link('What does a Peppol ID look like?', 'https://ecosio.com/en/blog/how-peppol-ids-work/'),
                DropdownItem::link('Peppol Accounting Requirements', 'https://docs.peppol.eu/poacc/billing/3.0/bis/#accountingreq'),
                DropdownItem::link(' Peppol Billing 3.0 - Syntax', '', $debugMode, false, [], ['class' => 'fa fa-window-restore', 'onclick'=>"window.open('".'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/'."')"]),
                DropdownItem::link(' Peppol Billing 3.0 - Tree', '', $debugMode, false, [], ['class' => 'fa fa-window-restore', 'onclick'=>"window.open('".'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/tree/'."')"]),    
                DropdownItem::link('Universal Business Language 2.1 (UBL)', 'http://www.datypic.com/sc/ubl21/ss.html'),    
                DropdownItem::link('StoreCove Documentation', 'https://www.storecove.com/docs'),
                DropdownItem::link('Peppol Company Search', 'https://directory.peppol.eu/public'),
                DropdownItem::link('ISO 3 letter currency codes - 4217 alpha-3', 'https://www.iso.org/iso-4217-currency-codes.html'),
                DropdownItem::link(' Xml Example 2.1', '', $debugMode, false, [], ['class' => 'fa fa-window-restore', 'onclick'=>"window.open('".'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/'."')"]),
                DropdownItem::link('Xml Example 3.0', 'https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/rules/examples/base-example.xml'),
                DropdownItem::link('Ecosio Xml Validator', '', $debugMode, false, [], ['class' => 'fa fa-window-restore', 'onclick'=>"window.open('".'https://ecosio.com/en/peppol-and-xml-document-validator/'."')"]),
                DropdownItem::link('Xml CodeLists', 'https://github.com/OpenPEPPOL/peppol-bis-invoice-3/tree/master/structure/codelist'),
                DropdownItem::link('Convert XML to PHP Array Online', 'https://wtools.io/convert-xml-to-php-array'),
                DropdownItem::link('Writing XML using Sabre', 'https://sabre.io/xml/writing/'),
                DropdownItem::link('Understanding Same Site Cookies', 'https://andrewlock.net/understanding-samesite-cookies/#:~:text=SameSite%3DLax%20cookies%20are%20not,Lax%20(or%20Strict%20)%20cookies'),
                DropdownItem::link('Scotland - e-invoice Template - Lessons Learned', 'https://www.gov.scot/publications/einvoicing-guide/documents/'),
                DropdownItem::link('Jsonld  Playground for flattening Jsonld files', 'https://json-ld.org/playground/'),
                DropdownItem::link('Converting flattened file to php array', 'https://wtools.io/convert-json-to-php-array'),    
                DropdownItem::link('Jsonld  Playground for flattening Jsonld files', 'https://json-ld.org/playground/'),   
                DropdownItem::link('jQuery UI 1.13.2', 'https://github.com/jquery/jquery-ui'),    
                DropdownItem::link($translator->translate('invoice.platform.scrutinizer.config.checks.php'), 'https://scrutinizer-ci.com/g/'. $scrutinizerRepository. '/settings/build-config/editor?language=php'),    
                DropdownItem::link($translator->translate('invoice.platform.scrutinizer.config.checks.javascript'), 'https://scrutinizer-ci.com/g/'. $scrutinizerRepository. '/settings/build-config'),    
                DropdownItem::link('Using ngrok and Wampserver VirtualHosts', 'https://ngrok.com/docs/using-ngrok-with/virtualHosts/'),    
                DropdownItem::link('Using ngrok and webhook testing', 'https://ngrok.com/use-cases/webhook-testing')
            )
            ->render();
            
            // peppol
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
                'url' => '#'
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('invoice.peppol.abbreviation'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('invoice.invoice.allowance.or.charge.add'), $urlGenerator->generate('allowancecharge/index')),
                DropdownItem::link($translator->translate('invoice.peppol.store.cove.1.1.1'), 'https://www.storecove.com/register/'),
                DropdownItem::link($translator->translate('invoice.peppol.store.cove.1.1.2'), $urlGenerator->generate('setting/tab_index')),
                DropdownItem::link($translator->translate('invoice.peppol.store.cove.1.1.3'), $urlGenerator->generate('invoice/store_cove_call_api')),
                DropdownItem::link($translator->translate('invoice.peppol.store.cove.1.1.4'), $urlGenerator->generate('invoice/store_cove_send_test_json_invoice')),
            )
            ->render();           
            
            // Client
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent(I::tag()->addClass('bi bi-people'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('invoice.client.add'), $urlGenerator->generate('client/add', ['origin' => 'main'])),
                DropdownItem::link($translator->translate('invoice.client.view'), $urlGenerator->generate('client/index')),
                DropdownItem::link($translator->translate('invoice.client.note.add'), $urlGenerator->generate('clientnote/add')),
                DropdownItem::link($translator->translate('invoice.client.note.view'), $urlGenerator->generate('clientnote/index')),
                DropdownItem::link($translator->translate('invoice.invoice.delivery.location'), $urlGenerator->generate('del/index')),
            )
            ->render();
            
            // Quote
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('i.quote'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('i.create_quote'), $urlGenerator->generate('quote/add', ['origin' => 'main'])),
                DropdownItem::link($translator->translate('i.view'), $urlGenerator->generate('quote/index')),
            )
            ->render();
                        
            // SalesOrder
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('invoice.salesorder'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('i.view'), $urlGenerator->generate('salesorder/index'))
            )
            ->render();
            
            // Invoice
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('i.invoice'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('i.create_invoice'), $urlGenerator->generate('inv/add', ['origin' => 'main'])),
                DropdownItem::link($translator->translate('i.view'), $urlGenerator->generate('inv/index')),
                DropdownItem::link($translator->translate('i.recurring'), $urlGenerator->generate('invrecurring/index'))    
            )
            ->render();
            
            // Payment
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent(I::tag()->addClass('bi bi-coin'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('i.enter_payment'), $urlGenerator->generate('payment/add')),
                DropdownItem::link($translator->translate('i.view'), $urlGenerator->generate('payment/index')),
                DropdownItem::link($translator->translate('i.payment_logs'), $urlGenerator->generate('payment/online_log'))    
            )
            ->render();
            
            // Product
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('i.product'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('i.add_product'), $urlGenerator->generate('product/add')),
                DropdownItem::link($translator->translate('i.view'), $urlGenerator->generate('product/index')),
                DropdownItem::link($translator->translate('i.family'), $urlGenerator->generate('family/index')),    
                DropdownItem::link($translator->translate('i.unit'), $urlGenerator->generate('unit/index')),    
                DropdownItem::link($translator->translate('invoice.peppol.unit'), $urlGenerator->generate('unitpeppol/index'))    
            )
            ->render();  
            
             // Tasks
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('i.tasks'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('i.add_task'), $urlGenerator->generate('task/add')),
                DropdownItem::link($translator->translate('i.view'), $urlGenerator->generate('task/index')),    
            )
            ->render();  
            
             // Projects
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('i.projects'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('i.create_project'), $urlGenerator->generate('project/add')),
                DropdownItem::link($translator->translate('i.view'), $urlGenerator->generate('project/index')),    
            )
            ->render();
            
             // Reports
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent($translator->translate('i.reports'))        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('i.sales_by_client'), $urlGenerator->generate('report/sales_by_client_index')),
                DropdownItem::link($translator->translate('invoice.report.sales.by.product'), $urlGenerator->generate('report/sales_by_product_index')),    
                DropdownItem::link($translator->translate('invoice.report.sales.by.task'), $urlGenerator->generate('report/sales_by_task_index')),
                DropdownItem::link($translator->translate('i.sales_by_date'), $urlGenerator->generate('report/sales_by_year_index')),
                DropdownItem::link($translator->translate('i.payment_history'), $urlGenerator->generate('report/payment_history_index')),
                DropdownItem::link($translator->translate('i.invoice_aging'), $urlGenerator->generate('report/invoice_aging_index')),
            )
            ->render();  
            
            // Php Watch
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 1rem;',
                'hidden' => !$debugMode
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent('🐘')        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link('8.3', 'https://php.watch/versions/8.3', $debugMode, false,  ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('8.4', 'https://php.watch/versions/8.4', $debugMode, false, ['style' => 'background-color: #ffcccb']), 
            )
            ->render();
            
            // Emojipedia.org
            echo Dropdown::widget()
            ->addClass('navbar fs-4')  
            ->addAttributes([
                'style' => 'font-size: 2rem; color: cornflowerblue;',
                'hidden' => !$debugMode
            ])
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleContent('😀')        
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link('✅', 'https://emojipedia.org/check-mark-button', $debugMode, false,  ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('❌', 'https://emojipedia.org/cross-mark', $debugMode, false, ['style' => 'background-color: #ffcccb']), 
                DropdownItem::link('⬅', 'https://emojipedia.org/left-arrow', $debugMode, false, ['style' => 'background-color: #ffcccb']), 
                DropdownItem::link('➡', 'https://emojipedia.org/right-arrow', $debugMode, false, ['style' => 'background-color: #ffcccb']), 
                DropdownItem::link('🖉', 'https://emojipedia.org/lower-left-pencil', $debugMode, false, ['style' => 'background-color: #ffcccb']), 
                DropdownItem::link('🐘', 'https://emojipedia.org/elephant', $debugMode, false, ['style' => 'background-color: #ffcccb']), 
                
            )
            ->render(); 
            
            // Login
            echo Dropdown::widget()
            ->addClass('bi bi-door-open-fill text-success navbar fs-4')
            ->addAttributes(['hidden' => !$isGuest])        
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link($translator->translate('i.login'), $urlGenerator->generate('auth/login'))
            )->render();
            
            // Signup
            echo Dropdown::widget()
            ->addClass('bi bi-person-plus-fill text-success navbar fs-4')
            ->addAttributes(['hidden' => !$isGuest])        
            ->toggleVariant(DropdownToggleVariant::INFO)
            ->toggleSizeLarge(true)        
            ->items(
                DropdownItem::link(str_repeat(' ',1).$translator->translate('i.setup_create_user'), $urlGenerator->generate('auth/signup'))
            )->render();            
            
        } //null!== currentPath && !isGuest  
        
        if (null!==$currentPath) {
            echo Nav::widget()
              ->currentPath($currentPath)
              ->options(['class' => 'navbar fs4'])
              ->items(
                [
                  $isGuest ? '' : Form::tag()
                    ->post($urlGenerator->generate('auth/logout'))
                    ->csrf($csrf)
                    ->open()
                    . Button::submit(
                      $translator->translate('menu.logout', ['login' => Html::encode($userLogin)])
                    )
                    ->class('btn btn-primary')
                    . Form::tag()->close(),
                ],
            );
        }
        
        echo NavBar::end();
        ?>

        <div id="main-area">
            <?php
// Display the sidebar if enabled
            if ($s->getSetting('disable_sidebar') !== (string) 1) {
              include dirname(__DIR__) . '/invoice/layout/sidebar.php';
            }
            ?>
            <main class="container py-4">
                <?php echo $content; ?>
            </main>

            <div id="fullpage-loader" style="display: none">
                <div class="loader-content">
                    <i id="loader-icon" class="fa fa-cog fa-spin"></i>
                    <div id="loader-error" style="display: none">
                        <br/>
                        <a href="" class="btn btn-primary btn-sm" target="_blank">
                            <i class="fa fa-support"></i>
                        </a>
                    </div>
                </div>
                <div class="text-right">
                    <button type="button" class="fullpage-loader-close btn btn-link tip" aria-label="<?php $translator->translate('i.close'); ?>"
                            title="<?= $translator->translate('i.close'); ?>" data-placement="left">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>
            </div>
        </div>
        <footer class="container py-4">
            <?= PerformanceMetrics::widget() ?>           
        </footer>
        <?php
        $this->endBody();
        ?>
    </body>
</html>

<?php
/**
 * @see invoice/src/ViewInjection/LayoutViewInjection.php
 */
echo Html::script($javascriptJqueryDateHelper)->type('module');
?>
<?php
    $this->endPage();
?>