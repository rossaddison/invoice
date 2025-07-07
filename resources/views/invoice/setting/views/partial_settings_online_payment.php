<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Libraries\Crypt $crypt
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 * @var array $gateway_drivers
 * @var array $gateway_currency_codes
 * @var array $gateway_regions
 * @var array $payment_methods
 */
?>
<?= Html::openTag('div', ['class' => 'row']); ?>
<div class="col-xs-12 col-md-8 col-md-offset-2">
<div class="panel panel-default">
    <div class="panel-heading">
        <?= $translator->translate('online.payments'); ?>
    </div>
    <div class="panel-body">
        <div class="form-group">
            <div class="checkbox">
                <?php $body['settings[enable_online_payments]'] = $s->getSetting('enable_online_payments');?>
                <label>
                    <input type="hidden" name="settings[enable_online_payments]" value="0">
                    <input type="checkbox" name="settings[enable_online_payments]" value="1"
                        <?php $s->check_select($body['settings[enable_online_payments]'], 1, '==', true) ?>>
                    <?= $translator->translate('enable.online.payments'); ?>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="online-payment-select">
                <?= $translator->translate('add.payment.provider'); ?>
            </label>
            <select id="online-payment-select" class="form-control">
                <option value=""><?= $translator->translate('none'); ?></option>
                <?php
                    /**
                     * @var string $driver
                     * @var array $fields
                     */
                    foreach ($gateway_drivers as $driver => $fields) {
                        $d = strtolower($driver);
                        ?>
                    <option value="<?= $d; ?>">
                        <?= ucwords(str_replace('_', ' ', $driver)); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

    </div>
</div>

<?php

/**
 * @var string $driver
 * @var array $fields
 */
foreach ($gateway_drivers as $driver => $fields) :
    $d = strtolower($driver);
    ?>
    <div id="gateway-settings-<?= $d; ?>"
        class="gateway-settings panel panel-default <?= $s->getSetting('gateway_' . $d . '_enabled') ? 'active-gateway' : 'hidden'; ?>">

        <div class="panel-heading">
            <?= ucwords(str_replace('_', ' ', $driver)); ?>
            <div class="pull-right">
                <div class="checkbox no-margin">
                    <label>
                        <?php $body['settings[gateway_' . $d . '_enabled]'] = $s->getSetting('gateway_' . $d . '_enabled');?>
                        <input type="hidden" name="settings[gateway_<?= $d; ?>_enabled]" value="0">
                        <input type="checkbox" name="settings[gateway_<?= $d; ?>_enabled]" value="1"
                            id="settings[gateway_<?= $d; ?>_enabled]"
                            <?php $s->check_select($body['settings[gateway_' . $d . '_enabled]'], 1, '==', true) ?>>
                        <?= $translator->translate('enabled'); ?>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="panel-body small">
          
                <?php
                    /**
                     * @var string $key
                     * @var array $setting
                     * @var string $setting['label']
                     * @var string $setting['password']
                     * @var string $setting['type']
                     */
                    foreach ($fields as $key => $setting) { ?>
                <?php $body['settings[gateway_' . $d . '_'.$key.']'] = $s->getSetting('gateway_' . $d . '_' . $key);?>
                <?php if ($setting['type'] == 'checkbox') : ?>

                    <div class="checkbox">
                        <label>                                    
                            <input type="hidden" name="settings[gateway_<?= $d; ?>_<?= $key ?>]"
                                value="0">
                            <input type="checkbox" name="settings[gateway_<?= $d; ?>_<?= $key ?>]"
                                value="1" <?php $s->check_select($body['settings[gateway_' . $d . '_'.$key.']'], 1, '==', true) ?>>
                            <?= $setting['label']; ?>
                        </label>
                    </div>
            
                <?php else : ?>

                    <div class="form-group">
                        <label for="settings[gateway_<?= $d; ?>_<?= $key ?>]">
                            <?= $translator->translate('online.payment.' . $key);?>
                        </label>
                                <input type="<?= $setting['type']; ?>" class="input-sm form-control"
                            name="settings[gateway_<?= $d; ?>_<?= $key ?>]"
                            id="settings[gateway_<?= $d; ?>_<?= $key ?>]" 
                                    <?php
                                        if ($setting['type'] == 'password') : ?>
                                        value="<?= (string)(strlen((string)$body['settings[gateway_' . $d . '_'.$key.']']) > 0
                                                ? $crypt->decode((string)$body['settings[gateway_' . $d . '_'.$key.']'])
                                                : ''); ?>"
                                    <?php else : ?>
                                        value="<?= (string)$body['settings[gateway_' . $d . '_'.$key.']']; ?>"
                                    <?php endif; ?>
                                >
                        <?php if ($setting['type'] == 'password') : ?>
                            <input type="hidden" value="1"
                                name="settings[gateway_<?= $d . '_' . $key ?>_field_is_password]">
                        <?php endif; ?>
                    </div>

                <?php endif; ?>
            <?php } ?>

            <hr>
            
            <?php
            // regions are specific to Amazon Pay
            if ($d == 'amazon_pay') { ?>
            <div class="form-group">
                <label for="settings[gateway_<?= $d; ?>_region]">
                    <?= $translator->translate('online.payment.region'); ?>
                </label>
                <?php $body['settings[gateway_' . $d . '_region]'] = $s->getSetting('gateway_' . $d . '_region');?>
                <select name="settings[gateway_<?= $d; ?>_region]"
                    id="settings[gateway_<?= $d; ?>_region]"
                    class="input-sm form-control">
                    <?php
                        /**
                         * @var string $val
                         * @var string $key
                         */
                        foreach ($gateway_regions as $val => $key) { ?>
                        <option value="<?= $val; ?>"
                            <?php $s->check_select($body['settings[gateway_' . $d . '_region]'], $val); ?>>
                            <?= $val; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <?php } ?>

            <div class="form-group">
                <label for="settings[gateway_<?= $d; ?>_currency]">
                    <?= $translator->translate('currency'); ?>
                </label>
                <?php $body['settings[gateway_' . $d . '_currency]'] = $s->getSetting('gateway_' . $d . '_currency');?>
                <select name="settings[gateway_<?= $d; ?>_currency]"
                    id="settings[gateway_<?= $d; ?>_currency]"
                    class="input-sm form-control">
                    <?php
                        /**
                         * @var string $val
                         * @var string $key
                         */
                        foreach ($gateway_currency_codes as $val => $key) { ?>
                        <option value="<?= $val; ?>"
                            <?php $s->check_select($body['settings[gateway_' . $d . '_currency]'], $val); ?>>
                            <?= $val; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            
            <?php if ($d == 'mollie') { ?>
                <div class="form-group">
                <label for="settings[gateway_<?= $d; ?>_locale]">
                    <?= $translator->translate('payment.gateway.default.locale'); ?>
                </label>
                <?php $body['settings[gateway_' . $d . '_locale]'] = $s->getSetting('gateway_' . $d . '_locale');?>
                <select name="settings[gateway_<?= $d; ?>_locale]"
                    id="settings[gateway_<?= $d; ?>_locale]"
                    class="input-sm form-control">
                    <?php
                        $locales = $s->mollieSupportedLocaleArray();
                /**
                 * @var array $locales
                 * @var string $key
                 * @var string $value
                 */
                foreach ($locales as $key => $value) { ?>
                        <option value="<?= $value; ?>"
                            <?php $s->check_select($body['settings[gateway_mollie_locale]'], $value); ?>>
                            <?= $value; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <?php } ?>
            
            <div class="form-group">
                <label for="settings[gateway_<?= $d; ?>_payment_method]">
                    <?= $translator->translate('online.payment.method'); ?>
                </label>
                <?php $body['settings[gateway_' . $d . '_payment_method]'] = $s->getSetting('gateway_' . $d . '_payment_method');?>
                <select name="settings[gateway_<?= $d; ?>_payment_method]"
                    id="settings[gateway_<?= $d; ?>_payment_method]"
                    class="input-sm form-control">
                    <?php
                /**
                 * @var App\Invoice\Entity\PaymentMethod $payment_method
                 */
                foreach ($payment_methods as $payment_method) { ?>
                        <option value="<?= $payment_method->getId(); ?>"
                            <?php $s->check_select($body['settings[gateway_' . $d . '_payment_method]'], $payment_method->getId()) ?>>
                            <?= $payment_method->getName(); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

        </div>

    </div>
<?php endforeach; ?>

</div>
<?= Html::closeTag('div'); ?>
