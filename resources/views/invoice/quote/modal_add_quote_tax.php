<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * @see views/quote/view.php line 67: id="add-quote-tax" triggered by
 *      <a href="#add-quote-tax" data-bs-toggle="modal"  style="text-decoration:none">
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $taxRates
 * @var string $csrf
 */
?>

<div id="add-quote-tax" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?php echo $translator->translate('add.quote.tax'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">
                    <div class="mb3 form-group">
                        <label for="tax_rate_id">
                            <?php echo $translator->translate('tax.rate'); ?>
                        </label>
                        <div>
                            <select name="tax_rate_id" id="tax_rate_id" class="form-control" required>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\TaxRate $taxRate
                                     */
                                    foreach ($taxRates as $taxRate) { ?>
                                    <option value="<?php echo $taxRate->getTaxRateId(); ?>">
                                        <?php echo $percent = $numberHelper->format_amount($taxRate->getTaxRatePercent());
                                        $name               = Html::encode($taxRate->getTaxRateName());
                                        if ($percent >= 0.00 && null !== $percent && strlen($name) > 0) {
                                            $percent.'% - '.$name;
                                        } else {
                                        } ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb3 form-group">
                        <label for="include_item_tax">
                            <?php echo $translator->translate('tax.rate.placement'); ?>
                        </label>

                        <div>
                            <select name="include_item_tax" id="include_item_tax" class="form-control">
                                <?php if ('0' === $s->getSetting('enable_vat_registration')) { ?>
                                <option value="0">
                                    <?php echo $translator->translate('apply.before.item.tax'); ?>
                                </option>
                                <option value="1">
                                    <?php echo $translator->translate('apply.after.item.tax'); ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <button class="quote_tax_submit btn btn-success" id="quote_tax_submit" type="button">
                        <i class="fa fa-check"></i><?php echo $translator->translate('submit'); ?>
                    </button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> <?php echo $translator->translate('cancel'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
