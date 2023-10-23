<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use \DateTimeImmutable;
use Yiisoft\VarDumper\VarDumper;

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
<form id="DeliveryForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('deliveries_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
<div class="mb-3 form-group has-feedback">
  <?php
  //echo VarDumper::dump($body['date_created']);
  $date = $datehelper->get_or_set_with_style($body['date_created'] ?? new \DateTimeImmutable('now')) ;
  ?>
  <label form-label for="date_created"><?= $translator->translate('invoice.invoice.delivery.date.created') . ' (' . $datehelper->display() . ')'; ?></label>
  <div class="input-group">
      <input type="text" name="date_created" id="date_created" placeholder="<?= ' (' . $datehelper->display() . ')'; ?>"
              class="form-control input-sm datepicker" 
              value="<?= null !== $date ? Html::encode($date instanceof \DateTimeImmutable ? $date->format($datehelper->style()) : $date) : null; ?>" role="presentation" autocomplete="off">
       <span class="input-group-text">
          <i class="fa fa-calendar fa-fw"></i>
      </span>
  </div>
</div>    
    
<div class="mb-3 form-group has-feedback">
  <?php
  $datem = $datehelper->get_or_set_with_style($body['date_modified'] ?? new \DateTimeImmutable('now'));
  ?>
  <label form-label for="date_modified"><?= $translator->translate('invoice.invoice.delivery.date.modified') . ' (' . $datehelper->display() . ')'; ?></label>
  <div class="input-group">
      <input type="text" name="date_modified" id="date_modified" placeholder="<?= ' (' . $datehelper->display() . ')'; ?>"
              class="form-control input-sm datepicker" 
              value="<?= null !== $datem ? Html::encode($datem instanceof \DateTimeImmutable ? $datem->format($datehelper->style()) : $datem) : null; ?>" role="presentation" autocomplete="off">
       <span class="input-group-text">
          <i class="fa fa-calendar fa-fw"></i>
      </span>
  </div>
</div>
 <div class="form-group">
   <input type="hidden" name="id" id="id" class="form-control"
 value="<?= Html::encode($body['id'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <input type="hidden" name="inv_id" id="inv_id" class="form-control"
 value="<?= $body['inv_id'] ?? $inv_id; ?>">
 </div>
 
<div class="mb-3 form-group has-feedback">
  <?php
  $adate = $datehelper->get_or_set_with_style($body['actual_delivery_date'] ?? new \DateTimeImmutable('now'));
  ?>
  <label form-label for="actual_delivery_date"><?= $translator->translate('invoice.invoice.delivery.actual.delivery.date') . ' (' . $datehelper->display() . ')'; ?></label>
  <div class="input-group">
      <input type="text" name="actual_delivery_date" id="actual_delivery_date" placeholder="<?= ' (' . $datehelper->display() . ')'; ?>"
              class="form-control input-sm datepicker" 
              value="<?= null !== $adate ? Html::encode($adate instanceof \DateTimeImmutable ? $adate->format($datehelper->style()) : $adate) : null; ?>" role="presentation" autocomplete="off">
       <span class="input-group-text">
          <i class="fa fa-calendar fa-fw"></i>
      </span>
  </div>
</div>
    
<div class="mb-3 form-group has-feedback">
  <?php
  $sdate = $datehelper->get_or_set_with_style($body['start_date'] ?? new \DateTimeImmutable('now'));
  ?>
  <label form-label for="start_date"><?= $translator->translate('invoice.invoice.delivery.start.date') . ' (' . $datehelper->display() . ')'; ?></label>
  <div class="input-group">
      <input type="text" name="start_date" id="start_date" placeholder="<?= ' (' . $datehelper->display() . ')'; ?>"
              class="form-control input-sm datepicker" 
              value="<?= null !== $sdate ? Html::encode($sdate instanceof \DateTimeImmutable ? $sdate->format($datehelper->style()) : $sdate) : null; ?>" role="presentation" autocomplete="off">
       <span class="input-group-text">
          <i class="fa fa-calendar fa-fw"></i>
      </span>
  </div>
</div> 

<div class="mb-3 form-group has-feedback">
  <?php
  $edate = $datehelper->get_or_set_with_style($body['end_date'] ?? new \DateTimeImmutable('now'));
  ?>
  <label form-label for="end_date"><?= $translator->translate('invoice.invoice.delivery.end.date') . ' (' . $datehelper->display() . ')'; ?></label>
  <div class="input-group">
      <input type="text" name="end_date" id="end_date" placeholder="<?= ' (' . $datehelper->display() . ')'; ?>"
              class="form-control input-sm datepicker" 
              value="<?= null !== $edate ? Html::encode($edate instanceof \DateTimeImmutable ? $edate->format($datehelper->style()) : $edate) : null; ?>" role="presentation" autocomplete="off">
       <span class="input-group-text">
          <i class="fa fa-calendar fa-fw"></i>
      </span>
  </div>
</div>
     
</div>
<div class="form-group">
        <div>
            <label for="delivery_location_id"><?= $translator->translate('invoice.invoice.delivery.location'); ?>: </label>
        </div>        
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <?php if ($del_count > 0) { ?>
                <select name="delivery_location_id" id="delivery_location_id"
                        class="form-control">
                    <?php foreach ($dels as $del) { ?>
                        <option value="<?php echo $del->getId(); ?>"
                            <?php echo $s->check_select(Html::encode($body['delivery_location_id'] ?? $del->getId()), $del->getId()); ?>>
                            <?php echo $del->getAddress_1(). ', '.$del->getAddress_2() .', '. $del->getCity() .', '. $del->getZip() ; ?>
                        </option>
                    <?php } ?>
                </select>
                <?php } else {
                    echo Html::a($translator->translate('invoice.invoice.delivery.location.add'), $urlGenerator->generate('del/add',['client_id'=>$inv->getClient_id()]),['class'=>'btn btn-danger btn-lg mt-3']);
                }
                ?>
            </div>
        </div>    
    </div>     

</div>

</div>
</form>
