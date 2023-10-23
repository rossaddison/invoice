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
<form id="UnitPeppolForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('unitpeppols_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
 <div class="mb3 form-group">
    <label for="unit_id"><?= $s->trans('unit');?></label>
    <select name="unit_id" id="unit_id" class="form-control" required>
          <?php foreach ($units as $unit) { ?>
          <option value="<?= $unit->getUnit_id(); ?>"
           <?php $s->check_select(Html::encode($body['unit_id'] ?? ''), $unit->getUnit_id()) ?>
           ><?= $unit->getUnit_name().  ' '. $unit->getUnit_name_plrl(); ?></option>
         <?php } ?>
    </select>
 </div>
 <div class="mb3 form-group">
   <input type="hidden" name="id" id="id" class="form-control"
 value="<?= Html::encode($body['id'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="code"><?= $translator->translate('invoice.unit.peppol.code'); ?></label>
   <select name="code" id="code" class="form-control" required>
        <?php foreach ($eneces as $key => $value) { ?>
            <option value="<?= $value['Id']; ?>" <?php $s->check_select($body['code'] ?? '', $value['Id']); ?>>
                <?php 
                $description = (array_key_exists('Description', $eneces[$key]) ? $eneces[$key]['Description'] : '');
                echo ' '.$eneces[$key]['Id'].' -------- '.$eneces[$key]['Name'] .' ------ '. $description. '</td>' ?>
            </option>
        <?php } ?>
    </select>
 </div>
 <div class="mb3 form-group" hidden>
   <label for="name"><?= $s->trans('name'); ?></label>
   <input type="text" name="name" id="name" class="form-control"
 value="<?= Html::encode($body['name'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group" hidden>
   <label for="description"><?= $s->trans('description'); ?></label>
   <input type="text" name="description" id="description" class="form-control"
 value="<?= Html::encode($body['description'] ??  ''); ?>">
 </div>
<!-- 
https://dev.to/dcodeyt/creating-beautiful-html-tables-with-css-428l
class styled-table found at C:\wamp64\www\yii3-i\src\Invoice\Asset\invoice\css\yii3i.css
--> 
<table class="styled-table">
  <thead>
    <tr>
      <th><?php $s->trans('id'); ?></th>
      <th><?php $s->trans('name'); ?></th>
      <th><?php $s->trans('description'); ?></th>
    </tr>
   </thead>
   <tbody>
     <?php foreach ($eneces as $key => $value) {
       $description = (array_key_exists('Description', $eneces[$key]) ? $eneces[$key]['Description'] : '');
       echo '<tr><td>'.$eneces[$key]['Id'].'</td><td>'.$eneces[$key]['Name'] .'</td><td>'. $description. '</td>';
     } ?>
   </tbody>
</table>
</div>

</div>

</div>
</form>