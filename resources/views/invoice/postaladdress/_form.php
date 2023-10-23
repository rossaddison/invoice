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
<form id="PostalAddressForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('postaladdresses_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
 <div class="mb3 form-group">
   <label hidden for="id"><?= $s->trans('id'); ?></label>
   <input type="text" hidden name="id" id="id" class="form-control"
 value="<?= Html::encode($body['id'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label hidden for="client_id"></label>
   <input type="text" hidden name="client_id" id="client_id" class="form-control"
 value="<?= Html::encode($body['client_id'] ??  $client_id); ?>">
 </div>   
 <div class="mb3 form-group">
   <label for="street_name"><?= $translator->translate('invoice.client.postaladdress.street.name'); ?></label>
   <input type="text" name="street_name" id="street_name" class="form-control"
 value="<?= Html::encode($body['street_name'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="additional_street_name"><?= $translator->translate('invoice.client.postaladdress.additional.street.name'); ?></label>
   <input type="text" name="additional_street_name" id="additional_street_name" class="form-control"
 value="<?= Html::encode($body['additional_street_name'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="building_number"><?= $translator->translate('invoice.client.postaladdress.building.number'); ?></label>
   <input type="text" name="building_number" id="building_number" class="form-control"
 value="<?= Html::encode($body['building_number'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="city_name"><?= $translator->translate('invoice.client.postaladdress.city.name'); ?></label>
   <input type="text" name="city_name" id="city_name" class="form-control"
 value="<?= Html::encode($body['city_name'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="postalzone"><?= $translator->translate('invoice.client.postaladdress.postalzone'); ?></label>
   <input type="text" name="postalzone" id="postalzone" class="form-control"
 value="<?= Html::encode($body['postalzone'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="countrysubentity"><?= $translator->translate('invoice.client.postaladdress.countrysubentity'); ?></label>
   <input type="text" name="countrysubentity" id="countrysubentity" class="form-control"
 value="<?= Html::encode($body['countrysubentity'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="country"><?= $translator->translate('invoice.client.postaladdress.country'); ?></label>
   <input type="text" name="country" id="country" class="form-control"
 value="<?= Html::encode($body['country'] ??  ''); ?>">
 </div>

</div>

</div>

</div>
</form>
