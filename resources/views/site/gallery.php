<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\Img;
use Yiisoft\Bootstrap5\Carousel;
use Yiisoft\Bootstrap5\CarouselItem;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */
?>
<?php
$w = 649;
$h = 383;
$divHeight = (string) 500;
?>
<?= Carousel::widget()
    ->items(
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/1.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            active: true,
            caption: '1',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/2.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '2',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/3.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '3',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/4.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '4',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/5.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '5',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/6.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '6',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/7.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '7',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/8.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '8',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/9.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '9',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/10.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '10',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/11.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '11',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/12.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '12',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/13.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '13',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/14.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '14',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
        CarouselItem::to(
            content: '<div class="bg-dark" style="height: ' . $divHeight . 'px; text-align: center"><br>'
                        . Img::tag()
                        ->src('/img/gallery/15.jpeg')
                        ->size($w, $h)
                        ->render()
                    . '</div>',
            caption: '15',
            encodeCaption: false,
            captionAttributes: ['class' => ['d-none', 'd-md-block']],
        ),
    )
    ->render();
?>
