<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 * @var array $stand_in_codes
 * @var array $gateway_currency_codes
 * @var string $config_tax_currency
 */
?>
<div class='row'>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $translator->translate('peppol.electronic.invoicing'); ?>
            </div>
            <div class="panel-body">
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                                <?php $body['settings[enable_peppol]'] = $s->getSetting('enable_peppol'); ?>
                                <label <?php echo $s->where('enable_peppol'); ?>">
                                    <input type="hidden" name="settings[enable_peppol]" value="0">
                                    <input type="checkbox" name="settings[enable_peppol]" value="1"
                                        <?php $s->check_select($body['settings[enable_peppol]'], 1, '==', true); ?>>
                                        <?php echo Html::a($translator->translate('peppol.enable'), 'http://www.datypic.com/sc/ubl21/ss.html', ['style' => 'text-decoration:none', 'data-bs-toggle' => 'tooltip', 'title' => '']); ?>
                                </label>
                            </div>                            
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                                <?php $body['settings[enable_client_peppol_defaults]'] = $s->getSetting('enable_client_peppol_defaults'); ?>
                                <label>
                                    <input type="hidden" name="settings[enable_client_peppol_defaults]" value="0">
                                    <input type="checkbox" name="settings[enable_client_peppol_defaults]" value="1"
                                        <?php $s->check_select($body['settings[enable_client_peppol_defaults]'], 1, '==', true); ?>>
                                        <?php echo $translator->translate('peppol.client.defaults'); ?>
                                </label>
                            </div>                            
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="settings[currency_code_from]" >
                            <?php echo $translator->translate('peppol.currency.code.from'); ?>
                        </label>
                        <?php $body['settings[currency_code_from]'] = $s->getSetting('currency_code_from') ?: $config_tax_currency; ?>
                        <select name="settings[currency_code_from]" disabled
                            id="settings[currency_code_from]"
                            class="input-sm form-control">
                            <option value="0"><?php echo $translator->translate('none'); ?></option>
                            <?php
                                /**
                                 * @var string $val
                                 * @var string $key
                                 */
                                foreach ($gateway_currency_codes as $val => $key) { ?>
                                <option value="<?php echo $val; ?>"
                                    <?php
                                        $s->check_select($body['settings[currency_code_from]'], $val);
                                    ?>>
                                    <?php echo $val; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="settings[currency_code_to]" >
                            <?php echo $translator->translate('peppol.currency.code.to'); ?>
                        </label>
                        <?php $body['settings[currency_code_to]'] = $s->getSetting('currency_code_to') ?: $config_tax_currency; ?>
                        <select name="settings[currency_code_to]"
                            id="settings[currency_code_to]"
                            class="input-sm form-control">
                            <option value="0"><?php echo $translator->translate('none'); ?></option>
                            <?php
                                /**
                                 * @var string $val
                                 * @var string $key
                                 */
                                foreach ($gateway_currency_codes as $val => $key) { ?>
                                <option value="<?php echo $val; ?>"
                                    <?php
                                        $s->check_select($body['settings[currency_code_to]'], $val);
                                    ?>>
                                    <?php echo $val; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="settings[currency_from_to]" <?php echo $s->where('currency_code_from_to'); ?>>
                            <?php echo $translator->translate('peppol.currency.from.to'); ?>
                            <?php echo '('.(string) Html::a('xe.com', 'https://www.xe.com/').')'; ?>
                        </label>
                        <?php $body['settings[currency_from_to]'] = $s->getSetting('currency_from_to') ?: '1.00'; ?>
                        <input type="text" name="settings[currency_from_to]" id="settings[currency_from_to]"
                                class="form-control"
                                value="<?php echo $body['settings[currency_from_to]']; ?>">
                        
                    </div>
                    <div class="form-group">
                        <label for="settings[currency_to_from]" >
                            <?php echo $translator->translate('peppol.currency.to.from'); ?>
                        </label>
                        <?php $body['settings[currency_to_from]'] = $s->getSetting('currency_to_from') ?: '1.00'; ?>
                        <input type="text" name="settings[currency_to_from]" id="settings[currency_to_from]"
                                class="form-control"
                                value="<?php echo $body['settings[currency_to_from]']; ?>">
                        
                    </div>
                     <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                                <?php $body['settings[include_delivery_period]'] = ($s->getSetting('include_delivery_period') ?: '0'); ?>
                                <label <?php echo $s->where('include_delivery_period'); ?>>
                                    <input type="hidden" name="settings[include_delivery_period]" value="0">
                                    <input type="checkbox" name="settings[include_delivery_period]" value="1"
                                        <?php $s->check_select($body['settings[include_delivery_period]'], 1, '==', true); ?>>
                                        <?php echo Html::a(
                                            $translator->translate('peppol.include.delivery.period'),
                                            'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/',
                                            ['style' => 'text-decoration:none'],
                                        ); ?>
                                </label>
                            </div>                            
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="settings[stand_in_code]" <?php echo $s->where('stand_in_code'); ?>>
                            <?php echo Html::a($translator->translate('peppol.stand.in.code'), 'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/cbc-DescriptionCode/', ['style' => 'text-decoration:none']); ?>
                        </label>
                        <div class="input-group">
                            <?php $body['settings[stand_in_code]'] = $s->getSetting('stand_in_code') ?: ''; ?>
                            <select name="settings[stand_in_code]"
                                id="settings[stand_in_code]"
                                class="input-sm form-control">
                                <?php
                                        /**
                                         * @var array  $value
                                         * @var string $key
                                         * @var string $value['rdf:value']
                                         * @var string $value['rdf:comment']
                                         */
                                        foreach ($stand_in_codes as $key => $value) { ?>
                                    <option value="<?php echo $value['rdf:value']; ?>"
                                        <?php
                                                $s->check_select($body['settings[stand_in_code]'] ?? '', $value['rdf:value']);
                                            ?>>
                                        <?php echo $value['rdf:value'].' '.(string) $value['rdfs:comment']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <span class="input-group-text"> 
                                 <a href="<?php echo $s->href('stand_in_code'); ?>" <?php echo $s->where('stand_in_code'); ?>><i class="fa fa-question fa-fw"></i></a> 
                            </span> 
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="settings[peppol_xml_stream]" <?php echo $s->where('peppol_xml_stream'); ?>>
                            <?php echo $translator->translate('peppol.xml.stream'); ?>
                        </label>
                        <?php $body['settings[peppol_xml_stream]'] = $s->getSetting('peppol_xml_stream'); ?>
                        <select name="settings[peppol_xml_stream]" id="settings[peppol_xml_stream]" class="form-control">
                            <option value="0">
                                <?php echo $translator->translate('no'); ?>
                            </option>
                            <option value="1" 
                                <?php
                                    $s->check_select($body['settings[peppol_xml_stream]'], '1');
?>>
                                <?php echo $translator->translate('yes'); ?>
                            </option>
                        </select>
                    </div>
                </div>    
            </div>    
        </div>
    </div>
</div>