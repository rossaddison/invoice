<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use App\Invoice\Helpers\DateHelper;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

echo $alert;

?>
<h1><?= Html::encode($title) ?></h1>
<div class="row">
 <div class="mb3 form-group">
<label for="file_name_original" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.upload.filename.original'); ?></label>
   <?= Html::encode($body['file_name_original'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="file_name_new" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.upload.filename.new'); ?></label>
   <?= Html::encode($body['file_name_new'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="description" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.upload.filename.description'); ?></label>
   <?= Html::encode($body['description'] ?? ''); ?>
 </div>   
<div class="mb3 form-group">
  <label for="uploaded_date" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.upload.date'); ?></label>
<?php $date = $body['uploaded_date']; if ($date && $date != "0000-00-00") {    $datehelper = new DateHelper($s);  $date = $datehelper->date_from_mysql($date);} else {  $date = null;}?><?= Html::encode($date); ?></div>
 <div class="mb3 form-group">
   <label for="product_id" class="form-label" style="background:lightblue"><?= $s->trans('product'); ?></label>
   <?= $productimage->getProduct()->getProduct_id();?>
 </div>
</div>
