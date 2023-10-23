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
<form id="FromDropDownForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('froms_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
 <div class="row mb3 form-group">
   <label for="id" hidden><?= $s->trans('id'); ?></label>
   <input type="text" name="id" id="id" class="form-control" value="<?= Html::encode($body['id'] ??  ''); ?>" hidden>
 </div>
 <div class="row mb3 form-group">
   <label for="email"><?= $s->trans('email'); ?></label>
   <input type="text" name="email" id="email" class="form-control"
 value="<?= Html::encode($body['email'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="include" class="form-label"><?= $translator->translate('invoice.from.include.in.dropdown'); ?></label>
   <input type="hidden" name="include" value="0">
   <input type="checkbox" name="include" id="include" value="1" <?php $s->check_select(Html::encode($body['include'] ??'' ), 1, '==', true) ?>>
 </div>
 <div class="mb3 form-group">
   <label for="default_email" class="form-label"><?= $translator->translate('invoice.from.default.in.dropdown'); ?></label>
   <input type="hidden" name="default_email" value="0">
   <input type="checkbox" name="default_email" id="default_email" value="1" <?php $s->check_select(Html::encode($body['default_email'] ??'' ), 1, '==', true) ?>>
 </div>

</div>

</div>

</div>
</form>
