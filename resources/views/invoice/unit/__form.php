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

<form id="settingForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
  <div class="row">
    <div class="mb-3 form-group">
        <label for="unit_name">
            <?= $s->trans('unit_name'); ?>
        </label>
        <input type="text" class="form-control" name="unit_name" id="unit_name" placeholder="Unit Name" required <?= in_array($body['unit_name'] ?? '', ['unit','service']) ? 'disabled' : '' ?>             
               value="<?= Html::encode($body['unit_name'] ?? '') ?>">
    </div>
    <div class="mb-3 form-group">
        <label for="unit_name_plrl">
            <?= $s->trans('unit_name_plrl'); ?>
        </label>
        <input type="text" class="form-control" name="unit_name_plrl" id="unit_name" placeholder="Unit Name Plural" value="<?= Html::encode($body['unit_name_plrl'] ?? '') ?>" required>
    </div>      
  </div>    
  <button type="submit" class="btn btn-primary"><?= $s->trans('submit'); ?></button>
</form>
