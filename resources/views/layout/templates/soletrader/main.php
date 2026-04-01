<?php

declare(strict_types=1);

use App\Auth\Asset\AuthAegisTotpKeypadAsset;

use App\Asset\AppCdnAsset as AppCdn; 
use App\Asset\AppNodeModulesAsset as AppNm;
use App\Widget\PerformanceMetrics;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Html as TagHtml;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Title;
use Yiisoft\Bootstrap5\Assets\BootstrapCdnAsset as BsCdn;
use Yiisoft\Bootstrap5\Assets\BootstrapAsset as BsNm;
use Yiisoft\Bootstrap5\ButtonSize;
use Yiisoft\Bootstrap5\ButtonVariant;
use Yiisoft\Bootstrap5\Dropdown;
use Yiisoft\Bootstrap5\DropdownItem;
use Yiisoft\Bootstrap5\Nav;
use Yiisoft\Bootstrap5\NavBarExpand;
use Yiisoft\Bootstrap5\NavBarPlacement;
use Yiisoft\Bootstrap5\NavLink;
use Yiisoft\Bootstrap5\NavBar;
use Yiisoft\Bootstrap5\NavStyle;
use Yiisoft\Yii\AuthClient\Asset\AuthChoiceAsset;

/**
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView  $this
 *
 * Related logic: see ..\config\common\params.php
 *         'yiisoft/yii-view' => [
 *             'viewPath' => '@views',
 *              //'layout' => '@views/layout/main.php',
 *              'layout' => '@views/layout/templates/soletrader/main.php'
 *          ],
 * @var App\User\User|null $user
 * @var bool $appCdnNotNodeModule 
 * @var bool $invCdnNotNodeModule 
 * @var bool $bootstrap5CdnNotNodeModule
 * @var bool $debugMode
 * @var bool $noFrontPageAbout
 * @var bool $noFrontPageAccreditations
 * @var bool $noFrontPageContactDetails
 * @var bool $noFrontPageContactUs
 * @var bool $noFrontPageGallery
 * @var bool $noFrontPagePricing
 * @var bool $noFrontPageTeam
 * @var bool $noFrontPageTestimonial
 * @var bool $noFrontPage
 * @var bool $noFrontPagePrivacyPolicy
 * @var bool $noFrontPageTermsOfService
 * @var bool $stopLoggingIn
 * @var bool $stopSigningUp
 * @var string $bootstrap5LayoutMainNavbarFont
 * @var string $bootstrap5LayoutMainNavbarFontSize
 * @var string $companySeoDescription 
 * @var string $content
 * @var string $companyFaceBook
 * @var string $companyLinkedIn
 * @var string $companyLogoHeight
 * @var string $companyLogoMargin
 * @var string $companyLogoWidth
 * @var string $companySlack
 * @var string $companyTwitter
 * @var string $companyWeb
 * @var string $companyWhatsApp
 * @var string $csrf
 * @var string $brandLabel
 * @var string $logoPath
 * @var string $title
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
$assetManager->register($bootstrap5CdnNotNodeModule ? BsCdn::class : BsNm::class);
$assetManager->register($appCdnNotNodeModule ? AppCdn::class : AppNm::class); 
$assetManager->register(AuthAegisTotpKeypadAsset::class);
$assetManager->register(AuthChoiceAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());
$t = $translator;
$isGuest = $user === null || $user->getId() === null;
$session->set('_language', $currentRoute->getArgument('_language'));
$this->beginPage();
/**
 * Related logic: see ./src/ViewInjection/LayoutViewInjection getLayoutParameters
 *  e.g. $title, $brandLabel, $companyWeb
 */
