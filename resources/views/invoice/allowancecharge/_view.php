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
<div class="row">
 <div class="mb3 form-group">
<label for="identifier" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.invoice.allowance.or.charge'); ?></label>
   <?= Html::encode($body['identifier'] === '1' ? $translator->translate('invoice.invoice.allowance.or.charge.charge') : $translator->translate('invoice.invoice.allowance.or.charge.allowance')); ?>
 </div>
 <div class="mb3 form-group">
<label for="reason_code" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.invoice.allowance.or.charge.reason.code'); ?></label>
   <?= Html::encode($body['reason_code'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="reason" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.invoice.allowance.or.charge.reason'); ?></label>
   <?= Html::encode($body['reason'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="multiplier_factor_numeric" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.invoice.allowance.or.charge.multiplier.factor.numeric'); ?></label>
   <?= Html::encode($body['multiplier_factor_numeric'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="amount" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.invoice.allowance.or.charge.amount'); ?></label>
   <?= Html::encode($body['amount'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="base_amount" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.invoice.allowance.or.charge.base.amount'); ?></label>
   <?= Html::encode($body['base_amount'] ?? ''); ?>
 </div>
</div>
