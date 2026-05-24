<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see InvController view function partialInvDeliveryLocation
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

$bg  = 'background:lightblue';
$sep = ':  ';
?>
<div class="card m-0">
    <div class="card-header">
      <i tooltip="data-bs-toggle" title="<?= $s->isDebugMode(6);?>">
              <?= Html::a($title, $urlGenerator->generate($actionName, $actionArguments), ['class' => 'text-decoration-none']); ?></i>
    </div>
    <div class="card-body">
        <div class="container">
          <?= Html::openTag('div', ['class' => 'row']); ?>
              <div class="row mb-3">
                  <div style="<?= $bg ?>"><span id="building_number"><?= $translator->translate('client.postaladdress.building.number') . ' ' . Html::encode($building_number ?? ''); ?></span></div>
              </div>
              <div class="row mb-3">
                  <div style="<?= $bg ?>"><?= $translator->translate('client.postaladdress.street.name') . $sep . Html::encode($address_1 ?? ''); ?></div>
              </div>
              <div class="row mb-3">
                  <div style="<?= $bg ?>"><?= $translator->translate('client.postaladdress.additional.street.name') . $sep . Html::encode($address_2 ?? ''); ?></div>
              </div>
              <div class="row mb-3">
                  <div style="<?= $bg ?>"><?= $translator->translate('client.postaladdress.city.name') . $sep . Html::encode($city ?? ''); ?></div>
              </div>
              <div class="row mb-3">
                  <div style="<?= $bg ?>"><?= $translator->translate('client.postaladdress.countrysubentity') . $sep . Html::encode($state ?? ''); ?></div>
              </div>
              <div class="row mb-3">
                  <div style="<?= $bg ?>"><?= $translator->translate('client.postaladdress.postalzone') . $sep . Html::encode($zip ?? ''); ?></div>
              </div>
              <div class="row mb-3">
                  <div style="<?= $bg ?>"><?= $translator->translate('client.postaladdress.country') . $sep . Html::encode($country ?? ''); ?></div>
              </div>
              <div class="row mb-3">
                  <div style="<?= $bg ?>"><?= $translator->translate('delivery.location.global.location.number') . $sep . Html::encode($global_location_number ?? ''); ?></div>
              </div>
          </div>
        </div>
      </div>
</div>