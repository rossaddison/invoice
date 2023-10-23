<?php
  declare(strict_types=1);  
  use Yiisoft\Html\Html;
?>
<div class="panel panel-default no-margin">
    <div class="panel-heading">
      <i tooltip="data-bs-toggle" title="<?= $s->isDebugMode(6);?>"><?= Html::a($title, $delivery_location_url,['style'=>'text-decoration:none']); ?></i>
    </div>
    <div class="panel-body clearfix">
        <div class="container">
          <div class="row">
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><span id="building_number"><?= $translator->translate('invoice.client.postaladdress.building.number').' '.Html::encode($building_number ?? ''); ?></span></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.street.name').':  '. Html::encode($address_1 ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.additional.street.name').':  '. Html::encode($address_2 ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.city.name').':  '. Html::encode($city ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.countrysubentity').':  '. Html::encode($state ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.postalzone').':  '. Html::encode($zip ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?= $translator->translate('invoice.client.postaladdress.country').':  '. Html::encode($country ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?= $translator->translate('invoice.delivery.location.global.location.number').':  '. Html::encode($global_location_number ?? ''); ?></div>
              </div>
          </div>
        </div>
      </div> 
</div>    