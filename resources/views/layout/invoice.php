<?php

declare(strict_types=1);

use App\Invoice\Asset\InvoiceAsset;
use App\Invoice\Asset\MonospaceAsset;
use App\Invoice\Asset\NProgressAsset;
// PCI Compliant Payment Gateway Assets
use App\Invoice\Asset\pciAsset\stripe_v10_Asset;
use App\Invoice\Asset\pciAsset\amazon_pay_v2_7_Asset;
use App\Invoice\Asset\pciAsset\braintree_dropin_1_33_7_Asset;
use App\Asset\AppAsset;
use App\Widget\PerformanceMetrics;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Style;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Bootstrap5\ButtonSize;
use Yiisoft\Bootstrap5\Dropdown;
use Yiisoft\Bootstrap5\DropdownItem;
use Yiisoft\Bootstrap5\ButtonVariant;
use Yiisoft\Bootstrap5\Nav;
use Yiisoft\Bootstrap5\NavBar;
use Yiisoft\Bootstrap5\NavLink;
use Yiisoft\Bootstrap5\NavStyle;
use Yiisoft\Bootstrap5\Offcanvas;
use Yiisoft\Bootstrap5\OffcanvasPlacement;

/**
 * Related logic: see ...src\ViewInjection\LayoutViewInjection
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\SubMenu $subMenu
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Config\Config $config
 * @var Yiisoft\Config\ConfigPaths $configPaths
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var bool $bootstrap5OffcanvasEnable
 * @var bool $isGuest
 * @var bool $buildDatabase
 * @var bool $debugMode
 * @var string $bootstrap5OffcanvasPlacement
 * @var string $bootstrap5LayoutInvoiceNavbarFont
 * @var string $bootstrap5LayoutInvoiceNavbarFontSize
 * @var string $brandLabel
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
 * Related logic: see ...src\ViewInjection\LayoutViewInjection.php
 * @var string $userLogin
 *
 * @var string $xdebug
 */
$assetManager->register(AppAsset::class);
$assetManager->register(InvoiceAsset::class);
$assetManager->register(NProgressAsset::class);
$assetManager->register(Yiisoft\Bootstrap5\Assets\BootstrapAsset::class);
$s->getSetting('monospace_amounts') == 1 ? $assetManager->register(MonospaceAsset::class) : '';
$assetManager->register(stripe_v10_Asset::class);
$assetManager->register(amazon_pay_v2_7_Asset::class);
$assetManager->register(braintree_dropin_1_33_7_Asset::class);
$vat = ($s->getSetting('enable_vat_registration') == '0');
// NOTE: $locale must correspond with SettingRepository/locale_language_array and
// ALSO: src/Invoice/Language/{folder_name}
$locale = match ($currentRoute->getArgument('_language') ?? 'en') {
    /**
     * Note: case 'x' must follow config/web/params locale => ['locales' => [ 'x' => 'x-XX'] format i.e. use key and NOT value i.e. use 'x' and NOT 'x-XX'
     * Note: If there is more than one official language in the country use the format x-YY for 'case' which should correspond with above locales array
     */
    'af-ZA' => 'AfrikaansSouthAfrican',
    'ar-BH' => 'ArabicBahrainian',
    'az' => 'Azerbaijani',
    'bs' => 'Bosnian',
    'de' => 'German',
    'en' => 'English',
    'fil' => 'Filipino',
    'fr' => 'French',
    'id' => 'Indonesian',
    'it' => 'Italian',
    'ja' => 'Japanese',
    'pl-PL' => 'Polish',
    'pt-BR' => 'PortugeseBrazil',
    'nl' => 'Dutch',
    'ru' => 'Russian',
    'sk' => 'Slovakian',
    'sl' => 'Slovenian',
    'es' => 'Spanish',
    'uk' => 'Ukrainian',
    'uz' => 'Uzbek',
    'vi' => 'Vietnamese',
    'zh-CN' => 'ChineseSimplified',
    'zh-TW' => 'TiawaneseMandarin',
    'zu-ZA' => 'ZuluSouthAfrican',
    default   => 'English',
};

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());

$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

// Platform, Performance, and Clear Assets Cache, and links Menu will disappear if set to false;
/**
 * Related logic: see src\ViewInjection\LayoutViewInjection.php $debugMode
 */
$this->beginPage();
?>
<!DOCTYPE html>
<html class="h-100" lang="<?= $currentRoute->getArgument('_language') ?? 'en'; ?>">
    <head>
        <?= Meta::documentEncoding('utf-8') ?>
        <?= Meta::pragmaDirective('X-UA-Compatible', 'IE=edge') ?>
        <?= Meta::data('viewport', 'width=device-width, initial-scale=1') ?>
        <?= Style::tag()->content('#nprogress .bar {
                    height: 2px !important; /* ~2mm */
                    background: #2196f3 !important;
                }')->render();
