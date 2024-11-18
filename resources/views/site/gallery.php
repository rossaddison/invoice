<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\Img;
use Yiisoft\Yii\Bootstrap5\Carousel;
use Yiisoft\Yii\Bootstrap5\CarouselItem;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */
?>
<?php
    $w = 649;
    $h = 383;
    $divHeight = 500;    
?>
<?= Carousel::widget()
    ->items(
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/1.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            active: true,
            caption: $translator->translate('gallery.caption.slide1'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/2.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide2'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/3.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide3'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/4.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide4'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),    
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/5.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide5'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),    
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/6.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide6'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/7.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide7'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/8.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide8'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),    
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/9.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide9'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),    
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/10.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide10'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),    
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/11.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide11'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),    
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/12.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide12'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),    
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/13.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide13'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),    
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/14.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide14'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),
        new CarouselItem(
            content:'<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                        Img::tag()
                        ->src('/img/gallery/15.jpeg')
                        ->size($w, $h)
                        ->render().
                    '</div>',
            caption: $translator->translate('gallery.caption.slide15'),
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']]    
        ),    
    )
    ->render();        
?>
