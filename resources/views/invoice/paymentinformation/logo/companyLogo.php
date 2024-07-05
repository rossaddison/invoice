<?php
    declare(strict_types=1);
    
    /**
     * @see App\Invoice\PaymentInformation\PaymentInformationController function renderPartialAsStringCompanyLogo
     * @var string $src e.g. ? 'public/logo/'.$companyLogoFileName : '/site/logo.png'
     */
    
    use Yiisoft\Html\Tag\A;
    use Yiisoft\Html\Tag\Img;
    
    echo A::tag()
        ->href('')
        ->target('_blank')
        ->content(Img::tag()
                  ->src($src)
                  ->width(280)
                  ->height(44)
                  ->addAttributes(['border' => 0])  
        )
        ->render();

