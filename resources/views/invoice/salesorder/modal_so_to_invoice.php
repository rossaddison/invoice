<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @see id="so-to-invoice" triggered by <a href="#so-to-invoice" data-bs-toggle="modal"  style="text-decoration:none"> on views/salesorder/view.php line 86
 * @var App\Invoice\Group\GroupRepository $gR
 * @var App\Invoice\Entity\SalesOrder $so
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 * */

?>

<div id="so-to-invoice" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title"><?php echo $translator->translate('salesorder.to.invoice'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="client_id" id="client_id" value="<?= $so->getClient_id(); ?>">
                    <input type="hidden" name="so_id" id="so_id" value="<?= $so->getId(); ?>">
                    <input type="hidden" name="user_id" id="user_id" value="<?= $so->getUser_id(); ?>">
                    <div class="form-group">
                        <label for="password"><?= $translator->translate('password'); ?></label>
                        <input type="text" name="password" id="invoice_password" class="form-control"
                               value="<?= $s->getSetting('invoice_pre_password') == '' ? '' : $s->getSetting('invoice_pre_password') ?>"
                               autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="group_id">
                            <?= $translator->translate('group'); ?>
                        </label>
                        <select name="group_id" id="group_id" class="form-control">
                            <?php
                                /**
                                 * @var App\Invoice\Entity\Group $group
                                 */
                                foreach ($gR->findAllPreloaded() as $group) { ?>
                                <option value="<?= $group->getId(); ?>"
                                    <?php $s->check_select($s->getSetting('default_invoice_group'), $group->getId()); ?>>
                                    <?= Html::encode($group->getName()); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <button class="so_to_invoice_confirm btn btn-success" id="so_to_invoice_confirm" type="button">
                        <i class="fa fa-check"></i> <?= $translator->translate('submit'); ?>
                    </button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> <?= $translator->translate('cancel'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>