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
<form id="InvAllowanceChargeForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $translator->translate('invoice.invoice.allowance.or.charge'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
 <div class="mb3 form-group" hidden>
    <label for="allowance_charge_id">Allowance charge</label>
    <select name="allowance_charge_id" id="allowance_charge_id" class="form-control">
       <option value="0">Allowance charge</option>
         <?php foreach ($allowance_charges as $allowance_charge) { ?>
          <option value="<?= $allowance_charge->getId(); ?>"
           <?php $s->check_select(Html::encode($body['allowance_charge_id'] ?? ''), $allowance_charge->getId()) ?>
           ><?= $allowance_charge->getId(); ?></option>
         <?php } ?>
    </select>
 </div>
 <div class="mb3 form-group" hidden>
   <label for="id"><?= $s->trans('id'); ?></label>
   <input type="text" name="id" id="id" class="form-control"
 value="<?= Html::encode($body['id'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="identifier"><?= ($allowancecharge->getIdentifier() ? $translator->translate('invoice.invoice.allowance.or.charge.charge') : $translator->translate('invoice.invoice.allowance.or.charge.allowance')). ' :'. $allowancecharge->getReason(). ' '. $allowancecharge->getReason_code(). ' '. $allowancecharge->getTaxRate()->getTax_rate_name(); ?></label>
 </div>   
 <div class="mb3 form-group">
   <label for="amount"><?= $translator->translate('invoice.invoice.allowance.or.charge.amount'); ?></label>
   <input type="text" name="amount" id="amount" class="form-control"
 value="<?= Html::encode($body['amount'] ??  ($allowancecharge->getAmount() ?: 0.00)); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="vat"><?= $allowancecharge->getIdentifier() ? $translator->translate('invoice.invoice.allowance.or.charge.charge.vat') : $translator->translate('invoice.invoice.allowance.or.charge.allowance.vat'); ?></label>
   <input type="text" name="vat" id="vat" class="form-control"
 value="<?= Html::encode($body['vat'] ??  ($allowancecharge->getVat() ?: 0.00)); ?>">
 </div>   
</div>

</div>

</div>
</form>
