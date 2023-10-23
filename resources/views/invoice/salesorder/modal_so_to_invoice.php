<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
// id="so-to-invoice" triggered by <a href="#so-to-invoice" data-toggle="modal"  style="text-decoration:none"> on views/salesorder/view.php line 86
?>
<div id="so-to-invoice" class="modal modal-lg" role="dialog" aria-labelledby="modal_so_to_invoice" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-body">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
            </div>       
            <div class="modal-header">
                <h5 class="col-12 modal-title text-center"><?php echo $translator->translate('invoice.salesorder.to.invoice'); ?></h5>
                <br>
            </div>
            <input type="hidden" name="client_id" id="client_id" value="<?= $so->getClient_id(); ?>">
            <input type="hidden" name="so_id" id="so_id" value="<?= $so->getId(); ?>">
            <input type="hidden" name="user_id" id="user_id" value="<?= $so->getUser_id(); ?>">
            <div class="form-group">
                <label for="password"><?= $s->trans('invoice_password'); ?></label>
                <input type="text" name="password" id="invoice_password" class="form-control"
                       value="<?= $s->get_setting('invoice_pre_password') == '' ? '' : $s->get_setting('invoice_pre_password') ?>"
                       autocomplete="off">
            </div>
            <div class="form-group">
                <label for="group_id">
                    <?= $s->trans('invoice_group'); ?>
                </label>
                <select name="group_id" id="group_id" class="form-control">
                    <?php foreach ($groups as $group) { ?>
                        <option value="<?= $group->getId(); ?>"
                            <?= $s->check_select($s->get_setting('default_invoice_group'), $group->getId()); ?>>
                            <?= Html::encode($group->getName()); ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <div class="btn-group">
                <button class="so_to_invoice_confirm btn btn-success" id="so_to_invoice_confirm" type="button">
                    <i class="fa fa-check"></i> <?= $s->trans('submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?= $s->trans('cancel'); ?>
                </button>
            </div>
        </div>
    </form>
</div>