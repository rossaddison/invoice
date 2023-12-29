<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use App\Invoice\Helpers\DateHelper;

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
<label for="name" class="form-label" style="background:lightblue"><?= $translator->translate('i.name'); ?></label>
   <?= Html::encode($body['name'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="address_1" class="form-label" style="background:lightblue"><?= $translator->translate('i.street_address'); ?></label>
   <?= Html::encode($body['address_1'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="address_2" class="form-label" style="background:lightblue"><?= $translator->translate('i.street_address_2'); ?></label>
   <?= Html::encode($body['address_2'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="city" class="form-label" style="background:lightblue"><?= $translator->translate('i.city'); ?></label>
   <?= Html::encode($body['city'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="state" class="form-label" style="background:lightblue"><?= $translator->translate('i.state'); ?></label>
   <?= Html::encode($body['state'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="zip" class="form-label" style="background:lightblue"><?= $translator->translate('i.zip'); ?></label>
   <?= Html::encode($body['zip'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="country" class="form-label" style="background:lightblue"><?= $translator->translate('i.country'); ?></label>
   <?= Html::encode($body['country'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="phone" class="form-label" style="background:lightblue"><?= $translator->translate('i.phone'); ?></label>
   <?= Html::encode($body['phone'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="fax" class="form-label" style="background:lightblue"><?= $translator->translate('i.fax'); ?></label>
   <?= Html::encode($body['fax'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="email" class="form-label" style="background:lightblue"><?= $translator->translate('i.email'); ?></label>
   <?= Html::encode($body['email'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="web" class="form-label" style="background:lightblue"><?= $translator->translate('i.web'); ?></label>
   <?= Html::encode($body['web'] ?? ''); ?>
 </div>
</div>
