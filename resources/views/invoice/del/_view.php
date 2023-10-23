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
 * @var App\Invoice\Helpers\DateHelper $datehelper
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
        <label for="date_created" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $s->trans('date_created'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode(($body['date_created'])->format($datehelper->style()) ?? ''); ?></label>
    </div>
    <div class="row mb3 form-group">
        <label for="date_modified" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.common.date.modified'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode(($body['date_modified'])->format($datehelper->style()) ?? ''); ?></label>
    </div>
    <div class="row mb3 form-group">
        <label for="name" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.common.name'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['name'] ?? ''); ?></label>
    </div>
    <div class="row mb3 form-group">
        <label for="building_number" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.building.number'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['building_number'] ?? ''); ?></label>
    </div>
    <div class="row mb3 form-group">
        <label for="address_1" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.street.name'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['address_1'] ?? ''); ?></label>
    </div>
    <div class="row mb3 form-group">
        <label for="address_2" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.additional.street.name'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['address_2'] ?? ''); ?></label>
    </div>
    <div class="row mb3 form-group">
        <label for="city" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.city.name'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['city'] ?? ''); ?></label>
    </div>
    <div class="row mb3 form-group">
        <label for="state" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.countrysubentity'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['state'] ?? ''); ?></label>
    </div>
    <div class="row mb3 form-group">
        <label for="zip" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.postalzone'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['zip'] ?? ''); ?></label>
    </div>
    <div class="row mb3 form-group">
        <label for="country" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.country'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['country'] ?? ''); ?></label>
    </div>
    <div class="row mb3 form-group">
        <label for="global_location_number" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.delivery.location.global.location.number'); ?></label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['global_location_number'] ?? ''); ?></label>
    </div>
</div>
