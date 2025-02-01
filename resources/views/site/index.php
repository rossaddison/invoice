<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Yii\Bootstrap5\Carousel;
use Yiisoft\Yii\Bootstrap5\CarouselItem;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */
    $tooltipTitle = $translator->translate('home.caption.slides.location.debug.mode');
    $w = 150;
    $h = 75;
    $divHeight = 250;
?>

<?= Html::openTag('div', ['class' => 'container mt-5']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'col-sm-4']); ?>
                <?= Html::openTag('h3'); ?>
                    OAuth2.0 Client Development
                <?= Html::closeTag('h3'); ?>
                <?= Html::openTag('p'); ?>
                    This is a development website using the Yii3 Framework.
                <?= Html::closeTag('p'); ?>
                <?= Html::openTag('p'); ?>
                    Currently I am assisting with the development of OAuth 2.0 Clients and the repository in question is available at ➡ 
                <?= Html::closeTag('p'); ?>
                <?= Html::openTag('p'); ?>
                <?= A::tag()->content('https://github.com/rossaddison/yii-auth-client')->href('https://github.com/rossaddison/yii-auth-client')->render(); ?>    
                <?= Html::closeTag('p'); ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'col-sm-4']); ?>
                <?= Html::openTag('h3'); ?>
                    Invoicing System Development 
                <?= Html::closeTag('h3'); ?>
                <?= Html::openTag('p'); ?>
                    Currently I am developing a php based invoicing system using the Yii3 Framework and the opensource code is available at ➡️ 
                <?= Html::closeTag('p'); ?>
                <?= Html::openTag('p'); ?>
                <?= A::tag()->content('https://github.com/rossaddison/invoice')->href('https://github.com/rossaddison/invoice')->render(); ?>
                <?= Html::closeTag('p'); ?>
                <?= Html::openTag('p'); ?>
                    This site will be used to demo quote and invoice creation at a future date.
                <?= Html::closeTag('p'); ?>
        <?= Html::closeTag('div'); ?> 
        <?= Html::openTag('div', ['class' => 'col-sm-4']); ?>
                <?= Html::openTag('h3'); ?>                    
                    Yii3 Demo
                <?= Html::closeTag('h3'); ?>
                <?= Html::openTag('p'); ?>
                    The Invoicing System uses a structure similar to the Yii3 Demo available at ➡️
                <?= Html::closeTag('p'); ?>
                <?= Html::openTag('p'); ?>
                <?= A::tag()->content('https://github.com/yiisoft/demo')->href('https://github.com/yiisoft/demo')->render(); ?>
                <?= Html::closeTag('p'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
<?= Html::openTag('div'); ?>
                        
<?php if ($s->getSetting('no_front_site_slider_page') == '0') { ?>
    <?= Html::openTag('header'); ?>
        <?= ($s->getSetting('debug_mode') == '1') 
            ?   Html::openTag('a', ['data-bs-toggle' => 'tooltip', 'title' => $tooltipTitle]).
                    Html::openTag('i', ['class' => 'bi bi-info-circle']).           
                    Html::closeTag('i').
                Html::closeTag('a')
            : ''; ?>
    <?= Html::closeTag('header'); ?>

    <?= Html::openTag('div', ['class' => 'container-fluid p-5 bg-primary text-white text-center']); ?>
        <?= Html::openTag('h1'); ?>
                yiisoft/demo/blog
        <?= Html::closeTag('h1'); ?>
    <?= Html::closeTag('div'); ?>

    <?= Carousel::widget()
        ->items(
            CarouselItem::to(
                content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/step1.jpg')
                            ->size($w, $h)
                            ->render().
                        '</div>',
                active: true,
                caption: $translator->translate('home.caption.slide1'),
                encodeCaption: false,
                captionAttributes: ['class' => ['d-none', 'd-md-block']]    
            ),
            CarouselItem::to(
                content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/step2.jpg')
                            ->size($w, $h)
                            ->render().
                        '</div>',
                caption: $translator->translate('home.caption.slide2'),
                encodeCaption: true,
                captionAttributes: ['class' => ['d-none', 'd-md-block']]    
            ),
            CarouselItem::to(
                content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/step3.jpg')
                            ->size($w, $h)
                            ->render().
                        '</div>',
                caption: $translator->translate('home.caption.slide3'),
                encodeCaption: true,
                captionAttributes: ['class' => ['d-none', 'd-md-block']]    
            )
        )
        ->render();        
    ?>    
<?php } ?>


