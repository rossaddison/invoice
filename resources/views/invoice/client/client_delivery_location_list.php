<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $locations
 */

?>
<?php
/**
 * @var App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation $del
 */    
    foreach ($locations as $del) { ?>
    <span class="client-address-street-line">
        <?=(strlen($deliveryAddress1 = $del->getAddress1() ?? '') > 0 ?
            Html::encode($deliveryAddress1) . '<br>' : ''); ?>
    </span>
    <span class="client-address-street-line">
        <?=(strlen($deliveryAddress2 = $del->getAddress2() ?? '') > 0 ?
            Html::encode($deliveryAddress2) . '<br>' : ''); ?>
    </span>
    <span class="client-adress-town-line">
        <?=(strlen($deliveryCity = $del->getCity() ?? '') > 0 ?
            Html::encode($deliveryCity) . ' ' : ''); ?>
        <?=(strlen($deliveryState = $del->getState() ?? '') > 0 ?
            Html::encode($deliveryState) . ' ' : ''); ?>
        <?=(strlen($deliveryZip = $del->getZip() ?? '') > 0 ?
            Html::encode($deliveryZip) : ''); ?>
    </span>
    <span class="client-adress-country-line">
        <?=(strlen($deliveryCountry = $del->getCountry() ?? '') > 0 ?
            '<br>'
            . $countryHelper->getCountryName($translator->translate('cldr'),
                    $deliveryCountry) : ''); ?>
    </span>
    <br>
    <br>
<?php } ?>
