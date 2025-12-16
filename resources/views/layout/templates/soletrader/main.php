<?php

declare(strict_types=1);

use App\Auth\Asset\AuthAegisTotpKeypadAsset;
use App\Asset\AppAsset;
use App\Widget\PerformanceMetrics;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Label;
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
$assetManager->register(AppAsset::class);
$assetManager->register(AuthAegisTotpKeypadAsset::class);
$assetManager->register(AuthChoiceAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$currentRouteName = $currentRoute->getName() ?? '';
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
    <html class="h-100" lang="<?= $currentRoute->getArgument('_language') ?? 'en'; ?>">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $this->getTitle() ? Html::encode($this->getTitle()) : '' ?></title>
        <?php $this->head() ?>
    </head>
    <body class="cover-container-fluid d-flex w-100 h-100 mx-auto flex-column">
    <header class="mb-auto">
        <?php $this->beginBody(); ?>        
        <?= NavBar::widget()
            ->addClass('navbar navbar-light bg-light navbar-expand-sm text-white')
            ->addCssStyle([
                'color' => 'red',
                'font-family' => 'Garamond',
                'font-weight' => 'bold',
            ])
            ->addAttributes([])
            ->brandImage($logoPath)
            ->brandImageAttributes(['margin' => $companyLogoMargin ?: '10',
                'width' => $companyLogoWidth ?: '100',
                'height' => $companyLogoHeight ?: '50'])
            ->brandText(str_repeat('&nbsp;', 7) . $brandLabel)
            ->brandUrl($urlGenerator->generate('site/index'))
            ->container(false)
            ->containerAttributes([])
            ->expand(NavBarExpand::LG)
            ->id('navbar')
            ->innerContainerAttributes(['class' => 'container-md'])
            ->placement(NavBarPlacement::STICKY_TOP)
            ->begin(); ?>
        
        <?= Dropdown::widget()
            ->addClass('dropdown bi bi-translate')
            ->addAttributes([
                'style' => 'font-size: 1rem; color: black;',
                'url' => '#',
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
?>
        <?php
    $currentPath = $currentRoute->getUri()?->getPath();
?> 
        <?= null !== $currentPath ? Nav::widget()
    ->items(
        NavLink::to(
            Label::tag()
            ->attributes([
                'class' => $debugMode ? 'bi bi-info-circle' : '',
                'style' => 'font-size: 1rem; color: cornflowerblue;',
                'data-bs-toggle' => 'tooltip',
                'title' => $debugMode ? '..\invoice\resources\views\layout\templates\soletrader\main.php && config/common/params.php yiisoft/yii-view layouts' : '',
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
            Label::tag()
            ->attributes([
                'class' => 'bi bi-info-circle-fill text-info',
                'style' => 'font-size: 1rem; color: cornflowerblue;',
            ])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.about')),
            $urlGenerator->generate('site/about'),
            $isGuest && !$noFrontPageAbout,
            !$isGuest && $noFrontPageAbout,
            false,
            [],
            [],
            $isGuest && !$noFrontPageAbout,
        ),
        NavLink::to(
            Label::tag()
            ->attributes([
                'class' => 'bi bi-patch-check',
            ])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.accreditations')),
            $urlGenerator->generate('site/accreditations'),
            $isGuest && !$noFrontPageAccreditations,
            !$isGuest && $noFrontPageAccreditations,
            false,
            [],
            [],
            $isGuest && !$noFrontPageAccreditations,
        ),
        NavLink::to(
            Label::tag()
            ->attributes(['class' => 'bi bi-images'])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.gallery')),
            $urlGenerator->generate('site/gallery'),
            $isGuest && !$noFrontPageGallery,
            !$isGuest && $noFrontPageGallery,
            false,
            [],
            [],
            $isGuest && !$noFrontPageGallery,
        ),
        NavLink::to(
            Label::tag()
            ->attributes(['class' => 'bi bi-people-fill'])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.team')),
            $urlGenerator->generate('site/team'),
            $isGuest && !$noFrontPageTeam,
            !$isGuest && $noFrontPageTeam,
            false,
            [],
            [],
            $isGuest && !$noFrontPageTeam,
        ),
        NavLink::to(
            Label::tag()
            ->attributes(['class' => 'bi bi-tags-fill text-danger'])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.pricing')),
            $urlGenerator->generate('site/pricing'),
            $isGuest && !$noFrontPagePricing,
            !$isGuest && $noFrontPagePricing,
            false,
            [],
            [],
            $isGuest && !$noFrontPagePricing,
        ),
        NavLink::to(
            Label::tag()
            ->attributes(['class' => 'bi bi-file-ruled'])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.testimonial')),
            $urlGenerator->generate('site/testimonial'),
            $isGuest && !$noFrontPageTestimonial,
            !$isGuest && $noFrontPageTestimonial,
            false,
            [],
            [],
            $isGuest && !$noFrontPageTestimonial,
        ),
        NavLink::to(
            Label::tag()
            ->attributes(['class' => 'bi bi-file-text'])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.privacy.policy')),
            $urlGenerator->generate('site/privacypolicy'),
            $isGuest && !$noFrontPagePrivacyPolicy,
            !$isGuest && $noFrontPagePrivacyPolicy,
            false,
            [],
            [],
            $isGuest && !$noFrontPagePrivacyPolicy,
        ),
        NavLink::to(
            Label::tag()
            ->attributes(['class' => 'bi bi-file-text-fill'])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.terms.of.service')),
            $urlGenerator->generate('site/termsofservice'),
            $isGuest && !$noFrontPageTermsOfService,
            !$isGuest && $noFrontPageTermsOfService,
            false,
            [],
            [],
            $isGuest && !$noFrontPageTermsOfService,
        ),
        NavLink::to(
            Label::tag()
            ->attributes(['class' => 'bi bi-person-lines-fill text-primary'])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.contact.us')),
            $urlGenerator->generate('site/contact'),
            $isGuest && !$noFrontPageContactDetails,
            !$isGuest && $noFrontPageContactDetails,
            false,
            [],
            [],
            $isGuest && !$noFrontPageContactDetails,
        ),
        NavLink::to(
            Label::tag()
            ->attributes(['class' => 'bi bi-door-open-fill text-success'])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.login')),
            $urlGenerator->generate('auth/login'),
            $isGuest && !$stopLoggingIn,
            !$isGuest && $stopLoggingIn,
            false,
            [],
            [],
            $isGuest && !$stopLoggingIn,
        ),
        NavLink::to(
            Label::tag()
            ->attributes([
                'class' => 'bi bi-person-plus-fill',
                'data-bs-toggle' => 'tooltip',
                'title' => str_repeat(' ', 1) . $translator->translate('setup.create.user'),
            ])
            ->content(str_repeat(' ', 1) . $translator->translate('menu.signup')),
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
             * Only render the logout button if user is an authenticated user i.e. not guest
             */
            $isGuest && !$stopLoggingIn
                ? ''
                : Form::tag()
                    ->post($urlGenerator->generate('auth/logout'))
                    ->csrf($csrf)
                    ->open()
                    . '<div class="mb-1">'
                    . Button::submit(
                        $translator->translate('logout', ['login' => Html::encode(null !== $user ? $user->getLogin() : '')]),
                    )
                    ->class('btn btn-xs btn-danger')
                    . '</div>'
                    . Form::tag()->close(),
            encodeLabel: false,
        ),
    )
    ->styles(NavStyle::NAVBAR) : '';
?> 
        <?= NavBar::end() ?>
    </header>

    <main class="container py-3">
        <?=
    /**
     * Related logic: see ./resources/views/site/index.php
     */
    $content
?>
    </main>

    <footer class='mt-auto bg-dark py-3'>
        <div class = 'd-flex flex-fill align-items-center container-fluid'>
            <div class = 'd-flex flex-fill float-start'>
                <i class=''></i>
                <a class='text-decoration-none' href='<?= $companyWeb ?>' target='_blank' rel='noopener'>
                   <?= $brandLabel; ?> - <?= date('Y'); ?> -
                </a>
                <div class="ms-2 text-white">
                    <?= PerformanceMetrics::widget() ?>
                </div>
            </div>

            <div class='float-end'>
                <a class='text-decoration-none px-1' href='<?= $companyWeb ?>' target='_blank' rel='noopener' >
                    <i class="bi bi-github text-white"></i>
                </a>
                <a class='text-decoration-none px-1' href='<?= $companySlack ?>' _blank' rel='noopener'>
                    <i class="bi bi-slack text-white"></i>
                </a>
                <a class='text-decoration-none px-1' href='<?= $companyFaceBook ?>' target='_blank' rel='noopener'>
                    <i class="bi bi-facebook text-white"></i>
                </a>
                <a class='text-decoration-none px-1' href='<?= $companyTwitter ?>' target='_blank' rel='noopener'>
                    <i class="bi bi-twitter text-white"></i>
                </a>
                <a class='text-decoration-none px-1' href='<?= $companyWhatsApp ?>' target='_blank' rel='noopener'>
                    <i class="bi bi-whatsapp text-white"></i>
                </a>
                <a class='text-decoration-none px-1' href='<?= $companyLinkedIn ?>' target='_blank' rel='noopener'>
                    <i class="bi bi-linkedin text-white"></i>
                </a> 
            </div>
        </div>
    </footer>
    
    <?php $this->endBody() ?>
    </body>
    </html>
<?php
$this->endPage(true);
