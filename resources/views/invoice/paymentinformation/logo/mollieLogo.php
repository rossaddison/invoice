<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Tag\A;
    use Yiisoft\Html\Tag\Img;
    
    echo A::tag()
        ->href('https://www.mollie.com/gb/resources')
        ->target('_blank')
        ->content(Img::tag()
                  ->src('https://www.mollie.com/wp-content/uploads/2022/08/og-image-mollie.png')
                  ->width(280)
                  ->height(44)
                  ->addAttributes(['border' => 0])  
        )
        ->render();

