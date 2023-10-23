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
<form id="ContractForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $translator->translate('invoice.invoice.contract.add'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
 <div class="mb3 form-group">
   <input type="hidden" name="id" id="id" class="form-control"
 value="<?= Html::encode($body['id'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <input type="hidden" name="client_id" id="client_id" class="form-control"
 value="<?= Html::encode($body['client_id'] ??  $client_id); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="reference"><?= $translator->translate('invoice.invoice.contract.reference'); ?></label>
   <input type="text" name="reference" id="reference" class="form-control"
 value="<?= Html::encode($body['reference'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="name"><?= $translator->translate('invoice.invoice.contract.name'); ?></label>
   <input type="text" name="name" id="name" class="form-control"
 value="<?= Html::encode($body['name'] ??  ''); ?>">
</div>
<div class="mb-3 form-group has-feedback"> 
    <label form-label for="period_start"><?= $translator->translate('invoice.invoice.contract.period.start') ." (".  $datehelper->display().") "; ?></label><div class="mb3 input-group"> 
<input type="text" name="period_start" id="period_start" placeholder="<?= $datehelper->display(); ?>" 
       class="form-control input-sm datepicker" 
       value="<?= Html::encode($datehelper->date_from_mysql($body['period_start'] ?? new DateTimeImmutable('now'))); ?>"> 
<span class="input-group-text"> 
<i class="fa fa-calendar fa-fw"></i> 
 </span> 
</div>
</div>
<div class="mb-3 form-group has-feedback">
    <label form-label for="period_end"><?= $translator->translate('invoice.invoice.contract.period.end') ." (".  $datehelper->display().") "; ?></label><div class="mb3 input-group"> 
    <input type="text" name="period_end" id="period_end" placeholder="<?= $datehelper->display(); ?>" 
           class="form-control input-sm datepicker" 
           value="<?= Html::encode($datehelper->date_from_mysql($body['period_end'] ?? new DateTimeImmutable('now'))); ?>"> 
<span class="input-group-text"> 
<i class="fa fa-calendar fa-fw"></i> 
 </span> 
</div>
</div> 

</div>

</div>

</div>
</form>
