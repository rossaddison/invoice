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

if (isset($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}

?>
<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['class' => 'row']); ?>
<div class="mb3 form-group">
<label for="name" class="form-label" style="background:lightblue"><?= $translator->translate('i.name'); ?></label>
   <?= Html::encode($body['name'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="description" class="form-label" style="background:lightblue"><?= $translator->translate('i.description'); ?></label>
   <?= Html::encode($body['description'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="quantity" class="form-label" style="background:lightblue"><?= $translator->translate('i.quantity'); ?></label>
   <?= Html::encode($body['quantity'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="price" class="form-label" style="background:lightblue"><?= $translator->translate('i.price'); ?></label>
   <?= Html::encode($body['price'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="discount_amount" class="form-label" style="background:lightblue"><?= $translator->translate('i.discount'); ?></label>
   <?= Html::encode($body['discount_amount'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="order" class="form-label" style="background:lightblue"><?= $translator->translate('i.order'); ?></label>
   <?= Html::encode($body['order'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="product_unit" class="form-label" style="background:lightblue"><?= $translator->translate('i.product_unit'); ?></label>
   <?= Html::encode($body['product_unit'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
   <label for="tax_rate_id" class="form-label" style="background:lightblue"><?= $translator->translate('i.tax_rate'); ?></label>
   <?= $quoteitem->getTaxRate()->getId();?>
 </div>
 <div class="mb3 form-group">
   <label for="product_id" class="form-label" style="background:lightblue"><?= $translator->translate('i.product'); ?></label>
   <?= $quoteitem->getProduct()->product_name;?>
 </div>
 <div class="mb3 form-group">
   <label for="quote_id" class="form-label" style="background:lightblue"><?= $translator->translate('i.quote'); ?></label>
   <?= $quoteitem->getQuote()->getId();?>
 </div>
</div>
