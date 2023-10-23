<?php
    declare(strict_types=1); 
    
    use Yiisoft\Html\Html;
    use App\Invoice\Helpers\CountryHelper;
    $countryhelper = new CountryHelper();
?>   

<span class="client-address-street-line">
    <?=($client->getClient_address_1() ? Html::encode($client->getClient_address_1()) . '<br>' : ''); ?>
</span>
<span class="client-address-street-line">
    <?=($client->getClient_address_2() ? Html::encode($client->getClient_address_2()) . '<br>' : ''); ?>
</span>
<span class="client-adress-town-line">
    <?=($client->getClient_city() ? Html::encode($client->getClient_city()) . ' ' : ''); ?>
    <?=($client->getClient_state() ? Html::encode($client->getClient_state()) . ' ' : ''); ?>
    <?=($client->getClient_zip() ? Html::encode($client->getClient_zip()) : ''); ?>
</span>
<span class="client-adress-country-line">
    <?=($client->getClient_country() ? '<br>' . $countryhelper->get_country_name($s->trans('cldr'), $client->getClient_country()) : ''); ?>
</span>
