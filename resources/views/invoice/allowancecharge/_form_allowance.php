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
<h1><?= (Html::a($title,'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AllowanceCharge/',['class'=>'btn btn-primary'])); ?></h1>
<form id="AllowanceChargeForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('allowancecharges_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
 <div class="mb3 form-group">
   <input type="hidden" name="id" id="id" class="form-control"
 value="<?= Html::encode($body['id'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="reason"><?= $translator->translate('invoice.invoice.allowance.or.charge.reason'); ?></label>
   <select name="reason" id="reason" class="form-control">
        <option value="0"><?= $s->trans('none'); ?></option>
        <?php foreach ($allowances as $key => $value) { ?>
            <option value="<?= $value; ?>" <?php $s->check_select($body['reason'] ?? 'Discount', $value) ?>>
                <?= ucfirst((string)$key).' '.$value; ?>
            </option>
        <?php } ?>
    </select>
 </div>
 <div class="mb3 form-group">
   <label for="multiplier_factor_numeric"><?= $translator->translate('invoice.invoice.allowance.or.charge.multiplier.factor.numeric'); ?></label>
   <input type="text" name="multiplier_factor_numeric" id="multiplier_factor_numeric" class="form-control"
 value="<?= Html::encode($body['multiplier_factor_numeric'] ??  '20'); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="amount"><?= $translator->translate('invoice.invoice.allowance.or.charge.amount'); ?></label>
   <input type="text" name="amount" id="amount" class="form-control"
 value="<?= Html::encode($body['amount'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="base_amount"><?= $translator->translate('invoice.invoice.allowance.or.charge.base.amount'); ?></label>
   <input type="text" name="base_amount" id="base_amount" class="form-control"
 value="<?= Html::encode($body['base_amount'] ??  '1000'); ?>">
 </div>
 <div class="mb3 form-group">
    <label for="tax_rate_id"><?= $translator->translate('invoice.invoice.tax.rate'); ?></label>
    <select name="tax_rate_id" id="tax_rate_id" class="form-control">
       <option value="0"></option>
         <?php foreach ($tax_rates as $tax_rate) { ?>
          <option value="<?= $tax_rate->getTax_rate_id(); ?>"
           <?php $s->check_select(Html::encode($body['tax_rate_id'] ?? ''), $tax_rate->getTax_rate_id()) ?>
           ><?= $tax_rate->getTax_rate_name(). ' '. $tax_rate->getTax_rate_percent(); ?></option>
         <?php } ?>
    </select>
 </div>   
    
</div>
</div>
</div>
</form>