$this->setTitle($title);
?>
<!DOCTYPE html>
<?php 
echo new TagHtml()
    ->addAttributes(['class' => 'h-100'])
    ->lang($currentRoute->getArgument('_language') ?? 'en'); 
 echo Html::openTag('head'); //1 
  echo Meta::documentEncoding('utf-8');
  echo Meta::data('robots', 'INDEX,FOLLOW');
  echo Meta::data('viewport', 'width=device-width, initial-scale=1');
  // Search Engine Optimization (SEO)
  echo Meta::data('description', $companySeoDescription);
  // Avoid 'ranking splitting' by identifying the real source
  // Settings ... Company Public Details ... web
  // Related logic: src\ViewInjection\CommonViewInjection.php
  echo new Link()->rel('canonical')->href($companyWeb ?:
    'https://yiiframework.com');
   echo new Title()
       ->content($this->getTitle() ? Html::encode($this->getTitle()) : ''); //3
 $this->head();
 echo Html::closeTag('head'); //1
 echo Html::openTag('body',
        ['class' =>
        'cover-container-fluid d-flex w-100 h-100 mx-auto flex-column']); //1
  echo Html::openTag('header', ['class' => 'mb-auto']); //2
    $this->beginBody();         
   echo NavBar::widget()
        ->addClass('navbar navbar-light bg-light navbar-expand-sm text-white')
        ->addCssStyle([
            'color' => 'red',
            'font-size' => $bootstrap5LayoutMainNavbarFontSize,
            'font-family' => $bootstrap5LayoutMainNavbarFont,
            'font-weight' => 'bold',
        ]) 
        ->addAttributes([])
        ->brandImage($logoPath)
        ->brandImageAttributes(['margin' => $companyLogoMargin ?: '10',
            'width' => $companyLogoWidth ?:
                ($bootstrap5LayoutMainNavbarFontSize ?: '100'),
            'height' => $companyLogoHeight ?:
                ($bootstrap5LayoutMainNavbarFontSize ?: '50')])
        ->brandText(str_repeat('&nbsp;', 7) . $brandLabel)
        ->brandAttributes([
            'style' => 'font-size: '
                . $bootstrap5LayoutMainNavbarFontSize
                . 'px; font-family: '
                . $bootstrap5LayoutMainNavbarFont,
        ])   
        ->brandUrl($urlGenerator->generate('site/index'))
        ->container(false)
        ->containerAttributes([])
        ->expand(NavBarExpand::LG)
        ->id('navbar')
        ->innerContainerAttributes(['class' => 'container-md'])
        ->placement(NavBarPlacement::STICKY_TOP)
        ->begin();
    echo Dropdown::widget()
         ->addClass('dropdown bi bi-translate')
         ->addAttributes([
             'style' => 'font-size: '
                . $bootstrap5LayoutMainNavbarFontSize . 'px; color: black;',
             'url' => '#',
         ])
         ->addTogglerCssStyle([
            'font-size' => $bootstrap5LayoutMainNavbarFontSize . 'px',
            'font-family' => $bootstrap5LayoutMainNavbarFont,
         ])   
         ->togglerVariant(ButtonVariant::LIGHT)
         ->togglerContent('')
         ->togglerSize(ButtonSize::SMALL)
         ->items(
             $afZA, $arBH, $az,
             $beBY, $bs, $zhCN, $zhTW, $en,
             $fil, $fr, $gdGB, $haNG, $heIL,
             $igNG, $nl, $de, $id, $it, $ja, $pl, $ptBR,
             $ru, $sk, $sl, $es, $uk, $uz, $vi, $yoNG, $zuZA
         )->render();
    $currentPath = $currentRoute->getUri()?->getPath();
   echo null !== $currentPath ?
      Nav::widget()
      ->addCssStyle([
        'font-size' => $bootstrap5LayoutMainNavbarFontSize . 'px',
        'font-family' => $bootstrap5LayoutMainNavbarFont,
      ])     
      ->items(
       NavLink::to(
            new Label()
           ->attributes([
               'class' => $debugMode ? 'bi bi-info-circle' : '',
               'style' => 'font-size: '
                    . $bootstrap5LayoutMainNavbarFontSize
                    . 'px; color: cornflowerblue;',
               'data-bs-toggle' => 'tooltip',
               'title' => $debugMode ?
                '..\invoice\resources\views\layout\templates\soletrader\main.php'
                   . ' && config/common/params.php yiisoft/yii-view layouts' : '',
            ]),
            '',
            //active
            $debugMode,
            //disabled
            !$debugMode,
            //encode label
            false,
        ),
        NavLink::to(
             new Label()
            ->attributes([
                'class' => 'bi bi-info-circle-fill text-info',
                'style' => 'font-size: '
                    . $bootstrap5LayoutMainNavbarFontSize
                    . 'px; color: cornflowerblue;',
            ])
            ->content(str_repeat(' ', 1) . $t->translate('menu.about')),
            $urlGenerator->generate('site/about'),
            $isGuest && !$noFrontPageAbout,
            !$isGuest && $noFrontPageAbout,
            false,
            [],
            [],
            $isGuest && !$noFrontPageAbout,
        ),
        NavLink::to(
             new Label()
            ->attributes([
                'class' => 'bi bi-patch-check',
            ])
            ->content(str_repeat(' ', 1)
                . $t->translate('menu.accreditations')),
            $urlGenerator->generate('site/accreditations'),
            $isGuest && !$noFrontPageAccreditations,
            !$isGuest && $noFrontPageAccreditations,
            false,
            [],
            [],
            $isGuest && !$noFrontPageAccreditations,
        ),
        NavLink::to(
             new Label()
            ->attributes(['class' => 'bi bi-images'])
            ->content(str_repeat(' ', 1) . $t->translate('menu.gallery')),
            $urlGenerator->generate('site/gallery'),
            $isGuest && !$noFrontPageGallery,
            !$isGuest && $noFrontPageGallery,
            false,
            [],
            [],
            $isGuest && !$noFrontPageGallery,
        ),
        NavLink::to(
             new Label()
            ->attributes(['class' => 'bi bi-people-fill'])
            ->content(str_repeat(' ', 1) . $t->translate('menu.team')),
            $urlGenerator->generate('site/team'),
            $isGuest && !$noFrontPageTeam,
            !$isGuest && $noFrontPageTeam,
            false,
            [],
            [],
            $isGuest && !$noFrontPageTeam,
        ),
        NavLink::to(
             new Label()
            ->attributes(['class' => 'bi bi-tags-fill text-danger'])
            ->content(str_repeat(' ', 1) . $t->translate('menu.pricing')),
            $urlGenerator->generate('site/pricing'),
            $isGuest && !$noFrontPagePricing,
            !$isGuest && $noFrontPagePricing,
            false,
            [],
            [],
            $isGuest && !$noFrontPagePricing,
        ),
        NavLink::to(
             new Label()
            ->attributes(['class' => 'bi bi-file-ruled'])
            ->content(str_repeat(' ', 1)
                . $t->translate('menu.testimonial')),
            $urlGenerator->generate('site/testimonial'),
            $isGuest && !$noFrontPageTestimonial,
            !$isGuest && $noFrontPageTestimonial,
            false,
            [],
            [],
            $isGuest && !$noFrontPageTestimonial,
        ),
        NavLink::to(
             new Label()
            ->attributes(['class' => 'bi bi-file-text'])
            ->content(str_repeat(' ', 1)
                . $t->translate('menu.privacy.policy')),
            $urlGenerator->generate('site/privacypolicy'),
            $isGuest && !$noFrontPagePrivacyPolicy,
            !$isGuest && $noFrontPagePrivacyPolicy,
            false,
            [],
            [],
            $isGuest && !$noFrontPagePrivacyPolicy,
        ),
        NavLink::to(
             new Label()
            ->attributes(['class' => 'bi bi-file-text-fill'])
            ->content(str_repeat(' ', 1)
                . $t->translate('menu.terms.of.service')),
            $urlGenerator->generate('site/termsofservice'),
            $isGuest && !$noFrontPageTermsOfService,
            !$isGuest && $noFrontPageTermsOfService,
            false,
            [],
            [],
            $isGuest && !$noFrontPageTermsOfService,
        ),
        NavLink::to(
             new Label()
            ->attributes(['class' => 'bi bi-person-lines-fill text-primary'])
            ->content(str_repeat(' ', 1)
                . $t->translate('menu.contact.us')),
            $urlGenerator->generate('site/contact'),
            $isGuest && !$noFrontPageContactDetails,
            !$isGuest && $noFrontPageContactDetails,
            false,
            [],
            [],
            $isGuest && !$noFrontPageContactDetails,
        ),
        NavLink::to(
             new Label()
            ->attributes(['class' => 'bi bi-door-open-fill text-success'])
            ->content(str_repeat(' ', 1)
                . $t->translate('menu.login')),
            $urlGenerator->generate('auth/login'),
            $isGuest && !$stopLoggingIn,
            !$isGuest && $stopLoggingIn,
            false,
            [],
            [],
            $isGuest && !$stopLoggingIn,
        ),
        NavLink::to(
             new Label()
            ->attributes([
                'class' => 'bi bi-person-plus-fill',
                'data-bs-toggle' => 'tooltip',
                'title' => str_repeat(' ', 1)
                    . $t->translate('setup.create.user'),
            ])
            ->content(str_repeat(' ', 1) . $t->translate('menu.signup')),
            $urlGenerator->generate('auth/signup'),
            $isGuest && !$stopSigningUp,
            !$isGuest && $stopSigningUp,
            false,
            [],
            [],
            $isGuest && !$stopSigningUp,
        ),
        NavLink::to(
            /**
             * Only render the logout button if user is an authenticated
             *  user i.e. not guest
             */
            $isGuest && !$stopLoggingIn
                ? ''
                :  new Form()
                    ->post($urlGenerator->generate('auth/logout'))
                    ->csrf($csrf)
                    ->open()
                    . '<div class="mb-1">'
                    . Button::submit(
                        $t->translate('logout',
                            ['login' =>
                                Html::encode(null !== $user ?
                                    $user->getLogin() : '')]),
                    )
                    ->addStyle('font-size: '
                    . $bootstrap5LayoutMainNavbarFontSize
                    . 'px; padding: '
                    . ((int) $bootstrap5LayoutMainNavbarFontSize * 0.15)
                    . 'px '
                    . ((int) $bootstrap5LayoutMainNavbarFontSize * 0.4) . 'px;')  
                    . '</div>'
                    .  new Form()->close(),
            encodeLabel: false,
        ),
    )
    ->styles(NavStyle::NAVBAR) : ''; 
   echo NavBar::end();
  echo Html::closeTag('header'); //2
  echo Html::openTag('main', ['class' => 'container py-3']); //2 
   /**
    * Related logic: see ./resources/views/site/index.php
    */
   echo $content;
  echo Html::closeTag('main'); //2
  echo Html::openTag('footer', ['class' => 'mt-auto bg-dark py-3']); //2
   echo Html::openTag('div', ['class' =>
            'd-flex flex-fill align-items-center container-fluid']); //3
    echo Html::openTag('div', ['class' => 'd-flex flex-fill float-start']); //4
     echo new A()
          ->href($companyWeb)
          ->addClass('text-decoration-none')
          ->addAttributes(['target' => '_blank', 'rel' => 'noopener'])
          ->content($brandLabel . ' - ' . date('Y') . ' -' )
          ->render();
      echo Html::openTag('div', ['class' => 'ms-2 text-white']); //5
       echo PerformanceMetrics::widget(); 
      echo Html::closeTag('div'); //5
    echo Html::closeTag('div'); //4
    echo Html::openTag('div', ['class' => 'float-end']); //4 
     $txtDecorNonePx1 = 'text-decoration-none px-1';
     echo new A()
          ->href($companyWeb)
          ->addClass($txtDecorNonePx1)
          ->addAttributes(['target' => '_blank', 'rel' => 'noopener'])
          ->content(new I()->addClass('bi bi-github text-white'))
          ->render();
     echo new A()
          ->href($companySlack)
          ->addClass($txtDecorNonePx1)
          ->addAttributes(['target' => '_blank', 'rel' => 'noopener'])
          ->content(new I()->addClass('bi bi-slack text-white'))
          ->render();
     echo new A()
          ->href($companyFaceBook)
          ->addClass($txtDecorNonePx1)
          ->addAttributes(['target' => '_blank', 'rel' => 'noopener'])
          ->content(new I()->addClass('bi bi-facebook text-white'))
          ->render();
     echo new A()
          ->href($companyTwitter)
          ->addClass($txtDecorNonePx1)
          ->addAttributes(['target' => '_blank', 'rel' => 'noopener'])
          ->content(new I()->addClass('bi bi-twitter text-white'))
          ->render();
     echo new A()
          ->href($companyWhatsApp)
          ->addClass($txtDecorNonePx1)
          ->addAttributes(['target' => '_blank', 'rel' => 'noopener'])
          ->content(new I()->addClass('bi bi-whatsapp text-white'))
          ->render();
     echo new A()
          ->href($companyLinkedIn)
          ->addClass($txtDecorNonePx1)
          ->addAttributes(['target' => '_blank', 'rel' => 'noopener'])
          ->content(new I()->addClass('bi bi-linkedin text-white'))
          ->render();    
    echo Html::closeTag('div'); //4
   echo Html::closeTag('div'); //3
  echo Html::closeTag('footer'); //2
  $this->endBody();
 echo Html::closeTag('body'); //1
echo Html::closeTag('html'); 
$this->endPage(true);
