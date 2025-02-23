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

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Yii\Bootstrap5\ButtonSize;
use Yiisoft\Yii\Bootstrap5\Dropdown;
use Yiisoft\Yii\Bootstrap5\DropdownItem;
use Yiisoft\Yii\Bootstrap5\DropdownTogglerVariant;
use Yiisoft\Yii\Bootstrap5\Nav;
use Yiisoft\Yii\Bootstrap5\NavBar;
use Yiisoft\Yii\Bootstrap5\NavBarExpand;
use Yiisoft\Yii\Bootstrap5\NavBarPlacement;
use Yiisoft\Yii\Bootstrap5\NavLink;
use Yiisoft\Yii\Bootstrap5\NavStyle;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\User\User|null $user
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var string $csrf 
 * @var string $content
 * @var string $brandLabel
 * @var string $companyLogoHeight 
 * @var string $companyLogoMargin
 * @var string $companyLogoWidth
 * @var string $logoPath
 */

$assetManager->register(AppAsset::class);
$assetManager->register(InvoiceAsset::class);
$assetManager->register(Yiisoft\Yii\Bootstrap5\Assets\BootstrapAsset::class);
$s->getSetting('monospace_amounts') == 1 ? $assetManager->register(MonospaceAsset::class) : '';
// '0' => PCI Compliant version 
$s->getSetting('gateway_stripe_version') == '0' ? $assetManager->register(stripe_v10_Asset::class) : '';
$s->getSetting('gateway_amazon_pay_version') == '0' ? $assetManager->register(amazon_pay_v2_4_Asset::class) : '';
$s->getSetting('gateway_braintree_version') == '0' ? $assetManager->register(braintree_dropin_1_33_7_Asset::class) : '';
// The InvoiceController/index receives the $session->get('_language') or 'drop-down' locale user selection and saves it into a setting called 'cldr'
// The $s value is configured for the layout in config/params.php yii-soft/view Reference::to and NOT by means of the InvoiceController

switch ($currentRoute->getArgument('_language')) {
    case 'af-ZA' : $assetManager->register(af_ZA_Asset::class); $locale = 'AfrikaansSouthAfrican'; break;
    case 'ar-BH' : $assetManager->register(ar_BH_Asset::class); $locale = 'ArabicBahrainian'; break;
    case 'az' : $assetManager->register(az_Asset::class); $locale = 'Azerbaijani'; break;
    case 'de' : $assetManager->register(de_DE_Asset::class); $locale = 'German'; break;
    case 'en' : $assetManager->register(en_GB_Asset::class); $locale = 'English'; break;
    case 'fil' : $assetManager->register(fil_PH_Asset::class); $locale = 'Filipino'; break;
    case 'fr' : $assetManager->register(fr_FR_Asset::class); $locale = 'French'; break;
    case 'id' : $assetManager->register(id_Asset::class); $locale = 'Indonesian'; break;
    case 'it' : $assetManager->register(id_Asset::class); $locale = 'Italian'; break;
    case 'ja' : $assetManager->register(ja_Asset::class); $locale = 'Japanese'; break;
    case 'pl-PL' : $assetManager->register(pl_Asset::class); $locale = 'Polish'; break;
    case 'pt-BR' : $assetManager->register(pt_BR_Asset::class); $locale = 'PortugeseBrazil'; break;
    case 'nl' : $assetManager->register(nl_Asset::class); $locale = 'Dutch'; break;
    case 'ru' : $assetManager->register(ru_Asset::class); $locale = 'Russian'; break;
    case 'sk' : $assetManager->register(sk_Asset::class); $locale = 'Slovensky'; break;    
    case 'es' : $assetManager->register(es_ES_Asset::class); $locale = 'Spanish'; break;
    case 'uk' : $assetManager->register(uk_UA_Asset::class); $locale = 'Ukrainian'; break;
    case 'uz' : $assetManager->register(uz_UZ_Asset::class); $locale = 'Uzbek'; break;
    case 'vi' : $assetManager->register(vi_VN_Asset::class); $locale = 'Vietnamese'; break;
    case 'zh-CN' : $assetManager->register(zh_CN_Asset::class); $locale = 'ChineseSimplified'; break;
    case 'zh-TW' : $assetManager->register(zh_TW_Asset::class); $locale = 'TiawaneseMandarin'; break;
    case 'zu-ZA' : $assetManager->register(zu_ZA_Asset::class); $locale = 'ZuluSouthAfrican'; break;
    default   : $assetManager->register(en_GB_Asset::class); $locale = 'English'; break;
}

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());

