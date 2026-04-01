<?php

declare(strict_types=1);

use App\Invoice\Asset\InvoiceCdnAsset as InvCdn;
use App\Invoice\Asset\InvoiceNodeModulesAsset as InvNm;
use App\Invoice\Asset\MonospaceAsset;
// PCI Compliant Payment Gateway Assets
use App\Invoice\Asset\pciAsset\StripeVersionTenAsset;
use App\Invoice\Asset\pciAsset\AmazonPayTwoSevenAsset;
use App\Invoice\Asset\pciAsset\BraintreeDropInOneThirtyThreeSevenAsset;
use App\Asset\AppCdnAsset as AppCdn;
use App\Asset\AppNodeModulesAsset as AppNm;
use App\Widget\PerformanceMetrics;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Html as TagHtml;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Title;
use Yiisoft\Bootstrap5\Assets\BootstrapCdnAsset as BsCdn;
use Yiisoft\Bootstrap5\Assets\BootstrapAsset as BsNm;
use Yiisoft\Bootstrap5\ButtonSize;
use Yiisoft\Bootstrap5\Dropdown;
use Yiisoft\Bootstrap5\DropdownItem;
use Yiisoft\Bootstrap5\ButtonVariant;
use Yiisoft\Bootstrap5\Nav;
use Yiisoft\Bootstrap5\NavBar;
use Yiisoft\Bootstrap5\NavBarExpand;
use Yiisoft\Bootstrap5\NavBarPlacement;
use Yiisoft\Bootstrap5\NavLink;
use Yiisoft\Bootstrap5\NavStyle;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\User\User|null $user
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var bool $bootstrap5CdnNotNodeModule 
 * @var bool $appCdnNotNodeModule
 * @var bool $invCdnNotNodeModule
 * @var string $bootstrap5LayoutGuestNavbarFont
 * @var string $bootstrap5LayoutGuestNavbarFontSize 
 * @var string $csrf
 * @var string $content
 * @var string $brandLabel
 * @var string $companyLogoHeight
 * @var string $companyLogoMargin
 * @var string $companyLogoWidth
 * @var string $logoPath
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
 */
// Settings ... View ... General
$assetManager->register($appCdnNotNodeModule ? AppCdn::class : AppNm::class);
$assetManager->register($invCdnNotNodeModule ? InvCdn::class : InvNm::class);
$assetManager->register($bootstrap5CdnNotNodeModule ? BsCdn::class : BsNm::class);
$s->getSetting('monospace_amounts') == 1 ?
        $assetManager->register(MonospaceAsset::class) : '';
$assetManager->register(StripeVersionTenAsset::class);
$assetManager->register(AmazonPayTwoSevenAsset::class);
$assetManager->register(BraintreeDropInOneThirtyThreeSevenAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());

$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());
$t = $translator;
$isGuest = $user === null || $user->getId() === null;
$itemFontArray = [
    'style' => 'font-size: ' . $bootstrap5LayoutGuestNavbarFontSize . 'px;'
    . ' color: black;'];
$this->beginPage();
?>
<!DOCTYPE html>
<?php
echo new TagHtml()->lang($currentRoute->getArgument('_language') ?? 'en');     
echo Html::openTag('head');
echo Meta::documentEncoding('utf-8');
echo Meta::data('viewport', 'width=device-width, initial-scale=1');
echo Meta::data('robots', 'NOINDEX,NOFOLLOW'); 
echo new Title()->content($s->getSetting('custom_title') ?: 'Yii-Invoice');
$this->head();
echo Html::closeTag('head'); 
echo Html::openTag('body'); 
echo Html::tag('Noscript', Html::tag('div',
    $t->translate('please.enable.js'),
    ['class' => 'alert alert-danger no-margin']));
