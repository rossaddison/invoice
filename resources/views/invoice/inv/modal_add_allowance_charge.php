<?php
  declare(strict_types=1);
  
  use App\Invoice\Helpers\NumberHelper;
  
  $numberhelper = new NumberHelper($s);
  
  // id="add-inv-allowance-charge" triggered by <a href="#add-inv-allowance-charge" data-toggle="modal"  style="text-decoration:none"> on views/inv/view.php 
  // see also Invoice/Asset/rebuild-1.13/js/inv.js/$(document).on('click', '#inv_tax_submit', function () {
  // see also InvController/save_inv_allowance_charge
?>

<div id="add-inv-allowance-charge" class="modal modal-lg" role="dialog" aria-labelledby="modal_add_inv_allowance_charge" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
        </div>
        <div class="modal-body">
            <div class="mb3 form-group">
                <h6><?= $translator->translate('invoice.invoice.allowance.or.charge.add'); ?></h6>
            </div>
            <div class="mb3 form-group">
                <label for="inv_allowance_charge_id">
                    <?= $translator->translate('invoice.invoice.allowance.or.charge'); ?>
                </label>
                <div>
                    <select name="inv_allowance_charge_id" id="inv_allowance_charge_id" class="form-control" required>
                        <option value="0"><?= $s->trans('none'); ?></option>
                        <?php foreach ($allowance_charges as $ac) { ?>
                            <option value="<?= $ac->getId(); ?>">
                                <?= 
                                   ($ac->getIdentifier() 
                                    ? $translator->translate('invoice.invoice.allowance.or.charge.charge')
                                    : $translator->translate('invoice.invoice.allowance.or.charge.allowance')). 
                                    '  '. $ac->getReason_code(). 
                                    ' '.
                                    $ac->getReason(); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="mb3 form-group">
                <label for="inv_allowance_charge_amount">
                    <?= $translator->translate('invoice.invoice.allowance.or.charge.amount'); ?>
                </label>
                <input type="text" name="inv_allowance_charge_amount" id="inv_allowance_charge_amount" class="form-control"
                       value="<?= $translator->translate('invoice.invoice.allowance.or.charge.amount'); ?>"
                       autocomplete="off">
            </div>
        </div>
        <div class="modal-footer">
            <div class="btn-group">
                <!-- see src/Invoice/Asset/rebuild-1.13/js/inv.js $(document).on('click', '#allowance_charge_submit', function -->
                <button class="allowance_charge_submit btn btn-success" id="allowance_charge_submit" type="button">
                    <i class="fa fa-check"></i><?= $s->trans('submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?= $s->trans('cancel'); ?>
                </button>
            </div>
        </div>
    </form>

</div>
