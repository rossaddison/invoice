<?php

declare(strict_types=1);

use App\Invoice\Asset\InvoiceCdnAsset as InvCdn;
use App\Invoice\Asset\InvoiceNodeModulesAsset as InvNm;
use App\Invoice\Asset\MonospaceAsset;
use App\Invoice\Asset\NProgressAsset;
// PCI Compliant Payment Gateway Assets
use App\Invoice\Asset\pciAsset\StripeVersionTenAsset;
use App\Invoice\Asset\pciAsset\AmazonPayTwoSevenAsset;
use App\Invoice\Asset\pciAsset\BraintreeDropInOneThirtyThreeSevenAsset;

use App\Asset\AppCdnAsset;
use App\Asset\AppNodeModulesAsset;

use App\Widget\PerformanceMetrics;
use Yiisoft\Bootstrap5\Assets\BootstrapCdnAsset as BsCdn;
use Yiisoft\Bootstrap5\Assets\BootstrapAsset as BsNm;
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
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Html\Tag\Style;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Meta;
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
 * @var bool $appCdnNotNodeModule
 * @var bool $invCdnNotNodeModule
 * @var bool $bootstrap5CdnNotNodeModule
 * @var bool $bootstrap5OffcanvasEnable
 * @var bool $isGuest
 * @var bool $buildDatabase
 * @var bool $debugMode
 * @var string $bootstrap5OffcanvasPlacement
 * @var string $bootstrap5LayoutInvoiceNavbarFont
 * @var string $bootstrap5LayoutInvoiceNavbarFontSize
 * @var int $bootstrap5FormInputHeight
 * @var int $bootstrap5FormFontSize
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
 * @var string $splitterLanguage
 * @var string $splitterRegion
 * @var string $currentLocaleFlag
 * @var array<string,string> $localeFlags
 * @var DropdownItem $afZA
 * @var DropdownItem $arBH
 * @var DropdownItem $az
 * @var DropdownItem $beBY
 * @var DropdownItem $bs
 * @var DropdownItem $zhCN
 * @var DropdownItem $zhTW
 * @var DropdownItem $en
 * @var DropdownItem $fil
 * @var DropdownItem $fr
 * @var DropdownItem $gdGB
 * @var DropdownItem $haNG
 * @var DropdownItem $heIL
 * @var DropdownItem $igNG
 * @var DropdownItem $nl
 * @var DropdownItem $de
 * @var DropdownItem $id
 * @var DropdownItem $it
 * @var DropdownItem $ja
 * @var DropdownItem $pl
 * @var DropdownItem $ptBR
 * @var DropdownItem $ru
 * @var DropdownItem $sk
 * @var DropdownItem $sl
 * @var DropdownItem $es
 * @var DropdownItem $uk
 * @var DropdownItem $uz
 * @var DropdownItem $vi
 * @var DropdownItem $yoNG
 * @var DropdownItem $zuZA
 * Related logic: see ...src\ViewInjection\LayoutViewInjection.php
 * @var string $userLogin
 *
 * @var string $xdebug
 *
 */

// Settings ... View ... General
$assetManager->register($appCdnNotNodeModule ? AppCdnAsset::class
                                             : AppNodeModulesAsset::class);
$assetManager->register($invCdnNotNodeModule ? InvCdn::class : InvNm::class);
$assetManager->register(NProgressAsset::class);
$assetManager->register($bootstrap5CdnNotNodeModule ? BsCdn::class : BsNm::class);
$s->getSetting('monospace_amounts') == 1 ?
    $assetManager->register(MonospaceAsset::class) : '';
$assetManager->register(StripeVersionTenAsset::class);
$assetManager->register(AmazonPayTwoSevenAsset::class);
$assetManager->register(BraintreeDropInOneThirtyThreeSevenAsset::class);
$vat = ($s->getSetting('enable_vat_registration') == '0');
$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());
$t = $translator;
$itemFontArray = [
    'style' => 'font-size: ' . $bootstrap5LayoutInvoiceNavbarFontSize . 'px;'
    . ' color: black;'];
// Platform, Performance, and Clear Assets Cache, and links Menu will disappear
// if set to false;
/**
 * Related logic: see src\ViewInjection\LayoutViewInjection.php $debugMode
 */
$this->beginPage();
?>
<!DOCTYPE html>
<?php
echo Html::openTag('html',
        ['class' => 'h-100', 'lang' => $splitterLanguage ?: 'en']);
 echo Html::openTag('head'); //1
 echo Meta::documentEncoding('utf-8');
 echo Meta::data('viewport', 'width=device-width, initial-scale=1');
  echo new Style()->content(
    ':root {'
    . ' --inv-nav-fs: ' . $bootstrap5LayoutInvoiceNavbarFontSize . 'px;'
    . ' --inv-nav-ff: ' . $bootstrap5LayoutInvoiceNavbarFont . ';'
    . ' --inv-input-height: ' . $bootstrap5FormInputHeight . 'px;'
    . ' --inv-form-fs: ' . $bootstrap5FormFontSize . 'px;'
    . ' }'
  )->render();
 echo Html::openTag('title'); //1
  echo $s->getSetting('custom_title') ?: 'Yii-Invoice';
 echo Html::closeTag('title'); //1
 $this->head();
echo Html::closeTag('head');
echo Html::openTag('body');
 echo Html::tag('noscript',
  Html::tag('div', $t->translate('please.enable.js'),
   ['class' => 'alert alert-danger no-margin']),
 );
