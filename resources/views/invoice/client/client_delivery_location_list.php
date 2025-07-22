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
     * @var App\Invoice\Entity\DeliveryLocation $delivery_location
     */
    foreach ($locations as $delivery_location) { ?>
    <span class="client-address-street-line">
        <?=(strlen($deliveryAddress1 = $delivery_location->getAddress_1() ?? '') > 0 ? Html::encode($deliveryAddress1) . '<br>' : ''); ?>
    </span>
    <span class="client-address-street-line">
        <?=(strlen($deliveryAddress2 = $delivery_location->getAddress_2() ?? '') > 0 ? Html::encode($deliveryAddress2) . '<br>' : ''); ?>
    </span>
    <span class="client-adress-town-line">
        <?=(strlen($deliveryCity = $delivery_location->getCity() ?? '') > 0 ? Html::encode($deliveryCity) . ' ' : ''); ?>
        <?=(strlen($deliveryState = $delivery_location->getState() ?? '') > 0 ? Html::encode($deliveryState) . ' ' : ''); ?>
        <?=(strlen($deliveryZip = $delivery_location->getZip() ?? '') > 0 ? Html::encode($deliveryZip) : ''); ?>
    </span>
    <span class="client-adress-country-line">
        <?=(strlen($deliveryCountry = $delivery_location->getCountry() ?? '') > 0 ? '<br>' . $countryHelper->get_country_name($translator->translate('cldr'), $deliveryCountry) : ''); ?>
    </span>
    <br>
    <br>
<?php } ?>
