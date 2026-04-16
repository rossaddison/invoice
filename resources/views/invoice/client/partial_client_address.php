<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see client\view.php and PaymentInformationController function inform search partial_client_address
 * @var App\Infrastructure\Persistence\Client\Client $client
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

?>

<span class="client-address-street-line">
    <?=(strlen($client->getClientAddress1() ?? '') > 0 ? Html::encode($client->getClientAddress1()) . '<br>' : ''); ?>
</span>
<span class="client-address-street-line">
    <?=(strlen($client->getClientAddress2() ?? '') > 0 ? Html::encode($client->getClientAddress2()) . '<br>' : ''); ?>
</span>
<span class="client-adress-town-line">
    <?=(strlen($client->getClientCity() ?? '') > 0 ? Html::encode($client->getClientCity()) . ' ' : ''); ?>
    <?=(strlen($client->getClientState() ?? '') > 0 ? Html::encode($client->getClientState()) . ' ' : ''); ?>
    <?=(strlen($client->getClientZip() ?? '') > 0 ? Html::encode($client->getClientZip()) : ''); ?>
</span>
<span class="client-adress-country-line">
    <?=(strlen($clientCountry = $client->getClientCountry() ?? '') > 0 ? '<br>' . $countryHelper->getCountryName($translator->translate('cldr'), $clientCountry) : ''); ?>
</span>