echo Html::script('NProgress.start();')->type('module');
$this->beginBody();

$offcanvasPlacement = match ($bootstrap5OffcanvasPlacement) {
    'bottom' => OffcanvasPlacement::BOTTOM,
    'end' => OffcanvasPlacement::END,
    'start' => OffcanvasPlacement::START,
    'top' => OffcanvasPlacement::TOP,
};

echo $bootstrap5OffcanvasEnable ? Offcanvas::widget()
        ->id('offcanvas' . ucFirst($bootstrap5OffcanvasPlacement))
        ->placement($offcanvasPlacement)
        ->title('Offcanvas')
        ->togglerContent('Toggle '
            . strtolower($bootstrap5OffcanvasPlacement) . ' offcanvas')
        ->begin() : '';

echo NavBar::widget()
//->addClass('navbar bg-body-tertiary')
->brandImage($logoPath)
->brandImageAttributes(
    ['margin' => $companyLogoMargin, 'width' => $companyLogoWidth,
     'height' => $companyLogoHeight],
)
->brandText(str_repeat('&nbsp;', 7) . $brandLabel)
->brandUrl($urlGenerator->generate('invoice/index'))
->container(false)
->containerAttributes([])
->addCssStyle([
    'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
    'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
])
->id('navbar')
->begin();

// Logout
echo  new Form()
->post($urlGenerator->generate('auth/logout'))
->csrf($csrf)
->open()
. Button::submit(
    (new I())->addClass('bi bi-box-arrow-right me-1')->render()
    . Html::encode($t->translate('menu.logout',
        ['login' => Html::encode(preg_replace('/\d+/', '', $userLogin))])),
)->encode(false)
->addClass('btn btn-outline-danger')
->addStyle('font-size: '
                . $bootstrap5LayoutInvoiceNavbarFontSize
                . 'px; padding: '
                . ((int) $bootstrap5LayoutInvoiceNavbarFontSize * 0.15)
                . 'px '
                . ((int) $bootstrap5LayoutInvoiceNavbarFontSize * 0.4) . 'px;')
.  new Form()->close();

$ifaq = 'invoice/faq';
$sel = 'selection';
$tpc = 'topic';

