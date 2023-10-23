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
 <div class="row mb3 form-group">
<label for="street_name" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.street.name'); ?></label>
   <?= Html::encode($body['street_name'] ?? ''); ?>
 </div>
 <div class="row mb3 form-group">
<label for="additional_street_name" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.additional.street.name'); ?></label>
   <?= Html::encode($body['additional_street_name'] ?? ''); ?>
 </div>
 <div class="row mb3 form-group">
<label for="building_number" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.building.number'); ?></label>
   <?= Html::encode($body['building_number'] ?? ''); ?>
 </div>
 <div class="row mb3 form-group">
<label for="city_name" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.city.name'); ?></label>
   <?= Html::encode($body['city_name'] ?? ''); ?>
 </div>
 <div class="row mb3 form-group">
<label for="postalzone" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.postalzone'); ?></label>
   <?= Html::encode($body['postalzone'] ?? ''); ?>
 </div>
 <div class="row mb3 form-group">
<label for="countrysubentity" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.countrysubentity'); ?></label>
   <?= Html::encode($body['countrysubentity'] ?? ''); ?>
 </div>
 <div class="row mb3 form-group">
<label for="country" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.country'); ?></label>
   <?= Html::encode($body['country'] ?? ''); ?>
 </div>
</div>
