<?php

declare(strict_types=1);

namespace App\Invoice\Asset\pciAsset;

use App\Invoice\Asset\pciAsset\__Asset;

class stripe_v10_Asset extends __Asset
{   
    public array $css = [    
        // stripe v10 15-11-2022  ./stripe/css/checkout.css
        // @see paymentinformation/form 
        // @see ...views/invoice/paymentinformation/paymentinformation.php
        'stripe/css/checkout.css'
    ];
    
    public array $js = [
        // stripe v10 15-11-2022
        '//js.stripe.com/v3/',
    ];
}