$subMenuPrometheus = [
    0 => [
        'items' => [
            'Dashboard' => ['prometheus/dashboard', []],
            'Raw Metrics' => ['prometheus/metrics', []],
            'Health Check' => ['prometheus/health', []],
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
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent('📋')
            ->togglerSize(ButtonSize::LARGE)
            ->addCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
            ])
            ->items(
                // Vat exists? Show red or green background
                DropdownItem::text($t->translate('vat'), $itemFontArray +
                    ['style' => $vat ? 'color: black; background-color: #ffcccb' :
                        'color: black; background-color: #90EE90']),
                // Debug Mode
                DropdownItem::text($t->translate('debug'), $itemFontArray),
                // Locale
                DropdownItem::text($t->translate('region')
                    . ' ➡️ ' . ($splitterRegion ?: 'unknown'), $itemFontArray),
                // cldr
                DropdownItem::text('cldr ➡️ ' . ($splitterLanguage ?: 'unknown'),
                    $itemFontArray),
                // File Location
                DropdownItem::text('File Location ➡️ '
                    . $s->debugModeFileLocation(0), $itemFontArray),
            ),
            // FAQ's
            Dropdown::widget()
            ->addTogglerCssStyle([
                'color: cornflowerblue;',
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent($t->translate('faq'))
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::link('Console Commands',
                    $urlGenerator->generate($ifaq,
                        [$tpc => 'consolecommands', $sel => '']),
                        itemAttributes: $itemFontArray),
                DropdownItem::link($t->translate('faq.taxpoint'),
                    $urlGenerator->generate($ifaq,
                        [$tpc => 'tp', $sel => '']),
                        itemAttributes: $itemFontArray),
                DropdownItem::link($t->translate('faq.shared.hosting'),
                    $urlGenerator->generate($ifaq,
                        [$tpc => 'shared', $sel => '']),
                        itemAttributes: $itemFontArray),
                DropdownItem::link($t->translate('faq.wsl.to.alpine'),
                    $urlGenerator->generate($ifaq,
                        [$tpc => 'wsl_to_alpine', $sel => '']),
                        itemAttributes: $itemFontArray),
                DropdownItem::link($t->translate('faq.payment.provider'),
                    $urlGenerator->generate($ifaq,
                        [$tpc => 'paymentprovider', $sel => '']),
                        itemAttributes: $itemFontArray),
                DropdownItem::link('JavaScript Analysis',
                    $urlGenerator->generate($ifaq,
                        [$tpc => 'javascript_analysis', $sel => '']),
                        itemAttributes: $itemFontArray),
                DropdownItem::link('Codeception Selectors Checklist',
                    $urlGenerator->generate($ifaq,
                        [$tpc => 'codeception_selectors_checklist', $sel => '']),
                        itemAttributes: $itemFontArray),
                DropdownItem::link($t->translate('faq.oauth2'),
                    $urlGenerator->generate($ifaq,
                        [$tpc => 'oauth2', $sel => '']),
                        itemAttributes: $itemFontArray),
                DropdownItem::link($t->translate('faq.ai.callback.session'),
                    $urlGenerator->generate($ifaq,
                        [$tpc => 'ai_callback_session', $sel => '']),
                        itemAttributes: $itemFontArray),
            ),
            // E-Invoicing
            Dropdown::widget()
            ->attributes(['class' => 'inv-warn'])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent( new Img()
                         ->width((int) $bootstrap5LayoutInvoiceNavbarFontSize * 2)
                         ->height((int) $bootstrap5LayoutInvoiceNavbarFontSize * 1)
                         ->src('/site/e-invoice-emoji.png'))
            ->togglerSize(ButtonSize::SMALL)
            ->items(
                DropdownItem::link('European Invoicing',
                    'https://ec.europa.eu/digital-building-blocks/'
                    . 'wikis/display/'
                    . 'DIGITAL/Compliance+with+eInvoicing+standard',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('European Digital Testing',
                    'https://ec.europa.eu/digital-building-blocks/'
                    . 'wikis/display/DIGITAL/'
                    . 'eInvoicing+Conformance+Testing',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('What does a Peppol ID look like?',
                    'https://ecosio.com/en/blog/how-peppol-ids-work/',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('Peppol Accounting Requirements',
                    'https://docs.peppol.eu/poacc/billing/3.0/bis/#accountingreq',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('➡️ Peppol Billing 3.0 - Syntax',
                    'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('➡️ Peppol Billing 3.0 - Tree',
                    'https://docs.peppol.eu/poacc/billing/3.0/'
                    . 'syntax/ubl-invoice/tree/',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('Universal Business Language 2.1 (UBL)',
                    'https://www.datypic.com/sc/ubl21/ss.html',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('StoreCove Documentation',
                    'https://www.storecove.com/docs',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('Peppol Company Search',
                    'https://directory.peppol.eu/public',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('ISO 3 letter currency codes - 4217 alpha-3',
                    'https://www.iso.org/iso-4217-currency-codes.html',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('Xml Example 2.1',
                    'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link(content: 'Xml Example 3.0', url:
                    'https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/'
                    . 'rules/examples/base-example.xml',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('Ecosio Xml Validator',
                    'https://ecosio.com/en/peppol-and-xml-document-validator/',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('Xml CodeLists',
                    'https://github.com/OpenPEPPOL/peppol-bis-invoice-3/'
                    . 'tree/master/structure/codelist',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('Convert XML to PHP Array Online',
                    'https://wtools.io/convert-xml-to-php-array',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('Writing XML using Sabre',
                    'https://sabre.io/xml/writing/',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::link('Scotland - e-invoice Template - Lessons Learned',
                    'https://www.gov.scot/publications/einvoicing-guide/documents/',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
                DropdownItem::divider(),
                DropdownItem::link('Understanding Same Site Cookies',
                    'https://andrewlock.net/understanding-samesite-cookies/'
                    . '#:~:text=SameSite%3DLax%20cookies%20are%20not,Lax'
                    . '%20(or%20Strict%20)%20cookies',
                    itemAttributes: $itemFontArray + ['target' => '_blank']),
            ),
            // Generator
            Dropdown::widget()
            ->attributes(['class' => 'inv-warn'])
            ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
                'color' => '#ffc107',
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent($t->translate('generator'))
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::link(
                    $t->translate('generator'),
                    $urlGenerator->generate('generator/index'),
                    false,
                    itemAttributes: $itemFontArray,
                ),
                DropdownItem::link(
                    $t->translate('generator.relations'),
                    $urlGenerator->generate('generatorrelation/index'),
                    false,
                    itemAttributes: $itemFontArray,
                ),
                DropdownItem::link(
                    $t->translate('generator.add'),
                    $urlGenerator->generate('generator/add'),
                    false,
                    itemAttributes: $itemFontArray,
                ),
                DropdownItem::link(
                    $t->translate('generator.relations.add'),
                    $urlGenerator->generate('generatorrelation/add'),
                    false,
                    itemAttributes: $itemFontArray,
                ),
                DropdownItem::link(
                    $t->translate('development.schema'),
                    $urlGenerator->generate('generator/quickViewSchema'),
                    false,
                    itemAttributes: $itemFontArray,
                ),
                // Using the saved locale dropdown setting under Settings
                // ... Views ... Google Translate, translate one of the three
                // files located in
                // ..resources/views/generator/templates_protected
                // Your Json file must be located in src/Invoice/
                // google_translate_unique folder
                // Get your downloaded Json file from
                DropdownItem::link(
                    $t->translate('generator.google.translate.app'),
                    $urlGenerator->generate('generator/googleTranslateLang',
                        ['type' => 'app']),
                    false,
                    itemAttributes: $itemFontArray +
                    ['data-bs-toggle' => 'tooltip',
                        'title' => $s->where('google_translate_json_filename'),
                            'hidden' => !$debugMode],
                ),
                DropdownItem::link(
                    $t->translate('generator.google.translate.diff'),
                    $urlGenerator->generate('generator/googleTranslateLang',
                        ['type' => 'diff']),
                    false,
                    itemAttributes: $itemFontArray +
                    ['data-bs-toggle' => 'tooltip',
                        'title' => 'src\Invoice\Language\English\diff_lang.php',
                            'hidden' => !$debugMode],
                ),
                DropdownItem::link(
                    $t->translate('generator.google.translate.info'),
                    $urlGenerator->generate('generator/googleTranslateInfo'),
                    false,
                    itemAttributes: $itemFontArray +
                    ['data-bs-toggle' => 'tooltip',
                        'title' => 'Translate resources/views/invoice/info/en/'
                        . 'invoice.php',
                            'hidden' => !$debugMode],
                ),
                DropdownItem::link(
                    $t->translate('test.reset.setting'),
                    $urlGenerator->generate('invoice/settingReset'),
                    false,
                    itemAttributes: $itemFontArray +
                    ['data-bs-toggle' => 'tooltip',
                        'title' => $t->translate('test.reset.setting.tooltip'),
                            'hidden' => !$debugMode],
                ),
                DropdownItem::link(
                    $t->translate('test.reset'),
                    $urlGenerator->generate('invoice/testDataReset'),
                    false,
                    itemAttributes: $itemFontArray +
                    ['data-bs-toggle' => 'tooltip',
                        'title' => $t->translate('test.reset.tooltip'),
                            'hidden' => !$debugMode],
                ),
                DropdownItem::link(
                    $t->translate('test.remove'),
                    $urlGenerator->generate('invoice/testDataRemove'),
                    false,
                    itemAttributes: $itemFontArray +
                    ['data-bs-toggle' => 'tooltip',
                        'title' => $t->translate('test.remove.tooltip'),
                            'hidden' => !$debugMode],
                ),
            ),
            // Performance
            Dropdown::widget()
            ->addAttributes([
                'class' => $read_write ? 'inv-warn' : 'inv-ok',
                'data-bs-toggle' => 'tooltip',
                'title' => $read_write ? $t->translate(
                                                'performance.label.switch.on')
                                       : $t->translate(
                                               'performance.label.switch.off'),
                'hidden' => !$debugMode,
            ])
            ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
                'color' => '#20c997',
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent($t->translate('performance'))
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::text(
                    $t->translate('platform.xdebug')
                        . ' ' . $xdebug,
                        [
                            'data-bs-toggle' => 'tooltip',
                            'title' => 'Via Wampserver Menu: Icon..Php 8.1.8'
                            . '-->Php extensions-->xdebug 3.1.5(click)'
                            . '-->Allow php command prompt to restart automatically'
                            . '-->(click)Restart All Services'
                            . '-->No typing in or editing of a php.ini file!!'
                        ],
                    itemAttributes: $itemFontArray,
                ),
                DropdownItem::text(
                    '...config/common/params.php SyncTable currently'
                    . ' not commented out'
                    . ' and PhpFileSchemaProvider::MODE_READ_AND_WRITE...fast....'
                    . 'MODE_WRITE_ONLY...slower',
                    itemAttributes: $itemFontArray),
                DropdownItem::divider(),
                DropdownItem::text(
                    'Non-CLI/Non-FCGI: Manually Edit'
                    . ' c:\wamp64\bin\apache\apache{version}\bin'
                    . ' php.ini then '
                    . '... Wampserver Icon ... Restart All Services',
                    itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'php.ini (line 425): max_execution_time (pref 400) = '
                    . ((string) ini_get('max_execution_time') ?: 'unknown')
                    . (((string) ini_get('max_execution_time')  == 400 ?
                    '✅' : '❌')), itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'php.ini: (line 1788): opcache.jit (pref see nothing) = '
                    . ((string) ini_get('opcache.jit') ?: 'unknown')
                    . (((string) ini_get('opcache.jit')  == '' ?
                    '✅' : '❌')), itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'php.ini: (line 1791): opcache.enable (pref 1) = '
                    . ((string) ini_get('opcache.enable') ?: 'unknown')
                    . (((string) ini_get('opcache.enable')  == 1 ?
                    '✅' : '❌')), itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'php.ini (line 1794): opcache.enable_cli (pref 1) = '
                    . ((string) ini_get('opcache.enable_cli') ?: 'unknown')
                    . (((string) ini_get('opcache.enable_cli') == 1 ?
                    '✅' : '❌')), itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'php.ini (line 1797): opcache.memory_consumption (pref 128) = '
                    . ((string) ini_get('opcache.memory_consumption') ?:
                            'unknown')
                    . (((string) ini_get('opcache.memory_consumption')
                            == 128 ?
                    '✅' : '❌')), itemAttributes: $itemFontArray +
                    ['data-bs-toggle' => 'tooltip',
                            'title' =>
                    'e.g. change manually in '
                    . 'C:\wamp64\bin\php\php8.1.13\phpForApache.ini'
                    . ' and restart all services.']),
                DropdownItem::text(
                    'php.ini (line 1800): opcache.interned_strings_buffer'
                    . ' (pref 64 for frameworks) = '
                    . ((string) ini_get('opcache.interned_strings_buffer')
                            ?: 'unknown')
                    . (((string) ini_get('opcache.interned_strings_buffer')
                            == 64 ? '✅' : '❌')),
                    itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'php.ini (line 1804): opcache.max_accelerated_files'
                    . ' (pref 10000) = '
                    . ((string) ini_get('opcache.max_accelerated_files')
                    ?: 'unknown')
                    . (((string) ini_get('opcache.max_accelerated_files')
                            == 10000 ? '✅' : '❌')),
                    itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'php.ini: (line 1818): opcache.validate_timestamps'
                    . ' (pref 0 for production'
                    . ' and 1 for development i.e. files checked on change) = '
                    . ((string) ini_get('opcache.validate_timestamps')
                        == 1 ? '1' : 'unknown')
                    . (((string) ini_get('opcache.validate_timestamps')
                        == 1 ? '✅' : '❌')),
                    itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'php.ini: (line 1822): opcache.revalidate_freq'
                    . ' (production: check'
                    . ' for changes every 60 sec, development:'
                    . ' 0 immediate updates) = '
                    . ((string) ini_get('opcache.revalidate_freq')
                            == 0 ? '0' : 'unknown')
                    . (((string) ini_get('opcache.revalidate_freq')
                            == 0 ? '✅' : '❌')),
                        itemAttributes: $itemFontArray),
                DropdownItem::divider(),
                    // https://tideways.com/profiler/blog/
                    // fine-tune-your-opcache-configuration-to
                    // -avoid-caching-suprises
                DropdownItem::text(PerformanceMetrics::opCacheHealthCheck(),
                        itemAttributes: $itemFontArray),
                DropdownItem::divider(),
                DropdownItem::link('Downloaded and loaded php extension for APCu '
                    . (extension_loaded('apcu') ? '✅' : '❌'),
                    'https://pecl.php.net/package/APCu/5.1.28/windows',
                        itemAttributes: $itemFontArray),
                DropdownItem::divider(),
                DropdownItem::text(
                    'Left Click Wampserver Icon... Php ... Php Settings'
                    . ' ... Memory Limit',
                    itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'php.ini (line 451): memory_limit (pref 1024 M) = '
                    . ((string) ini_get('memory_limit') ?: 'unknown')
                    . (((string) ini_get('memory_limit') == '1024M' ? '✅' : '❌')),
                    itemAttributes: $itemFontArray),
                DropdownItem::divider(),
                DropdownItem::text(
                    '.env: BUILD_DATABASE= (pref see nothing) = '
                    . ($buildDatabase ?
                    'You have built the database using'
                    . ' BUILD_DATABASE=true, now assign the '
                    . ' environment varirable to nothing i.e. BUILD_DATABASE=' :
                        '✅'),
                    itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'config.params: yiisoft/yii-debug: enabled ,'
                    . ' disable for improved performance',
                    itemAttributes: $itemFontArray),
                DropdownItem::text(
                    'config.params: yiisoft/yii-debug-api: enabled,'
                    . ' disable for improved performance',
                    itemAttributes: $itemFontArray),
                DropdownItem::divider(),
                // Prometheus Monitoring Section
                DropdownItem::text($subMenu->generate('Prometheus Monitoring',
                    $urlGenerator, $subMenuPrometheus,
                    $bootstrap5LayoutInvoiceNavbarFont,
                    $bootstrap5LayoutInvoiceNavbarFontSize)),
            ),
            // Platform
            Dropdown::widget()
            ->addAttributes([
                'hidden' => !$debugMode,
            ])
            ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
                'color' => '#adb5bd',
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent($t->translate('platform'))
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::text('WAMP'),
                DropdownItem::text(
                    $t->translate('platform.editor')
                    . ': Apache Netbeans IDE 28 64 bit',
                    itemAttributes: $itemFontArray),
                DropdownItem::text($t->translate('platform.server')
                    . ': Wampserver 3.4.0 64 bit',
                    itemAttributes: $itemFontArray),
                DropdownItem::text('Apache: 2.4.65 64 bit',
                    itemAttributes: $itemFontArray),
                DropdownItem::text($t->translate('platform.mySqlVersion')
                    . ': 9.1.0 ',
                    itemAttributes: $itemFontArray),
                DropdownItem::text($t->translate('platform.windowsVersion')
                    . ': Windows 11 Pro Edition',
                    itemAttributes: $itemFontArray),
                DropdownItem::text($t->translate('platform.PhpVersion')
                    . ' ' . PHP_VERSION,
                    itemAttributes: $itemFontArray),
                DropdownItem::link($t->translate('platform.PhpSupport'),
                    'https://php.net/supported-versions',
                    itemAttributes: $itemFontArray),
                DropdownItem::link($t->translate('Psalm\'s Daniil Gentilli\'s Blog'),
                    'https://blog.daniil.it/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link($t->translate('platform.update'),
                    'https://wampserver.aviatechno.net/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Testing temporary signup emails',
                    'https://guerrillamail.com/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Email forwarding instead of a mailserver',
                    'https://improvmx.com/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Packages Microsoft Com',
                    'https://packages.microsoft.com/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link(
                    'Microsoft Typescript-Go Development Site for Typescript'
                    . ' Version 7 (10x faster):'
                    . ' Superceding Typescript 5.95',
                    'https://github.com/microsoft/typescript-go',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('SonarLint4NetbeansPlugin',
                    'https://plugins.netbeans.apache.org/catalogue/?id=21',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Eclipse IDE for Php',
                    'https://www.eclipse.org/downloads/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Windows Installer Netbeans 28',
                    'https://installers.friendsofapachenetbeans.org/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Bootstrap 5 Icons with Filter',
                    'https://icons.getbootstrap.com/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('BootstrapBrain Free Wavelight Template',
                    'https://bootstrapbrain.com/template/'
                    . 'free-bootstrap-5-multipurpose-one-page-template-wave/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Html to Markdown',
                    'https://convertsimple.com/convert-html-to-markdown/',
                    itemAttributes: $itemFontArray),
                DropdownItem::divider(),
                DropdownItem::link('HMRC Developer Hub',
                    'https://developer.service.hmrc.gov.uk/developer/login',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('E-Invoicing UK Compulsory from April 2029',
                    'https://www.gov.uk/government/consultations/'
                    . 'promoting-electronic-invoicing-across-uk-businesses'
                    . '-and-the-public-sector/'
                    . 'outcome/promoting-electronic-invoicing'
                    . '-across-uk-businesses-and-the'
                    . '-public-sector-consultation-response',
                    itemAttributes: $itemFontArray),
                DropdownItem::divider(),
                DropdownItem::link('Cycle/orm HasOne Relation: Using the'
                    . ' outerKey explicitly to avoid auto inserted'
                    . ' CamelCase Foreign Keys',
                    'https://cycle-orm.dev/docs/relation-has-one/'
                    . 'current/en#differences-from-belongsto',
                    itemAttributes: $itemFontArray),
                DropdownItem::divider(),
                DropdownItem::link('German, and Swiss Law Amendments now'
                    . ' prioritize Opensource in Public Sector',
                    'https://interoperable-europe.ec.europa.eu/collection/'
                    . 'open-source-observatory-osor/'
                    . 'news/'
                    . 'germanys-ozg-20-favors-open-source-solutions',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('StoreCove Whitepapers',
                    'https://www.storecove.com/us/en/whitepapers',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Jsonld  Playground for flattening Jsonld files',
                    'https://json-ld.org/playground/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Converting flattened file to php array',
                    'https://wtools.io/convert-json-to-php-array',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Using ngrok and Wampserver VirtualHosts',
                    'https://ngrok.com/docs/using-ngrok-with/virtualHosts/',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Using ngrok and webhook testing',
                    'https://ngrok.com/use-cases/webhook-testing',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Google Oauth2 Playground',
                    'https://developers.google.com/oauthplayground',
                    itemAttributes: $itemFontArray),
                DropdownItem::link('Google Oauth2 Web Application',
                    'https://console.cloud.google.com/apis/credentials/oauthclient',
                    itemAttributes: $itemFontArray),
            ),
            // Php Watch
            Dropdown::widget()
            ->addAttributes([
                'class' => 'inv-php-menu',
                'hidden' => !$debugMode,
            ])
            ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent('🐘')
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::link('8.3',
                    'https://php.watch/versions/8.3',
                    $debugMode, false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('8.4',
                    'https://php.watch/versions/8.4',
                    $debugMode, false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('8.5',
                    'https://php.watch/versions/8.5',
                    $debugMode, false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
            ),
            // Emojipedia.org
            Dropdown::widget()
            ->addAttributes([
                'class' => 'inv-emoji-menu',
                'hidden' => !$debugMode,
            ])
            ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
            ])
            ->togglerVariant(ButtonVariant::INFO)
            ->togglerContent('😀')
            ->togglerSize(ButtonSize::LARGE)
            ->items(
                DropdownItem::link('✅', 
                    'https://emojipedia.org/check-mark-button',
                    $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('➕', 
                    'https://emojipedia.org/plus', $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('❌', 
                    'https://emojipedia.org/cross-mark', $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('🛈', 
                    'https://emojipedia.org/'
                    . 'circled-information-source', $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('⬅', 
                    'https://emojipedia.org/left-arrow', $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('➡', 
                    'https://emojipedia.org/right-arrow', $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('↔️', 
                    'https://emojipedia.org/left-right-arrow', $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('🖉', 
                    'https://emojipedia.org/lower-left-pencil', $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('🔘', 
                    'https://emojipedia.org/radio-button', $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('☑️', 
                    'https://emojipedia.org/check-box-with-check', $debugMode,
                    false, itemAttributes: $itemFontArray +
                        ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('🐘', 
                    'https://emojipedia.org/elephant', $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
                DropdownItem::link('⚙️', 
                    'https://emojipedia.org/gear', $debugMode,
                    false, itemAttributes: $itemFontArray +
                    ['style' => 'background-color: #ffcccb']),
            ),
        );
    }

    echo Nav::widget()
    ->class('navbar inv-nav')
    ->items(
        // Translate
        Dropdown::widget()
        ->addClass('navbar')
        ->addAttributes([
            'class' => 'inv-cornflower',
            'data-bs-toggle' => 'tooltip',
            'title' => $t->translate('language'),
            'url' => '#',
        ])
        ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
                'color' => '#0dcaf0',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent($currentLocaleFlag . ' ' . new I()->class('bi bi-translate'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
// Related logic: config/web/params, src/ViewInjection/LayoutViewInjection
            $afZA, $arBH, $az, $beBY, $bs, $zhCN, $zhTW, $en,
            $fil, $fr, $gdGB, $haNG, $heIL, $igNG, $nl, $de,
            $id, $it, $ja, $pl, $ptBR,
            $ru, $sk, $sl, $es, $uk, $uz, $vi, $yoNG, $zuZA
        ),
        NavLink::to(
            //label
             new I()->class('bi bi-speedometer'),
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
        ->addClass('navbar')
        ->addTogglerCssStyle([
            'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
            'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
            'color' => '#adb5bd',
        ])
        ->togglerVariant(ButtonVariant::SECONDARY)
        ->togglerContent( new I()->addClass('bi bi-gear'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('debug') . ': ' . $t->translate('view'),
                $urlGenerator->generate('setting/debugIndex'),
                    false, !$debugMode,
                        $itemFontArray + ['style' => 'background-color: #ffcccb; font-size: ' . $bootstrap5LayoutInvoiceNavbarFontSize . 'px;',
                         'hidden' => !$debugMode]),
            DropdownItem::link($t->translate('setting.add'),
                $urlGenerator->generate('setting/add'),
                    false, !$debugMode,
                        $itemFontArray + ['style' => 'background-color: #ffcccb; font-size: ' . $bootstrap5LayoutInvoiceNavbarFontSize . 'px;',
                         'hidden' => !$debugMode]),
            DropdownItem::link($t->translate('caution.delete.invoices'),
                $urlGenerator->generate('inv/flush'),
                    false, !$debugMode,
                        $itemFontArray + ['style' => 'background-color: #ffcccb; font-size: ' . $bootstrap5LayoutInvoiceNavbarFontSize . 'px;',
                         'hidden' => !$debugMode]),
            DropdownItem::link($t->translate('view'),
                $urlGenerator->generate('setting/tabIndex'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate((
                ($s->getSetting('install_test_data') == '1')
                && ($s->getSetting('use_test_data') == '1'))
                ? 'install.test.data' : 'install.test.data.goto.tab.index'),
                    (($s->getSetting('install_test_data') == '1'
                        && $s->getSetting('use_test_data') == '1')
                ? $urlGenerator->generate('invoice/index') :
                $urlGenerator->generate('setting/tabIndex')),
                    ($s->getSetting('install_test_data') == '1'
                        && $s->getSetting('use_test_data') == '1'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('email.template'),
                $urlGenerator->generate('emailtemplate/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('email.from.dropdown'),
                $urlGenerator->generate('from/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('email.log'),
                $urlGenerator->generate('invsentlog/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('custom.fields'),
                $urlGenerator->generate('customfield/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('group'),
                $urlGenerator->generate('group/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('archive'),
                $urlGenerator->generate('inv/archive'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('payment.method'),
                $urlGenerator->generate('paymentmethod/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('tax.rate'),
                $urlGenerator->generate('taxrate/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('contract'),
                $urlGenerator->generate('contract/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('user.account'),
                $urlGenerator->generate('userinv/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('password.change'),
                $urlGenerator->generate('auth/change'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('user.api.list'),
                $urlGenerator->generate('user/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('setting.company'),
                $urlGenerator->generate('company/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('setting.company.private'),
                $urlGenerator->generate('companyprivate/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('setting.company.profile'),
                $urlGenerator->generate('profile/index'),
                itemAttributes: $itemFontArray),
        ),
        // peppol
        Dropdown::widget()
        ->addClass('navbar')
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
            'url' => '#',
        ])
        ->addTogglerCssStyle([
            'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
            'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
            'color' => '#ffa500',
        ])
        ->togglerVariant(ButtonVariant::WARNING)
        ->togglerContent($t->translate('peppol.abbreviation'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('allowance.or.charge.add'),
                $urlGenerator->generate('allowancecharge/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('peppol.store.cove.1.1.1'),
                'https://www.storecove.com/register/',
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('peppol.store.cove.1.1.2'),
                $urlGenerator->generate('setting/tabIndex'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('peppol.store.cove.1.1.3'),
                $urlGenerator->generate('invoice/storeCoveCallApi'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('peppol.store.cove.1.1.4'),
                $urlGenerator->generate('invoice/storeCoveSendTestJsonInvoice'),
                itemAttributes: $itemFontArray
            ),
        ),
        // Client
        Dropdown::widget()
        ->addClass('navbar')
        ->addTogglerCssStyle([
            'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
            'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
            'color' => '#198754',
        ])
        ->togglerVariant(ButtonVariant::SUCCESS)
        ->togglerContent( new I()->addClass('bi bi-people'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('client.add'),
                $urlGenerator->generate('client/add', ['origin' => 'main']),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('client.view'),
                $urlGenerator->generate('client/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('client.note.add'),
                $urlGenerator->generate('clientnote/add'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('client.note.view'),
                $urlGenerator->generate('clientnote/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('delivery.location'),
                $urlGenerator->generate('del/index'),
                itemAttributes: $itemFontArray),
        ),
        // Quote
        Dropdown::widget()
        ->addClass('navbar inv-cornflower')
        ->addTogglerCssStyle([
            'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
            'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
            'color' => '#0dcaf0',
        ])
        ->togglerVariant(ButtonVariant::INFO)
        ->togglerContent(new I()->addClass('bi bi-chat-square-text') . ' ' . $t->translate('quote'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('create.quote'),
                $urlGenerator->generate('quote/add', ['origin' => 'main']),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('view'),
                $urlGenerator->generate('quote/index'),
                itemAttributes: $itemFontArray),
        ),
        // SalesOrder
        Dropdown::widget()
        ->addClass('navbar inv-cornflower')
        ->addTogglerCssStyle([
            'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
            'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
            'color' => '#0d6efd',
        ])
        ->togglerVariant(ButtonVariant::PRIMARY)
        ->togglerContent(new I()->addClass('bi bi-bag') . ' ' . $t->translate('salesorder'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('view'),
                $urlGenerator->generate('salesorder/index'),
                itemAttributes: $itemFontArray),
        ),
        // Invoice
        Dropdown::widget()
        ->addClass('navbar inv-cornflower')
        ->addTogglerCssStyle([
            'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
            'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
            'color' => '#dc3545',
        ])
        ->togglerVariant(ButtonVariant::DANGER)
        ->togglerContent(new I()->addClass('bi bi-file-text') . ' ' . $t->translate('invoice'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('create.invoice'),
                $urlGenerator->generate('inv/add', ['origin' => 'main']),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('view'),
                $urlGenerator->generate('inv/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('recurring'),
                $urlGenerator->generate('invrecurring/index'),
                itemAttributes: $itemFontArray),
        ),
        // Payment
        Dropdown::widget()
        ->addClass('navbar inv-cornflower')
        ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
                'color' => '#20c997',
        ])
        ->togglerVariant(ButtonVariant::SUCCESS)
        ->togglerContent( new I()->addClass('bi bi-coin'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('enter.payment'),
                $urlGenerator->generate('payment/add'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('view'),
                $urlGenerator->generate('payment/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('payment.logs'),
                $urlGenerator->generate('payment/onlineLog'),
                itemAttributes: $itemFontArray),
        ),
        // Product
        Dropdown::widget()
        ->addClass('navbar inv-cornflower')
        ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
                'color' => '#fd7e14',
        ])
        ->togglerVariant(ButtonVariant::WARNING)
        ->togglerContent(new I()->addClass('bi bi-box-seam') . ' ' . $t->translate('product'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('add.product'),
                $urlGenerator->generate('product/add'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('view'),
                $urlGenerator->generate('product/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('category.primary'),
                $urlGenerator->generate('categoryprimary/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('category.secondary'),
                $urlGenerator->generate('categorysecondary/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('family'),
                $urlGenerator->generate('family/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('family.search'),
                $urlGenerator->generate('family/search'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('faq'),
                $urlGenerator->generate('qa/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('unit'),
                $urlGenerator->generate('unit/index'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('peppol.unit'),
                $urlGenerator->generate('unitpeppol/index'),
                itemAttributes: $itemFontArray),
        ),
        Dropdown::widget()
        ->addClass('navbar inv-cornflower')
        ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
                'color' => '#6f42c1',
            ])
        ->togglerVariant(ButtonVariant::SECONDARY)
        ->togglerContent(new I()->addClass('bi bi-check2-square'). ' ' . $t->translate('tasks'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('add.task'),
                $urlGenerator->generate('task/add'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('view'),
                $urlGenerator->generate('task/index'),
                itemAttributes: $itemFontArray),
        ),
        // Projects
        Dropdown::widget()
        ->addClass('navbar inv-cornflower')
        ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
                'color' => '#6610f2',
            ])
        ->togglerVariant(ButtonVariant::PRIMARY)
        ->togglerContent(new I()->addClass('bi bi-kanban'). ' ' . $t->translate('projects'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('create.project'),
                $urlGenerator->generate('project/add'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('view'),
                $urlGenerator->generate('project/index'),
                itemAttributes: $itemFontArray),
        ),
        // Reports
        Dropdown::widget()
        ->addClass('navbar inv-cornflower')
        ->addTogglerCssStyle([
                'font-size' => $bootstrap5LayoutInvoiceNavbarFontSize . 'px',
                'font-family' => $bootstrap5LayoutInvoiceNavbarFont,
                'color' => '#343a40',
        ])
        ->togglerVariant(ButtonVariant::DARK)
        ->togglerContent(new I()->addClass('bi bi-bar-chart-line') . ' ' . $t->translate('reports'))
        ->togglerSize(ButtonSize::LARGE)
        ->items(
            DropdownItem::link($t->translate('sales.by.client'),
                $urlGenerator->generate('report/salesByClientIndex'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('report.sales.by.product'),
                $urlGenerator->generate('report/salesByProductIndex'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('report.sales.by.task'),
                $urlGenerator->generate('report/salesByTaskIndex'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('sales.by.date'),
                $urlGenerator->generate('report/salesByYearIndex'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('payment.history'),
                $urlGenerator->generate('report/paymentHistoryIndex'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate('aging'),
                $urlGenerator->generate('report/invoiceAgingIndex'),
                itemAttributes: $itemFontArray),
            DropdownItem::link($t->translate(
                    'report.test.fraud.prevention.headers.api'),
                $urlGenerator->generate('backend/hmrc/fphValidate'),
                itemAttributes: $itemFontArray),
        ),
    )
    ->styles(NavStyle::NAVBAR);
} //null!== currentPath && !isGuest
echo NavBar::end();
echo $bootstrap5OffcanvasEnable ? Offcanvas::end() : '';
echo Html::openTag('div', ['id' => 'main-area']);
 // Display the sidebar if enabled
 if ($s->getSetting('disable_sidebar') !== (string) 1) {
  include dirname(__DIR__) . '/invoice/layout/sidebar.php';
 }
 echo Html::openTag('main', ['class' => 'container-fluid py-4']); //1
  echo $content;
 echo Html::closeTag('main'); //1
 echo Html::openTag('div', [
  'id' => 'fullpage-loader',
  'style' => 'display: none',
 ]); //1
  echo Html::openTag('div', ['class' => 'loader-content']); //2
   echo Html::openTag('div', [
    'id' => 'loader-icon',
    'class' => 'spinner-border text-primary',
    'role' => 'status',
   ]);
    echo Html::tag('span', 'Loading...', ['class' => 'visually-hidden']);
   echo Html::closeTag('div');
  echo Html::closeTag('div'); //2
 echo Html::closeTag('div'); //1
echo Html::closeTag('div');
echo Html::openTag('footer', ['class' => 'container py-4']);
 echo PerformanceMetrics::widget();
echo Html::closeTag('footer');
echo Html::script('NProgress.done();')->type('module');
$this->endBody();
echo Html::closeTag('body');
echo Html::closeTag('html');
$this->endPage();