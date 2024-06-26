<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
?>
<?= Html::openTag('div', ['class' => 'row']); ?>
<div class="col-xs-12 col-md-8 col-md-offset-2">
<div class="panel panel-default">
    <div class="panel-heading">
        <?= $translator->translate('g.online_payments'); ?>
    </div>
    <div class="panel-body">
        <div class="form-group">
            <div class="checkbox">
                <?php $body['settings[enable_online_payments]'] = $s->get_setting('enable_online_payments');?>
                <label>
                    <input type="hidden" name="settings[enable_online_payments]" value="0">
                    <input type="checkbox" name="settings[enable_online_payments]" value="1"
                        <?php $s->check_select($body['settings[enable_online_payments]'], 1, '==', true) ?>>
                    <?= $translator->translate('g.enable_online_payments'); ?>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="online-payment-select">
                <?= $translator->translate('g.add_payment_provider'); ?>
            </label>
            <select id="online-payment-select" class="form-control">
                <option value=""><?= $translator->translate('i.none'); ?></option>
                <?php foreach ($gateway_drivers as $driver => $fields) {
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
// see SettingRepository payment_gateways
// $d = stripe, $fields = ['apiKey, publishableKey, secretKey, version]
// eg. 'Stripe' => array(
//                'apiKey' => array(
//                    'type' => 'password',
//                    'label' => 'Api Key',
//                ),
//             @see src/Invoice/Language/English/gateway_lang
//             Not server-side ie. client-side                
//             'publishableKey' => array(
//                 'type' => 'password',
//                 'label' => 'Publishable Key',
//              ),
//              server-side @see https://dashboard.stripe.com/test/dashboard
//              'secretKey' => array(
//                  'type' => 'password',
//                  'label' => 'Secret Key',
//              ),
//              'version' => array(
//                  'type' => 'checkbox',
//                  'label' => 'Omnipay Version'
//                  'tooltip' => 'This is a tooltip'                    
//              ),
// ),

foreach ($gateway_drivers as $driver => $fields) :
    $d = strtolower($driver);
    ?>
    <div id="gateway-settings-<?= $d; ?>"
        class="gateway-settings panel panel-default <?= $s->get_setting('gateway_' . $d . '_enabled') ? 'active-gateway' : 'hidden'; ?>">

        <div class="panel-heading">
            <?= ucwords(str_replace('_', ' ', $driver)); ?>
            <div class="pull-right">
                <div class="checkbox no-margin">
                    <label>
                        <?php $body['settings[gateway_' . $d . '_enabled]'] = $s->get_setting('gateway_' . $d . '_enabled');?>
                        <input type="hidden" name="settings[gateway_<?= $d; ?>_enabled]" value="0">
                        <input type="checkbox" name="settings[gateway_<?= $d; ?>_enabled]" value="1"
                            id="settings[gateway_<?= $d; ?>_enabled]"
                            <?php $s->check_select($body['settings[gateway_' . $d . '_enabled]'], 1, '==', true) ?>>
                        <?= $translator->translate('i.enabled'); ?>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="panel-body small">

             
                <!-- 'AuthorizeNet_AIM' => array(
                            'version' => array(
                                'type' => 'checkbox',
                                'label' => 'Omnipay Version'                    
                      ) 
                -->
            
                <?php // 'driver' => 'AuthorizeNet_AIM'      
                   // foreach 'version' as 'type' => 'checkbox'
                      foreach ($fields as $key => $setting) { ?>
                <?php $body['settings[gateway_' . $d . '_'.$key.']'] = $s->get_setting('gateway_' . $d . '_' . $key);?>
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
                            <?= $translator->translate('g.online_payment_' . $key);?>
                        </label>
                                <input type="<?= $setting['type']; ?>" class="input-sm form-control"
                            name="settings[gateway_<?= $d; ?>_<?= $key ?>]"
                            id="settings[gateway_<?= $d; ?>_<?= $key ?>]" 
                                    <?php
                                        if ($setting['type'] == 'password') : ?>
                                        value="<?= strlen($body['settings[gateway_' . $d . '_'.$key.']']) > 0 
                                                ? $crypt->decode($body['settings[gateway_' . $d . '_'.$key.']']) 
                                                : ''; ?>"
                                    <?php else : ?>
                                        value="<?= $body['settings[gateway_' . $d . '_'.$key.']']; ?>"
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
            if ($d == 'amazon_pay') 
            { ?>
            <div class="form-group">
                <label for="settings[gateway_<?= $d; ?>_region]">
                    <?= $translator->translate('g.online_payment_region'); ?>
                </label>
                <?php $body['settings[gateway_' . $d . '_region]'] = $s->get_setting('gateway_' . $d . '_region');?>
                <select name="settings[gateway_<?= $d; ?>_region]"
                    id="settings[gateway_<?= $d; ?>_region]"
                    class="input-sm form-control">
                    <?php foreach ($gateway_regions as $val => $key) { ?>
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
                    <?= $translator->translate('i.currency'); ?>
                </label>
                <?php $body['settings[gateway_' . $d . '_currency]'] = $s->get_setting('gateway_' . $d . '_currency');?>
                <select name="settings[gateway_<?= $d; ?>_currency]"
                    id="settings[gateway_<?= $d; ?>_currency]"
                    class="input-sm form-control">
                    <?php foreach ($gateway_currency_codes as $val => $key) { ?>
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
                    <?= $translator->translate('invoice.payment.gateway.default.locale'); ?>
                </label>
                <?php $body['settings[gateway_' . $d . '_locale]'] = $s->get_setting('gateway_' . $d . '_locale');?>
                <select name="settings[gateway_<?= $d; ?>_locale]"
                    id="settings[gateway_<?= $d; ?>_locale]"
                    class="input-sm form-control">
                    <?php 
                        $locales = $s->mollieSupportedLocaleArray();
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
                    <?= $translator->translate('g.online_payment_method'); ?>
                </label>
                <?php $body['settings[gateway_' . $d . '_payment_method]'] = $s->get_setting('gateway_' . $d . '_payment_method');?>
                <select name="settings[gateway_<?= $d; ?>_payment_method]"
                    id="settings[gateway_<?= $d; ?>_payment_method]"
                    class="input-sm form-control">
                    <?php foreach ($payment_methods as $payment_method) { ?>
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
