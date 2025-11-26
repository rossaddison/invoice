<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

use App\Widget\LabelSwitch;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Helpers\ClientHelper $clientHelper 
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var App\Invoice\Setting\SettingRepository $s 
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $_language
 */

?>

<div class="col-xs-12 col-sm-6 col-md-5">
    <h3>
        <a href="<?= $urlGenerator->generate('client/view', ['_language' => $_language, 'id' => (int) $quote->getClient()?->getClient_id()]); ?>">
        <?= Html::encode($clientHelper->format_client($quote->getClient())); ?>
        </a>
    </h3>
    <br>
    <div id="pre_save_client_id" value="<?php echo $quote->getClient()?->getClient_id(); ?>" hidden></div>
    <div class="client-address">
        <span class="client-address-street-line-1">
            <?php echo null !== $quote->getClient()?->getClient_address_1() ? Html::encode($quote->getClient()?->getClient_address_1()) . '<br>' : ''; ?>
        </span>
        <span class="client-address-street-line-2">
            <?php echo null !== $quote->getClient()?->getClient_address_2() ? Html::encode($quote->getClient()?->getClient_address_2()) . '<br>' : ''; ?>
        </span>
        <span class="client-address-town-line">
            <?php echo null !== $quote->getClient()?->getClient_city() ? Html::encode($quote->getClient()?->getClient_city()) . '<br>' : ''; ?>
            <?php echo null !== $quote->getClient()?->getClient_state() ? Html::encode($quote->getClient()?->getClient_state()) . '<br>' : ''; ?>
            <?php echo null !== $quote->getClient()?->getClient_zip() ? Html::encode($quote->getClient()?->getClient_zip()) : ''; ?>
        </span>
        <span class="client-address-country-line">
            <?php
                $countryName = $quote->getClient()?->getClient_country();
                if (null !== $countryName) {
                    echo '<br>' . $countryHelper->get_country_name($translator->translate('cldr'), $countryName);
                } ?>
        </span>
    </div>
    <hr>
    <?php if (null !== $quote->getClient()?->getClient_phone()): ?>
        <div class="client-phone">
            <?= $translator->translate('phone'); ?>:&nbsp;
            <?= Html::encode($quote->getClient()?->getClient_phone()); ?>
        </div>
    <?php endif; ?>
    <?php if (null !== $quote->getClient()?->getClient_mobile()): ?>
        <div class="client-mobile">
            <?= $translator->translate('mobile'); ?>:&nbsp;
            <?= Html::encode($quote->getClient()?->getClient_mobile()); ?>
        </div>
    <?php endif; ?>
    <?php if (null !== $quote->getClient()?->getClient_email()): ?>
        <div class='client-email'>
            <?= $translator->translate('email'); ?>:&nbsp;
            <?php echo $quote->getClient()?->getClient_email(); ?>
        </div>
    <?php endif; ?>
    <br>
</div>
