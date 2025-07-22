<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * @see InvController view function partial_inv_delivery_location
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>
<div class="panel panel-default no-margin">
    <div class="panel-heading">
      <i tooltip="data-bs-toggle" title="<?php echo $s->isDebugMode(6); ?>">
              <?php echo Html::a($title, $urlGenerator->generate($actionName, $actionArguments), ['style' => 'text-decoration:none']); ?></i>
    </div>
    <div class="panel-body clearfix">
        <div class="container">
          <?php echo Html::openTag('div', ['class' => 'row']); ?>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><span id="building_number"><?php echo $translator->translate('client.postaladdress.building.number').' '.Html::encode($building_number ?? ''); ?></span></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?php echo $translator->translate('client.postaladdress.street.name').':  '.Html::encode($address_1 ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?php echo $translator->translate('client.postaladdress.additional.street.name').':  '.Html::encode($address_2 ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?php echo $translator->translate('client.postaladdress.city.name').':  '.Html::encode($city ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?php echo $translator->translate('client.postaladdress.countrysubentity').':  '.Html::encode($state ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?php echo $translator->translate('client.postaladdress.postalzone').':  '.Html::encode($zip ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?php echo $translator->translate('client.postaladdress.country').':  '.Html::encode($country ?? ''); ?></div>
              </div>
              <div class="row mb3 form-group">
                  <div style="background:lightblue"><?php echo $translator->translate('delivery.location.global.location.number').':  '.Html::encode($global_location_number ?? ''); ?></div>
              </div>
          </div>
        </div>
      </div> 
</div>    