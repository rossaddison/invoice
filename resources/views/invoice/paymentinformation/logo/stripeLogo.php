<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Img;

echo  new A()
    ->href('https://stripe.com')
    ->target('_blank')
    ->content(
        new Img()
              ->src('/img/stripe.png')
              ->width(75)
              ->height(50)
              ->addAttributes(['border' => 0]),
    )
    ->render();