?>
        <title>
            <?= $s->getSetting('custom_title') ?: 'Yii-Invoice'; ?>
        </title>
        <?php $this->head() ?>
    </head>
    <body>
        <?php
    Html::tag('Noscript', Html::tag('Div', $translator->translate('please.enable.js'), ['class' => 'alert alert-danger no-margin']));
?>
        <?php
echo Html::script('NProgress.start();')->type('module');
$this->beginBody();

$offcanvasPlacement = match ($bootstrap5OffcanvasPlacement) {
    'bottom' => OffcanvasPlacement::BOTTOM,
    'end' => OffcanvasPlacement::END,
    'start' => OffcanvasPlacement::START,
    'top' => OffcanvasPlacement::TOP,
};

echo($bootstrap5OffcanvasEnable ? Offcanvas::widget()
        ->id('offcanvas' . ucFirst($bootstrap5OffcanvasPlacement))
        ->placement($offcanvasPlacement)
        ->title('Offcanvas')
        ->togglerContent('Toggle ' . strtolower($bootstrap5OffcanvasPlacement) . ' offcanvas')
        ->begin() : '');

echo NavBar::widget()
  // public folder represented by first forward slash ie. root
  ->addClass('navbar bg-body-tertiary')
  ->brandImage($logoPath)
  ->brandImageAttributes(
      ['margin' => $companyLogoMargin, 'width' => $companyLogoWidth, 'height' => $companyLogoHeight],
  )
  ->brandText(str_repeat('&nbsp;', 7) . $brandLabel)
  ->brandUrl($urlGenerator->generate('invoice/index'))
  ->container(false)
  ->containerAttributes([])
  ->addCssStyle([
      'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize,
      'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
  ])
  ->id('navbar')
  ->begin();

// Logout
echo Form::tag()
->post($urlGenerator->generate('auth/logout'))
->csrf($csrf)
->open()
. (string) Button::submit(
    $translator->translate('menu.logout', ['login' => Html::encode(preg_replace('/\d+/', '', $userLogin))]),
)
->class('btn btn-xs btn-warning')
. Form::tag()->close();

