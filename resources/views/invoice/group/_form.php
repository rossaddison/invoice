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
<form id="GroupForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $translator->translate('i.groups_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<?= Html::openTag('div', ['class' => 'row']); ?>
 <div class="mb3 form-group">
   <label for="name"><?= $translator->translate('i.name'); ?></label>
   <input type="text" name="name" id="name" class="form-control"
 value="<?= Html::encode($body['name'] ??  ''); ?>" required placeholder="<?= $translator->translate('i.name'); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="identifier_format"><?= $translator->translate('i.identifier_format'); ?></label>
   <input type="text" name="identifier_format" id="identifier_format" class="form-control taggable"
    value="<?= Html::encode($body['identifier_format'] ??  ''); ?>" placeholder="INV-{{{id}}}" required>
 </div>
 <div class="mb3 form-group">
   <label for="left_pad"><?= $translator->translate('i.left_pad'); ?></label>
   <input type="text" name="left_pad" id="left_pad" class="form-control"
 value="<?= Html::encode($body['left_pad'] ??  '0'); ?>" required placeholder="0">
 </div>
 <div class="mb3 form-group">
   <label for="next_id"><?= $translator->translate('i.next_id'); ?></label>
   <input type="number" name="next_id" id="next_id" class="form-control"
 value="<?= Html::encode($body['next_id'] ??  '1'); ?>" required placeholder="1">
 </div>
</div>
<hr>
<div class="form-group no-margin">
    <label for="tags_client"><?= $translator->translate('i.identifier_format_template_tags'); ?></label>
    <p class="small"><?= $translator->translate('i.identifier_format_template_tags_instructions'); ?></p>
    <div class="col-sm-6 col-md-4">
    <select id="tags_client" class="tag-select form-control">
        <option value="{{{id}}}">
             <?= $translator->translate('i.id'); ?>
        </option>
        <option value="{{{year}}}">
            <?= $translator->translate('i.current_year'); ?>
        </option>
        <option value="{{{yy}}}">
            <?= $translator->translate('i.current_yy'); ?>
        </option>
        <option value="{{{month}}}">
            <?= $translator->translate('i.current_month'); ?>
        </option>
        <option value="{{{day}}}">
            <?= $translator->translate('i.current_day'); ?>
        </option>
    </select>
    </div>
</div>   
</form>
