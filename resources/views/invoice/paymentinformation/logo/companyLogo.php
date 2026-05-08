<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Img;

/**
 * Related logic: see App\Invoice\PaymentInformation\PaymentInformationController function renderPartialAsStringCompanyLogo
 * Related logic: see $src e.g. ? 'public/logo/'.$companyLogoFileName : '/site/logo.png'
 * @var string $src
 * @var int $logoHeight
 * @var int $logoWidth
 * @var int $logoMargin
 */

echo  new A()
    ->href('')
    ->target('_blank')
    ->content(
         new Img()
              ->src($src)
              ->width($logoWidth ?: 280)
              ->height($logoHeight ?: 44)
              ->addAttributes(['border' => 0, 'margin' => $logoMargin ?: 0]),
    )
    ->render();
