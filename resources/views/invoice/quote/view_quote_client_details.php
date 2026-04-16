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
        <a href="<?= $urlGenerator->generate('client/view', ['_language' => $_language, 'id' => (int) $quote->getClient()?->reqId()]); ?>">
        <?= Html::encode($clientHelper->formatClient($quote->getClient())); ?>
        </a>
    </h3>
    <br>
    <div id="pre_save_client_id" value="<?php echo $quote->getClient()?->reqId(); ?>" hidden></div>
    <div class="client-address">
        <span class="client-address-street-line-1">
            <?php echo null !== $quote->getClient()?->getClientAddress1() ? Html::encode($quote->getClient()?->getClientAddress1()) . '<br>' : ''; ?>
        </span>
        <span class="client-address-street-line-2">
            <?php echo null !== $quote->getClient()?->getClientAddress2() ? Html::encode($quote->getClient()?->getClientAddress2()) . '<br>' : ''; ?>
        </span>
        <span class="client-address-town-line">
            <?php echo null !== $quote->getClient()?->getClientCity() ? Html::encode($quote->getClient()?->getClientCity()) . '<br>' : ''; ?>
            <?php echo null !== $quote->getClient()?->getClientState() ? Html::encode($quote->getClient()?->getClientState()) . '<br>' : ''; ?>
            <?php echo null !== $quote->getClient()?->getClientZip() ? Html::encode($quote->getClient()?->getClientZip()) : ''; ?>
        </span>
        <span class="client-address-country-line">
            <?php
                $countryName = $quote->getClient()?->getClientCountry();
                if (null !== $countryName) {
                    echo '<br>' . $countryHelper->getCountryName($translator->translate('cldr'), $countryName);
                } ?>
        </span>
    </div>
    <hr>
    <?php if (null !== $quote->getClient()?->getClientPhone()): ?>
        <div class="client-phone">
            <?= $translator->translate('phone'); ?>:&nbsp;
            <?= Html::encode($quote->getClient()?->getClientPhone()); ?>
        </div>
    <?php endif; ?>
    <?php if (null !== $quote->getClient()?->getClientMobile()): ?>
        <div class="client-mobile">
            <?= $translator->translate('mobile'); ?>:&nbsp;
            <?= Html::encode($quote->getClient()?->getClientMobile()); ?>
        </div>
    <?php endif; ?>
    <?php if (null !== $quote->getClient()?->getClientEmail()): ?>
        <div class='client-email'>
            <?= $translator->translate('email'); ?>:&nbsp;
            <?php echo $quote->getClient()?->getClientEmail(); ?>
        </div>
    <?php endif; ?>
    <br>
</div>
