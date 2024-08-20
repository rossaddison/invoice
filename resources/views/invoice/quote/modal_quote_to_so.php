<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @see id="quote-to-so" triggered by <a href="#quote-to-so" data-bs-toggle="modal"  style="text-decoration:none">
 * @see views/quote/view.php
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $groups
 * @var string $csrf
 */
?>

<div id="quote-to-so" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?= $translator->translate('invoice.quote.to.so'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="client_id" id="client_id" value="<?= $quote->getClient_id(); ?>">            
                    <input type="hidden" name="user_id" id="user_id" value="<?= $quote->getUser_id(); ?>">
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
                            <?php
                                /**
                                 * @var App\Invoice\Entity\Group $group
                                 */
                                foreach ($groups as $group) { ?>
                                <option value="<?php echo $group->getId(); ?>"
                                    <?php $s->check_select($s->get_setting('default_sales_order_group'), $group->getId()); ?>>
                                    <?= Html::encode($group->getName()); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <button class="quote_to_so_confirm btn btn-success" id="quote_to_so_confirm" type="button">
                        <i class="fa fa-check"></i> <?= $translator->translate('i.submit'); ?>
                    </button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> <?= $translator->translate('i.cancel'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>