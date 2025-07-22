<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/*
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
<?php echo Html::openTag('div', ['class' => 'row']); ?>
<div class="col-xs-12 col-md-8 col-md-offset-2">
<div class="panel panel-default">
    <div class="panel-heading">
        <?php echo $translator->translate('online.payments'); ?>
    </div>
    <div class="panel-body">
        <div class="form-group">
            <div class="checkbox">
                <?php $body['settings[enable_online_payments]'] = $s->getSetting('enable_online_payments'); ?>
                <label>
                    <input type="hidden" name="settings[enable_online_payments]" value="0">
                    <input type="checkbox" name="settings[enable_online_payments]" value="1"
                        <?php $s->check_select($body['settings[enable_online_payments]'], 1, '==', true); ?>>
                    <?php echo $translator->translate('enable.online.payments'); ?>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="online-payment-select">
                <?php echo $translator->translate('add.payment.provider'); ?>
            </label>
            <select id="online-payment-select" class="form-control">
                <option value=""><?php echo $translator->translate('none'); ?></option>
                <?php
                    /**
                     * @var string $driver
                     * @var array  $fields
                     */
                    foreach ($gateway_drivers as $driver => $fields) {
                        $d = strtolower($driver);
                        ?>
                    <option value="<?php echo $d; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $driver)); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

    </div>
</div>

<?php

/**
 * @var string $driver
 * @var array  $fields
 */
foreach ($gateway_drivers as $driver => $fields) {
    $d = strtolower($driver);
    ?>
    <div id="gateway-settings-<?php echo $d; ?>"
        class="gateway-settings panel panel-default <?php echo $s->getSetting('gateway_'.$d.'_enabled') ? 'active-gateway' : 'hidden'; ?>">

        <div class="panel-heading">
            <?php echo ucwords(str_replace('_', ' ', $driver)); ?>
            <div class="pull-right">
                <div class="checkbox no-margin">
                    <label>
                        <?php $body['settings[gateway_'.$d.'_enabled]'] = $s->getSetting('gateway_'.$d.'_enabled'); ?>
                        <input type="hidden" name="settings[gateway_<?php echo $d; ?>_enabled]" value="0">
                        <input type="checkbox" name="settings[gateway_<?php echo $d; ?>_enabled]" value="1"
                            id="settings[gateway_<?php echo $d; ?>_enabled]"
                            <?php $s->check_select($body['settings[gateway_'.$d.'_enabled]'], 1, '==', true); ?>>
                        <?php echo $translator->translate('enabled'); ?>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="panel-body small">
          
                <?php
                    /**
                     * @var string $key
                     * @var array  $setting
                     * @var string $setting['label']
                     * @var string $setting['password']
                     * @var string $setting['type']
                     */
                    foreach ($fields as $key => $setting) { ?>
                <?php $body['settings[gateway_'.$d.'_'.$key.']'] = $s->getSetting('gateway_'.$d.'_'.$key); ?>
                <?php if ('checkbox' == $setting['type']) { ?>

                    <div class="checkbox">
                        <label>                                    
                            <input type="hidden" name="settings[gateway_<?php echo $d; ?>_<?php echo $key; ?>]"
                                value="0">
                            <input type="checkbox" name="settings[gateway_<?php echo $d; ?>_<?php echo $key; ?>]"
                                value="1" <?php $s->check_select($body['settings[gateway_'.$d.'_'.$key.']'], 1, '==', true); ?>>
                            <?php echo $setting['label']; ?>
                        </label>
                    </div>
            
                <?php } else { ?>

                    <div class="form-group">
                        <label for="settings[gateway_<?php echo $d; ?>_<?php echo $key; ?>]">
                            <?php echo $translator->translate('online.payment.'.$key); ?>
                        </label>
                                <input type="<?php echo $setting['type']; ?>" class="form-control"
                            name="settings[gateway_<?php echo $d; ?>_<?php echo $key; ?>]"
                            id="settings[gateway_<?php echo $d; ?>_<?php echo $key; ?>]" 
                                    <?php
                                        if ('password' == $setting['type']) { ?>
                                        value="<?php echo (string) (strlen((string) $body['settings[gateway_'.$d.'_'.$key.']']) > 0
                                                ? $crypt->decode((string) $body['settings[gateway_'.$d.'_'.$key.']'])
                                                : ''); ?>"
                                    <?php } else { ?>
                                        value="<?php echo (string) $body['settings[gateway_'.$d.'_'.$key.']']; ?>"
                                    <?php } ?>
                                >
                        <?php if ('password' == $setting['type']) { ?>
                            <input type="hidden" value="1"
                                name="settings[gateway_<?php echo $d.'_'.$key; ?>_field_is_password]">
                        <?php } ?>
                    </div>

                <?php } ?>
            <?php } ?>

            <hr>
            
            <?php
            // regions are specific to Amazon Pay
            if ('amazon_pay' == $d) { ?>
            <div class="form-group">
                <label for="settings[gateway_<?php echo $d; ?>_region]">
                    <?php echo $translator->translate('online.payment.region'); ?>
                </label>
                <?php $body['settings[gateway_'.$d.'_region]'] = $s->getSetting('gateway_'.$d.'_region'); ?>
                <select name="settings[gateway_<?php echo $d; ?>_region]"
                    id="settings[gateway_<?php echo $d; ?>_region]"
                    class="form-control">
                    <?php
                        /**
                         * @var string $val
                         * @var string $key
                         */
                        foreach ($gateway_regions as $val => $key) { ?>
                        <option value="<?php echo $val; ?>"
                            <?php $s->check_select($body['settings[gateway_'.$d.'_region]'], $val); ?>>
                            <?php echo $val; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <?php } ?>

            <div class="form-group">
                <label for="settings[gateway_<?php echo $d; ?>_currency]">
                    <?php echo $translator->translate('currency'); ?>
                </label>
                <?php $body['settings[gateway_'.$d.'_currency]'] = $s->getSetting('gateway_'.$d.'_currency'); ?>
                <select name="settings[gateway_<?php echo $d; ?>_currency]"
                    id="settings[gateway_<?php echo $d; ?>_currency]"
                    class="form-control">
                    <?php
                        /**
                         * @var string $val
                         * @var string $key
                         */
                        foreach ($gateway_currency_codes as $val => $key) { ?>
                        <option value="<?php echo $val; ?>"
                            <?php $s->check_select($body['settings[gateway_'.$d.'_currency]'], $val); ?>>
                            <?php echo $val; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            
            <?php if ('mollie' == $d) { ?>
                <div class="form-group">
                <label for="settings[gateway_<?php echo $d; ?>_locale]">
                    <?php echo $translator->translate('payment.gateway.default.locale'); ?>
                </label>
                <?php $body['settings[gateway_'.$d.'_locale]'] = $s->getSetting('gateway_'.$d.'_locale'); ?>
                <select name="settings[gateway_<?php echo $d; ?>_locale]"
                    id="settings[gateway_<?php echo $d; ?>_locale]"
                    class="form-control">
                    <?php
                        $locales = $s->mollieSupportedLocaleArray();
                /**
                 * @var array  $locales
                 * @var string $key
                 * @var string $value
                 */
                foreach ($locales as $key => $value) { ?>
                        <option value="<?php echo $value; ?>"
                            <?php $s->check_select($body['settings[gateway_mollie_locale]'], $value); ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <?php } ?>
            
            <div class="form-group">
                <label for="settings[gateway_<?php echo $d; ?>_payment_method]">
                    <?php echo $translator->translate('online.payment.method'); ?>
                </label>
                <?php $body['settings[gateway_'.$d.'_payment_method]'] = $s->getSetting('gateway_'.$d.'_payment_method'); ?>
                <select name="settings[gateway_<?php echo $d; ?>_payment_method]"
                    id="settings[gateway_<?php echo $d; ?>_payment_method]"
                    class="form-control">
                    <?php
                /**
                 * @var App\Invoice\Entity\PaymentMethod $payment_method
                 */
                foreach ($payment_methods as $payment_method) { ?>
                        <option value="<?php echo $payment_method->getId(); ?>"
                            <?php $s->check_select($body['settings[gateway_'.$d.'_payment_method]'], $payment_method->getId()); ?>>
                            <?php echo $payment_method->getName(); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

        </div>

    </div>
<?php } ?>

</div>
<?php echo Html::closeTag('div'); ?>
