<?php

declare(strict_types=1);

use App\Asset\AppAsset;
use App\User\User;
use App\Widget\PerformanceMetrics;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Yii\Bootstrap5\Nav;
use Yiisoft\Yii\Bootstrap5\NavBar;

/**
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 *
 * @see \App\ApplicationViewInjection
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
        <?= NavBar::widget()
            ->brandImage($logoPath)
            ->brandImageAttributes(['margin' => $companyLogoMargin, 
                                    'width' => $companyLogoWidth, 
                                    'height' => $companyLogoHeight])    
            ->brandText(str_repeat('&nbsp;', 7).$brandLabel)
            ->brandUrl($urlGenerator->generate('site/index'))
            ->options(['class' => 'navbar navbar-light bg-light navbar-expand-sm text-white'])
            ->begin() ?>

        <?php 
            $currentPath = $currentRoute->getUri()?->getPath();
        ?> 
        <?= null!==$currentPath ? Nav::widget()
            ->currentPath($currentPath)
            ->options(['class' => 'navbar-nav'])
            ->items(
                [
                    [
                        'label' => $translator->translate('i.language'),
                        'url' => '#',
                        'items' => [
                            [
                        'label' => 'Afrikaans South African',                                
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'af-ZA'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Arabic Bahrainian / عربي',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'ar-BH'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Azerbaijani / Azərbaycan',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'az'], fallbackRouteName: 'site/index'),
                    ], 
                    [
                        'label' => 'Chinese Simplified / 简体中文',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'zh-CN'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Tiawanese Mandarin / 简体中文',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'zh-TW'], fallbackRouteName: 'site/index'),
                    ],        
                    [
                        'label' => 'English',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'en'], fallbackRouteName: 'site/index'),
                    ], 
                    [
                        'label' => 'Filipino / Filipino',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'fil'], fallbackRouteName: 'site/index'),
                    ],             
                    [
                        'label' => 'French / Français',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'fr'], fallbackRouteName: 'site/index'),
                    ],        
                    [
                        'label' => 'Dutch / Nederlands',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'nl'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'German / Deutsch',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'de'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Indonesian / bahasa Indonesia',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'id'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Italian / Italiano',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'it'], fallbackRouteName: 'site/index'),
                    ],        
                    [
                        'label' => 'Japanese / 日本',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'ja'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Polish / Polski',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'pl'], fallbackRouteName: 'site/index'),
                    ],               
                    [
                        'label' => 'Portugese Brazilian / Português Brasileiro',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'pt-BR'], fallbackRouteName: 'site/index'),
                    ],        
                    [
                        'label' => 'Russian / Русский',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'ru'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Slovakian / Slovenský',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'sk'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Spanish / Española x',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'es'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Ukrainian / українська',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'uk'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Uzbek / o'."'".'zbek',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'uz'], fallbackRouteName: 'site/index'),
                    ],        
                    [
                        'label' => 'Vietnamese / Tiếng Việt',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'vi'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Zulu / Zulu',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'zu-ZA'], fallbackRouteName: 'site/index'),
                    ],         
                        ],
                    ],
                    [
                        'label' => $translator->translate('i.login'),
                        'url' => $urlGenerator->generate('auth/login'),
                        'visible' => $isGuest && !$stopLoggingIn,
                    ],
                    [
                        'label' => $translator->translate('i.setup_create_user'),
                        'url' => $urlGenerator->generate('auth/signup'),
                        'visible' => $isGuest && !$stopSigningUp,
                    ],
                    $isGuest ? '' : Form::tag()
                            ->post($urlGenerator->generate('auth/logout'))
                            ->csrf($csrf)
                            ->open()
                        . '<div class="mb-1">'
                        . Button::submit(
                            $translator->translate('i.logout', ['login' => Html::encode(null!==$user ? preg_replace('/\d+/', '', $user->getLogin()) : '')])
                        )
                            ->class('btn btn-primary')
                        . '</div>'
                        . Form::tag()->close(),
                ],
            ) : ''; ?>
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
