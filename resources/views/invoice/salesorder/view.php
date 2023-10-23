<?php
declare(strict_types=1);

/**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\View\WebView $this
 * @var array $body
 * @var string $csrf
 * @var string $title 
 * @var \Yiisoft\Session\Flash\FlashInterface $flash_interface
 */

$this->setTitle($translator->translate('invoice.salesorder'));

use Yiisoft\Html\Html;
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;

$vat = $s->get_setting('enable_vat_registration');
?>
<div class="panel panel-default">
<div class="panel-heading">
    <?= Html::encode($this->getTitle()); ?>
</div>
    <?php
        $clienthelper = new ClientHelper($s);
        $countryhelper = new CountryHelper();  
        $datehelper = new DateHelper($s);  
        $numberhelper = new NumberHelper($s);
        echo $modal_salesorder_to_pdf;
        echo $modal_so_to_invoice;
    ?>
<div>
<br>
<br>
</div> 
<input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   
<div id="headerbar">
    <h1 class="headerbar-title">
    <?php
        echo $translator->translate('invoice.salesorder');
        echo($so->getNumber() ? '#' . $so->getNumber() :  $so->getId());
    ?>
    </h1>
        <div class="headerbar-item pull-right">
        <div class="options btn-group">
            <a class="btn btn-default" data-toggle="dropdown" href="#">
                <i class="fa fa-chevron-down"></i><?= $s->trans('options'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <?php
                if ($invEdit) { ?> 
                <li>
                    <a href="<?= $urlGenerator->generate('salesorder/edit',['id'=>$so->getId()]) ?>" style="text-decoration:none">
                        <i class="fa fa-edit fa-margin"></i>
                        <?= $s->trans('edit'); ?>
                    </a>
                </li>
                <?php } ?>
                <li>
                    <a href="#so-to-pdf"  data-toggle="modal" style="text-decoration:none">
                        <i class="fa fa-print fa-margin"></i>
                        <?= $s->trans('download_pdf'); ?>
                    </a>
                </li>
                <?php 
                // if there is a sales order number do not show button
                // if the status is draft do not show button
                // only show the button if the sales order has reached invoice generate stage ie 6
                if ($so->getInv_id() || (in_array($so->getStatus_id(),[1,2,3,4,5]))) {} else {?> 
                    <?php if ($invEdit) { ?> 
                        <li>
                            <a href="#so-to-invoice" data-toggle="modal"  style="text-decoration:none">
                                <i class="fa fa-refresh fa-margin"></i>
                                <?= $translator->translate('invoice.salesorder.to.invoice'); ?>
                            </a>
                        </li>
                    <?php } ?>    
                <?php } ?>
            </ul>
        </div>        
    </div>
</div>

<div id="content">    
    <?= $alert; ?>  
    <div id="salesorder_form">
        <div class="salesorder">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-5">
                    <h3>
                        <a href="<?= $urlGenerator->generate('client/view',['id' => $so->getClient()->getClient_id()]); ?>">
                            <?= Html::encode($clienthelper->format_client($so->getClient())); ?>
                        </a>
                    </h3>
                    <br>
                    <div id="pre_save_client_id" value="<?php echo $so->getClient()->getClient_id(); ?>" hidden></div>
                    <div class="client-address">
                        <span class="client-address-street-line-1">
                            <?php echo($so->getClient()->getClient_address_1() ? Html::encode($so->getClient()->getClient_address_1()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-street-line-2">
                            <?php echo($so->getClient()->getClient_address_2() ? Html::encode($so->getClient()->getClient_address_2()) . '<br>' : ''); ?>
                        </span>
                        <span class="client-address-town-line">
                            <?php echo($so->getClient()->getClient_city() ? Html::encode($so->getClient()->getClient_city()) . '<br>' : ''); ?>
                            <?php echo($so->getClient()->getClient_state() ? Html::encode($so->getClient()->getClient_state()) . '<br>' : ''); ?>
                            <?php echo($so->getClient()->getClient_zip() ? Html::encode($so->getClient()->getClient_zip()) : ''); ?>
                        </span>
                        <span class="client-address-country-line">
                            <?php echo($so->getClient()->getClient_country() ? '<br>' . $countryhelper->get_country_name($s->trans('cldr'), $so->getClient()->getClient_country()) : ''); ?>
                        </span>
                    </div>
                    <hr>
                    <?php if ($so->getClient()->getClient_phone()): ?>
                        <div class="client-phone">
                            <?= $s->trans('phone'); ?>:&nbsp;
                            <?= Html::encode($so->getClient()->getClient_phone()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($so->getClient()->getClient_mobile()): ?>
                        <div class="client-mobile">
                            <?= $s->trans('mobile'); ?>:&nbsp;
                            <?= Html::encode($so->getClient()->getClient_mobile()); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($so->getClient()->getClient_email()): ?>
                        <div class='client-email'>
                            <?= $s->trans('email'); ?>:&nbsp;
                            <?php echo $so->getClient()->getClient_email(); ?>
                        </div>
                    <?php endif; ?>
                    <br>
                </div>

                <div class="col-xs-12 visible-xs"><br></div>

                <div class="col-xs-12 col-sm-6 col-md-7">
                    <div class="details-box">
                        <div class="row">

                            <div class="col-xs-12 col-md-6">

                                <div>
                                    <label for="salesorder_number">
                                        <?= $translator->translate('invoice.salesorder'); ?> #
                                    </label>
                                    <input type="text" id="salesorder_number" class="form-control input-sm" readonly
                                        <?php if ($so->getNumber()) : ?> value="<?= $so->getNumber(); ?>"
                                        <?php else : ?> placeholder="<?= $s->trans('not_set'); ?>"
                                        <?php endif; ?>>
                                </div>
                                <div has-feedback">
                                    <label for="salesorder_date_created">
                                        <?= $vat == '0' ? $translator->translate('invoice.invoice.date.issued') : $translator->translate('invoice.salesorder.date.created'); ?>
                                    </label>
                                    <div class="input-group">
                                        <?php  $date = $so->getDate_created() ?? null; 
                                            if ($date && $date !== "0000-00-00") { 
                                                //use the DateHelper
                                                $datehelper = new DateHelper($s); 
                                                $sodate = $datehelper->date_from_mysql($date); 
                                            } else { 
                                                $sodate = null; 
                                            }
                                        ?>
                                        <input name="salesorder_date_created" id="salesorder_date_created" disabled
                                               class="form-control input-sm datepicker"
                                               value="<?= Html::encode($sodate); ?>"/>
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar fa-fw"></i>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($inv_number) { ?>  
                                <div has-feedback">
                                    <label for="salesorder_to_url"><?= $translator->translate('invoice.salesorder.invoice'); ?></label>
                                    <div class="input-group">
                                        <?= Html::a($inv_number, $urlGenerator->generate('inv/view',['id'=>$so->getInv_id()]), ['class'=>'btn btn-success']); ?>
                                    </div>
                                </div>
                                <?php } ?>
                                <div>
                                    <?php foreach ($custom_fields as $custom_field): ?>
                                        <?php if ($custom_field->getLocation() !== 1) {continue;} ?>
                                        <?php  $cvH->print_field_for_view($so_custom_values, $custom_field, $custom_values); ?>                                   
                                    <?php endforeach; ?>
                                </div>    
                            </div>
                            <div class="col-xs-12 col-md-6">

                                <div>
                                    <label for="status_id">
                                        <?= $s->trans('status'); ?>
                                    </label>
                                    <select name="status_id" id="status_id" disabled
                                            class="form-control">
                                        <?php foreach ($so_statuses as $key => $status) { ?>
                                            <option value="<?php echo $key; ?>" <?php if ($key === $body['status_id']) {  $s->check_select(Html::encode($body['status_id'] ?? ''), $key);} ?>>
                                                <?= Html::encode($status['label']); ?> 
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="salesorder_password" hidden>
                                        <?= $translator->translate('invoice.salesorder.password'); ?>
                                    </label>
                                    <input type="text" id="salesorder_password" class="form-control input-sm" disabled value="<?= Html::encode($body['password'] ?? ''); ?>" hidden>
                                </div>
                                <div>
                                    <label for="salesorder_client_purchase_order_number">
                                        <?= $translator->translate('invoice.salesorder.clients.purchase.order.number'); ?>
                                    </label>
                                    <input type="text" id="salesorder_client_purchase_order_number" class="form-control input-sm" disabled value="<?= Html::encode($body['client_po_number'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label for="salesorder_client_purchase_order_person">
                                        <?= $translator->translate('invoice.salesorder.clients.purchase.order.person'); ?>
                                    </label>
                                    <input type="text" id="salesorder_client_purchase_order_number" class="form-control input-sm" disabled value="<?= Html::encode($body['client_po_person'] ?? ''); ?>">
                                </div>
                               
                                    <?php
                                        // 2 => Terms Agreement Required 8=> Rejected
                                        if (in_array($so->getStatus_id(),[2,8]) && !$invEdit) 
                                        { ?>
                                        <div>
                                            <br>
                                            <a href="<?= $urlGenerator->generate('salesorder/url_key',['key' => $so->getUrl_key()]); ?>" class="btn btn-success">  
                                                <?= $translator->translate('invoice.salesorder.agree.to.terms').'/'.$translator->translate('invoice.salesorder.reject'); ?>    
                                            </a>
                                        </div>
                                    <?php } ?>
                                <input type="text" id="dropzone_client_id" readonly  hidden class="form-control" value="<?= $so->getClient()->getClient_id(); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
   <div id="partial_item_table_parameters" quote_items="<?php $so_items; ?>" disabled>
    <?=
       $partial_item_table;
    ?>     
   </div>
    
   <div class="row">
            <div class="col-xs-12 col-md-6">
                <div class="panel panel-default no-margin">
                    <div class="panel-heading">
                        <?= $s->trans('notes'); ?>
                    </div>
                    <div class="panel-body">
                        <textarea name="notes" id="notes" rows="3" disabled
                                  class="input-sm form-control"><?= Html::encode($body['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
                <br>
                <div class="panel panel-default no-margin">
                    <div class="panel-heading">
                        <?= $translator->translate('invoice.salesorder.payment.terms'); ?>
                    </div>
                    <div class="panel-body">
                        <textarea name="terms" id="terms" rows="3" disabled
                                  class="input-sm form-control"><?= Html::encode($payment_terms[$body['payment_term']] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="col-xs-12 visible-xs visible-sm"><br></div>

            </div>
            <div id="view_custom_fields" class="col-xs-12 col-md-6">
                 <?php echo $view_custom_fields; ?>
            </div>
    </div>
</div>
</div>    
<div>  
</div>
       
