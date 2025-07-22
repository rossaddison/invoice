<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Img;

/**
 * Related logic: see App\Invoice\PaymentInformation\PaymentInformationController function renderPartialAsStringCompanyLogo
 * Related logic: see $src e.g. ? 'public/logo/'.$companyLogoFileName : '/site/logo.png'
 * @var string $src
 */

echo A::tag()
    ->href('')
    ->target('_blank')
    ->content(
        Img::tag()
              ->src($src)
              ->width(280)
              ->height(44)
              ->addAttributes(['border' => 0]),
    )
    ->render();
