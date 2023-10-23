<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}

?>
<h1><?= Html::encode($title) ?></h1>
<form id="InvItemAllowanceChargeForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('aciis_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
 <div class="mb3 form-group">
 <input type="hidden" name="id" id="id" class="form-control"
 value="<?= Html::encode($body['id'] ??  ''); ?>">
 </div>
 <div>   
 <input type="hidden" name="inv_id" id="inv_id" class="form-control"
 value="<?= Html::encode($body['inv_id'] ??  $inv_id); ?>">
 </div>
 <div>   
 <input type="hidden" name="inv_item_id" id="inv_item_id" class="form-control"
 value="<?= Html::encode($body['inv_item_id'] ??  $inv_item_id); ?>">
 </div>   
 <div class="mb3 form-group">
    <label for="allowance_charge_id"><?= $translator->translate('invoice.invoice.allowance.or.charge.item'); ?></label>
    <select name="allowance_charge_id" id="allowance_charge_id" class="form-control" required>
       <option value="0"><?= $translator->translate('invoice.invoice.allowance.or.charge'); ?></option>
         <?php
         foreach ($allowance_charges as $allowance_charge) { ?>
          <option value="<?= $allowance_charge->getId(); ?>"
           <?php $s->check_select(Html::encode($body['allowance_charge_id'] ?? $acii->getAllowance_charge_id()), $allowance_charge->getId()); ?>
           ><?= ($allowance_charge->getIdentifier() 
             ? $translator->translate('invoice.invoice.allowance.or.charge.charge')
             : $translator->translate('invoice.invoice.allowance.or.charge.allowance')) . ' ' . $allowance_charge->getReason() . ' ' . $allowance_charge->getReason_code() . ' '. $allowance_charge->getTaxRate()->getTax_rate_name(). ' ' . $translator->translate('invoice.invoice.allowance.or.charge.allowance'); ?></option>
         <?php } ?>
    </select>
 </div>    
<div class="form-group">
  <label for="amount"><?= $s->trans('amount'); ?></label>
      <div class="input-group has-feedback">
          <input type="text" name="amount" id="amount" class="form-control"
              value="<?= $s->format_amount((float)($body['amount'] ?? $acii->getAmount())); ?>">
              <span class="input-group-text"><?= $s->get_setting('currency_symbol'); ?></span>
      </div>
 </div>

</div>

</div>

</div>
</form>