<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */


?>
<h1><?= Html::encode($title) ?></h1>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ClientNoteForm')
    ->open() ?>

<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('clientnotes_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
 <div class="mb3 form-group">
    <label for="client_id" required>Client</label>
    <select name="client_id" id="client_id" class="form-control" required>   
        <option value=""><?= $s->trans('client'); ?></option>
         <?php foreach ($clients as $client) { ?>
          <option value="<?= $client->getClient_id(); ?>"
           <?php $s->check_select(Html::encode($form->getClient_id() ?? ''), $client->getClient_id()) ?>
           ><?= $client->getClient_name() . ' '. $client->getClient_surname(); ?></option>
         <?php } ?>
    </select>
 </div>
 <div class="mb3 form-group">
   <input type="hidden" name="id" id="id" class="form-control"
 value="<?= Html::encode($form->getClient_id() ??  ''); ?>">
 </div>
 <div class="mb-3 form-group has-feedback"> 
 <?php
    $date = $datehelper->get_or_set_with_style($form->getDate() ?? new \DateTimeImmutable('now'));
 ?>  
<label form-label for="date" required><?= $s->trans('date') ." (".  $datehelper->display().") "; ?></label><div class="mb3 input-group"> 
<input type="text" name="date" id="date" placeholder="<?= $datehelper->display(); ?>" 
       class="form-control input-sm datepicker" required 
       value="<?= null!== $date ? Html::encode($date instanceof \DateTimeImmutable ? $date->format($datehelper->style()) : $date) : null; ?>" role="presentation" autocomplete="off"> 
<span class="input-group-text"> 
<i class="fa fa-calendar fa-fw"></i> 
 </span> 
</div>
</div>
<div class="mb3 form-group">
   <label for="note" required><?= $s->trans('note'); ?></label>
   <input type="text" name="note" id="note" class="form-control" required
 value="<?= Html::encode($form->getNote() ?? ''); ?>">
 </div>

</div>

</div>

</div>
<?= Form::tag()->close() ?>