echo Html::openTag('header'); 
$this->beginBody();
echo NavBar::widget()
    ->addAttributes([])
    //->addClass('navbar navbar-light bg-light navbar-expand-sm text-white')
    ->addClass('navbar bg-body-tertiary')
    ->brandImage($logoPath)
    ->brandImageAttributes(['margin' => $companyLogoMargin,
        'width' => $companyLogoWidth,
        'height' => $companyLogoHeight])
    ->brandUrl($urlGenerator->generate('site/index'))
    ->container(false)
    ->containerAttributes([])
    ->addCssStyle([
      'font-size' => $bootstrap5LayoutGuestNavbarFontSize,
      'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])    
    ->expand(NavBarExpand::LG)
    ->id('navbar')
    ->innerContainerAttributes(['class' => 'container-md'])
    ->placement(NavBarPlacement::STICKY_TOP)
    ->begin();

$currentPath = $currentRoute->getUri()?->getPath();
if ((null !== $currentPath) && !$isGuest) {
    // Client
    echo Dropdown::widget()
    ->addClass('navbar')
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent($t->translate('client'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('client/guest'),
            itemAttributes: $itemFontArray),
    )
    ->render();

    // Quote
    echo Dropdown::widget()
    ->addClass('navbar')
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent($t->translate('quote'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('quote/guest'),
            itemAttributes: $itemFontArray),
    )
    ->render();

    // SalesOrder
    echo Dropdown::widget()
    ->addClass('navbar')
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent($t->translate('salesorder'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('salesorder/guest'),
            itemAttributes: $itemFontArray)
    )
    ->render();

    // Invoice
    echo Dropdown::widget()
    ->addClass('navbar')
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent($t->translate('invoice'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('inv/guest'),
            itemAttributes: $itemFontArray),
    )
    ->render();

    // Payment
    echo Dropdown::widget()
    ->addClass('navbar')
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent((string)  new I()->addClass('bi bi-coin')
            . ' ' . $t->translate('payment'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('payment/guest'),
            itemAttributes: $itemFontArray),
        DropdownItem::link($t->translate('online.log'),
            $urlGenerator->generate('payment/guestOnlineLog'),
            itemAttributes: $itemFontArray),
    )
    ->render();

    // Settings
    echo Dropdown::widget()
    ->addClass('navbar')
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent((string)  new I()->addClass('fa fa-cogs')
            . ' ' . $t->translate('settings'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        DropdownItem::link($t->translate('view'),
            $urlGenerator->generate('userinv/guest'),
            itemAttributes: $itemFontArray),
        DropdownItem::link($t->translate('password.change'),
            $urlGenerator->generate('auth/change'),
            itemAttributes: $itemFontArray),
        DropdownItem::link($t->translate('email.log'),
            $urlGenerator->generate('invsentlog/guest'),
            itemAttributes: $itemFontArray),
    )
    ->render();
    // Translate
    echo Dropdown::widget()
    ->addClass('navbar')
    ->addAttributes([
        'data-bs-toggle' => 'tooltip',
        'title' => $t->translate('language'),
        'url' => '#',
    ])
    ->addTogglerCssStyle([
        'font-size' => $bootstrap5LayoutGuestNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutGuestNavbarFont,
    ])        
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent( new I()->addClass('bi bi-translate'))
    ->togglerSize(ButtonSize::LARGE)
    ->items(
        // Related logic: config/web/params, src/ViewInjection/LayoutViewInjection
        $afZA, $arBH, $az, $beBY, $bs, $zhCN, $zhTW, $en,
        $fil, $fr, $gdGB, $haNG, $heIL, $igNG, $nl, $de,
        $id, $it, $ja, $pl, $ptBR, $ru, $sk, $sl, $es,
        $uk, $uz, $vi, $yoNG, $zuZA
    )->render();
}

if (null !== $currentPath && $isGuest) {
    echo Nav::widget()
    ->items(
        NavLink::to(
             new Label()
            ->attributes([
                'class' => 'bi bi-door-open-fill text-success',
            ])
            ->content(),
            $urlGenerator->generate('auth/login'),
            true,
            false,
            false,
        ),
        NavLink::to(
             new Label()
            ->attributes(
                [
                    'class' => 'bi bi-person-plus-fill',
                    'data-bs-toggle' => 'tooltip',
                    'title' => str_repeat(' ', 1)
                    . $t->translate('setup.create.user'),
                ],
            ),
            $urlGenerator->generate('auth/signup'),
            true,
            false,
            false,
        ),
    )
    ->styles(NavStyle::NAVBAR);
}

if (!$isGuest) {
    echo  new Form()
    ->post($urlGenerator->generate('auth/logout'))
    ->csrf($csrf)
    ->open()
    . Html::openTag('div') 
    . (string) Button::submit(null !== $user ?
        (string) preg_replace('/\d+/', '', $user->getLogin()
        . ' ' . $t->translate('logout')) : '' . ' '
        . $t->translate('logout'))
        ->addStyle('font-size: '
                . $bootstrap5LayoutGuestNavbarFontSize
                . 'px; padding: '
                . ((int) $bootstrap5LayoutGuestNavbarFontSize * 0.15)
                . 'px '
                . ((int) $bootstrap5LayoutGuestNavbarFontSize * 0.4) . 'px;')  
    . Html::closeTag('div')
    . new Form()->close();
}
echo NavBar::end();
echo Html::closeTag('header');  
echo Html::openTag('div', ['id' => 'main-area']);    
  echo Html::openTag('main', ['class' => 'container-fluid py-4']);         
  echo $content;
  echo Html::openTag('div', [
    'id' => 'fullpage-loader',
    'style' => 'display: none'
   ]); //2                     
   echo Html::openTag('div', ['class' => 'loader-content']); //3
    echo new I()
          ->addAttributes(['id' => 'loader-icon'])
          ->addClass('fa fa-cog fa-spin')
          ->render(); //4                
   echo Html::CloseTag('div'); //3   
  echo Html::closeTag('div'); //2
 echo Html::closeTag('main');
echo Html::closeTag('div');
  echo Html::openTag('footer', ['class' => 'container-fluid py-4']); //2          
   echo PerformanceMetrics::widget(); //3
  echo Html::closeTag('footer'); //2
 $this->endBody();
 echo Html::closeTag('body'); //1
echo Html::closeTag('html'); 
$this->endPage(true);
