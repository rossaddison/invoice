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
 <div  class="form-check form-switch">
    <label for="current" class="form-check-label ">
      <?= $translator->translate('i.active'); ?>
      <input class="form-check-input" id="current" name="current" type="checkbox" value="1" disabled
      <?php $s->check_select(Html::encode($body['current'] ?? ''), 1, '==', true) ?>>
    </label>   
 </div>
 </div> 
 <div class="mb3 form-group">
<label for="mobile" class="form-label" style="background:lightblue"><?= $translator->translate('i.mobile'); ?></label>
   <?= Html::encode($body['mobile'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="email" class="form-label" style="background:lightblue"><?= $translator->translate('i.email'); ?></label>
   <?= Html::encode($body['email'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
   <label for="company_id" class="form-label" style="background:lightblue"><?= $translator->translate('i.name'); ?></label>
   <?= Html::encode($profile->getCompany()->name); ?>
 </div>
</div>
