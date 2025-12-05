<?php

declare(strict_types=1);

use App\Asset\AppAsset;
use App\User\User;
use App\Widget\PerformanceMetrics;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Label;
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
 * Related logic: see Note: This layout is not currently being used. Refer to soletrader/main.php
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 *
 * Related logic: see \App\ApplicationViewInjection
 *
 * @var User|null $user
 * @var bool $debugMode
 * @var bool $stopLoggingIn
 * @var bool $stopSigningUp
 * @var string $brandLabel
 * @var string $companyLogoHeight
 * @var string $companyLogoMargin
 * @var string $companyLogoWidth
 * @var string $content
 * @var string $csrf
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
$assetManager->register(AppAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$currentRouteName = $currentRoute->getName() ?? '';
$isGuest = $user === null || $user->getId() === null;
$session->set('_language', $currentRoute->getArgument('_language'));
$this->beginPage();
?>
    <!DOCTYPE html>
    <html class="h-100" lang="<?= $currentRoute->getArgument('_language') ?? 'en'; ?>">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Yii3-i<?= $this->getTitle() ? ' - ' . Html::encode($this->getTitle()) : '' ?></title>
        <?php $this->head() ?>
    </head>
    <body class="cover-container-fluid d-flex w-100 h-100 mx-auto flex-column">
    <header class="mb-auto">
        <?php $this->beginBody(); ?>
        <?php echo NavBar::widget()
            ->addAttributes([])
            ->addClass('navbar navbar-light bg-light navbar-expand-sm text-white')
            ->brandImage($logoPath)
            ->brandImageAttributes(['margin' => $companyLogoMargin,
                'width' => $companyLogoWidth,
                'height' => $companyLogoHeight])
            ->brandText(str_repeat('&nbsp;', 7) . $brandLabel)
            ->brandUrl($urlGenerator->generate('site/index'))
            ->class()
            ->container(false)
            ->containerAttributes([])
            ->expand(NavBarExpand::LG)
            ->id('navbar')
            //->innerContainerAttributes(['class' => 'container-md'])
            ->placement(NavBarPlacement::STICKY_TOP)
            ->begin();
?>
        <?php
    $currentPath = $currentRoute->getUri()?->getPath();
?> 
        
        <?= null !== $currentPath ? Nav::widget()
    ->items(
        NavLink::to(
            Label::tag()
            ->attributes(['class' => 'bi bi-door-open-fill text-success'])
            ->content(),
            $urlGenerator->generate('auth/login'),
            $isGuest && !$stopLoggingIn,
            !$isGuest && $stopLoggingIn,
            false,
        ),
        NavLink::to(
            Label::tag()
            ->attributes([
                'class' => 'bi bi-person-plus-fill',
                'data-bs-toggle' => 'tooltip',
                'title' => str_repeat(' ', 1) . $translator->translate('setup.create.user'),
            ])
            ->content(),
            $urlGenerator->generate('auth/signup'),
            $isGuest && !$stopSigningUp,
            !$isGuest && $stopSigningUp,
            false,
        ),
    )
    ->styles(NavStyle::NAVBAR) : '';
?>
        <?= Dropdown::widget()
    ->addClass('bi bi-translate')
    ->addAttributes([
        'style' => 'font-size: 1rem; color: cornflowerblue;',
        'title' => $translator->translate('language'),
        'url' => '#',
    ])
    ->togglerVariant(ButtonVariant::INFO)
    ->togglerContent('')
    ->togglerSize(ButtonSize::SMALL)
    ->items(
        $afZA, $arBH, $az, $beBY, $bs, $zhCN, $zhTW, $en, 
                $fil, $fr, $gdGB, $haNG, $heIL, $igNG, $nl, $de, $id, $it, $ja, $pl, $ptBR,
                $ru, $sk, $sl, $es, $uk, $uz, $vi, $yoNG, $zuZA
    )->render();
?>
<?=
    $isGuest ? '' : Form::tag()
                    ->post($urlGenerator->generate('auth/logout'))
                    ->csrf($csrf)
                    ->open()
                . '<div class="mb-1">'
                . (string) Button::submit(
                    $translator->translate('logout', ['login' => Html::encode(null !== $user ? preg_replace('/\d+/', '', $user->getLogin()) : '')]),
                )
                    ->class('btn btn-xs btn-warning')
                . '</div>'
                . Form::tag()->close();
?>
        
        <?= NavBar::end() ?>
    </header>

    <main class="container py-3">
        <?= $content ?>
    </main>

    <footer class='mt-auto bg-dark py-3'>
        <div class = 'd-flex flex-fill align-items-center container-fluid'>
            <div class = 'd-flex flex-fill float-start'>
                <i class=''></i>
                <a class='text-decoration-none' href='https://www.yiiframework.com/' target='_blank' rel='noopener'>
                    Yii Framework - <?= date('Y') ?> -
                </a>
                <div class="ms-2 text-white">
                    <?= PerformanceMetrics::widget() ?>
                </div>
            </div>

            <div class='float-end'>
                <a class='text-decoration-none px-1' href='https://github.com/yiisoft' target='_blank' rel='noopener' >
                    <i class="bi bi-github text-white"></i>
                </a>
                <a class='text-decoration-none px-1' href='https://join.slack.com/t/yii/shared_invite/enQtMzQ4MDExMDcyNTk2LTc0NDQ2ZTZhNjkzZDgwYjE4YjZlNGQxZjFmZDBjZTU3NjViMDE4ZTMxNDRkZjVlNmM1ZTA1ODVmZGUwY2U3NDA' target='_blank' rel='noopener'>
                    <i class="bi bi-slack text-white"></i>
                </a>
                <a class='text-decoration-none px-1' href='https://www.facebook.com/groups/yiitalk' target='_blank' rel='noopener'>
                    <i class="bi bi-facebook text-white"></i>
                </a>
                <a class='text-decoration-none px-1' href='https://twitter.com/yiiframework' target='_blank' rel='noopener'>
                    <i class="bi bi-twitter text-white"></i>
                </a>
                <a class='text-decoration-none px-1' href='https://t.me/yii3ru' target='_blank' rel='noopener'>
                    <i class="bi bi-telegram text-white"></i>
                </a>
            </div>
        </div>
    </footer>

    <?php $this->endBody() ?>
    </body>
    </html>
<?php
$this->endPage();
