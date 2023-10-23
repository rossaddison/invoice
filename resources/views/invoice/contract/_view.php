<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use App\Invoice\Helpers\DateHelper;

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
$datehelper = new DateHelper($s);
?>
<h1><?= Html::encode($title) ?></h1>
<div class="row">
 <div class="mb3 form-group">
<label for="reference" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.client.contract.reference'); ?></label>
   <?= Html::encode($body['reference'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="name" class="form-label" style="background:lightblue"><?= $s->trans('name'); ?></label>
   <?= Html::encode($body['name'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="period_start" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.client.contract.period.start'); ?></label>
   <?= (($body['period_start'])->format($datehelper->style()) ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="period_end" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.client.contract.period.end'); ?></label>
   <?= (($body['period_end'])->format($datehelper->style()) ?? ''); ?>
 </div>
</div>