$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$currentRouteName = $currentRoute->getName() ?? '';

$isGuest = $user === null || $user->getId() === null;
$this->beginPage();
?>

<!DOCTYPE html>
<html lang="<?= $currentRoute->getArgument('_language') ?? 'en'; ?>">
<head>
    <?= Meta::documentEncoding('utf-8')?>
    <?= Meta::pragmaDirective('X-UA-Compatible', 'IE=edge,chrome=1') ?>
    <?= Meta::data('viewport', 'width=device-width, initial-scale=1') ?>
    <?= Meta::data('robots', 'NOINDEX,NOFOLLOW') ?>
    <title>
        <?= $s->getSetting('custom_title') ?: 'Yii-Invoice'; ?>
    </title>
    <?php $this->head() ?>
</head>
<body>
<?php
    Html::tag('Noscript',Html::tag('Div',$translator->translate('i.please_enable_js'),['class'=>'alert alert-danger no-margin']));
?>
<header>
<?php
$this->beginBody();

echo NavBar::widget()
    ->addAttributes([])    
    ->addClass('navbar navbar-light bg-light navbar-expand-sm text-white')    
    ->brandImage($logoPath)
    ->brandImageAttributes(['margin' => $companyLogoMargin, 
                            'width' => $companyLogoWidth, 
                            'height' => $companyLogoHeight])    
    //->brandText(str_repeat('&nbsp;', 7).$brandLabel)
    ->brandUrl($urlGenerator->generate('site/index'))
    ->class()
    ->container(false) 
    ->containerAttributes([])      
    ->expand(NavBarExpand::LG)
    ->id('navbar')      
    ->innerContainerAttributes(['class' => 'container-md'])      
    ->placement(NavBarPlacement::STICKY_TOP)     
    ->begin();

    $currentPath = $currentRoute->getUri()?->getPath();
    if ((null!== $currentPath) && !$isGuest) {
        // Client
        echo Dropdown::widget()
        ->addClass('navbar fs-4')  
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(DropdownTogglerVariant::INFO)
        ->togglerContent($translator->translate('invoice.client'))        
        ->togglerSize(ButtonSize::SMALL)        
        ->items(
            DropdownItem::link($translator->translate('invoice.view'), $urlGenerator->generate('client/guest'))    
        )
        ->render();

        // Quote
        echo Dropdown::widget()
        ->addClass('navbar fs-4')  
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(DropdownTogglerVariant::INFO)
        ->togglerContent($translator->translate('invoice.quote'))        
        ->togglerSize(ButtonSize::LARGE)        
        ->items(
            DropdownItem::link($translator->translate('invoice.view'), $urlGenerator->generate('quote/guest'))    
        )
        ->render();

        // SalesOrder
        echo Dropdown::widget()
        ->addClass('navbar fs-4')  
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(DropdownTogglerVariant::INFO)
        ->togglerContent($translator->translate('invoice.salesorder'))        
        ->togglerSize(ButtonSize::LARGE)        
        ->items(
            DropdownItem::link($translator->translate('invoice.view'), $urlGenerator->generate('salesorder/guest'))    
        )
        ->render();

        // Invoice
        echo Dropdown::widget()
        ->addClass('navbar fs-4')  
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(DropdownTogglerVariant::INFO)
        ->togglerContent($translator->translate('i.invoice'))        
        ->togglerSize(ButtonSize::LARGE)        
        ->items(
            DropdownItem::link($translator->translate('i.view'), $urlGenerator->generate('inv/guest'))    
        )
        ->render();

        // Payment
        echo Dropdown::widget()
        ->addClass('navbar fs-4')  
        ->addAttributes([
            'style' => 'font-size: 1rem; color: cornflowerblue;',
        ])
        ->togglerVariant(DropdownTogglerVariant::INFO)
        ->togglerContent((string)I::tag()->addClass('bi bi-coin').' '.$translator->translate('i.payment'))        
        ->togglerSize(ButtonSize::LARGE)        
        ->items(
            DropdownItem::link($translator->translate('invoice.view'), $urlGenerator->generate('payment/guest')),
            DropdownItem::link($translator->translate('invoice.online.log'), $urlGenerator->generate('payment/guest_online_log'))    
        )
        ->render();

        // Settings
        echo Dropdown::widget()
        ->addClass('navbar fs-4')  
        ->addAttributes([
            'style' => 'font-size: 1rem;',
        ])
        ->togglerVariant(DropdownTogglerVariant::INFO)
        ->togglerContent((string)I::tag()->addClass('fa fa-cogs'). ' '. $translator->translate('i.settings'))        
        ->togglerSize(ButtonSize::LARGE)        
        ->items(
            DropdownItem::link($translator->translate('invoice.view'), $urlGenerator->generate('userinv/guest')),
            DropdownItem::link($translator->translate('password.change'), $urlGenerator->generate('auth/change')),
            DropdownItem::link($translator->translate('invoice.email.log'), $urlGenerator->generate('invsentlog/guest')),    
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
        ->togglerVariant(DropdownTogglerVariant::INFO)
        ->togglerContent(I::tag()->addClass('bi bi-translate'))        
        ->togglerSize(ButtonSize::LARGE)        
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
    }

    if (null!==$currentPath && $isGuest) {
        echo Nav::widget()
        ->items(
            NavLink::to(
                Label::tag()
                ->attributes([
                    'class' => 'bi bi-door-open-fill text-success'
                ])
                ->content(),    
                $urlGenerator->generate('auth/login'), 
                true, 
                false,                        
                false        
            ),
            NavLink::to(
                Label::tag()
                ->attributes(
                [
                    'class' => 'bi bi-person-plus-fill',
                    'data-bs-toggle' => 'tooltip',
                    'title' => str_repeat(' ',1).$translator->translate('i.setup_create_user')
                ]), 
                $urlGenerator->generate('auth/signup'), 
                true, 
                false,                        
                false
            )
        )
        ->styles(NavStyle::NAVBAR);
    }

    if (!$isGuest) { 
        echo Form::tag()
        ->post($urlGenerator->generate('auth/logout'))
        ->csrf($csrf)
        ->open()
        . '<div class="mb-1">'
        . (string)Button::submit(null!==$user ? (string)preg_replace('/\d+/', '', $user->getLogin().' '.$translator->translate('i.logout')) : ''. ' '.
            $translator->translate('i.logout'))->class('btn btn-primary')
        . '</div>'
        . Form::tag()->close();
    }    
    echo NavBar::end();
?>    
</header>
<div id="main-area">
    <main class="container py-4">        
        <?php echo $content; ?>
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
    </main>
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
    $js1 = "$(function () {".
       '$(".form-control.input-sm.datepicker").datepicker({dateFormat:"'.$dateHelper->datepicker_dateFormat()
                                                        .'", firstDay:'.$dateHelper->datepicker_firstDay()
                                                        .', changeMonth: true'
                                                        .', changeYear: true'
                                                        .', yearRange: "-50:+10"'
                                                        .', clickInput: true'
                                                        .', constrainInput: false'
                                                        .', highlightWeek: true'
                                                        .' });'.
    '});';
    echo Html::script($js1)->type('module');
?>
<?php
   $this->endPage();     
?>
