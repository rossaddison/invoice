<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use App\Invoice\Helpers\NumberHelper;

/**
 * @var \App\Invoice\Entity\Quote $quote
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
        - <?= $s->trans('quote'); ?> <?= $quote->getNumber(); ?>
    </title>

    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<div class="container">
    <div id="content">
        <div class="webpreview-header">
            <h2><?= $s->trans('quote'); ?>&nbsp;<?= $quote->getNumber(); ?></h2>
            <div class="btn-group">
                <?php 
                    if (in_array($quote->getStatus_id(), array(2, 3, 5)) && $quote->getSo_id() === '0') : ?>
                    <?= $modal_purchase_order_number; ?>
                    <a href="#purchase-order-number" data-toggle="modal" 
                       class="btn btn-warning">
                        <i class="fa fa-check"></i><?= $translator->translate('invoice.quote.approve'); ?>
                    </a>
                <?php endif; ?>                
                <?php 
                    // Only show the reject button if it was not previously approved ie there is no sales order id attached to this quote
                    // if there is a sales order id (ie approved previously) the client can reject the subsequent sales order only
                    if ($quote->getStatus_id() !== 4 && $quote->getStatus_id() !== 5 && $quote->getSo_id() === '0') { ?>
                    <a href="<?= $urlGenerator->generate('quote/reject',['url_key'=>$quote->getUrl_key()]); ?>" class="btn btn-danger ajax-loader">
                        <i class="fa fa-check"></i><?= $translator->translate('invoice.quote.reject'); ?>
                    </a>
                <?php } ?>                
                <?php 
                    // Show an approved message if the quote is approved
                    if ($quote->getStatus_id()  === 4) :  ?>
                    <label class="btn btn-success" disabled><?= $s->trans('approved'); ?></label>
                <?php endif; ?>
                <?php 
                    // Show a rejected message if the quote has been rejected by the client. A new quote will have to be issued
                    if ($quote->getStatus_id() === 5) :  ?>
                    <label class="btn btn-danger" disabled><?= $s->trans('rejected'); ?></label>
                <?php endif; ?>
                <?php 
                    // Show a canceled message if the quote has been canceled by the company
                    if ($quote->getStatus_id() === 6) :  ?>
                    <label class="btn btn-danger" disabled><?= $s->trans('canceled'); ?></label>
                <?php endif; ?>    
            </div>

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
                            <td style="text-align:right;"><?= $datehelper->date_from_mysql($quote->getDate_created()); ?></td>
                        </tr>
                        <tr class="<?= ($has_expired ? 'overdue' : '') ?>">
                            <td><?= $s->trans('expires'); ?></td>
                            <td class="text-right">
                                <?= $datehelper->date_from_mysql($quote->getDate_expires()); ?>
                            </td>
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
                                <td class="amount"><?= $numberhelper->format_currency($quote_item_amount->repoQuoteItemAmountquery((string)$item->getId())->getSubtotal() ?? 0.00); ?></td>
                            </tr>
                        <?php endforeach ?>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right"><?= $s->trans('subtotal'); ?>:</td>
                            <td class="amount"><?= $numberhelper->format_currency($quote_amount->getItem_subtotal()); ?></td>
                        </tr>
                        <?php if ($quote_amount->getItem_tax_total() > 0) { ?>
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $vat === '1' ? $translator->translate('invoice.invoice.vat.break.down') : $s->trans('item_tax'); ?></td>
                                <td class="amount"><?= $numberhelper->format_currency($quote_amount->getItem_tax_total()); ?></td>
                            </tr>
                        <?php } ?>
                        <?php 
                            if (null!== $quote_tax_rates && $vat == '0') {
                                foreach ($quote_tax_rates as $quote_tax_rate) : ?>
                                <tr>
                                    <td class="no-bottom-border" colspan="4"></td>
                                    <td class="text-right">
                                        <?= Html::encode($quote_tax_rate->getTaxRate()->getTax_rate_name()) . ' ' . $numberhelper->format_amount($quote_tax_rate->getTaxRate()->getTax_rate_percent()); ?>
                                        %
                                    </td>
                                    <td class="amount"><?= $numberhelper->format_currency($quote_tax_rate->getQuote_tax_rate_amount()); ?></td>
                                </tr>
                            <?php endforeach; } ?>
                        <?php if ($vat === '0') { ?>          
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right"><?= $s->trans('discount'); ?>:</td>
                            <td class="amount">
                                <?php
                                if ($quote->getDiscount_percent() > 0) {
                                    echo $numberhelper->format_amount($quote->getDiscount_percent()) . ' %';
                                } else {
                                    echo $numberhelper->format_amount($quote->getDiscount_amount());
                                }
                                ?>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right"><?= $s->trans('total'); ?>:</td>
                            <td class="amount"><?= $numberhelper->format_currency($quote_amount->getTotal()); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div><!-- .invoice-items -->

            <hr>

            <div class="row">
                <?php if ($quote->getNotes()) { ?>
                    <div class="col-xs-12 col-md-6">
                        <h4><?= $s->trans('notes'); ?></h4>
                        <p><?= nl2br(Html::encode($quote->getNotes())); ?></p>
                    </div>
                <?php } ?>
            </div>
            
             <?php //TODO attachments?>            
            
        </div><!-- .quote-items -->
    </div><!-- #content -->
</div>

</body>
</html>