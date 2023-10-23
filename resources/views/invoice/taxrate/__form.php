<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use App\Invoice\Enum\StoreCoveTaxType;

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
<form id="taxrateForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
        <h1 class="headerbar-title"><?= $title; ?></h1>
        <?php
            $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]);
            echo (string)$response->getBody();
        ?>
        <div class="mb-3 form-group btn-group-sm">
        </div>
</div>
  <div class="row">
    <label for="tax_rate_name"><?= $translator->translate('invoice.tax.rate.name'); ?></label> 
    <div class="mb-3 form-group">
        <input type="text" class="form-control" name="tax_rate_name" id="tax_rate_name" placeholder="Tax Rate Name" value="<?= Html::encode($body['tax_rate_name'] ?? ''); ?>" required>
    </div>
    <label for="tax_rate_percent"><?= $translator->translate('invoice.tax.rate.percent'); ?></label> 
    <div class="mb-3 form-group">
        <input type="text" class="form-control" name="tax_rate_percent" id="tax_rate_percent" placeholder="Tax Rate Percent" value="<?= Html::encode($body['tax_rate_percent'] ?? ''); ?>" required>
        <span class="form-control-feedback">%</span>
    </div>
    <div  class="p-2">
        <label for="tax_rate_default" class="control-label ">
            <?= $translator->translate('invoice.default'); ?>
            <input id="tax_rate_default" name="tax_rate_default" type="checkbox" value="1"
            <?php $s->check_select(Html::encode($body['tax_rate_default'] ?? ''), 1, '==', true) ?>>
        </label>   
    </div>
    <div class="mb-3 form-group">
        <label for="peppol_tax_rate_code"><?= $translator->translate('invoice.peppol.tax.rate.code'); ?></label> 
        <select name="peppol_tax_rate_code" id="peppol_tax_rate_code" class="form-control" placeholder="<?= $translator->translate('invoice.storecove.tax.rate.code'); ?>" required>
        <?php foreach ($peppol_tax_rate_code_array as $key => $value) { ?>
            <option value="<?= $value['Id']; ?>" 
                <?php $s->check_select(($body['peppol_tax_rate_code'] ?? ''), $value['Id']); ?>>
                <?= $value['Id'] . str_repeat("-", 10) . $value['Name'] . str_repeat("-", 10) . $value['Description']; ?>
            </option>
        <?php } ?>
        </select>
    </div>
    <div class="mb-3 form-group">
        <label for="storecove_tax_type"><?= $translator->translate('invoice.storecove.tax.rate.code'); ?></label> 
        <select name="storecove_tax_type" id="storecove_tax_type" class="form-control" placeholder="<?= $translator->translate('invoice.storecove.tax.rate.code'); ?>">
        <?php foreach (array_column(StoreCoveTaxType::cases(),'value') as $key => $value) { ?>
            <option value="<?= $value; ?>" 
                <?php $s->check_select(($body['storecove_tax_type'] ?? ''), $value); ?>>
                <?= $value; ?>
            </option>
        <?php } ?>
        </select>
    </div>  
  </div>      
</form>
 