<?php
    declare(strict_types=1);
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">

        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $s->trans('taxes'); ?>
            </div>
            <div class="panel-body">

                <div class="row">
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[default_invoice_tax_rate]">
                                <?= $s->trans('default_invoice_tax_rate'); ?>
                            </label>
                            <?php $body['settings[default_invoice_tax_rate]'] = $s->get_setting('default_invoice_tax_rate');?>
                            <select name="settings[default_invoice_tax_rate]" id="settings[default_invoice_tax_rate]"
                                class="form-control">
                                <option value=""><?= $s->trans('none'); ?></option>
                                <?php foreach ($tax_rates as $tax_rate) { ?>
                                    <option value="<?= $tax_rate->getTax_rate_id(); ?>"
                                        <?php $s->check_select($body['settings[default_invoice_tax_rate]'], $tax_rate->getTax_rate_id()); ?>>
                                        <?= $tax_rate->getTax_rate_percent() . '% - ' . $tax_rate->getTax_rate_name(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="settings[default_item_tax_rate]">
                                <?= $s->trans('default_item_tax_rate'); ?>
                            </label>                            
                            <?php $body['settings[default_item_tax_rate]'] = $s->get_setting('default_item_tax_rate');?>
                            <select name="settings[default_item_tax_rate]" id="settings[default_item_tax_rate]"
                                class="form-control">
                                <option value=""><?= $s->trans('none'); ?></option>
                                <?php foreach ($tax_rates as $tax_rate) { ?>
                                    <option value="<?= $tax_rate->getTax_rate_id(); ?>"
                                        <?php $s->check_select($body['settings[default_item_tax_rate]'], $tax_rate->getTax_rate_id()); ?>>
                                        <?= $tax_rate->getTax_rate_percent() . '% - ' . $tax_rate->getTax_rate_name(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                    </div>
                    <div class="col-xs-12 col-md-6">

                        <div class="form-group">
                            <label for="settings[default_include_item_tax]">
                                <?= $s->trans('default_invoice_tax_rate_placement'); ?>
                            </label>
                            <?php $body['settings[default_include_item_tax]'] = $s->get_setting('default_include_item_tax');?>
                            <select name="settings[default_include_item_tax]" id="settings[default_include_item_tax]"
                                class="form-control" data-minimum-results-for-search="Infinity">
                                <option value=""><?= $s->trans('none'); ?></option>
                                <option value="0" <?php $s->check_select($body['settings[default_include_item_tax]'], '0'); ?>>
                                    <?= $s->trans('apply_before_item_tax'); ?>
                                </option>
                                <option value="1" <?php $s->check_select($body['settings[default_include_item_tax]'], '1'); ?>>
                                    <?= $s->trans('apply_after_item_tax'); ?>
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[this_tax_year_from_date_year]">
                                Tax Start Date Year
                            </label>
                            <?php $body['settings[this_tax_year_from_date_year]'] = $s->get_setting('this_tax_year_from_date_year');?>
                            <select name="settings[this_tax_year_from_date_year]" id="settings[this_tax_year_from_date_year]"
                                class="form-control">
                                <option value=""><?= $s->trans('none'); ?></option>                                
                                <?php
                                    $years = []; 
                                    for ($y = 1980, $now = date('Y') + 10; $y <= $now; ++$y) {
                                        $years[$y] = array('name' => $y, 'value' => $y);
                                    }                                 
                                    foreach ($years as $year) { ?>
                                    <option value="<?= $year['value']; ?>" <?= $s->check_select($body['settings[this_tax_year_from_date_year]'], $year['value']); ?>>                                                                          
                                         <?= $year['value']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[this_tax_year_from_date_month]">
                                Tax Start Date Month
                            </label>
                            <?php $body['settings[this_tax_year_from_date_month]'] = $s->get_setting('this_tax_year_from_date_month');?>
                            <select name="settings[this_tax_year_from_date_month]" id="settings[this_tax_year_from_date_month]"
                                class="form-control">
                                <option value=""><?= $s->trans('none'); ?></option>                                
                                <?php
                                    $months = ['01','02','03','04','05','06','07','08','09','10','11','12'];                     
                                    foreach ($months as $month) { ?>
                                    <option value="<?= $month; ?>" <?= $s->check_select($body['settings[this_tax_year_from_date_month]'], $month); ?>>                                                                          
                                         <?= $month; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[this_tax_year_from_date_day]">
                                Tax Start Date Day
                            </label>
                            <?php $body['settings[this_tax_year_from_date_day]'] = $s->get_setting('this_tax_year_from_date_day');?>
                            <select name="settings[this_tax_year_from_date_day]" id="settings[this_tax_year_from_date_day]"
                                class="form-control">
                                <option value=""><?= $s->trans('none'); ?></option>                                
                                <?php
                                    $days = ['01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31'];                     
                                    foreach ($days as $day) { ?>
                                    <option value="<?= $day; ?>" <?= $s->check_select($body['settings[this_tax_year_from_date_day]'], $day); ?>>                                                                          
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
