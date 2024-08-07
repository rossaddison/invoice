<?php

  declare(strict_types=1); 
 
  /**
   * @see InvController function view, search modal_add_inv_tax
   * @see id="add-inv-tax" triggered by <a href="#add-inv-tax" data-toggle="modal"  style="text-decoration:none"> on views/inv/view.php line 67
   * @see Invoice/Asset/rebuild-1.13/js/inv.js/$(document).on('click', '#inv_tax_submit', function () {
   * @see InvController/save_inv_tax_rate
   * @var App\Invoice\Helpers\NumberHelper $numberHelper
   * @var App\Invoice\Setting\SettingRepository $s
   * @var Yiisoft\Translator\TranslatorInterface $translator 
   * @var array $taxRates
   */
  
?>

<div id="add-inv-tax" class="modal modal-lg" role="dialog" aria-labelledby="modal_add_inv_tax" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
        </div>
        <div class="modal-body">
            <div class="mb3 form-group">
                <h6><?= $translator->translate('i.add_invoice_tax'); ?></h6>
            </div>
            <div class="mb3 form-group">
                <label for="inv_tax_rate_id">
                    <?= $translator->translate('i.tax_rate'); ?>
                </label>
                <div>
                    <select name="inv_tax_rate_id" id="inv_tax_rate_id" class="form-control" required>
                        <option value="0"><?= $translator->translate('i.none'); ?></option>
                        <?php
                            /**
                             * @var App\Invoice\Entity\TaxRate $taxRate
                             */
                            foreach ($taxRates as $taxRate) { ?>
                            <option value="<?= $taxRate->getTax_rate_id(); ?>">
                                <?php 
                                        $taxRatePercent = $numberHelper->format_amount($taxRate->getTax_rate_percent());
                                        $taxRateName = $taxRate->getTax_rate_name();
                                        if ($taxRatePercent >= 0.00 && null!==$taxRatePercent && null!==$taxRateName) {
                                            echo $taxRatePercent . '% - ' . ($taxRateName); 
                                        }
                                ?>s
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="mb3 form-group">
                <label for="include_inv_item_tax">
                    <?= $translator->translate('i.tax_rate_placement'); ?>
                </label>

                <div>
                    <select name="include_inv_item_tax" id="include_inv_item_tax" class="form-control">
                        <?php if ($s->get_setting('enable_vat_registration') === '0') { ?>
                        <option value="0">
                            <?php echo $translator->translate('i.apply_before_item_tax'); ?>
                        </option>
                        <option value="1">
                            <?php echo $translator->translate('i.apply_after_item_tax'); ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <div class="btn-group">
                <!-- see src/Invoice/Asset/rebuild-1.13/js/inv.js $(document).on('click', '#inv_tax_submit', function -->
                <button class="inv_tax_submit btn btn-success" id="inv_tax_submit" type="button">
                    <i class="fa fa-check"></i><?= $translator->translate('i.submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?= $translator->translate('i.cancel'); ?>
                </button>
            </div>
        </div>

    </form>

</div>
