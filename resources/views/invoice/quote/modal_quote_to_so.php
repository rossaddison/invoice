<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
// id="quote-to-so" triggered by <a href="#quote-to-so" data-toggle="modal"  style="text-decoration:none"> on views/quote/view.php
?>
<div id="quote-to-so" class="modal modal-lg" role="dialog" aria-labelledby="modal_quote_to_so" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-body">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
            </div>       
            <div class="modal-header">
                <h5 class="col-12 modal-title text-center"><?php echo $translator->translate('invoice.quote.to.so'); ?></h5>
                <br>
            </div>
            <input type="hidden" name="client_id" id="client_id" value="<?php echo $quote->getClient_id(); ?>">            
            <input type="hidden" name="user_id" id="user_id" value="<?php echo $quote->getUser_id(); ?>">
            <div class="form-group">
                <label for="po_number"><?= $translator->translate('invoice.quote.with.purchase.order.number') ?></label>
                <input type="text" name="po_number" id="po_number" class="form-control" value="">
            </div>
            <div class="form-group">
                <label for="po_person"><?= $translator->translate('invoice.quote.with.purchase.order.person') ?></label>
                <input type="text" name="po_person" id="po_person" class="form-control" value="">
            </div>
            <div class="form-group">
                <label for="password"><?= $translator->translate('invoice.quote.to.so.password'); ?></label>
                <input type="text" name="password" id="password" class="form-control"
                       value="<?= $s->get_setting('so_pre_password') == '' ? '' : $s->get_setting('so_pre_password') ?>"
                       autocomplete="off">
            </div>
            <div class="form-group">
                <label for="so_group_id">
                    <?= $translator->translate('invoice.salesorder.default.group'); ?>
                </label>
                <select name="so_group_id" id="so_group_id" class="form-control">
                    <?php foreach ($groups as $group) { ?>
                        <option value="<?php echo $group->getId(); ?>"
                            <?php echo $s->check_select($s->get_setting('default_sales_order_group'), $group->getId()); ?>>
                            <?= Html::encode($group->getName()); ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <div class="btn-group">
                <button class="quote_to_so_confirm btn btn-success" id="quote_to_so_confirm" type="button">
                    <i class="fa fa-check"></i> <?= $s->trans('submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?= $s->trans('cancel'); ?>
                </button>
            </div>
        </div>
    </form>
</div>