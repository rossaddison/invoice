<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Carousel;
use Yiisoft\Bootstrap5\CarouselItem;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Img;

/**
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */
$tooltipTitle = $translator->translate('home.caption.slides.location.debug.mode');
$w            = 150;
$h            = 75;
$divHeight    = (string) 250;
?>

<?php echo Html::openTag('div', ['class' => 'container mt-5']); ?>
    <?php echo Html::openTag('div', ['class' => 'row']); ?>
        <?php echo Html::openTag('div', ['class' => 'col-sm-4']); ?>
                <?php echo Html::openTag('h3'); ?>
                    OAuth2.0 Client Development
                <?php echo Html::closeTag('h3'); ?>
                <?php echo Html::openTag('p'); ?>
                    This is a development website using the Yii3 Framework.
                <?php echo Html::closeTag('p'); ?>
                <?php echo Html::openTag('p'); ?>
                    Currently I am assisting with the development of OAuth 2.0 Clients and the repository in question is available at ➡ 
                <?php echo Html::closeTag('p'); ?>
                <?php echo Html::openTag('p'); ?>
                <?php echo A::tag()->content('https://github.com/rossaddison/yii-auth-client')->href('https://github.com/rossaddison/yii-auth-client')->render(); ?>    
                <?php echo Html::closeTag('p'); ?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'col-sm-4']); ?>
                <?php echo Html::openTag('h3'); ?>
                    Invoicing System Development 
                <?php echo Html::closeTag('h3'); ?>
                <?php echo Html::openTag('p'); ?>
                    Currently I am developing a php based invoicing system using the Yii3 Framework and the opensource code is available at ➡️ 
                <?php echo Html::closeTag('p'); ?>
                <?php echo Html::openTag('p'); ?>
                <?php echo A::tag()->content('https://github.com/rossaddison/invoice')->href('https://github.com/rossaddison/invoice')->render(); ?>
                <?php echo Html::closeTag('p'); ?>
                <?php echo Html::openTag('p'); ?>
                    This site will be used to demo quote and invoice creation at a future date.
                <?php echo Html::closeTag('p'); ?>
        <?php echo Html::closeTag('div'); ?> 
        <?php echo Html::openTag('div', ['class' => 'col-sm-4']); ?>
                <?php echo Html::openTag('h3'); ?>                    
                    Yii3 Demo
                <?php echo Html::closeTag('h3'); ?>
                <?php echo Html::openTag('p'); ?>
                    The Invoicing System uses a structure similar to the Yii3 Demo available at ➡️
                <?php echo Html::closeTag('p'); ?>
                <?php echo Html::openTag('p'); ?>
                <?php echo A::tag()->content('https://github.com/yiisoft/demo')->href('https://github.com/yiisoft/demo')->render(); ?>
                <?php echo Html::closeTag('p'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
<?php echo Html::openTag('div'); ?>
                        
<?php if ('0' == $s->getSetting('no_front_site_slider_page')) { ?>
    <?php echo Html::openTag('header'); ?>
        <?php echo ('1' == $s->getSetting('debug_mode'))
            ? Html::openTag('a', ['data-bs-toggle' => 'tooltip', 'title' => $tooltipTitle]).
                    Html::openTag('i', ['class' => 'bi bi-info-circle']).
                    Html::closeTag('i').
                Html::closeTag('a')
            : ''; ?>
    <?php echo Html::closeTag('header'); ?>

    <?php echo Html::openTag('div', ['class' => 'container-fluid p-5 bg-primary text-white text-center']); ?>
        <?php echo Html::openTag('h1'); ?>
                yiisoft/demo/blog
        <?php echo Html::closeTag('h1'); ?>
    <?php echo Html::closeTag('div'); ?>

    <?php echo Carousel::widget()
        ->items(
            CarouselItem::to(
                content: '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                                ->src('/img/step1.jpg')
                                ->size($w, $h)
                                ->render().
                        '</div>',
                active: true,
                caption: $translator->translate('home.caption.slide1'),
                encodeCaption: false,
                captionAttributes: ['class' => ['d-none', 'd-md-block']],
            ),
            CarouselItem::to(
                content: '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                                ->src('/img/step2.jpg')
                                ->size($w, $h)
                                ->render().
                        '</div>',
                caption: $translator->translate('home.caption.slide2'),
                encodeCaption: true,
                captionAttributes: ['class' => ['d-none', 'd-md-block']],
            ),
            CarouselItem::to(
                content: '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                                ->src('/img/step3.jpg')
                                ->size($w, $h)
                                ->render().
                        '</div>',
                caption: $translator->translate('home.caption.slide3'),
                encodeCaption: true,
                captionAttributes: ['class' => ['d-none', 'd-md-block']],
            ),
        )
        ->render();
    ?>    
<?php } ?>


