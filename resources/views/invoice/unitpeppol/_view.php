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

<!-- class customTable at C:\wamp64\www\yii3-i-4\src\Invoice\Asset\invoice\css\yii3i.css -->
<table class="customTable">
  <thead>
    <tr>
      <th><?= $translator->translate('invoice.unit.peppol.code'); ?></th>
      <th><?= $s->trans('name'); ?></th>
      <th><?= $s->trans('description'); ?></th>
      <th><?= $s->trans('unit_name'); ?></th>
      <th><?= $s->trans('unit_name_plrl'); ?></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><?= Html::encode($body['code'] ?? ''); ?></td>
      <td><?= Html::encode($body['name'] ?? ''); ?></td>
      <td><?= Html::encode($body['description'] ?: $translator->translate('invoice.unit.description.not.provided')); ?></td>
      <td><?= Html::encode($unitpeppol->getUnit()->getUnit_name()); ?></td>
      <td><?= Html::encode($unitpeppol->getUnit()->getUnit_name_plrl()); ?></td>
    </tr>
  </tbody>
</table>
<!-- Generated at CSSPortal.com -->


