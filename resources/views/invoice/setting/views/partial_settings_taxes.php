<?php
    declare(strict_types=1);
        
    /**
     * @var App\Invoice\Setting\SettingRepository $s 
     * @var Yiisoft\Translator\TranslatorInterface $translator
     * @var array $body
     * @var array $tax_rates
     */
?>
<div class = 'row'>
    <div class="col-xs-12 col-md-8 col-md-offset-2">

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('i.taxes'); ?>
            </div>
            <div class="panel-body">

                <div class = 'row'>
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[default_invoice_tax_rate]">
                                <?= $translator->translate('i.default_invoice_tax_rate'); ?>
                            </label>
                            <?php $body['settings[default_invoice_tax_rate]'] = $s->getSetting('default_invoice_tax_rate');?>
                            <select name="settings[default_invoice_tax_rate]" id="settings[default_invoice_tax_rate]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\TaxRate $taxRate
                                     */
                                    foreach ($tax_rates as $taxRate) { ?>
                                    <option value="<?= $taxRate->getTaxRateId(); ?>"
                                        <?php $s->check_select($body['settings[default_invoice_tax_rate]'], $taxRate->getTaxRateId()); ?>>
                                        <?php 
                                           $percent = $taxRate->getTaxRatePercent();
                                           $sign =  '% - ';
                                           $name = $taxRate->getTaxRateName();
                                           if ((null!==$percent) && (null!==$name)) {
                                               echo $percent.$sign.$name;
                                           } else {
                                               echo '';
                                           }
                                        ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[default_item_tax_rate]">
                                <?= $translator->translate('i.default_item_tax_rate'); ?>
                            </label>                            
                            <?php $body['settings[default_item_tax_rate]'] = $s->getSetting('default_item_tax_rate');?>
                            <select name="settings[default_item_tax_rate]" id="settings[default_item_tax_rate]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\TaxRate $taxRate
                                     */
                                    foreach ($tax_rates as $taxRate) { ?>
                                    <option value="<?= $taxRate->getTaxRateId(); ?>"
                                        <?php $s->check_select($body['settings[default_item_tax_rate]'], $taxRate->getTaxRateId()); ?>>
                                        <?php 
                                           $percent = $taxRate->getTaxRatePercent();
                                           $sign =  '% - ';
                                           $name = $taxRate->getTaxRateName();
                                           if ((null!==$percent) && (null!==$name)) {
                                               echo $percent.$sign.$name;
                                           } else {
                                               echo '';
                                           }
                                        ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[default_include_item_tax]" <?= $s->where('default_include_item_tax'); ?>>
                                <?= $translator->translate('i.default_invoice_tax_rate_placement'); ?>
                            </label>
                            <?php $body['settings[default_include_item_tax]'] = $s->getSetting('default_include_item_tax');?>
                            <select name="settings[default_include_item_tax]" id="settings[default_include_item_tax]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value=""><?= $translator->translate('i.none'); ?></option>
                                <option value="0" <?php $s->check_select($body['settings[default_include_item_tax]'], '0'); ?>>
                                    <?= $translator->translate('i.apply_before_item_tax'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[default_include_item_tax]'], '1'); ?>>
                                    <?= $translator->translate('i.apply_after_item_tax'); ?>
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[this_tax_year_from_date_year]">
                                <?= $translator->translate('i.tax').' '.$translator->translate('i.start'). ' '. $translator->translate('i.date').' '.$translator->translate('i.year'); ?>
                            </label>
                            <?php $body['settings[this_tax_year_from_date_year]'] = $s->getSetting('this_tax_year_from_date_year');?>
                            <select name="settings[this_tax_year_from_date_year]" id="settings[this_tax_year_from_date_year]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>                                
                                <?php
                                    $years = []; 
                                    for ($y = 1980, $now = date('Y') + 10; $y <= $now; ++$y) {
                                        $years[$y] = array('name' => $y, 'value' => $y);
                                    }
                                    /**
                                     * @var array $year
                                     * @var string $year['value']
                                     */
                                    foreach ($years as $year) { ?>
                                    <option value="<?= $year['value']; ?>" <?php $s->check_select($body['settings[this_tax_year_from_date_year]'], $year['value']); ?>>                                                                          
                                         <?= $year['value']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[this_tax_year_from_date_month]">
                                <?= $translator->translate('i.tax').' '.$translator->translate('i.start'). ' '. $translator->translate('i.date').' '.$translator->translate('i.month'); ?>
                            </label>
                            <?php $body['settings[this_tax_year_from_date_month]'] = $s->getSetting('this_tax_year_from_date_month');?>
                            <select name="settings[this_tax_year_from_date_month]" id="settings[this_tax_year_from_date_month]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>                                
                                <?php
                                    $months = ['01','02','03','04','05','06','07','08','09','10','11','12'];                     
                                    foreach ($months as $month) { ?>
                                    <option value="<?= $month; ?>" <?php $s->check_select($body['settings[this_tax_year_from_date_month]'], $month); ?>>                                                                          
                                         <?= $month; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[this_tax_year_from_date_day]">
                                <?= $translator->translate('i.tax').' '.$translator->translate('i.start'). ' '. $translator->translate('i.date').' '.rtrim($translator->translate('i.days'),'s'); ?>
                            </label>
                            <?php $body['settings[this_tax_year_from_date_day]'] = $s->getSetting('this_tax_year_from_date_day');?>
                            <select name="settings[this_tax_year_from_date_day]" id="settings[this_tax_year_from_date_day]"
                                class="form-control">
                                <option value=""><?= $translator->translate('i.none'); ?></option>                                
                                <?php
                                    $days = ['01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31'];                     
                                    foreach ($days as $day) { ?>
                                    <option value="<?= $day; ?>" <?php $s->check_select($body['settings[this_tax_year_from_date_day]'], $day); ?>>                                                                          
                                         <?= $day; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
