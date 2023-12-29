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
<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['class' => 'row']); ?>
 <div class="mb3 form-group">
   <label for="id" class="form-label" style="background:lightblue"><?= $translator->translate('i.id'); ?></label>
   <?= Html::encode($body['id'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
   <label for="name" class="form-label" style="background:lightblue"><?= $translator->translate('i.name'); ?></label>
   <?= Html::encode($body['name'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
   <label for="identifier_format" class="form-label" style="background:lightblue"><?= $translator->translate('i.identifier_format'); ?></label>
   <?= Html::encode($body['identifier_format'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
   <label for="left_pad" class="form-label" style="background:lightblue"><?= $translator->translate('i.left_pad'); ?></label>
   <?= Html::encode($body['left_pad'] ?? ''); ?>
 </div>
  <div class="mb3 form-group">
   <label for="next_id" class="form-label" style="background:lightblue"><?= $translator->translate('i.next_id'); ?></label>
   <?= Html::encode($body['next_id'] ??  ''); ?>
 </div>   
</div>
