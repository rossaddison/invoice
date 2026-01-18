<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
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
                <?= $translator->translate('peppol.electronic.invoicing'); ?>
            </div>
<!-- Enable Peppol -->            
            <div class="panel-body">
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
     <?php $body['settings[enable_peppol]'] = $s->getSetting('enable_peppol');?>
                                <label <?= $s->where('enable_peppol'); ?>>
                                    <input type="hidden"
                                           name="settings[enable_peppol]"
                                           value="0">
                                    <input type="checkbox"
                                           name="settings[enable_peppol]"
                                           value="1"
     <?php $s->check_select($body['settings[enable_peppol]'], 1, '==', true) ?>>
                            <?= Html::a($translator->translate('peppol.enable'),
                                 'https://www.datypic.com/sc/ubl21/ss.html',
                                 [
                                     'style' => 'text-decoration:none',
                                     'data-bs-toggle' => 'tooltip',
                                     'title' => ''
                                 ]); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="settings[peppol_debug_with_emojis]"
                                  <?= $s->where('peppol_debug_with_emojis'); ?>>
                     <?= $translator->translate('peppol.debug.with.emojis'); ?>
                        </label>
                             <?php $body['settings[peppol_debug_with_emojis]'] =
                                  $s->getSetting('peppol_debug_with_emojis'); ?>
                        <select name="settings[peppol_debug_with_emojis]"
                                id="settings[peppol_debug_with_emojis]"
                                class="form-control">
                            <option value="0">
                                <?= $translator->translate('no'); ?>
                            </option>
                            <option value="1"
                                <?php
             $s->check_select($body['settings[peppol_debug_with_emojis]'], '1');
                            ?>>
                                <?= $translator->translate('yes'); ?>
                            </option>
                        </select>
                    </div>
<!-- Fill Client Peppol Form with OpenPeppol defaults for testing -->
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                        <?php $body['settings[enable_client_peppol_defaults]']
                            = $s->getSetting('enable_client_peppol_defaults');?>
                                <label>
                                    <input type="hidden"
                                           name=
                                       "settings[enable_client_peppol_defaults]"
                                           value="0">
                                    <input type="checkbox"
                                           name=
                                       "settings[enable_client_peppol_defaults]"
                                           value="1"