$subMenuPhpInfo = [
    0 => [
        'items' => [
            $translator->translate('faq.php.info.all') => ['invoice/phpinfo', ['selection' => '-1']],
            $translator->translate('faq.php.info.general') => ['invoice/phpinfo', ['selection' => '1']],
            $translator->translate('faq.php.info.credits') => ['invoice/phpinfo', ['selection' => '2']],
            $translator->translate('faq.php.info.configuration') => ['invoice/phpinfo', ['selection' => '4']],
            $translator->translate('faq.php.info.modules') => ['invoice/phpinfo', ['selection' => '8']],
            $translator->translate('faq.php.info.environment') => ['invoice/phpinfo', ['selection' => '16']],
            $translator->translate('faq.php.info.variables') => ['invoice/phpinfo', ['selection' => '32']],
            $translator->translate('faq.php.info.licence') => ['invoice/phpinfo', ['selection' => '64']],
        ],
    ],
];
$currentPath = $currentRoute->getUri()?->getPath();
if ((null !== $currentPath) && !$isGuest) {
    // nav items available in debugMode
    if ($debugMode) {
        echo Nav::widget()
        ->class('nav')
        ->addAttributes(['style' => 'background-color: #ffcccb'])
        ->items(
            Dropdown::widget()
            ->addClass('navbar fs-4')
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent('📋')
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                // Assets Clear
                DropdownItem::link(
                    $translator->translate('utility.assets.clear'),
                    $urlGenerator->generate('setting/clear'),
                    false,
                    false,
                    [
                        'data-bs-toggle' => 'tooltip',
                        'title' => 'Clear the assets cache which resides in /public/assets.',
                    ],
                ),
                // Vat exists? Show red or green background
                DropdownItem::text($translator->translate('vat'), ['style' => $vat ? 'background-color: #ffcccb' : 'background-color: #90EE90']),
                // Debug Mode
                DropdownItem::text($translator->translate('debug')),
                // Locale
                DropdownItem::text(
                    'Locale ➡️ ' . $locale,
                ),
                // cldr
                DropdownItem::text('cldr ➡️ ' . ($currentRoute->getArgument('_language') ?? 'unknown')),
                // File Location
                DropdownItem::text('File Location ➡️ ' . $s->debug_mode_file_location(0)),
            ),
            // FAQ's
            Dropdown::widget()
            ->addClass('navbar fs-4')
            ->addAttributes([
                'style' => 'font-size: 2rem; color: cornflowerblue;',
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent($translator->translate('faq'))
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::link('Console Commands', $urlGenerator->generate('invoice/faq', ['topic' => 'consolecommands', 'selection' => ''])),
                DropdownItem::link($translator->translate('faq.taxpoint'), $urlGenerator->generate('invoice/faq', ['topic' => 'tp', 'selection' => ''])),
                DropdownItem::link($translator->translate('faq.shared.hosting'), $urlGenerator->generate('invoice/faq', ['topic' => 'shared', 'selection' => ''])),
                DropdownItem::link($translator->translate('faq.payment.provider'), $urlGenerator->generate('invoice/faq', ['topic' => 'paymentprovider', 'selection' => ''])),
                DropdownItem::text($subMenu->generate($translator->translate('faq.php.info.details'), $urlGenerator, $subMenuPhpInfo)),
                DropdownItem::link($translator->translate('faq.oauth2'), $urlGenerator->generate('invoice/faq', ['topic' => 'oauth2', 'selection' => ''])),
                DropdownItem::link($translator->translate('faq.ai.callback.session'), $urlGenerator->generate('invoice/faq', ['topic' => 'ai_callback_session', 'selection' => ''])),
            ),
            // Generator
            Dropdown::widget()
            ->addClass('navbar fs-4')
            ->attributes([
                'style' => 'background-color: #ffcccb',
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent($translator->translate('generator'))
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::link(
                    $translator->translate('generator'),
                    $urlGenerator->generate('generator/index'),
                    false,
                    false,
                ),
                DropdownItem::link(
                    $translator->translate('generator.relations'),
                    $urlGenerator->generate('generatorrelation/index'),
                    false,
                    false,
                ),
                DropdownItem::link(
                    $translator->translate('generator.add'),
                    $urlGenerator->generate('generator/add'),
                    false,
                    false,
                ),
                DropdownItem::link(
                    $translator->translate('generator.relations.add'),
                    $urlGenerator->generate('generatorrelation/add'),
                    false,
                    false,
                ),
                DropdownItem::link(
                    $translator->translate('development.schema'),
                    $urlGenerator->generate('generator/quick_view_schema'),
                    false,
                    false,
                ),
                // Using the saved locale dropdown setting under Settings ... Views ... Google Translate, translate one of the three files located in
                // ..resources/views/generator/templates_protected
                // Your Json file must be located in src/Invoice/google_translate_unique folder
                // Get your downloaded Json file from
                DropdownItem::link(
                    $translator->translate('generator.google.translate.app'),
                    $urlGenerator->generate('generator/google_translate_lang', ['type' => 'app']),
                    false,
                    false,
                    ['data-bs-toggle' => 'tooltip', 'title' => $s->where('google_translate_json_filename'), 'hidden' => !$debugMode],
                ),
                DropdownItem::link(
                    $translator->translate('generator.google.translate.diff'),
                    $urlGenerator->generate('generator/google_translate_lang', ['type' => 'diff']),
                    false,
                    false,
                    ['data-bs-toggle' => 'tooltip', 'title' => 'src\Invoice\Language\English\diff_lang.php', 'hidden' => !$debugMode],
                ),
                DropdownItem::link(
                    $translator->translate('test.reset.setting'),
                    $urlGenerator->generate('invoice/setting_reset'),
                    false,
                    false,
                    ['data-bs-toggle' => 'tooltip', 'title' => $translator->translate('test.reset.setting.tooltip'), 'hidden' => !$debugMode],
                ),
                DropdownItem::link(
                    $translator->translate('test.reset'),
                    $urlGenerator->generate('invoice/test_data_reset'),
                    false,
                    false,
                    ['data-bs-toggle' => 'tooltip', 'title' => $translator->translate('test.reset.tooltip'), 'hidden' => !$debugMode],
                ),
                DropdownItem::link(
                    $translator->translate('test.remove'),
                    $urlGenerator->generate('invoice/test_data_remove'),
                    false,
                    false,
                    ['data-bs-toggle' => 'tooltip', 'title' => $translator->translate('test.remove.tooltip'), 'hidden' => !$debugMode],
                ),
            ),
            // Performance
            Dropdown::widget()
            ->addClass('navbar fs-4')
            ->addAttributes([
                'style' => $read_write ? 'background-color: #ffcccb'
                                       : 'background-color: #90EE90',
                'data-bs-toggle' => 'tooltip',
                'title' => $read_write ? $translator->translate('performance.label.switch.on')
                                       : $translator->translate('performance.label.switch.off'),
                'hidden' => !$debugMode,
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent($translator->translate('performance'))
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::text($translator->translate('platform.xdebug') . ' ' . $xdebug, ['data-bs-toggle' => 'tooltip', 'title' => 'Via Wampserver Menu: Icon..Php 8.1.8-->Php extensions-->xdebug 3.1.5(click)-->Allow php command prompt to restart automatically-->(click)Restart All Services-->No typing in or editing of a php.ini file!!']),
                DropdownItem::text('...config/common/params.php SyncTable currently not commented out and PhpFileSchemaProvider::MODE_READ_AND_WRITE...fast....MODE_WRITE_ONLY...slower'),
                DropdownItem::divider(),
                DropdownItem::text('Non-CLI/Non-FCGI: Manually Edit c:\wamp64\bin\apache\apache{version}\bin php.ini then ... Wampserver Icon ... Restart All Services'),
                DropdownItem::text('php.ini (line 425): max_execution_time (pref 360) = ' . ((string) ini_get('max_execution_time') ?: 'unknown') . (((string) ini_get('max_execution_time')  == 360 ? '✅' : '❌'))),
                DropdownItem::text('php.ini: (line 1788): opcache.jit (pref see nothing) = ' . ((string) ini_get('opcache.jit') ?: 'unknown') . (((string) ini_get('opcache.jit')  == '' ? '✅' : '❌'))),
                DropdownItem::text('php.ini: (line 1791): opcache.enable (pref 1) = ' . ((string) ini_get('opcache.enable') ?: 'unknown') . (((string) ini_get('opcache.enable')  == 1 ? '✅' : '❌'))),
                DropdownItem::text('php.ini (line 1794): opcache.enable_cli (pref 1) = ' . ((string) ini_get('opcache.enable_cli') ?: 'unknown') . (((string) ini_get('opcache.enable_cli') == 1 ? '✅' : '❌'))),
                DropdownItem::text('php.ini (line 1797): opcache.memory_consumption (pref 128) = ' . ((string) ini_get('opcache.memory_consumption') ?: 'unknown') . (((string) ini_get('opcache.memory_consumption')  == 128 ? '✅' : '❌')), ['data-bs-toggle' => 'tooltip', 'title' => 'e.g. change manually in C:\wamp64\bin\php\php8.1.13\phpForApache.ini and restart all services.']),
                DropdownItem::text('php.ini (line 1800): opcache.interned_strings_buffer (pref 64 for frameworks) = ' . ((string) ini_get('opcache.interned_strings_buffer') ?: 'unknown') . (((string) ini_get('opcache.interned_strings_buffer')  == 64 ? '✅' : '❌'))),
                DropdownItem::text('php.ini (line 1804): opcache.max_accelerated_files (pref 10000) = ' . ((string) ini_get('opcache.max_accelerated_files') ?: 'unknown') . (((string) ini_get('opcache.max_accelerated_files') == 10000 ? '✅' : '❌'))),
                DropdownItem::text('php.ini: (line 1818): opcache.validate_timestamps (pref 0 for production and 1 for development i.e. files checked on change) = ' . ((string) ini_get('opcache.validate_timestamps') ?: 'unknown') . (((string) ini_get('opcache.validate_timestamps') == 1 ? '✅' : '❌'))),
                DropdownItem::text('php.ini: (line 1822): opcache.revalidate_freq (production: check for changes every 60 sec, development: 0 immediate updates) = ' . ((string) ini_get('opcache.revalidate_freq') ?: 'unknown') . (((string) ini_get('opcache.revalidate_freq') == 0 ? '✅' : '❌'))),
                DropdownItem::divider(),
                // https://tideways.com/profiler/blog/fine-tune-your-opcache-configuration-to-avoid-caching-suprises
                DropdownItem::text(PerformanceMetrics::opCacheHealthCheck()),
                DropdownItem::divider(),
                DropdownItem::text('Left Click Wampserver Icon... Php ... Php Settings ... Memory Limit'),
                DropdownItem::text('php.ini (line 451): memory_limit (pref 1024 M) = ' . ((string) ini_get('memory_limit') ?: 'unknown') . (((string) ini_get('memory_limit') == '1024M' ? '✅' : '❌'))),
                DropdownItem::divider(),
                DropdownItem::text('.env: BUILD_DATABASE= (pref see nothing) = ' . ($buildDatabase ? 'You have built the database using BUILD_DATABASE=true, now assign the environment varirable to nothing i.e. BUILD_DATABASE=' : '✅')),
                DropdownItem::text('config.params: yiisoft/yii-debug: enabled , disable for improved performance'),
                DropdownItem::text('config.params: yiisoft/yii-debug-api: enabled, disable for improved performance'),
            ),
            // Platform
            Dropdown::widget()
            ->addClass('navbar fs-4')
            ->addAttributes([
                'hidden' => !$debugMode,
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent($translator->translate('platform'))
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::text('WAMP'),
                DropdownItem::text($translator->translate('platform.editor') . ': Apache Netbeans IDE 23 64 bit'),
                DropdownItem::text($translator->translate('platform.server') . ': Wampserver 3.3.6 64 bit'),
                DropdownItem::text('Apache: 2.4.59 64 bit'),
                DropdownItem::text($translator->translate('platform.mySqlVersion') . ': 8.3.0 '),
                DropdownItem::text($translator->translate('platform.windowsVersion') . ': Windows 11 Pro Edition'),
                DropdownItem::text($translator->translate('platform.PhpVersion') . ' ' . PHP_VERSION),
                DropdownItem::link($translator->translate('platform.PhpSupport'), 'https://php.net/supported-versions'),
                DropdownItem::link($translator->translate('platform.update'), 'https://wampserver.aviatechno.net/'),
                DropdownItem::link('Bootstrap 5 Icons with Filter', 'https://icons.getbootstrap.com/'),
                DropdownItem::link('BootstrapBrain Free Wavelight Template', 'https://bootstrapbrain.com/template/free-bootstrap-5-multipurpose-one-page-template-wave/'),
                DropdownItem::link('Html to Markdown', 'https://convertsimple.com/convert-html-to-markdown/'),
                DropdownItem::link('European Invoicing', 'https://ec.europa.eu/digital-building-blocks/wikis/display/DIGITAL/Compliance+with+eInvoicing+standard'),
                DropdownItem::link('European Digital Testing', 'https://ec.europa.eu/digital-building-blocks/wikis/display/DIGITAL/eInvoicing+Conformance+Testing'),
                DropdownItem::link('What does a Peppol ID look like?', 'https://ecosio.com/en/blog/how-peppol-ids-work/'),
                DropdownItem::link('Peppol Accounting Requirements', 'https://docs.peppol.eu/poacc/billing/3.0/bis/#accountingreq'),
                DropdownItem::link('➡️ Peppol Billing 3.0 - Syntax', 'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/'),
                DropdownItem::link('➡️ Peppol Billing 3.0 - Tree', 'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/tree/'),
                DropdownItem::link('Universal Business Language 2.1 (UBL)', 'http://www.datypic.com/sc/ubl21/ss.html'),
                DropdownItem::link('StoreCove Documentation', 'https://www.storecove.com/docs'),
                DropdownItem::link('Peppol Company Search', 'https://directory.peppol.eu/public'),
                DropdownItem::link('ISO 3 letter currency codes - 4217 alpha-3', 'https://www.iso.org/iso-4217-currency-codes.html'),
                DropdownItem::link('Xml Example 2.1', 'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/'),
                DropdownItem::link('Xml Example 3.0', 'https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/rules/examples/base-example.xml'),
                DropdownItem::link('Ecosio Xml Validator', 'https://ecosio.com/en/peppol-and-xml-document-validator/'),
                DropdownItem::link('Xml CodeLists', 'https://github.com/OpenPEPPOL/peppol-bis-invoice-3/tree/master/structure/codelist'),
                DropdownItem::link('Convert XML to PHP Array Online', 'https://wtools.io/convert-xml-to-php-array'),
                DropdownItem::link('Writing XML using Sabre', 'https://sabre.io/xml/writing/'),
                DropdownItem::link('Understanding Same Site Cookies', 'https://andrewlock.net/understanding-samesite-cookies/#:~:text=SameSite%3DLax%20cookies%20are%20not,Lax%20(or%20Strict%20)%20cookies'),
                DropdownItem::link('HMRC Developer Hub', 'https://developer.service.hmrc.gov.uk/developer/login'),
                DropdownItem::link('HMRC Developer Hub - Web App Via Server', 'https://developer.service.hmrc.gov.uk/guides/fraud-prevention/connection-method/web-app-via-server/'),
                DropdownItem::link('Scotland - e-invoice Template - Lessons Learned', 'https://www.gov.scot/publications/einvoicing-guide/documents/'),
                DropdownItem::link('German, and Swiss Law Amendments now prioritize Opensource in Public Sector', 'https://interoperable-europe.ec.europa.eu/collection/open-source-observatory-osor/news/germanys-ozg-20-favors-open-source-solutions'),
                DropdownItem::link('Jsonld  Playground for flattening Jsonld files', 'https://json-ld.org/playground/'),
                DropdownItem::link('Converting flattened file to php array', 'https://wtools.io/convert-json-to-php-array'),
                DropdownItem::link('Jsonld  Playground for flattening Jsonld files', 'https://json-ld.org/playground/'),
                DropdownItem::link('jQuery UI 1.14.1 (9th March 2025) - Customize download - {keycode; Widgets => datepicker}', 'https://jqueryui.com/download/'),
                DropdownItem::link('Using ngrok and Wampserver VirtualHosts', 'https://ngrok.com/docs/using-ngrok-with/virtualHosts/'),
                DropdownItem::link('Using ngrok and webhook testing', 'https://ngrok.com/use-cases/webhook-testing'),
                DropdownItem::link('Google Oauth2 Playground', 'https://developers.google.com/oauthplayground'),
                DropdownItem::link('Google Oauth2 Web Application', 'https://console.cloud.google.com/apis/credentials/oauthclient'),
            ),
            // Php Watch
            Dropdown::widget()
            ->addClass('navbar fs-4')
            ->addAttributes([
                'style' => 'font-size: 1rem;',
                'hidden' => !$debugMode,
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent('🐘')
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::link('8.3', 'https://php.watch/versions/8.3', $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('8.4', 'https://php.watch/versions/8.4', $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('8.5', 'https://php.watch/versions/8.5', $debugMode, false, ['style' => 'background-color: #ffcccb']),
            ),
            // Emojipedia.org
            Dropdown::widget()
            ->addClass('navbar fs-4')
            ->addAttributes([
                'style' => 'font-size: 2rem; color: cornflowerblue;',
                'hidden' => !$debugMode,
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent('😀')
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::link('✅', 'https://emojipedia.org/check-mark-button', $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('❌', 'https://emojipedia.org/cross-mark', $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('⬅', 'https://emojipedia.org/left-arrow', $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('➡', 'https://emojipedia.org/right-arrow', $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('🖉', 'https://emojipedia.org/lower-left-pencil', $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('🔘', 'https://emojipedia.org/radio-button', $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('☑️', 'https://emojipedia.org/check-box-with-check', $debugMode, false, ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('🐘', 'https://emojipedia.org/elephant', $debugMode, false, ['style' => 'background-color: #ffcccb']),
            ),
        );
    }

    echo Nav::widget()
    ->class('nav')
    ->addAttributes(['style' => 'background-color: #e3f2fd'])
    ->items(
        NavLink::to(
            //label
            I::tag()->class('fa fa-dashboard'),
            // url
            $urlGenerator->generate('invoice/dashboard'),
            // active
            false,
            // disabled
            $isGuest,
            // encodeLabel
            false,
            // attributes
            [],
            // url attributes
            [],
            // visible
            true,
        ),
        // Settings
        Dropdown::widget()
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent(I::tag()->addClass('fa fa-cogs'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('view'), $urlGenerator->generate('setting/debug_index'), false, !$debugMode, ['style' => 'background-color: #ffcccb', 'hidden' => !$debugMode]),
            DropdownItem::link($translator->translate('setting.add'), $urlGenerator->generate('setting/add'), false, !$debugMode, ['style' => 'background-color: #ffcccb', 'hidden' => !$debugMode]),
            DropdownItem::link($translator->translate('caution.delete.invoices'), $urlGenerator->generate('inv/flush'), false, !$debugMode, ['style' => 'background-color: #ffcccb', 'hidden' => !$debugMode]),
            DropdownItem::link($translator->translate('view'), $urlGenerator->generate('setting/tab_index')),
            DropdownItem::link($translator->translate((($s->getSetting('install_test_data') == '1') && ($s->getSetting('use_test_data') == '1'))
            ? 'install.test.data' : 'install.test.data.goto.tab.index'), (($s->getSetting('install_test_data') == '1' && $s->getSetting('use_test_data') == '1')
            ? $urlGenerator->generate('invoice/index') : $urlGenerator->generate('setting/tab_index')), ($s->getSetting('install_test_data') == '1' && $s->getSetting('use_test_data') == '1')),
            DropdownItem::link($translator->translate('email.template'), $urlGenerator->generate('emailtemplate/index')),
            DropdownItem::link($translator->translate('email.from.dropdown'), $urlGenerator->generate('from/index')),
            DropdownItem::link($translator->translate('email.log'), $urlGenerator->generate('invsentlog/index')),
            DropdownItem::link($translator->translate('custom.fields'), $urlGenerator->generate('customfield/index')),
            DropdownItem::link($translator->translate('group'), $urlGenerator->generate('group/index')),
            DropdownItem::link($translator->translate('archive'), $urlGenerator->generate('inv/archive')),
            DropdownItem::link($translator->translate('payment.method'), $urlGenerator->generate('paymentmethod/index')),
            DropdownItem::link($translator->translate('tax.rate'), $urlGenerator->generate('taxrate/index')),
            DropdownItem::link($translator->translate('contract'), $urlGenerator->generate('contract/index')),
            DropdownItem::link($translator->translate('user.account'), $urlGenerator->generate('userinv/index')),
            DropdownItem::link($translator->translate('password.change'), $urlGenerator->generate('auth/change')),
            DropdownItem::link($translator->translate('user.api.list'), $urlGenerator->generate('user/index')),
            DropdownItem::link($translator->translate('setting.company'), $urlGenerator->generate('company/index')),
            DropdownItem::link($translator->translate('setting.company.private'), $urlGenerator->generate('companyprivate/index')),
            DropdownItem::link($translator->translate('setting.company.profile'), $urlGenerator->generate('profile/index')),
        ),
        // peppol
        Dropdown::widget()
        ->addClass('navbar fs-4')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
            'url' => '#',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent($translator->translate('peppol.abbreviation'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('allowance.or.charge.add'), $urlGenerator->generate('allowancecharge/index')),
            DropdownItem::link($translator->translate('peppol.store.cove.1.1.1'), 'https://www.storecove.com/register/'),
            DropdownItem::link($translator->translate('peppol.store.cove.1.1.2'), $urlGenerator->generate('setting/tab_index')),
            DropdownItem::link($translator->translate('peppol.store.cove.1.1.3'), $urlGenerator->generate('invoice/store_cove_call_api')),
            DropdownItem::link($translator->translate('peppol.store.cove.1.1.4'), $urlGenerator->generate('invoice/store_cove_send_test_json_invoice')),
        ),
        // Client
        Dropdown::widget()
        ->addClass('navbar fs-4')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent(I::tag()->addClass('bi bi-people'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('client.add'), $urlGenerator->generate('client/add', ['origin' => 'main'])),
            DropdownItem::link($translator->translate('client.view'), $urlGenerator->generate('client/index')),
            DropdownItem::link($translator->translate('client.note.add'), $urlGenerator->generate('clientnote/add')),
            DropdownItem::link($translator->translate('client.note.view'), $urlGenerator->generate('clientnote/index')),
            DropdownItem::link($translator->translate('delivery.location'), $urlGenerator->generate('del/index')),
        ),
        // Quote
        Dropdown::widget()
        ->addClass('navbar fs-4')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent($translator->translate('quote'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('create.quote'), $urlGenerator->generate('quote/add', ['origin' => 'main'])),
            DropdownItem::link($translator->translate('view'), $urlGenerator->generate('quote/index')),
        ),
        // SalesOrder
        Dropdown::widget()
        ->addClass('navbar fs-4')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent($translator->translate('salesorder'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('view'), $urlGenerator->generate('salesorder/index')),
        ),
        // Invoice
        Dropdown::widget()
        ->addClass('navbar fs-4')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent($translator->translate('invoice'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('create.invoice'), $urlGenerator->generate('inv/add', ['origin' => 'main'])),
            DropdownItem::link($translator->translate('view'), $urlGenerator->generate('inv/index')),
            DropdownItem::link($translator->translate('recurring'), $urlGenerator->generate('invrecurring/index')),
        ),
        // Payment
        Dropdown::widget()
        ->addClass('navbar fs-4')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent(I::tag()->addClass('bi bi-coin'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('enter.payment'), $urlGenerator->generate('payment/add')),
            DropdownItem::link($translator->translate('view'), $urlGenerator->generate('payment/index')),
            DropdownItem::link($translator->translate('payment.logs'), $urlGenerator->generate('payment/online_log')),
        ),
        // Product
        Dropdown::widget()
        ->addClass('navbar fs-4')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent($translator->translate('product'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('add.product'), $urlGenerator->generate('product/add')),
            DropdownItem::link($translator->translate('view'), $urlGenerator->generate('product/index')),
            DropdownItem::link($translator->translate('category.primary'), $urlGenerator->generate('categoryprimary/index')),
            DropdownItem::link($translator->translate('category.secondary'), $urlGenerator->generate('categorysecondary/index')),
            DropdownItem::link($translator->translate('family'), $urlGenerator->generate('family/index')),
            DropdownItem::link($translator->translate('family.search'), $urlGenerator->generate('family/search')),
            DropdownItem::link($translator->translate('unit'), $urlGenerator->generate('unit/index')),
            DropdownItem::link($translator->translate('peppol.unit'), $urlGenerator->generate('unitpeppol/index')),
        ),
        // Tasks
        Dropdown::widget()
        ->addClass('navbar fs-4')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent($translator->translate('tasks'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('add.task'), $urlGenerator->generate('task/add')),
            DropdownItem::link($translator->translate('view'), $urlGenerator->generate('task/index')),
        ),
        // Projects
        Dropdown::widget()
        ->addClass('navbar fs-4')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent($translator->translate('projects'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('create.project'), $urlGenerator->generate('project/add')),
            DropdownItem::link($translator->translate('view'), $urlGenerator->generate('project/index')),
        ),
        // Reports
        Dropdown::widget()
        ->addClass('navbar fs-4')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent($translator->translate('reports'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($translator->translate('sales.by.client'), $urlGenerator->generate('report/sales_by_client_index')),
            DropdownItem::link($translator->translate('report.sales.by.product'), $urlGenerator->generate('report/sales_by_product_index')),
            DropdownItem::link($translator->translate('report.sales.by.task'), $urlGenerator->generate('report/sales_by_task_index')),
            DropdownItem::link($translator->translate('sales.by.date'), $urlGenerator->generate('report/sales_by_year_index')),
            DropdownItem::link($translator->translate('payment.history'), $urlGenerator->generate('report/payment_history_index')),
            DropdownItem::link($translator->translate('aging'), $urlGenerator->generate('report/invoice_aging_index')),
            DropdownItem::link($translator->translate('report.test.fraud.prevention.headers.api'), $urlGenerator->generate('backend/hmrc/fphValidate')),
        ),
        // Translate
        Dropdown::widget()
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
            'data-bs-toggle' => 'tooltip',
            'title' => $translator->translate('language'),
            'url' => '#',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent(I::tag()->class('bi bi-translate'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link('Afrikaans South African', $urlGenerator->generateFromCurrent(['_language' => 'af-ZA'], fallbackRouteName: 'site/index')),
            DropdownItem::link('Arabic Bahrainian/ عربي', $urlGenerator->generateFromCurrent(['_language' => 'ar-BH'], fallbackRouteName: 'site/index')),
            DropdownItem::link('Azerbaijani / Azərbaycan', $urlGenerator->generateFromCurrent(['_language' => 'az'], fallbackRouteName: 'site/index')),
            DropdownItem::link('Bosnian / Bosanski', $urlGenerator->generateFromCurrent(['_language' => 'bs'], fallbackRouteName: 'site/index')),
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
            DropdownItem::link('Slovenian / Slovenski', $urlGenerator->generateFromCurrent(['_language' => 'sl'], fallbackRouteName: 'site/index')),
            DropdownItem::link('Spanish /  Española x', $urlGenerator->generateFromCurrent(['_language' => 'es'], fallbackRouteName: 'site/index')),
            DropdownItem::link('Ukrainian / українська', $urlGenerator->generateFromCurrent(['_language' => 'uk'], fallbackRouteName: 'site/index')),
            DropdownItem::link('Uzbek / o' . "'" . 'zbek', $urlGenerator->generateFromCurrent(['_language' => 'uz'], fallbackRouteName: 'site/index')),
            DropdownItem::link('Vietnamese / Tiếng Việt', $urlGenerator->generateFromCurrent(['_language' => 'vi'], fallbackRouteName: 'site/index')),
            DropdownItem::link('Zulu South African/ Zulu South African', $urlGenerator->generateFromCurrent(['_language' => 'zu-ZA'], fallbackRouteName: 'site/index')),
        ),
    )
         ->styles(NavStyle::NAVBAR);
} //null!== currentPath && !isGuest
echo NavBar::end();
echo $bootstrap5OffcanvasEnable ? Offcanvas::end() : '';
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
                    <button type="button" class="fullpage-loader-close btn btn-link tip" aria-label="<?php $translator->translate('close'); ?>"
                            title="<?= $translator->translate('close'); ?>" data-placement="left">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>
            </div>
        </div>
        <footer class="container py-4">
            <?= PerformanceMetrics::widget(); ?>           
        </footer>
        <?php
            echo Html::script('NProgress.done();')->type('module');
$this->endBody();
?>
    </body>
</html>

<?php
    $this->endPage();
?>