<?php

declare(strict_types=1);

use App\Asset\AppAsset;
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
 * @var Yiisoft\View\WebView  $this
 *
 * @see ..\config\common\params.php 
 *         'yiisoft/yii-view' => [
 *             'viewPath' => '@views',
 *              //'layout' => '@views/layout/main.php',  
 *              'layout' => '@views/layout/templates/soletrader/main.php'
 *          ],
 * @var App\User\User|null $user
 * @var bool $debugMode
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
/**
 * @see ./src/ViewInjection/LayoutViewInjection getLayoutParameters
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
                        'label' => '',
                        'visible' => $debugMode,
                        'linkOptions' => [
                            'class' => 'bi bi-info-circle',
                            'style' => 'font-size: 1rem; color: cornflowerblue;',
                            'data-bs-toggle' => 'tooltip',
                            'title' => '..\invoice\resources\views\layout\templates\soletrader\main.php && config/common/params.php yiisoft/yii-view layouts'
                        ]
                    ],
                    [
                        'label' => str_repeat(' ', 1).$translator->translate('menu.about'),
                        'url' => $urlGenerator->generate('site/about'),
                        'visible' => $isGuest,
                        'linkOptions' => [
                            'class' => 'bi bi-info-circle-fill text-info',
                            'style' => 'font-size: 1rem; color: cornflowerblue;'
                        ]
                    ],
                    [
                        'label' => str_repeat(' ', 1).$translator->translate('menu.gallery'),
                        'url' => $urlGenerator->generate('site/gallery'),
                        'visible' => $isGuest,
                        'linkOptions' => ['class' => 'bi bi-images']
                    ],
                    [
                        'label' => str_repeat(' ', 1).$translator->translate('menu.team'),
                        'url' => $urlGenerator->generate('site/team'),
                        'visible' => $isGuest,
                        'linkOptions' => ['class' => 'bi bi-people-fill']
                    ],
                    [
                        'label' => str_repeat(' ',1).$translator->translate('menu.pricing'),
                        'url' => $urlGenerator->generate('site/pricing'),
                        'visible' => $isGuest,
                        'linkOptions' => ['class' => 'bi bi-tags-fill text-danger']
                    ],
                    [
                        'label' => str_repeat(' ',1).$translator->translate('menu.testimonial'),
                        'url' => $urlGenerator->generate('site/testimonial'),
                        'visible' => $isGuest,
                    ],
                    [
                        'label' => str_repeat(' ',1).$translator->translate('menu.contact.details'),
                        'url' => $urlGenerator->generate('site/contact'),
                        'visible' => $isGuest,
                        'linkOptions' => ['class' => 'bi bi-person-lines-fill text-primary']
                    ],
                    [
                        'label' => str_repeat(' ',1).$translator->translate('menu.contact.us'),
                        'url' => $urlGenerator->generate('contact/interest'),
                        'visible' => $isGuest,
                        'linkOptions' => ['class' => 'bi bi-person-fill-add text-primary']
                    ],
                    [
                        'label' => str_repeat(' ',1).$translator->translate('i.login'),
                        'url' => $urlGenerator->generate('auth/login'),
                        'visible' => $isGuest && !$stopLoggingIn,
                        'linkOptions' => ['class' => 'bi bi-door-open-fill text-success']
                    ],
                    [
                        'label' => '',
                        'url' => $urlGenerator->generate('auth/signup'),
                        'visible' => $isGuest && !$stopSigningUp,
                        'linkOptions' => [
                            'class' => 'bi bi-person-plus-fill',
                            'data-bs-toggle' => 'tooltip',
                            'title' => str_repeat(' ',1).$translator->translate('i.setup_create_user')
                        ]
                    ],
                    [
                        'label' => '',
                        'url' => '#',
                        'linkOptions' => [
                            'class' => 'bi bi-translate',
                            'style' => 'font-size: 1rem; color: cornflowerblue;',
                            'data-bs-toggle' => 'dropdown',
                            'title' => $translator->translate('i.language')
                        ],    
                        'items' => [
                            [
                        'label' => 'Afrikaans South African',                                
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'af-ZA'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Arabic Bahrainian/ عربي',
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
                        'label' => 'Zulu South African/ Zulu South African',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'zu-ZA'], fallbackRouteName: 'site/index'),
                    ],           
                        ],
                    ],
                    $isGuest ? '' : Form::tag()
                            ->post($urlGenerator->generate('auth/logout'))
                            ->csrf($csrf)
                            ->open()
                        . '<div class="mb-1">'
                        . Button::submit(
                            $translator->translate('i.logout', ['login' => Html::encode(null!==$user ? $user->getLogin() : '')])
                        )
                            ->class('btn btn-primary')
                        . '</div>'
                        . Form::tag()->close(),
                ],
            ) : ''; ?>
        <?= NavBar::end() ?>
    </header>

    <main class="container py-3">
        <?= 
            /**
             * @see ./resources/views/site/index.php
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
