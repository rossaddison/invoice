<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
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
            new CarouselItem(
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
            new CarouselItem(
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
            new CarouselItem(
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

    <?= Html::openTag('div', ['class' => 'container mt-5']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'col-sm-4']); ?>
                    <?= Html::openTag('h3'); ?>
                        Column 1
                    <?= Html::closeTag('h3'); ?>
                    <?= Html::openTag('p'); ?>
                        Lorem ipsum dolor sit amet, consectetur adipisicing elit...
                    <?= Html::closeTag('p'); ?>
                    <?= Html::openTag('p'); ?>
                        Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris...    
                    <?= Html::closeTag('p'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col-sm-4']); ?>
                    <?= Html::openTag('h3'); ?>
                        Column 1
                    <?= Html::closeTag('h3'); ?>
                    <?= Html::openTag('p'); ?>
                        Lorem ipsum dolor sit amet, consectetur adipisicing elit...
                    <?= Html::closeTag('p'); ?>
                    <?= Html::openTag('p'); ?>
                        Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris...    
                    <?= Html::closeTag('p'); ?>
            <?= Html::closeTag('div'); ?> 
            <?= Html::openTag('div', ['class' => 'col-sm-4']); ?>
                    <?= Html::openTag('h3'); ?>
                        Column 1
                    <?= Html::closeTag('h3'); ?>
                    <?= Html::openTag('p'); ?>
                        Lorem ipsum dolor sit amet, consectetur adipisicing elit...
                    <?= Html::closeTag('p'); ?>
                    <?= Html::openTag('p'); ?>
                        Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris...    
                    <?= Html::closeTag('p'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div'); ?>
    <?= Html::openTag('div'); ?>
<?php } ?>


