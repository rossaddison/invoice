<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * Related logic: see InvController function view_modal_create_credit
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $invoice_groups
 * @var string $csrf
 */

?>

<div id="create-credit-inv" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?php echo $translator->translate('create.credit.invoice'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="user_id" id="user_id" class="form-control" value="<?php echo $inv->getUser_id(); ?>">

                    <input type="hidden" name="parent_id" id="parent_id"
                           value="<?php echo $inv->getId(); ?>">

                    <input type="hidden" name="client_id" id="client_id" class="hidden"
                           value="<?php echo $inv->getClient_id(); ?>">

                    <input type="hidden" name="inv_date_created" id="inv_date_created"
                           value="<?php echo $credit_date = (new DateTimeImmutable('now'))->format('Y-m-d');
echo $credit_date; ?>">

                    <div class="form-group">
                        <label for="inv_password"><?php echo $translator->translate('password'); ?></label>
                        <input type="text" name="inv_password" id="inv_password" class="form-control"
                               value="<?php echo '' == $s->getSetting('invoice_pre_password') ? '' : $s->getSetting('invoice_pre_password'); ?>"
                               style="margin: 0 auto;" autocomplete="off">
                    </div>

                    <div>
                    <?php $credit_invoice_group = ''; ?>
                        <select name="inv_group_id" id="inv_group_id" class="hidden">
                            <?php
     /**
       * @var App\Invoice\Entity\Group $invoice_group
      */
     foreach ($invoice_groups as $invoice_group) { ?>
                                <option value="<?php echo $invoice_group->getId(); ?>"
                                    <?php if ($s->getSetting('default_invoice_group') === $invoice_group->getId()) {
                                        echo 'selected="selected"';
                                        $credit_invoice_group = Html::encode($invoice_group->getName() ?? '');
                                    } ?>>
                                    <?php if ($s->getSetting('default_invoice_group') === $invoice_group->getId()) {
                                        echo $credit_invoice_group;
                                    } else {
                                        echo '';
                                    } ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <p><strong><?php echo $translator->translate('credit.invoice.details'); ?></strong></p>

                    <ul>
                        <li><?php echo $translator->translate('client').': '.Html::encode($inv->getClient()?->getClient_name()); ?></li>
                        <li><?php echo $translator->translate('credit.invoice.date').': '.$credit_date; ?></li>
                        <li><?php echo $translator->translate('group').': '.(!empty($credit_invoice_group) ? $credit_invoice_group : ''); ?></li>
                    </ul>

                    <div class="alert alert-danger no-margin">
                        <?php echo $translator->translate('create.credit.invoice.alert'); ?>
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <button class="create-credit-confirm btn btn-success" id="create-credit-confirm" type="button">
                        <i class="fa fa-check"></i> <?php echo $translator->translate('confirm'); ?>
                    </button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss"modal">
                        <i class="fa fa-times"></i> <?php echo $translator->translate('cancel'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
