<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * Related logic: see client\view.php and PaymentInformationController function inform search partial_client_address
 * @var App\Invoice\Entity\Client $client
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

?>   

<span class="client-address-street-line">
    <?php echo strlen($client->getClient_address_1() ?? '') > 0 ? Html::encode($client->getClient_address_1()).'<br>' : ''; ?>
</span>
<span class="client-address-street-line">
    <?php echo strlen($client->getClient_address_2() ?? '') > 0 ? Html::encode($client->getClient_address_2()).'<br>' : ''; ?>
</span>
<span class="client-adress-town-line">
    <?php echo strlen($client->getClient_city() ?? '')  > 0 ? Html::encode($client->getClient_city()).' ' : ''; ?>
    <?php echo strlen($client->getClient_state() ?? '') > 0 ? Html::encode($client->getClient_state()).' ' : ''; ?>
    <?php echo strlen($client->getClient_zip() ?? '')   > 0 ? Html::encode($client->getClient_zip()) : ''; ?>
</span>
<span class="client-adress-country-line">
    <?php echo strlen($clientCountry = $client->getClient_country() ?? '') > 0 ? '<br>'.$countryHelper->get_country_name($translator->translate('cldr'), $clientCountry) : ''; ?>
</span>
