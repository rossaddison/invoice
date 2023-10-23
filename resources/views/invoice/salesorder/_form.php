<?php
declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\ModalHelper;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */


$modalhelper = new ModalHelper($s);
$datehelper = new DateHelper($s);

if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}

?>
<form class="row" class="form-horizontal" id="SalesOrderForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div id="headerbar">
        <h1 class="headerbar-title"><?= $translator->translate('invoice.salesorder'); ?></h1>    
        <?php
            $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]);
            echo (string)$response->getBody();
        ?>
    </div>
    <div class="form-group">
      <div class="col-xs-12 col-sm-2 text-right text-left-xs">  
        <label for="number" class="control-label"><?= $translator->translate('invoice.salesorder'); ?></label>
      </div>
      <div class="col-xs-12 col-sm-6">  
        <div clsss="input-group">  
            <input type="text" name="number" id="number" class="form-control" required disabled value="<?= Html::encode($body['number'] ??  ''); ?>">
        </div>
      </div>    
    </div>
    <div class="form-group">
      <div class="col-xs-12 col-sm-2 text-right text-left-xs">
        <label for="client_id" class="control-label"><?= $s->trans('client'); ?></label>
      </div>
      <div class="col-xs-12 col-sm-6">  
        <select name="client_id" id="client_id" class="form-control" disabled>
           <option value=""><?= $s->trans('client'); ?></option>
             <?php foreach ($clients as $client) { ?>
              <option value="<?= $client->getClient_id(); ?>"
               <?php $s->check_select(Html::encode($body['client_id'] ?? ''), $client->getClient_id()) ?>
               ><?= $client->getClient_name(); ?></option>
             <?php } ?>     
        </select>
      </div>    
    </div>
    <div class="form-group">
      <div class="col-xs-12 col-sm-2 text-right text-left-xs">  
        <label for="group_id" class="control-label"><?= $translator->translate('invoice.salesorder.default.group'); ?>: </label>
      </div>
      <div class="col-xs-12 col-sm-6">
          <select name="group_id" id="group_id" class="form-control" disabled>         
          <?php foreach ($groups as $group) { ?>
              <option value="<?php echo $group->getId(); ?>"
                  <?php $s->check_select(Html::encode($body['group_id'] ?? $s->get_setting('default_salesorder_group')), $group->getId()); ?>>
                  <?= Html::encode($group->getName()); ?>
              </option>
          <?php } ?>
      </select>
      </div>
    </div>
    
    <?php if ($del_count > 0) { ?>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="delivery_location_id"><?= $translator->translate('invoice.invoice.delivery.location'); ?>: </label>
        </div>        
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">  
                <select name="delivery_location_id" id="delivery_location_id"
                        class="form-control" disabled>
                    <?php foreach ($dels as $del) { ?>
                        <option value="<?php echo $del->getId(); ?>"
                            <?php echo $s->check_select(Html::encode($body['delivery_location_id'] ?? $del->getId()), $del->getId()); ?>>
                            <?php echo $del->getAddress_1(). ', '.$del->getAddress_2() .', '. $del->getCity() .', '. $del->getZip() ; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>    
    </div>
    <?php } else {
        echo '<div>';
        echo $no_delivery_locations ? $alert : '';
        echo Html::a($translator->translate('invoice.invoice.delivery.location.add'), $urlGenerator->generate('del/add',['client_id'=>$so->getClient_id()]));
        echo '</div>';
    }
    ?>

    <div class="form-group">
      <div class="col-xs-12 col-sm-2 text-right text-left-xs">  
        <label form-label for="date_created" class="control-label"><?= $s->trans('created') ." (".  $datehelper->display().") "; ?></label>
      </div>
      <div class="col-xs-12 col-sm-6">  
            <div class="input-group"> 
            <input type="text" name="date_created" disabled id="date_created" placeholder="<?= $datehelper->display(); ?>" 
                   class="form-control input-sm datepicker" 
                   disabled
                   value="<?= Html::encode($datehelper->date_from_mysql($body['date_created'] ?? (new \DateTimeImmutable('now'))   )); ?>"> 
            <span class="input-group-text"> 
            <i class="fa fa-calendar fa-fw"></i> 
             </span> 
            </div>
      </div>    
    </div>
    <div class="form-group">
      <div class="col-xs-12 col-sm-2 text-right text-left-xs">  
        <label for="password" class="control-label"><?= $s->trans('quote_pre_password'); ?></label>
      </div>
      <div class="col-xs-12 col-sm-6">  
           <div class="input-group">  
                <input type="text" disabled  name="password" id="password" class="form-control" value="<?= Html::encode($body['password'] ??  ''); ?>">
           </div>
      </div>    
    </div> 
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="status_id" class="control-label"><?php echo $s->trans('status'); ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <select name="status_id" id="status_id" class="form-control">
                <option value="0"><?php Html::encode($body['status_id'] ?? 1); ?></option>
                <?php foreach ($so_statuses as $key => $status) { ?>
                    <option value="<?php echo $key; ?>" <?php $s->check_select(Html::encode($body['status_id'] ?? ''), $key) ?>>
                        <?php echo $status['label']; ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="form-group">
            <div class="col-xs-12 col-sm-2 text-right text-left-xs">
                <label for="url_key" class="control-label"></label>
            </div>
            <input type="text" name="url_key" id="url_key" class="form-control" readonly value="<?= Html::encode($body['url_key']); ?>" hidden>
        </div>
        <div class="form-group">
            <div class="col-xs-12 col-sm-2 text-right text-left-xs">
                <label for="quote_id" class="control-label"></label>
            </div>
            <input type="text" name="quote_id" id="quote_id" class="form-control" readonly value="<?= Html::encode($body['quote_id']); ?>" hidden>
        </div>
    </div>
    <div class="form-group">
      <div class="col-xs-12 col-sm-2 text-right text-left-xs">  
        <label for="discount_amount" class="control-label"><?= $s->trans('discount'); ?></label>
      </div>
      <div class="col-xs-12 col-sm-6">
        <div class="input-group">  
        <input disabled type="number" name="discount_amount" id="discount_amount" class="form-control" value="<?= $s->format_amount((float)Html::encode($body['discount_amount'] ??  '')); ?>"> 
                  <span class="input-group-text"><?= $s->get_setting('currency_symbol'); ?></span>
        </div>          
      </div>
    </div>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">
            <label for="discount_percent" class="control-label"><?= $s->trans('discount'); ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="number" name="discount_percent" id="discount_percent" class="form-control"
                       disabled
                       value="<?= $s->format_amount((float)Html::encode($body['discount_percent'] ??  '')); ?>">
                    <span class="input-group-text">&percnt;</span>
            </div>
        </div>    
    </div>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">   
            <label for="notes" class="control-label"><?= $s->trans('notes'); ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="text" name="notes" id="notes" class="form-control" value="<?= Html::encode($body['notes'] ??  ''); ?>">
            </div>
        </div>    
    </div>
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">   
            <label for="terms_and_conditions_file" class="control-label"><?= $translator->translate('invoice.term') ?></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <textarea  class="form-control" rows="20" cols="20"><?= $terms_and_conditions_file; ?></textarea>
            </div>
        </div>    
    </div>
    <?php foreach ($custom_fields as $custom_field): ?>
    <div class="form-group">
    <?= $cvH->print_field_for_form(
            $quote_custom_values,
            $custom_field,
            // Custom values to fill drop down list if a dropdown box has been created
            $custom_values, 
            // Class for div surrounding input
            'col-xs-12 col-sm-6',
            // Class surrounding above div
            'form-group',
            // Label class similar to above
            'control-label'); 
    ?>
    </div>    
    <?php endforeach; ?>
    
    <div class="form-group">
      <div class="col-xs-12 col-sm-2 text-right text-left-xs">  
        <label for="inv_id" class="control-label"><?= $translator->translate('invoice.salesorder.invoice.number'); ?></label>
      </div>
      <div class="col-xs-12 col-sm-6">  
        <div clsss="input-group">  
            <input type="text" name="inv_number" id="inv_number" class="form-control" required disabled value="<?= $inv_number; ?>">
        </div>
      </div>
    </div> 
    <div class="form-group">
        <div class="col-xs-12 col-sm-2 text-right text-left-xs">   
            <label for="id" class="control-label"></label>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="input-group">
                <input type="hidden" name="id" id="id" class="form-control" value="<?= Html::encode($body['id'] ??  ''); ?>">
            </div>
        </div>    
    </div>
</form>