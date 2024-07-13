<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Tag\A;
    use Yiisoft\Html\Tag\Img;
    
    /**
     * @link https://www.braintreepayments.com/gb/badge
     * @var string $merchantId
     */
    
    echo A::tag()
        ->href('https://www.braintreegateway.com/merchants/'. $merchantId. '/verified')
        ->target('_blank')
        ->content(Img::tag()
                  ->src('https://s3.amazonaws.com/braintree-badges/braintree-badge-wide-dark.png')
                  ->width(280)
                  ->height(44)
                  ->addAttributes(['border' => 0])  
        )
        ->render();

