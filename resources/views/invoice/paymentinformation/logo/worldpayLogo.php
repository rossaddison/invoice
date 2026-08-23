<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\A;

// No verified local asset or exact badge-image URL for Worldpay (same
// reasoning as adyenLogo.php) — a plain text link avoids guessing an
// image URL that might not exist.
echo  new A()
    ->href('https://developer.worldpay.com')
    ->target('_blank')
    ->addAttributes(['class' => 'text-muted small text-decoration-none'])
    ->content('Powered by Worldpay')
    ->render();
