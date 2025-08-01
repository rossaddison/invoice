<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see id="quote-to-invoice" triggered by <a href="#quote-to-invoice" data-bs-toggle="modal"  style="text-decoration:none">
 * Related logic: see views/quote/view.php
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $groups
 * @var string $csrf
 */

?>

<div id="quote-to-invoice" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?= $translator->translate('quote.to.invoice'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="client_id" id="client_id" value="<?= $quote->getClient_id(); ?>">            
                    <input type="hidden" name="user_id" id="user_id" value="<?= $quote->getUser_id(); ?>">
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
                                foreach ($groups as $group) { ?>
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
                    <button class="quote_to_invoice_confirm btn btn-success" id="quote_to_invoice_confirm" type="button">
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
