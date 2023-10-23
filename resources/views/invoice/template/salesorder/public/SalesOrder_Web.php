<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Invoice\Helpers\NumberHelper;

/**
 * @var \App\Invoice\Entity\SalesOrder $salesorder
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Session\Flash\FlashInterface $flash_interface
 */

$numberhelper = new NumberHelper($s);
$vat = $s->get_setting('enable_vat_registration');
?>

<!DOCTYPE html>
<html lang="<?= $s->trans('cldr'); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>
        <?= $s->get_setting('custom_title', 'yii-invoice', true); ?>
        - <?= $translator->translate('invoice.salesorder'); ?> <?= $salesorder->getNumber(); ?>
    </title>

    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<div class="container">
    <div id="content">
        <div class="webpreview-header">
            <div class="row">
                    <h1><?= $translator->translate('invoice.term'); ?></h1>
                    <div class="col-xs-12 col-sm-6 label label-info">
                        <div class="input-group label label-info">
                            <textarea  class="form-control" rows="20" cols="20"><?= $terms_and_conditions_file; ?></textarea>
                        </div>
                    </div>    
                </div>
                <br>
            </div>
            <div class="btn-group">
                <?php
                    // 2=>Terms Agreement Required
                    // 3=>Client Agreed to Terms
                    // 8=>Rejected
                    if (in_array($salesorder->getStatus_id(), array(2, 8)) && $salesorder->getQuote_id() !== '0' && $salesorder->getInv_id() === '0') : ?>
                    <a href="<?= $urlGenerator->generate('salesorder/agree_to_terms', ['url_key'=>$salesorder_url_key]); ?>"
                       class="btn btn-success" data-bs-toggle = "tooltip" title="Goods and Services will now be assembled/packaged/prepared">
                        <i class="fa fa-check"></i><?= $translator->translate('invoice.salesorder.agree.to.terms'); ?>
                    </a>
                <?php endif; ?>                
                <?php if (in_array($salesorder->getStatus_id(), array(2)) && $salesorder->getQuote_id() !== '0' && $salesorder->getInv_id() === '0') :  ?>
                    <a href="<?= $urlGenerator->generate('salesorder/reject', ['url_key'=>$salesorder_url_key]); ?>"
                       class="btn btn-danger">
                        <i class="fa fa-times-circle"></i><?= $translator->translate('invoice.salesorder.reject'); ?>
                    </a>
                <?php endif; ?>
                <?php if (in_array($salesorder->getStatus_id(), array(3)) && $salesorder->getQuote_id() !== '0' && $salesorder->getInv_id() === '0') :  ?>
                    <label class="btn btn-success"><?= $translator->translate('invoice.salesorder.client.confirmed.terms'); ?></label>
                <?php endif; ?>
            </div>
            <br>
            <br>
            <h2><?= $translator->translate('invoice.salesorder'); ?>&nbsp;<?= $salesorder->getNumber(); ?></h2>
        </div>
        <hr>

        <?= $alert; ?>

        <div class="invoice">

            <?php if ($logo) {echo $logo . '<br><br>'; } ?>

            <div class="row">
                <div class="col-xs-12 col-md-6 col-lg-5">

                    <h4><?= Html::encode($userinv->getName()); ?></h4>
                    <p><?php if ($userinv->getVat_id()) {
                            echo $s->lang("vat_id_short") . ": " . $userinv->getVat_id() . '<br>';
                        } ?>
                        <?php if ($userinv->getTax_code()) {
                            echo $s->lang("tax_code_short") . ": " . $userinv->getTax_code() . '<br>';
                        } ?>
                        <?php if ($userinv->getAddress_1()) {
                            echo Html::encode($userinv->getAddress_1()) . '<br>';
                        } ?>
                        <?php if ($userinv->getAddress_2()) {
                            echo Html::encode($userinv->getAddress_2()) . '<br>';
                        } ?>
                        <?php if ($userinv->getCity()) {
                            echo Html::encode($userinv->getCity()) . ' ';
                        } ?>
                        <?php if ($userinv->getState()) {
                            echo Html::encode($userinv->getState()) . ' ';
                        } ?>
                        <?php if ($userinv->getZip()) {
                            echo Html::encode($userinv->getZip()) . '<br>';
                        } ?>
                        <?php if ($userinv->getPhone()) { ?><?= $s->trans('phone_abbr'); ?>: <?= Html::encode($userinv->getPhone()); ?>
                            <br><?php } ?>
                        <?php if ($userinv->getFax()) { ?><?= $s->trans('fax_abbr'); ?>: <?= Html::encode($userinv->getFax()); ?><?php } ?>
                    </p>

                </div>
                <div class="col-lg-2"></div>
                <div class="col-xs-12 col-md-6 col-lg-5 text-right">

                    <h4><?= Html::encode($clienthelper->format_client($client)); ?></h4>
                   <p><?php if ($client->getClient_vat_id()) {
                            echo $s->lang("vat_id_short") . ": " . $client->getClient_vat_id() . '<br>';
                        } ?>
                        <?php if ($client->getClient_tax_code()) {
                            echo $s->lang("tax_code_short") . ": " . $client->getClient_tax_code() . '<br>';
                        } ?>
                        <?php if ($client->getClient_address_1()) {
                            echo Html::encode($client->getClient_address_1()) . '<br>';
                        } ?>
                        <?php if ($client->getClient_address_2()) {
                            echo Html::encode($client->getClient_address_2()) . '<br>';
                        } ?>
                        <?php if ($client->getClient_city()) {
                            echo Html::encode($client->getClient_city()) . ' ';
                        } ?>
                        <?php if ($client->getClient_state()) {
                            echo Html::encode($client->getClient_state()) . ' ';
                        } ?>
                        <?php if ($client->getClient_zip()) {
                            echo Html::encode($client->getClient_zip()) . '<br>';
                        } ?>
                        <?php if ($client->getClient_phone()) {
                            echo $s->trans('phone_abbr') . ': ' . Html::encode($client->getClient_phone()); ?>
                            <br>
                        <?php } ?>
                    </p>
                    <br>
                    <table class="table table-condensed">
                        <tbody>
                        <tr>
                            <td><?= $vat == '1' ? $translator->translate('invoice.invoice.date.issued') : $s->trans('quote_date'); ?></td>
                            <td style="text-align:right;"><?= $datehelper->date_from_mysql($salesorder->getDate_created()); ?></td>
                        </tr>
                        </tbody>
                    </table>

                </div>
            </div>

            <br>

            <div class="invoice-items">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th><?= $s->trans('item'); ?></th>
                            <th><?= $s->trans('description'); ?></th>
                            <th class="text-right"><?= $s->trans('qty'); ?></th>
                            <th class="text-right"><?= $s->trans('price'); ?></th>
                            <th class="text-right"><?= $s->trans('discount'); ?></th>
                            <th class="text-right"><?= $s->trans('total'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item) : ?>
                            <tr>
                                <td><?= Html::encode($item->getName()); ?></td>
                                <td><?= nl2br(Html::encode($item->getDescription())); ?></td>
                                <td class="amount">
                                    <?= $numberhelper->format_amount($item->getQuantity()); ?>
                                    <?php if ($item->getProduct_unit()) : ?>
                                        <br>
                                        <small><?= Html::encode($item->getProduct_unit()); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="amount"><?= $numberhelper->format_currency($item->getPrice()); ?></td>
                                <td class="amount"><?= $numberhelper->format_currency($item->getDiscount_amount()); ?></td>
                                <td class="amount"><?= $numberhelper->format_currency($salesorder_item_amount->repoSalesOrderItemAmountquery((string)$item->getId())->getSubtotal() ?? 0.00); ?></td>
                            </tr>
                        <?php endforeach ?>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right"><?= $s->trans('subtotal'); ?>:</td>
                            <td class="amount"><?= $numberhelper->format_currency($salesorder_amount->getItem_subtotal()); ?></td>
                        </tr>
                        <?php if ($salesorder_amount->getItem_tax_total() > 0) { ?>
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $vat === '1' ? $translator->translate('invoice.invoice.vat.break.down') : $s->trans('item_tax'); ?></td>
                                <td class="amount"><?= $numberhelper->format_currency($salesorder_amount->getItem_tax_total()); ?></td>
                            </tr>
                        <?php } ?>
                        <?php 
                            if (null!== $salesorder_tax_rates && $vat == '0') {
                                foreach ($salesorder_tax_rates as $salesorder_tax_rate) : ?>
                                <tr>
                                    <td class="no-bottom-border" colspan="4"></td>
                                    <td class="text-right">
                                        <?= Html::encode($salesorder_tax_rate->getTaxRate()->getTax_rate_name()) . ' ' . $numberhelper->format_amount($salesorder_tax_rate->getTaxRate()->getTax_rate_percent()); ?>
                                        %
                                    </td>
                                    <td class="amount"><?= $numberhelper->format_currency($salesorder_tax_rate->getSalesOrder_tax_rate_amount()); ?></td>
                                </tr>
                            <?php endforeach; } ?>
                        <?php if ($vat === '0') { ?>          
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right"><?= $s->trans('discount'); ?>:</td>
                            <td class="amount">
                                <?php
                                if ($salesorder->getDiscount_percent() > 0) {
                                    echo $numberhelper->format_amount($salesorder->getDiscount_percent()) . ' %';
                                } else {
                                    echo $numberhelper->format_amount($salesorder->getDiscount_amount());
                                }
                                ?>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right"><?= $s->trans('total'); ?>:</td>
                            <td class="amount"><?= $numberhelper->format_currency($salesorder_amount->getTotal()); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <hr>

            <div class="row">
                <?php if ($salesorder->getNotes()) { ?>
                    <div class="col-xs-12 col-md-6">
                        <h4><?= $s->trans('notes'); ?></h4>
                        <p><?= nl2br(Html::encode($salesorder->getNotes())); ?></p>
                    </div>
                <?php } ?>
            </div>
            
             <?php //TODO attachments?>            
            
        </div><!-- .salesorder-items -->
    </div><!-- #content -->
</div>

</body>
</html>