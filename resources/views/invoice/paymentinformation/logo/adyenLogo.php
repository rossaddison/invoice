<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\A;

// No verified local asset or exact badge-image URL for Adyen (unlike the
// Braintree/Mollie badges, which link real, confirmed CDN images) — a plain
// text link to Adyen's own site avoids guessing an image URL that might not
// exist.
echo  new A()
    ->href('https://www.adyen.com')
    ->target('_blank')
    ->addAttributes(['class' => 'text-muted small text-decoration-none'])
    ->content('Powered by Adyen')
    ->render();
