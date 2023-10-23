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
 <div class="row mb3 form-group">
<label for="auto_reference" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('auto_reference'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['auto_reference'] ?? ''); ?><\label>
 </div>
 <div class="row mb3 form-group">
<label for="provider" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('provider'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['provider'] ?? ''); ?><\label>
 </div>
</div>
