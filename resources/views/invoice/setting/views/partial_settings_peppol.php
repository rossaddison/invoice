<?php
    declare(strict_types=1);
    use Yiisoft\Html\Html;
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('invoice.peppol'); ?>
            </div>
            <div class="panel-body">
                
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                                <?php $body['settings[enable_peppol]'] = $s->get_setting('enable_peppol');?>
                                <label for="settings[enable_peppol]" <?= $s->where('enable_peppol'); ?>">
                                    <input type="hidden" name="settings[enable_peppol]" value="0">
                                    <input type="checkbox" name="settings[enable_peppol]" value="1"
                                        <?php $s->check_select($body['settings[enable_peppol]'], 1, '==', true) ?>>
                                        <?= Html::a($translator->translate('invoice.peppol.enable'),'http://www.datypic.com/sc/ubl21/ss.html',['style'=>'text-decoration:none','data-bs-toggle'=>'tooltip','title'=>'']); ?>
                                </label>
                            </div>                            
                        </div>
                    </div>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                                <?php $body['settings[enable_client_peppol_defaults]'] = $s->get_setting('enable_client_peppol_defaults');?>
                                <label for="settings[enable_client_peppol_defaults]">
                                    <input type="hidden" name="settings[enable_client_peppol_defaults]" value="0">
                                    <input type="checkbox" name="settings[enable_client_peppol_defaults]" value="1"
                                        <?php $s->check_select($body['settings[enable_client_peppol_defaults]'], 1, '==', true) ?>>
                                        <?= $translator->translate('invoice.peppol.client.defaults'); ?>
                                </label>
                            </div>                            
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="settings[currency_code_from]" >
                            <?= $translator->translate('invoice.peppol.currency.code.from'); ?>
                        </label>
                        <?php $body['settings[currency_code_from]'] = $s->get_setting('currency_code_from', '', true) ?: $config_tax_currency; ?>
                        <select name="settings[currency_code_from]" disabled
                            id="settings[currency_code_from]"
                            class="input-sm form-control">
                            <option value="0"><?= $s->trans('none'); ?></option>
                            <?php foreach ($gateway_currency_codes as $val => $key) { ?>
                                <option value="<?= $val; ?>"
                                    <?php
                                        $s->check_select($body['settings[currency_code_from]'], $val); 
                                    ?>>
                                    <?= $val; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="settings[currency_code_to]" >
                            <?= $translator->translate('invoice.peppol.currency.code.to'); ?>
                        </label>
                        <?php $body['settings[currency_code_to]'] = $s->get_setting('currency_code_to', '', true) ?: $config_tax_currency; ?>
                        <select name="settings[currency_code_to]"
                            id="settings[currency_code_to]"
                            class="input-sm form-control">
                            <option value="0"><?= $s->trans('none'); ?></option>
                            <?php foreach ($gateway_currency_codes as $val => $key) { ?>
                                <option value="<?= $val; ?>"
                                    <?php
                                        $s->check_select($body['settings[currency_code_to]'], $val); 
                                    ?>>
                                    <?= $val; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="settings[currency_from_to]" <?= $s->where('currency_code_from_to'); ?>>
                            <?= $translator->translate('invoice.peppol.currency.from.to'); ?>
                            <?= '('. Html::a('xe.com' ,'https://www.xe.com/') . ')'; ?>
                        </label>
                        <?php $body['settings[currency_from_to]'] = $s->get_setting('currency_from_to', '', true) ?: '1.00'; ?>
                        <input type="text" name="settings[currency_from_to]" id="settings[currency_from_to]"
                                class="form-control"
                                value="<?= $body['settings[currency_from_to]']; ?>">
                        
                    </div>
                    <div class="form-group">
                        <label for="settings[currency_to_from]" >
                            <?= $translator->translate('invoice.peppol.currency.to.from'); ?>
                        </label>
                        <?php $body['settings[currency_to_from]'] = $s->get_setting('currency_to_from', '', true) ?: '1.00'; ?>
                        <input type="text" name="settings[currency_to_from]" id="settings[currency_to_from]"
                                class="form-control"
                                value="<?= $body['settings[currency_to_from]']; ?>">
                        
                    </div>
                     <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                                <?php $body['settings[include_delivery_period]'] = ($s->get_setting('include_delivery_period') ?: '0');?>
                                <label for="settings[include_delivery_period]" <?= $s->where('include_delivery_period'); ?>>
                                    <input type="hidden" name="settings[include_delivery_period]" value="0">
                                    <input type="checkbox" name="settings[include_delivery_period]" value="1"
                                        <?php $s->check_select($body['settings[include_delivery_period]'], 1, '==', true) ?>>
                                        <?= Html::a($translator->translate('invoice.peppol.include.delivery.period'),
                                            'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/',['style'=>'text-decoration:none']); ?>
                                </label>
                            </div>                            
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="settings[stand_in_code]" <?= $s->where('stand_in_code'); ?>>
                            <?= Html::a($translator->translate('invoice.peppol.stand.in.code'),'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/cbc-DescriptionCode/',['style'=>'text-decoration:none']); ?>
                        </label>
                        <div class="input-group">
                            <?php $body['settings[stand_in_code]'] = $s->get_setting('stand_in_code', '', true) ?: ''; ?>
                            <select name="settings[stand_in_code]"
                                id="settings[stand_in_code]"
                                class="input-sm form-control">
                                <?php foreach ($stand_in_codes as $key => $value) { ?>
                                    <option value="<?= $value['rdf:value']; ?>"
                                        <?php
                                            $s->check_select($body['settings[stand_in_code]'] ?? '', $value['rdf:value']); 
                                        ?>>
                                        <?= $value['rdf:value']. ' '. $value['rdfs:comment']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <span class="input-group-text"> 
                                 <a href="<?= $s->href('stand_in_code'); ?>" <?= $s->where('stand_in_code'); ?>><i class="fa fa-question fa-fw"></i></a> 
                            </span> 
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="settings[peppol_xml_stream]" <?= $s->where('peppol_xml_stream'); ?>>
                            <?= $translator->translate('invoice.peppol.xml.stream'); ?>
                        </label>
                        <?php $body['settings[peppol_xml_stream]'] = $s->get_setting('peppol_xml_stream'); ?>
                        <select name="settings[peppol_xml_stream]" id="settings[peppol_xml_stream]" class="form-control">
                            <option value="0">
                                <?= $s->trans('no'); ?>
                            </option>
                            <option value="1" 
                                <?php
                                    $s->check_select($body['settings[peppol_xml_stream]'], '1'); 
                                ?>>
                                <?= $s->trans('yes'); ?>
                            </option>
                        </select>
                    </div>
                </div>    
            </div>    
        </div>
    </div>
</div>