<?php $s->check_select($body['settings[enable_client_peppol_defaults]'], 1, '==',
                                                                       true) ?>>
                        <?= $translator->translate('peppol.client.defaults'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
<!-- Peppol From Currency e.g. GBP -->                    
                    <div class="form-group">
                        <label for="settings[currency_code_from]" >
                     <?= $translator->translate('peppol.currency.code.from'); ?>
                        </label>
                                   <?php $body['settings[currency_code_from]'] =
                $s->getSetting('currency_code_from') ?: $config_tax_currency; ?>
                        <select name="settings[currency_code_from]" disabled
                            id="settings[currency_code_from]"
                            class="input-sm form-control">
                            <option value="0">
                                          <?= $translator->translate('none'); ?>
                            </option>
                        <?php
                        /**
                         * @var string $val
                         * @var string $key
                         */
                        foreach ($gateway_currency_codes as $val => $key) { ?>
                                <option value="<?= $val; ?>"
                                    <?php
                                        $s->check_select(
                                    $body['settings[currency_code_from]'], $val);
                                    ?>>
                                    <?= $val; ?>
                                </option>
                        <?php } ?>
                        </select>
                    </div>
<!-- Peppol To Currency e.g. ZAR -->
                    <div class="form-group">
                        <label for="settings[currency_code_to]" >
                            <?= $translator->translate('peppol.currency.code.to'); ?>
                        </label>
                                     <?php $body['settings[currency_code_to]'] =
                                           $s->getSetting('currency_code_to') ?:
                                           $config_tax_currency; ?>
                        <select name="settings[currency_code_to]"
                            id="settings[currency_code_to]"
                            class="input-sm form-control">
                            <option value="0">
                                        <?= $translator->translate('none'); ?>
                            </option>
                        <?php
                        /**
                         * @var string $val
                         * @var string $key
                         */
                        foreach ($gateway_currency_codes as $val => $key) { ?>
                                <option value="<?= $val; ?>"
                                    <?php
                    $s->check_select($body['settings[currency_code_to]'], $val);
                                    ?>>
                                    <?= $val; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
<!-- Peppol Document Currency -->
                    <div class="form-group">
                        <label for="settings[peppol_document_currency]"
                            <?= $s->where('peppol_document_currency'); ?>>🛈
                      <?= $translator->translate('peppol.document.currency'); ?>
                        </label>
                             <?php $body['settings[peppol_document_currency]'] =
                                   $s->getSetting('peppol_document_currency') ?:
                                     $config_tax_currency; ?>
                        <select name="settings[peppol_document_currency]"
                            id="settings[peppol_document_currency]"
                            class="input-sm form-control">
                            <option value="0">
                                <?= $translator->translate('none'); ?>
                            </option>
                    <?php
                        /**
                         * @var string $val
                         * @var string $key
                         */
                        foreach ($gateway_currency_codes as $val => $key) { ?>
                                <option value="<?= $val; ?>"
        <?php
            $s->check_select($body['settings[peppol_document_currency]'], $val);
        ?>>
                                    <?= $val; ?>
                                </option>
                    <?php } ?>
                        </select>
                    </div>
<!-- One of 'From' Currency Today converts to this of 'To' Currency -->                    
                    <div class="form-group">
                        <label for="settings[currency_from_to]" <?= $s->where(
                                                   'currency_code_from_to'); ?>>
                       <?= $translator->translate('peppol.currency.from.to'); ?>
           <?= '(' . (string) Html::a('xe.com', 'https://www.xe.com/') . ')'; ?>
                        </label>
                                     <?php $body['settings[currency_from_to]'] =
                                $s->getSetting('currency_from_to') ?: '1.00'; ?>
                        <input type="text"
                               name="settings[currency_from_to]"
                               id="settings[currency_from_to]"
                               class="form-control"
                               value="<?= $body['settings[currency_from_to]']; ?>">
                        
                    </div>
<!-- One of 'To' Currency Today converts to this of 'From' Currency -->
                    <div class="form-group">
                        <label for="settings[currency_to_from]" >
                       <?= $translator->translate('peppol.currency.to.from'); ?>
                        </label>
                                     <?php $body['settings[currency_to_from]'] =
                                $s->getSetting('currency_to_from') ?: '1.00'; ?>
                        <input type="text"
                               name="settings[currency_to_from]"
                               id="settings[currency_to_from]"
                               class="form-control"
                               value="<?= $body['settings[currency_to_from]']; ?>">
                        
                    </div>
                     <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                              <?php $body['settings[include_delivery_period]'] =
                           ($s->getSetting('include_delivery_period') ?: '0');?>
                                <label 
                                    <?= $s->where('include_delivery_period'); ?>>
                                    <input type="hidden"
                                           name="settings[include_delivery_period]"
                                           value="0">
                                    <input type="checkbox"
                                           name="settings[include_delivery_period]"
                                           value="1"
     <?php $s->check_select($body['settings[include_delivery_period]'], 1, '==', 
                                                                        true) ?>>
<?= Html::a(
    $translator->translate('peppol.include.delivery.period'),
    'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/',
    ['style' => 'text-decoration:none'],
); ?>
                                </label>
                            </div> 
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="settings[stand_in_code]" <?= $s->where(
                                                            'stand_in_code'); ?>>
                     <?= Html::a($translator->translate('peppol.stand.in.code'),
'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/'
        . 'cbc-DescriptionCode/', ['style' => 'text-decoration:none']); ?>
                        </label>
                        <div class="input-group">
<?php $body['settings[stand_in_code]'] = $s->getSetting('stand_in_code') ?: ''; ?>
                            <select 
                                name="settings[stand_in_code]"
                                id="settings[stand_in_code]"
                                class="input-sm form-control">
                        <?php
                                /**
                                 * @var array $value
                                 * @var string $key
                                 * @var string $value['rdf:value']
                                 * @var string $value['rdf:comment']
                                 */
                                foreach ($stand_in_codes as $key => $value) { ?>
                                    <option value="<?= $value['rdf:value']; ?>"
<?php
                        $s->check_select($body['settings[stand_in_code]'] ?? '',
                                $value['rdf:value']);
    ?>>
<?= $value['rdf:value'] . ' ' . (string) $value['rdfs:comment']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <span class="input-group-text">
                                 <a href="<?= $s->href('stand_in_code'); ?>"
                                             <?= $s->where('stand_in_code'); ?>>
                                     <i class="fa fa-question fa-fw"></i>
                                 </a>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="settings[peppol_xml_stream]"
                                         <?= $s->where('peppol_xml_stream'); ?>>
                            <?= $translator->translate('peppol.xml.stream'); ?>
                        </label>
                                    <?php $body['settings[peppol_xml_stream]'] =
                                         $s->getSetting('peppol_xml_stream'); ?>
                        <select name="settings[peppol_xml_stream]"
                                id="settings[peppol_xml_stream]"
                                class="form-control">
                            <option value="0">
                                <?= $translator->translate('no'); ?>
                            </option>
                            <option value="1"
                                <?php
                    $s->check_select($body['settings[peppol_xml_stream]'], '1');
                            ?>>
                                <?= $translator->translate('yes'); ?>
                            </option>
                        </select>
                    </div>
                </div>    
            </div>    
        </div>
    </div>
</div>
