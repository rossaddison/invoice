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
<h1><?= Html::encode($title) ?></h1>
<div class="row">
 <div class="mb3 form-group">
<label for="url_key" class="form-label" style="background:lightblue"><?= $translator->translate('invoice.upload.url.key'); ?></label>
   <?= Html::encode($body['url_key'] ?? ''); ?>
 </div>
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
   <label for="client_id" class="form-label" style="background:lightblue"><?= $s->trans('client'); ?></label>
   <?= $upload->getClient()->id;?>
 </div>
</div>
