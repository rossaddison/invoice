<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Yii\Bootstrap5\Carousel;

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
<?= 
    Carousel::widget()
    ->items([
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/1.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide1'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/2.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide2'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/3.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide3'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/4.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide4'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/5.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide5'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/6.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide6'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/7.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide7'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/8.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide8'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/9.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide9'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/10.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide10'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/11.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide11'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/12.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide12'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/13.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide13'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/14.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide14'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
        [
            'content' => '<div class="bg-dark" style="height: '.$divHeight.'px; text-align: center"><br>'.
                            Img::tag()
                            ->src('/img/gallery/15.jpeg')
                            ->size($w,$h)
                            ->render().
                         '</div>',
            'caption' => $translator->translate('gallery.caption.slide15'),
            'captionOptions' => ['class' => ['d-none', 'd-md-block']],
        ],
    ]);        
?>  
