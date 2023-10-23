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
<form id="SumexForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('sumexs_form'); ?></h1>
    <?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
    <?php echo (string)$response->getBody(); ?>
<div id="content">
    <div class="row">
    <div class="mb3 form-group" hidden>
       <label for="invoice"><?= $s->trans('invoice'); ?></label>
       <input type="text" name="invoice" id="invoice" class="form-control" required
     value="<?= Html::encode($body['invoice'] ??  ''); ?>">
    </div>
    <div class="mb3 form-group has-feedback">
        <span class="input-group"><?= $s->trans('reason'); ?></span>
        <select name="reason" id="reason" class="form-control input-sm">
            <?php $reasons = [
                'disease',
                'accident',
                'maternity',
                'prevention',
                'birthdefect',
                'unknown'
            ]; ?>
            <?php foreach ($reasons as $key => $reason): ?>
                <?php $selected = ($body['reason'] === $key ? " selected" : ""); ?>
                <option value="<?= $key; ?>"<?= $selected; ?>>
                    <?= $s->trans('reason_' . $reason); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb3 form-group has-feedback">
       <label for="diagnosis"><?= $s->trans('invoice_sumex_diagnosis'); ?></label>
       <textarea name="diagnosis" id="diagnosis" class="form-control" required>
            <?= Html::encode($body['diagnosis'] ?:  ''); ?>
       </textarea>
    </div>
    <div class="mb3 form-group has-feedback">
       <label for="observations"><?= $s->trans('sumex_observations'); ?></label>
       <textarea name="observations" id="observations" class="form-control" required>
            <?= Html::encode($body['observations'] ?:  ''); ?>
       </textarea>
    </div>
    <div class="mb-3 form-group has-feedback">
        <?php
           $tdate = $datehelper->get_or_set_with_style($body['treatmentstart']);
        ?>
        <label form-label for="treatmentstart"><?= $s->trans('treatment_start') .' ('.$datehelper->display().')'; ?></label>
        <div class="input-group">
            <input type="text" name="treatmentstart" id="treatmentstart" placeholder="<?= ' ('.$datehelper->display().')';?>" required
                   class="form-control input-sm datepicker" readonly                   
                   value="<?= null!== $tdate ? Html::encode($tdate instanceof \DateTimeImmutable ? $tdate->format($datehelper->style()) : $tdate) : null; ?>" role="presentation" autocomplete="off">
            <span class="input-group-text">
            <i class="fa fa-calendar fa-fw"></i>
        </span>
        </div>        
    </div>  
    <div class="mb-3 form-group has-feedback">
        <?php
           $edate = $datehelper->get_or_set_with_style($body['treatmentend']);
        ?>
        <label form-label for="treatmentend"><?= $s->trans('treatment_end') .' ('.$datehelper->display().')'; ?></label>
        <div class="input-group">
            <input type="text" name="treatmentend" id="treatmentend" placeholder="<?= ' ('.$datehelper->display().')';?>" required
                   class="form-control input-sm datepicker" readonly                   
                   value="<?= null!== $edate ? Html::encode($edate instanceof \DateTimeImmutable ? $edate->format($datehelper->style()) : $edate) : null; ?>" role="presentation" autocomplete="off">
            <span class="input-group-text">
                <i class="fa fa-calendar fa-fw"></i>
            </span>
        </div>        
    </div>
    </div>
    
    <div class="mb-3 form-group has-feedback">
        <?php
           $cdate = $datehelper->get_or_set_with_style($body['casedate']);
        ?>
        <label form-label for="casedate"><?= $s->trans('case_date') .' ('.$datehelper->display().')'; ?></label>
        <div class="input-group">
            <input type="text" name="casedate" id="casedate" placeholder="<?= ' ('.$datehelper->display().')';?>" required
                   class="form-control input-sm datepicker" readonly                   
                   value="<?= null!== $cdate ? Html::encode($cdate instanceof \DateTimeImmutable ? $cdate->format($datehelper->style()) : $cdate) : null; ?>" role="presentation" autocomplete="off">
            <span class="input-group-text">
            <i class="fa fa-calendar fa-fw"></i>
            </span>
        </div>        
    </div>
    
    <div class="mb3 form-group has-feedback">
       <label for="casenumber"><?= $s->trans('case_number'); ?></label>
       <input type="text" name="casenumber" id="casenumber" class="form-control" required
       value="<?= Html::encode($body['casenumber'] ??  ''); ?>">
    </div>
</div>
</div>
</form>