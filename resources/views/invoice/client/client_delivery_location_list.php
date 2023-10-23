<?php
    declare(strict_types=1); 
    
    use Yiisoft\Html\Html;
    use App\Invoice\Helpers\CountryHelper;
    $countryhelper = new CountryHelper();
?>   
<?php foreach ($locations as $delivery_location) { ?>
    <span class="client-address-street-line">
        <?=($delivery_location->getAddress_1() ? Html::encode($delivery_location->getAddress_1()) . '<br>' : ''); ?>
    </span>
    <span class="client-address-street-line">
        <?=($delivery_location->getAddress_2() ? Html::encode($delivery_location->getAddress_2()) . '<br>' : ''); ?>
    </span>
    <span class="client-adress-town-line">
        <?=($delivery_location->getCity() ? Html::encode($delivery_location->getCity()) . ' ' : ''); ?>
        <?=($delivery_location->getState() ? Html::encode($delivery_location->getState()) . ' ' : ''); ?>
        <?=($delivery_location->getZip() ? Html::encode($delivery_location->getZip()) : ''); ?>
    </span>
    <span class="client-adress-country-line">
        <?=($delivery_location->getCountry() ? '<br>' . $countryhelper->get_country_name($s->trans('cldr'), $delivery_location->getCountry()) : ''); ?>
    </span>
    <br>
    <br>
<?php } ?>
