<?php
declare(strict_types=1);

use App\Invoice\Asset\InvoiceAsset;
use App\Invoice\Asset\MonospaceAsset;
// DatePicker Assets available for dropdown locale/cldr selection
use App\Invoice\Asset\i18nAsset\af_Asset;
use App\Invoice\Asset\i18nAsset\ar_Asset;
use App\Invoice\Asset\i18nAsset\az_Asset;
use App\Invoice\Asset\i18nAsset\de_DE_Asset;
use App\Invoice\Asset\i18nAsset\en_GB_Asset;
use App\Invoice\Asset\i18nAsset\es_ES_Asset;
use App\Invoice\Asset\i18nAsset\fr_FR_Asset;
use App\Invoice\Asset\i18nAsset\id_Asset;
use App\Invoice\Asset\i18nAsset\ja_Asset;
use App\Invoice\Asset\i18nAsset\nl_Asset;
use App\Invoice\Asset\i18nAsset\ru_Asset;
use App\Invoice\Asset\i18nAsset\sk_Asset;
use App\Invoice\Asset\i18nAsset\uk_UA_Asset;
use App\Invoice\Asset\i18nAsset\uz_UZ_Asset;
use App\Invoice\Asset\i18nAsset\zh_CN_Asset;
// PCI Compliant Payment Gateway Assets
use App\Invoice\Asset\pciAsset\stripe_v10_Asset;
use App\Invoice\Asset\pciAsset\amazon_pay_v2_4_Asset;
use App\Invoice\Asset\pciAsset\braintree_dropin_1_33_7_Asset;
use App\Asset\AppAsset;
use App\Widget\PerformanceMetrics;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Yii\Bootstrap5\Nav;
use Yiisoft\Yii\Bootstrap5\NavBar;
use Yiisoft\Yii\Bootstrap5\Offcanvas;

/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Router\CurrentRoute $currentRoute
 * @var \Yiisoft\View\WebView $this
 * @var \Yiisoft\Assets\AssetManager $assetManager
 * @var \Yiisoft\Config\Config $config
 * @var \Yiisoft\Config\ConfigPaths $configPaths
 * @var \App\Invoice\Setting\SettingRepository $s
 * @var \App\Invoice\Helpers\DateHelper $datehelper
 * @see \App\ApplicationViewInjection
 * @var \App\User\User|null $user
 * @var string $csrf
 * @var string $content
 * @var string $brandLabel
 */
$assetManager->register(AppAsset::class);
$assetManager->register(InvoiceAsset::class);
$assetManager->register(Yiisoft\Yii\Bootstrap5\Assets\BootstrapAsset::class);
$s->get_setting('monospace_amounts') == 1 ? $assetManager->register(MonospaceAsset::class) : '';
// '0' => PCI Compliant version
$s->get_setting('gateway_stripe_version') == '0' ? $assetManager->register(stripe_v10_Asset::class) : '';
$s->get_setting('gateway_amazon_pay_version') == '0' ? $assetManager->register(amazon_pay_v2_4_Asset::class) : '';
$s->get_setting('gateway_braintree_version') == '0' ? $assetManager->register(braintree_dropin_1_33_7_Asset::class) : '';
$vat = ($s->get_setting('enable_vat_registration') == '0');
// The InvoiceController/index receives the $session->get('_language') or 'drop-down' locale user selection and saves it into a setting called 'cldr'
// The $s value is configured for the layout in config/common/params.php yii-soft/view Reference::to and NOT by means of the InvoiceController
// NOTE: $locale must correspond with SettingRepository/locale_language_array and
// ALSO: src/Invoice/Language/{folder_name}
switch ($session->get('_language') ?? $session->set('_language', $currentRoute->getArgument('_language'))) {
  case 'af' : $assetManager->register(af_Asset::class);
    $locale = 'Afrikaans';
    break;
  case 'ar' : $assetManager->register(ar_Asset::class);
    $locale = 'Arabic';
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
  case 'fr' : $assetManager->register(fr_FR_Asset::class);
    $locale = 'French';
    break;
  case 'id' : $assetManager->register(id_Asset::class);
    $locale = 'Indonesian';
    break;
  case 'ja' : $assetManager->register(ja_Asset::class);
    $locale = 'Japanese';
    break;
  case 'nl' : $assetManager->register(nl_Asset::class);
    $locale = 'Dutch';
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
  case 'zh' : $assetManager->register(zh_CN_Asset::class);
    $locale = 'Chinese Simplified';
    break;
  default : $assetManager->register(en_GB_Asset::class);
    $locale = 'English';
    break;
}

