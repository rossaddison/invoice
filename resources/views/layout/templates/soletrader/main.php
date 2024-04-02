<?php

declare(strict_types=1);

use App\Asset\AppAsset;
use App\User\User;
use App\Widget\PerformanceMetrics;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\Bootstrap5\Nav;
use Yiisoft\Yii\Bootstrap5\NavBar;

/**
 * @var UrlGeneratorInterface $urlGenerator
 * @var CurrentRoute          $currentRoute
 * @var WebView               $this
 * @var AssetManager          $assetManager
 * @var string                $content
 *
 * @see ..\config\common\params.php 
 *         'yiisoft/yii-view' => [
 *             'viewPath' => '@views',
 *              //'layout' => '@views/layout/main.php',  
 *              'layout' => '@views/layout/templates/soletrader/main.php'
 *          ],
 * @var User|null $user
 * @var string    $csrf
 * @var string    $brandLabel
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
    <html class="h-100" lang="<?= $s->get_setting('cldr');?>">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $this->getTitle() ? Html::encode($this->getTitle()) : '' ?></title>
        <?php $this->head() ?>
    </head>
    <body class="cover-container-fluid d-flex w-100 h-100 mx-auto flex-column">
    <header class="mb-auto">
        <?php $this->beginBody();
              // if a company logo has not been setup in companyprivate => use the site default logo
              $logoPath = ((null!==$companyLogoFileName) 
                                ? '/logo/'. $companyLogoFileName 
                                : '/site/'. $s->public_logo().'.png'
              );  
        ?>
        <?= NavBar::widget()
            ->brandImage($logoPath)
            ->brandImageAttributes(['margin' => 10, 
                                    'width' => 80, 
                                    'height' => 40])    
            ->brandText(str_repeat('&nbsp;', 7).$brandLabel)
            ->brandUrl($urlGenerator->generate('site/index'))
            ->options(['class' => 'navbar navbar-light bg-light navbar-expand-sm text-white'])
            ->begin() ?>

        <?= Nav::widget()
            ->currentPath($currentRoute
                ->getUri()
                ->getPath())
            ->options(['class' => 'navbar-nav'])
            ->items(
                [
                    [
                        'label' => '',
                        'visible' => $s->get_setting('debug_mode') == '1',
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
                        'url' => $urlGenerator->generate('contact/fill'),
                        'visible' => $isGuest,
                        'linkOptions' => ['class' => 'bi bi-person-fill-add text-primary']
                    ],
                    [
                        'label' => str_repeat(' ',1).$translator->translate('i.login'),
                        'url' => $urlGenerator->generate('auth/login'),
                        'visible' => $isGuest,
                        'linkOptions' => ['class' => 'bi bi-door-open-fill text-success']
                    ],
                    [
                        'label' => '',
                        'url' => $urlGenerator->generate('auth/signup'),
                        'visible' => $isGuest,
                        'linkOptions' => [
                            'class' => 'bi bi-person-plus-fill',
                            'data-bs-toggle' => 'tooltip',
                            'title' => str_repeat(' ',1).$translator->translate('i.setup_create_user')
                        ]
                    ],
                    [
                        'label' => str_repeat(' ',1).$translator->translate('i.language'),
                        'url' => '#',
                        'linkOptions' => ['class' => 'bi bi-translate'],
                        'items' => [
                            [
                        'label' => 'Afrikaans',                                
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'af'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Arabic / عربي',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'ar'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'Azerbaijani / Azərbaycan',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'az'], fallbackRouteName: 'site/index'),
                    ], 
                    [
                        'label' => 'Chinese Simplified / 简体中文',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'zh'], fallbackRouteName: 'site/index'),
                    ],
                    [
                        'label' => 'English',
                        'url' => $urlGenerator->generateFromCurrent(['_language' => 'en'], fallbackRouteName: 'site/index'),
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
                        ],
                    ],
                    $isGuest ? '' : Form::tag()
                            ->post($urlGenerator->generate('auth/logout'))
                            ->csrf($csrf)
                            ->open()
                        . '<div class="mb-1">'
                        . Button::submit(
                            $translator->translate('i.logout', ['login' => Html::encode($user->getLogin())])
                        )
                            ->class('btn btn-primary')
                        . '</div>'
                        . Form::tag()->close(),
                ],
            ) ?>
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
