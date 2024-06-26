<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;
use App\Invoice\Helpers\NumberHelper;

/**
 * @var \App\Invoice\Entity\Inv $inv
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Session\Flash\FlashInterface $flash_interface
 */

$numberhelper = new NumberHelper($s);
$vat = $s->get_setting('enable_vat_registration');
?>

<!DOCTYPE html>
<html lang="<?= $translator->translate('i.cldr'); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>
        <?= $s->get_setting('custom_title', 'yii-invoice', true); ?>
        - <?= $translator->translate('i.invoice'); ?> <?= $inv->getNumber(); ?>
    </title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
<?php $this->beginBody();
    echo $alert;  
?>
<section class="py-3 py-md-5">    
    <div class="container">
        <div id="content">
            <div class="webpreview-header">
                <h2><?= $translator->translate('i.invoice'); ?>&nbsp;<?= $inv->getNumber(); ?></h2>
                <div class="btn-group">
                    <!-- Include custom fields -->

                    <?php //if (null!==$sumex->getid()) TODO Sumex
                        if (2===1)   : ?>
                        <a href="<?//= $urlGenerator->generate('inv/key_download_pdf_non_sumex', ['url_key' => $inv_url_key]); ?>" class="btn btn-primary" style="text-decoration:none">
                    <?php else : ?>
                        <a href="<?= $urlGenerator->generate('inv/pdf_download_include_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-primary" style="text-decoration:none">
                    <?php endif; ?>
                        <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('i.download_pdf').'=>'.$translator->translate('i.yes').' '.$translator->translate('i.custom_fields'); ?>
                        </a>

                    <!-- Exclude custom fields -->         
                    <?php if (2===1) : ?>
                        <a href="<?//= $urlGenerator->generate('inv/key_download_pdf_non_sumex', ['url_key' => $inv_url_key]); ?>" class="btn btn-danger" style="text-decoration:none">
                    <?php else : ?>
                        <a href="<?= $urlGenerator->generate('inv/pdf_download_exclude_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-danger" style="text-decoration:none">
                    <?php endif; ?>
                        <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('i.download_pdf').'=>'.$translator->translate('i.no').' '.$translator->translate('i.custom_fields'); ?>
                        </a>

                    <?php if ($s->get_setting('enable_online_payments') == 1 && $inv_amount->getBalance() > 0) { ?>
                        <a href="<?= $urlGenerator->generate('paymentinformation/inform', 
                                ['url_key' => $inv_url_key, 
                                 'gateway' => $client_chosen_gateway]); ?>" class="btn btn-success">
                            <i class="fa fa-credit-card"></i><?= $translator->translate('i.pay_now').' '. str_replace('_',' ',$client_chosen_gateway); ?>
                        </a>
                    <?php } ?>
                    <?php if ($s->get_setting('enable_online_payments') == 1 && $inv_amount->getBalance() == 0) { ?>
                        <a href="" class="btn btn-success"><?= $translator->translate('i.paid'); ?></a>    
                    <?php } ?>
                </div>
            </div>
            <hr>

            <div class="invoice">
                <?php
                    // if a company logo has not been setup in companyprivate => use the site default logo
                    echo Img::tag()
                         ->width($companyLogoWidth)
                         ->height($companyLogoHeight)
                         ->src($logoPath);   
                ?>
                
                <br>
                <br>
                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <div class="col-xs-12 col-md-6 col-lg-5">

                        <h4><?= Html::encode($userinv->getName()); ?></h4>
                        <p><?php if ($userinv->getVat_id()) {
                                echo $translator->translate('i.vat_id_short') . ": " . $userinv->getVat_id() . '<br>';
                            } ?>
                            <?php if ($userinv->getTax_code()) {
                                echo $translator->translate('i.tax_code_short') . ": " . $userinv->getTax_code() . '<br>';
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
                            <?php if ($userinv->getPhone()) { ?><?= $translator->translate('i.phone_abbr'); ?>: <?= Html::encode($userinv->getPhone()); ?>
                                <br><?php } ?>
                            <?php if ($userinv->getFax()) { ?><?= $translator->translate('i.fax_abbr'); ?>: <?= Html::encode($userinv->getFax()); ?><?php } ?>
                        </p>

                    </div>
                    <div class="col-lg-2"></div>
                    <div class="col-xs-12 col-md-6 col-lg-5 text-right">

                        <h4><?= Html::encode($clienthelper->format_client($client)); ?></h4>
                        <p><?php if ($client->getClient_vat_id()) {
                                echo $translator->translate('i.vat_id_short') . ": " . $client->getClient_vat_id() . '<br>';
                            } ?>
                            <?php if ($client->getClient_tax_code()) {
                                echo $translator->translate('i.tax_code_short') . ": " . $client->getClient_tax_code() . '<br>';
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
                                echo $translator->translate('i.phone_abbr') . ': ' . Html::encode($client->getClient_phone()); ?>
                                <br>
                            <?php } ?>
                        </p>

                        <br>

                        <table class="table table-condensed">
                            <tbody>
                            <tr>
                                <td><?= $translator->translate('i.invoice_date'); ?></td>
                                <td style="text-align:right;"><?= $inv->getDate_created()->format($dateHelper->style()); ?></td>
                            </tr>
                            <tr class="<?=($is_overdue ? 'overdue' : '') ?>">
                                <td><?= $translator->translate('i.due_date'); ?></td>
                                <td class="text-right">
                                    <?= $inv->getDate_due()->format($dateHelper->style()); ?>
                                </td>
                            </tr>
                            <tr class="<?=($is_overdue ? 'overdue' : '') ?>">
                                <td><?= $translator->translate('i.amount_due'); ?></td>
                                <td style="text-align:right;"><?= $numberhelper->format_currency($inv_amount->getBalance() ?? 0.00); ?></td>
                            </tr>
                            <?php if ($payment_method): ?>
                                <tr>
                                    <td><?= $translator->translate('i.payment_method') . ': '; ?></td>
                                    <td><?= Html::encode($payment_method->getName()); ?></td>
                                </tr>
                            <?php endif; ?>
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
                                <th><?= $translator->translate('i.item'); ?></th>
                                <th><?= $translator->translate('i.description'); ?></th>
                                <th class="text-right"><?= $translator->translate('i.qty'); ?></th>
                                <th class="text-right"><?= $translator->translate('i.price'); ?></th>
                                <th class="text-right"><?= $translator->translate('i.discount'); ?></th>
                                <th class="text-right"><?= $translator->translate('i.total'); ?></th>
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
                                    <td class="amount"><?= $numberhelper->format_currency($item->getPrice() ?? 0.00); ?></td>
                                    <td class="amount"><?= $numberhelper->format_currency($item->getDiscount_amount() ?? 0.00); ?></td>
                                    <td class="amount"><?= $numberhelper->format_currency($inv_item_amount->repoInvItemAmountquery((string)$item->getId())->getSubtotal() ?? 0.00); ?></td>                                   
                                </tr>
                            <?php endforeach ?>
                            <tr>
                                <td colspan="4"></td>
                                <td class="text-right"><?= $translator->translate('i.subtotal'); ?>:</td>
                                <td class="amount"><?= $numberhelper->format_currency($inv_amount->getItem_subtotal() ?? 0.00); ?></td>
                            </tr>

                            <?php if ($inv_amount->getItem_tax_total() > 0) { 
                                $percentage = (float)$inv_item_amount->repoInvItemAmountquery((string)$item->getId())->getInvItem()?->getTaxRate()?->getTax_rate_percent() ??  0.00;
                                ?>
                                <tr>
                                    <td class="no-bottom-border" colspan="4"></td>
                                    <td class="text-right"><?= $vat === '0' ? $translator->translate('i.item_tax').' ('. $percentage .'%)' : $translator->translate('invoice.invoice.vat.abbreviation') ?></td>
                                    <td class="amount"><?= $numberhelper->format_currency($inv_amount->getItem_tax_total() ?? 0.00)?></td>
                                </tr>
                            <?php } ?>

                            <?php 
                                if (null!== $inv_tax_rates && $vat === '0') {
                                    foreach ($inv_tax_rates as $inv_tax_rate) : ?>
                                    <tr>
                                        <td class="no-bottom-border" colspan="4"></td>
                                        <td class="text-right">
                                            <?= Html::encode($inv_tax_rate->getTaxRate()->getTax_rate_name()) . ' ' . $numberhelper->format_amount($inv_tax_rate->getTaxRate()->getTax_rate_percent() ?? 0.00); ?>
                                            %
                                        </td>
                                        <td class="amount"><?= $numberhelper->format_currency($inv_tax_rate->getInv_tax_rate_amount() ?? 0.00); ?></td>
                                    </tr>
                            <?php   endforeach; 

                                }  ?>

                            <?php if ($vat  === '0') { ?>        
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $translator->translate('i.discount'); ?>:</td>
                                <td class="amount">
                                    <?php
                                    if ($inv->getDiscount_percent()) {
                                        echo $numberhelper->format_amount($inv->getDiscount_percent()) . ' %';
                                    } else {
                                        echo $numberhelper->format_amount($inv->getDiscount_amount() ?? 0.00);
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php } ?>

                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $translator->translate('i.total'); ?>:</td>
                                <td class="amount"><?= $numberhelper->format_currency($inv_amount->getTotal() ?? 0.00); ?></td>
                            </tr>

                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $translator->translate('i.paid'); ?></td>
                                <td class="amount"><?= $numberhelper->format_currency($inv_amount->getPaid() ?? 0.00) ?></td>
                            </tr>
                            <tr class="<?= ($is_overdue) ? 'overdue' : 'text-success'; ?>">
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $translator->translate('i.balance'); ?></td>
                                <td class="amount">
                                    <b><?= $numberhelper->format_currency($balance ?? 0.00) ?></b>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php                
                    // img folder located in public folder
                    if ($inv_amount->getBalance() == 0) {
                        echo '<img src="/img/paid.png" class="paid-stamp">';
                    } 
                    if ($is_overdue) {
                        echo '<img src="/img/overdue.png" class="overdue-stamp">';
                    } ?>

                </div><!-- .invoice-items -->

                <hr>

                <?= Html::openTag('div', ['class' => 'row']); ?>

                    <?php if ($inv->getTerms()) { ?>

                        <div class="col-xs-12 col-md-6">
                            <h4><?= $translator->translate('i.terms'); ?></h4>
                            <p><?= nl2br(Html::encode($paymentTermsArray[$inv->getTerms()] ?? '')); ?></p>
                        </div>
                    <?php } ?>

                    <?php echo $attachments; ?>

                </div>

            </div><!-- .invoice-items -->
        </div><!-- #content -->
    </div>
</section>
<?php $this->endBody() ?>
</body>
</html>