// If the dropdown locale has not been set on login => use the cldr setting value. If the cldr does not exist => use the 'en' value
$s->save_session_locale_to_cldr($session->get('_language') ?? ($s->get_setting('cldr') ?: 'en'));

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());

$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$currentRouteName = $currentRoute->getName() ?? '';

$isGuest = $user === null || $user->getId() === null;

$xdebug = extension_loaded('xdebug') ? 'php.ini zend_extension Installed : Performance compromised!' : 'php.ini zend_extension Commented out: Performance NOT compromised';

// Platform, Performance, and Clear Assets Cache, and links Menu will disappear if set to false;
$debug_mode = true;
$s->debug_mode($debug_mode);

// 0 => fast Read and Write, // 1 => slower Write Only
$read_write = $s->getSchemaProvidersMode();

$this->beginPage();
?>
<!DOCTYPE html>
<html class="h-100" lang="<?= $s->get_setting('cldr') !== $session->get('_language') ? $session->get('_language') : $s->get_setting('cldr'); ?>">
    <head>
        <?= Meta::documentEncoding('utf-8') ?>
        <?= Meta::pragmaDirective('X-UA-Compatible', 'IE=edge') ?>
        <?= Meta::data('viewport', 'width=device-width, initial-scale=1') ?>
        <title>
            <?= $s->get_setting('custom_title') ?: 'Yii-Invoice'; ?>
        </title>
        <?php $this->head() ?>
    </head>
    <body>
        <?php
        Html::tag('Noscript', Html::tag('Div', $s->trans('please_enable_js'), ['class' => 'alert alert-danger no-margin']));
        ?>
        <?php
        $this->beginBody();

        $offcanvas = new Offcanvas();
        $offcanvas->title($s->get_setting('custom_title') ?: 'Yii-Invoice');

        echo NavBar::widget()
          // public folder represented by first forward slash ie. root
          ->brandImage('/site/favicon.ico')
          ->brandImageAttributes(['width' => 40, 'height' => 20])
          ->brandUrl($urlGenerator->generate('invoice/index'))
          ->withWidget(
            // If not full screen => 'burger icon ie. 3 horizontal lines' represents menu and
            // navbar moves in from left
            $offcanvas
          )
          ->begin();

        echo Nav::widget()
          ->currentPath($currentRoute->getUri()->getPath())
          ->options(['class' => 'navbar fs-4'])
          ->items(
            $isGuest ? [] :
              [
              ['label' => $s->trans('dashboard'), 'url' => $urlGenerator->generate('invoice/dashboard')],
              ['label' => $translator->translate('invoice.peppol.abbreviation'),
                'items' => [
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.invoice.allowance.or.charge.add'), 'url' => $urlGenerator->generate('allowancecharge/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.peppol.store.cove.1.1.1'), 'url' => 'https://www.storecove.com/register/'],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.peppol.store.cove.1.1.2'), 'url' => $urlGenerator->generate('setting/tab_index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.peppol.store.cove.1.1.3'), 'url' => $urlGenerator->generate('invoice/store_cove_call_api')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.peppol.store.cove.1.1.4'), 'url' => $urlGenerator->generate('invoice/store_cove_send_test_json_invoice')],
                ],
              ],
              ['label' => $s->trans('client'),
                'items' => [
                  ['options' => ['class' => 'nav fs-4 ajax-loader'], 'label' => $s->trans('view'), 'url' => $urlGenerator->generate('client/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.client.note.add'), 'url' => $urlGenerator->generate('clientnote/add')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.invoice.delivery.location'), 'url' => $urlGenerator->generate('del/index')],
                ],
              ],
              ['label' => $s->trans('quote'),
                'items' => [
                  ['options' => ['class' => 'nav fs-4 ajax-loader'], 'label' => $s->trans('view'), 'url' => $urlGenerator->generate('quote/index')],
                ],
              ],
              ['label' => $translator->translate('invoice.salesorder'),
                'items' => [
                  ['options' => ['class' => 'nav fs-4 ajax-loader'], 'label' => $s->trans('view'), 'url' => $urlGenerator->generate('salesorder/index')],
                ],
              ],
              ['label' => $s->trans('invoice'),
                'items' => [
                  ['options' => ['class' => 'nav fs-4 ajax-loader'], 'label' => $s->trans('view'), 'url' => $urlGenerator->generate('inv/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('recurring'), 'url' => $urlGenerator->generate('invrecurring/index')],
                ],
              ],
              ['label' => $s->trans('payment'),
                'items' => [
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('enter_payment'), 'url' => $urlGenerator->generate('payment/add')],
                  ['options' => ['class' => 'nav fs-4 ajax-loader'], 'label' => $s->trans('view'), 'url' => $urlGenerator->generate('payment/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('payment_logs'), 'url' => $urlGenerator->generate('payment/online_log')]
                ],
              ],
              ['label' => $s->trans('product'),
                'items' => [
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('create'), 'url' => $urlGenerator->generate('product/add')],
                  ['options' => ['class' => 'nav fs-4 ajax-loader'], 'label' => $s->trans('view'), 'url' => $urlGenerator->generate('product/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('family'), 'url' => $urlGenerator->generate('family/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('unit'), 'url' => $urlGenerator->generate('unit/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.peppol.unit'), 'url' => $urlGenerator->generate('unitpeppol/index')],
                  
                ],
              ],
              ['label' => $s->trans('tasks'),
                'items' => [
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('create'), 'url' => $urlGenerator->generate('task/add')],
                  ['options' => ['class' => 'nav fs-4 ajax-loader'], 'label' => $s->trans('view'), 'url' => $urlGenerator->generate('task/index')],
                ],
              ],
              ['label' => $s->trans('projects'),
                'items' => [
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('create'), 'url' => $urlGenerator->generate('project/add')],
                  ['options' => ['class' => 'nav fs-4 ajax-loader'], 'label' => $s->trans('view'), 'url' => $urlGenerator->generate('project/index')],
                ],
              ],
              ['label' => $s->trans('reports'),
                'items' => [
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('sales_by_client'), 'url' => $urlGenerator->generate('report/sales_by_client_index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.report.sales.by.product'), 'url' => $urlGenerator->generate('report/sales_by_product_index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.report.sales.by.task'), 'url' => $urlGenerator->generate('report/sales_by_task_index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('sales_by_date'), 'url' => $urlGenerator->generate('report/sales_by_year_index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('payment_history'), 'url' => $urlGenerator->generate('report/payment_history_index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('invoice_aging'), 'url' => $urlGenerator->generate('report/invoice_aging_index')],
                ],
              ],
              ['label' => $s->trans('settings'),
                'items' => [['options' => ['class' => 'nav fs-4 ajax-loader'], 'label' => $s->trans('view'), 'options' => ['style' => 'background-color: #ffcccb'], 'url' => $urlGenerator->generate('setting/debug_index'), 'visible' => $debug_mode],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('add'), 'options' => ['style' => 'background-color: #ffcccb'], 'url' => $urlGenerator->generate('setting/add'), 'visible' => $debug_mode],
                  ['options' => ['class' => 'nav fs-4 ajax-loader'], 'label' => $s->trans('view'), 'url' => $urlGenerator->generate('setting/tab_index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate((($s->get_setting('install_test_data') == '1') && ($s->get_setting('use_test_data') == '1')) 
                                                                   ? 'invoice.install.test.data' : 'invoice.install.test.data.goto.tab.index'), 
                                                          'url' =>  (($s->get_setting('install_test_data') == '1' && $s->get_setting('use_test_data') == '1') 
                                                                   ? $urlGenerator->generate('invoice/index') : $urlGenerator->generate('setting/tab_index')),
                                                          'visible' => ($s->get_setting('install_test_data') == '1' && $s->get_setting('use_test_data') == '1')],         
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('email_template'), 'url' => $urlGenerator->generate('emailtemplate/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.email.from.dropdown'), 'url' => $urlGenerator->generate('from/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('custom_fields'), 'url' => $urlGenerator->generate('customfield/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('invoice_group'), 'url' => $urlGenerator->generate('group/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('invoice_archive'), 'url' => $urlGenerator->generate('inv/archive')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('payment_method'), 'url' => $urlGenerator->generate('paymentmethod/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $s->trans('invoice_tax_rate'), 'url' => $urlGenerator->generate('taxrate/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.invoice.contract'), 'url' => $urlGenerator->generate('contract/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.user.account'), 'url' => $urlGenerator->generate('userinv/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('reset'), 'url' => $urlGenerator->generate('auth/reset')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.user.api.list'), 'url' => $urlGenerator->generate('user/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.setting.company'), 'url' => $urlGenerator->generate('company/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.setting.company.private'), 'url' => $urlGenerator->generate('companyprivate/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.setting.company.profile'), 'url' => $urlGenerator->generate('profile/index')],
                ],
              ],
              ['label' => $translator->translate('invoice.platform'), 'options' => ['style' => 'background-color: #ffcccb'], 'visible' => $debug_mode,
                'items' => [
                  ['label' => 'WAMP'],
                  ['label' => $translator->translate('invoice.platform.editor') . ': Apache Netbeans 12.4 64 bit'],
                  ['label' => $translator->translate('invoice.platform.server') . ': Wampserver 3.2.9 64 bit'],
                  ['label' => 'Apache: 2.4.54 64 bit'],
                  ['label' => $translator->translate('invoice.platform.mySqlVersion') . ': 5.7.31 || 8.0.30 '],
                  ['label' => $translator->translate('invoice.platform.windowsVersion') . ': Windows 10 Home Edition'],
                  ['label' => $translator->translate('invoice.platform.PhpVersion') . ': 8.1.11 (Compatable with PhpAdmin 5.2.0)'],
                  ['label' => $translator->translate('invoice.platform.PhpMyAdmin') . ': 5.2.0 (Compatable with php 8.1.12)'],
                  ['label' => $translator->translate('invoice.platform.PhpSupport'), 'url' => 'https://php.net/supported-versions'],
                  ['label' => $translator->translate('invoice.platform.update'), 'url' => 'https://wampserver.aviatechno.net/'],
                  ['label' => $translator->translate('invoice.vendor.nikic.fast-route'), 'url' => 'https://github.com/nikic/FastRoute'],
                  ['label' => $translator->translate('invoice.platform.netbeans.UTF-8'), 'url' => 'https://stackoverflow.com/questions/59800221/gradle-netbeans-howto-set-encoding-to-utf-8-in-editor-and-compiler'],
                  ['label' => $translator->translate('invoice.platform.csrf'), 'url' => 'https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#use-of-custom-request-headers'],
                  ['label' => $translator->translate('invoice.development.progress'), 'url' => $urlGenerator->generate('invoice/index')],
                  ['label' => 'Html to Markdown', 
                     'url' => 'https://convertsimple.com/convert-html-to-markdown/'],
                  ['label' => 'European Invoicing', 
                     'url' => 'https://ec.europa.eu/digital-building-blocks/wikis/display/DIGITAL/Compliance+with+eInvoicing+standard'],
                  ['label' => 'European Digital Testing', 
                     'url' => 'https://ec.europa.eu/digital-building-blocks/wikis/display/DIGITAL/eInvoicing+Conformance+Testing'],
                  ['label' => 'What does a Peppol ID look like?', 
                     'url' => 'https://ecosio.com/en/blog/how-peppol-ids-work/'],
                  ['label' => 'Peppol Accounting Requirements', 
                     'url' => 'https://docs.peppol.eu/poacc/billing/3.0/bis/#accountingreq'],
                  ['label' => ' Peppol Billing 3.0 - Syntax', 
                     // open up in a new window
                     'url' => '',
                     'linkOptions' => ['class' => 'fa fa-window-restore', 'onclick'=>"window.open('".'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/'."')"]
                  ],
                  ['label' => ' Peppol Billing 3.0 - Tree', 
                     // open up in a new window
                     'url' => '',  
                     'linkOptions' => ['class' => 'fa fa-window-restore', 'onclick'=>"window.open('".'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/tree/'."')"]
                  ],
                  ['label' => 'Universal Business Language 2.1 (UBL)', 
                     'url' => 'http://www.datypic.com/sc/ubl21/ss.html'],
                  ['label' => 'StoreCove Documentation', 
                     'url' => 'https://www.storecove.com/docs'],
                  ['label' => 'Peppol Company Search', 
                     'url' => 'https://directory.peppol.eu/public'],
                  ['label' => 'ISO 3 letter currency codes - 4217 alpha-3', 
                     'url' => 'https://www.iso.org/iso-4217-currency-codes.html'],
                  ['label' => ' Xml Example 2.1',
                     //open up in a new window
                     'url' => '',
                     'linkOptions' => ['class' => 'fa fa-window-restore', 'onclick'=>"window.open('".'https://docs.oasis-open.org/ubl/cs1-UBL-2.1/xml/UBL-Invoice-2.1-Example.xml'."')"]
                  ],
                  ['label' => 'Xml Example 3.0', 
                     'url' => 'https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/rules/examples/base-example.xml'],
                  ['label' => ' Ecosio Xml Validator',
                     //open up in a new window 
                     'url' => '', 
                     'linkOptions' =>['class' => 'fa fa-window-restore', 'onclick'=>"window.open('". 'https://ecosio.com/en/peppol-and-xml-document-validator-button/?pk_abe=EN_Peppol_XML_Validator_Page&pk_abv=With_CTA' . "')" ]
                  ],
                  ['label' => 'Xml Code Lists', 'url' => 'https://github.com/OpenPEPPOL/peppol-bis-invoice-3/tree/master/structure/codelist'],
                  ['label' => 'Convert XML to PHP Array Online', 'url' => 'https://wtools.io/convert-xml-to-php-array'],
                  ['label' => 'Writing XML using Sabre', 'url' => 'https://sabre.io/xml/writing/'],
                  ['label' => 'Understanding Same Site Cookies', 'url' => 'https://andrewlock.net/understanding-samesite-cookies/#:~:text=SameSite%3DLax%20cookies%20are%20not,Lax%20(or%20Strict%20)%20cookies'],
                  ['label' => 'Scotland - e-invoice Template - Lessons Learned', 'url' => 'https://www.gov.scot/publications/einvoicing-guide/documents/'],
                  ['label' => 'Jsonld  Playground for flattening Jsonld files', 'url' => 'https://json-ld.org/playground/'],
                  ['label' => 'Converting flattened file to php array', 'url' => 'https://wtools.io/convert-json-to-php-array'],
                  ['label' => 'jQuery UI 1.13.2', 'url' => 'https://github.com/jquery/jquery-ui'],
                  ['label' => 'LAMP'],
                  ['label' => $translator->translate('invoice.platform.editor') . ': Apache Netbeans 12.4 64 bit'],
                  ['label' => $translator->translate('invoice.platform.server') . ': Ubuntu LTS 22.04 64 bit'],
                  ['label' => 'Apache: 2.4.52 64 bit'],
                  ['label' => $translator->translate('invoice.platform.mySqlVersion') . ': 5.7.31 || 8.0.29 '],
                  ['label' => $translator->translate('invoice.platform.PhpVersion') . ': 8.2.1 (Compatable with PhpAdmin 5.2.0)'],
                  ['label' => $translator->translate('invoice.platform.PhpMyAdmin') . ': 5.2.0 (Compatable with php 8.1.2)'],
                  ['label' => $translator->translate('invoice.development.progress'), 'url' => $urlGenerator->generate('invoice/ubuntu')],
                ],
              ],
              ['label' => $translator->translate('invoice.faq'), 'options' => ['style' => 'background-color: #ffcccb'], 'visible' => $debug_mode,
                'items' => [
                  ['label' => $translator->translate('invoice.faq.taxpoint'), 'url' => $urlGenerator->generate('invoice/faq', ['topic' => 'tp'])],
                  ['label' => $translator->translate('invoice.faq.shared.hosting'), 'url' => $urlGenerator->generate('invoice/faq', ['topic' => 'shared'])],
                ]],
              ['label' => $translator->translate('invoice.vat'), 'options' => ['style' => $vat ? 'background-color: #ffcccb' : 'background-color: #90EE90'], 'visible' => $debug_mode],
              ['label' => $translator->translate('invoice.performance'), 'options' => ['style' => $read_write ? 'background-color: #ffcccb' : 'background-color: #90EE90','data-bs-toggle'=>'tooltip','title' => $read_write ? $translator->translate('invoice.performance.label.switch.on') : $translator->translate('invoice.performance.label.switch.off')], 'visible' => $debug_mode,
                'items' => [
                  ['label' => $translator->translate('invoice.platform.xdebug') . ' ' . $xdebug, 'options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip', 'title' => 'Via Wampserver Menu: Icon..Php 8.1.8-->Php extensions-->xdebug 3.1.5(click)-->Allow php command prompt to restart automatically-->(click)Restart All Services-->No typing in or editing of a php.ini file!!']],
                  ['label' => '...config/common/params.php SyncTable currently not commented out and PhpFileSchemaProvider::MODE_READ_AND_WRITE...fast....MODE_WRITE_ONLY...slower'],
                  ['label' => 'php.ini: opcache.memory_consumption=128'],
                  ['label' => 'php.ini: oopcache.interned_strings_buffer=8'],
                  ['label' => 'php.ini: opcache.max_accelerated_files=4000'],
                  ['label' => 'php.ini: opcache.revalidate_freq=60'],
                  ['label' => 'php.ini: opcache.enable=1'],
                  ['label' => 'php.ini: opcache.enable_cli=1'],
                  ['label' => 'config.params: yiisoft/yii-debug: enabled , disable for improved performance'],
                  ['label' => 'config.params: yiisoft/yii-debug-api: enabled, disable for improved performance'],
                ],
              ],
              ['label' => $translator->translate('invoice.generator'), 'options' => ['style' => 'background-color: #ffcccb'], 'visible' => $debug_mode,
                'items' => [
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.generator'), 'url' => $urlGenerator->generate('generator/index')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.generator.add'), 'url' => $urlGenerator->generate('generator/add')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.generator.relations.add'), 'url' => $urlGenerator->generate('generatorrelation/add')],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.development.schema'), 'url' => $urlGenerator->generate('generator/quick_view_schema')],
                  // Using the saved locale dropdown setting under Settings ... Views ... Google Translate, translate one of the three files located in
                  // ..resources/views/generator/templates_protected
                  // Your Json file must be located in src/Invoice/google_translate_unique folder
                  // Get your downloaded Json file from
                  ['options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip', 'title' => $s->where('google_translate_json_filename')],
                    'label' => $translator->translate('invoice.generator.google.translate.ip'), 'url' => $urlGenerator->generate('generator/google_translate_lang', ['type' => 'ip'])],
                  ['options' => ['class' => 'nav fs-4'], 'label' => $translator->translate('invoice.generator.google.translate.gateway'), 'url' => $urlGenerator->generate('generator/google_translate_lang', ['type' => 'gateway'])],
                  ['options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip', 'title' => $s->where('google_translate_en_app_php')], 'label' => $translator->translate('invoice.generator.google.translate.app'), 'url' => $urlGenerator->generate('generator/google_translate_lang', ['type' => 'app'])],
                  ['label' => $translator->translate('invoice.test.reset.setting'), 'url' => $urlGenerator->generate('invoice/setting_reset'),
                    'options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.test.reset.setting.tooltip')]],
                  ['label' => $translator->translate('invoice.test.reset'), 'url' => $urlGenerator->generate('invoice/test_data_reset'),
                    'options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.test.reset.tooltip')]],
                  ['label' => $translator->translate('invoice.test.remove'), 'url' => $urlGenerator->generate('invoice/test_data_remove'),
                    'options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.test.remove.tooltip')]]
                ],
              ],
              ['label' => $translator->translate('invoice.utility.assets.clear'),
                'url' => $urlGenerator->generate('setting/clear'), 'options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip',
                  'title' => 'Clear the assets cache which resides in /public/assets.', 'style' => 'background-color: #ffcccb'],
                'visible' => $debug_mode],
              ['label' => $translator->translate('invoice.debug'),
                'url' => '',
                'options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip', 'title' => 'Disable in views/layout/invoice.php. Red background links and menus will disappear.', 'style' => 'background-color: #ffcccb'],
                'visible' => $debug_mode],
              ['label' => 'Locale => ' . $locale,
                'url' => '',
                'options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip', 'title' => 'Storage: session/runtime file.', 'style' => 'background-color: #90EE90'],
                'visible' => $debug_mode],
              ['label' => 'cldr => ' . $s->get_setting('cldr'),
                'url' => '',
                'options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip', 'title' => 'Storage: database', 'style' => 'background-color: #ffffe0'],
                'visible' => $debug_mode],
              ['label' => 'File Location',
                'url' => '',
                'options' => ['class' => 'nav fs-4', 'data-bs-toggle' => 'tooltip', 'title' => $s->debug_mode_file_location(0), 'style' => 'background-color: #ffcccb'],
                'visible' => $debug_mode],
              ]
        );

        echo Nav::widget()
          ->currentPath($currentRoute
            ->getUri()
            ->getPath())
          ->options(['class' => 'navbar-nav'])
          ->items(
            [
              [
                'label' => $s->trans('language'),
                'url' => '#',
                //'visible' => $isGuest,
                'items' => [
                  [
                    'label' => 'Afrikaans',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'af'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Arabic / عربي',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'ar'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Azerbaijani / Azərbaycan',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'az'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Chinese Simplified / 简体中文',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'zh'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'English',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'en'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'French / Français',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'fr'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Dutch / Nederlands',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'nl'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'German / Deutsch',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'de'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Indonesian / bahasa Indonesia',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'id'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Japanese / 日本',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'ja'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Russian / Русский',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'ru'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Slovakian / Slovenský',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'sk'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Spanish / Española x',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'es'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Ukrainian / українська',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'uk'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Uzbek / o' . "'" . 'zbek',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'uz'], fallbackRouteName: 'invoice/index'),
                  ],
                  [
                    'label' => 'Vietnamese / Tiếng Việt',
                    'url' => $urlGenerator->generateFromCurrent(['_language' => 'vi'], fallbackRouteName: 'invoice/index'),
                  ],
                ],
              ],
              [
                'label' => $s->trans('login'),
                'url' => $urlGenerator->generate('auth/login'),
                'visible' => $isGuest,
              ],
              [
                'label' => $s->trans('enter_user_account'),
                'url' => $urlGenerator->generate('auth/signup'),
                'visible' => $isGuest,
              ],
              $isGuest ? '' : Form::tag()
                ->post($urlGenerator->generate('auth/logout'))
                ->csrf($csrf)
                ->open()
                . '<div class="mb-1">'
                . Button::submit(
                  $translator->translate('menu.logout', ['login' => Html::encode($user->getLogin())])
                )
                ->class('btn btn-primary')
                . '</div>'
                . Form::tag()->close(),
            ],
        );

        echo NavBar::end();
        ?>

        <div id="main-area">
            <?php
// Display the sidebar if enabled
            if ($s->get_setting('disable_sidebar') !== (string) 1) {
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
                    <button type="button" class="fullpage-loader-close btn btn-link tip" aria-label="<?php $s->trans('close'); ?>"
                            title="<?= $s->trans('close'); ?>" data-placement="left">
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
// https://api.jqueryui.com/datepicker
$js1 = "$(function () {" .
  '$(".form-control.input-sm.datepicker").datepicker({dateFormat:"' . $datehelper->datepicker_dateFormat()
  . '", firstDay:' . $datehelper->datepicker_firstDay()
  . ', changeMonth: true'
  . ', changeYear: true'
  . ', yearRange: "-110:+10"'
  . ', clickInput: true'
  . ', constrainInput: false'
  . ', highlightWeek: true'
  . ' });' .
  '});';
echo Html::script($js1)->type('module');
?>
<?php
$this->endPage(true);
?>