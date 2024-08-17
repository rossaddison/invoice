<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;

/**
 * @see QuoteController function url_key 
 * @var App\Invoice\Entity\Client $client 
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Entity\QuoteAmount $quote_amount
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * 
 * @var array $items
 * @var array $quote_tax_rates
 * @var bool $has_expired
 * 
 * @see src\ViewInjection\LayoutViewInjection
 * @var string $companyLogoFileName
 * @var string $logoPath
 * @var int $companyLogoWidth
 * @var int $companyLogoHeight
 * 
 * @var string $alert
 * @var string $modal_purchase_order_number
 */

$vat = $s->get_setting('enable_vat_registration');
?>

<!DOCTYPE html>
<html lang="<?= $translator->translate('i.cldr'); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>
        <?= $s->get_setting('custom_title'); ?>
        - <?= $translator->translate('i.quote'); ?> <?= $quote->getNumber(); ?>
    </title>

    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<div class="container">
    <div id="content">
        <div class="webpreview-header">
            <h2><?= $translator->translate('i.quote'); ?>&nbsp;<?= $quote->getNumber(); ?></h2>
            <div class="btn-group">
                <?php 
                    if (in_array($quote->getStatus_id(), array(2, 3, 5)) && $quote->getSo_id() === '0') : ?>
                    <?= $modal_purchase_order_number; ?>
                    <a href="#purchase-order-number" data-bs-toggle="modal" 
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
                    <label class="btn btn-success" disabled><?= $translator->translate('i.approved'); ?></label>
                <?php endif; ?>
                <?php 
                    // Show a rejected message if the quote has been rejected by the client. A new quote will have to be issued
                    if ($quote->getStatus_id() === 5) :  ?>
                    <label class="btn btn-danger" disabled><?= $translator->translate('i.rejected'); ?></label>
                <?php endif; ?>
                <?php 
                    // Show a canceled message if the quote has been canceled by the company
                    if ($quote->getStatus_id() === 6) :  ?>
                    <label class="btn btn-danger" disabled><?= $translator->translate('i.canceled'); ?></label>
                <?php endif; ?>    
            </div>

        </div>

        <hr>
        
        <?= $alert; ?>

        <div class="invoice">

            <?php
                /**
                 * @see src\ViewInjection\LayoutViewInjection.php $logoPath, $companyLogoWidth, $companyLogoHeight
                 */
                echo Img::tag()
                    ->width($companyLogoWidth)
                    ->height($companyLogoHeight)
                    ->src($logoPath)   
            ?>
            <br>
            <br>
            <div class='row'>
                <div class="col-xs-12 col-md-6 col-lg-5">
                    <h4><?= Html::encode($userInv->getName()); ?></h4>
                    <p><?php if (strlen($userInv->getVat_id() ?: '') > 0) {
                            echo $translator->translate('i.vat_id_short') . ": " . ($userInv->getVat_id() ?: '') . '<br>';
                        } ?>
                        <?php if (strlen($userInv->getTax_code() ?? '') > 0) {
                            echo $translator->translate('i.tax_code_short') . ": " . ($userInv->getTax_code() ?? '' ) . '<br>';
                        } ?>
                        <?php if (strlen($userInv->getAddress_1() ?? '') > 0) {
                            echo Html::encode($userInv->getAddress_1()) . '<br>';
                        } ?>
                        <?php if (strlen($userInv->getAddress_2() ?? '') > 0) {
                            echo Html::encode($userInv->getAddress_2()) . '<br>';
                        } ?>
                        <?php if (strlen($userInv->getCity() ?? '') > 0) {
                            echo Html::encode($userInv->getCity()) . ' ';
                        } ?>
                        <?php if (strlen($userInv->getState() ?? '') > 0) {
                            echo Html::encode($userInv->getState()) . ' ';
                        } ?>
                        <?php if (strlen($userInv->getZip() ?? '') > 0) {
                            echo Html::encode($userInv->getZip()) . '<br>';
                        } ?>
                        <?php if (strlen($userInv->getPhone() ?? '') > 0) { ?><?= $translator->translate('i.phone_abbr'); ?>: <?= Html::encode($userInv->getPhone()); ?>
                            <br><?php } ?>
                        <?php if (strlen($userInv->getFax() ?? '') > 0) { ?><?= $translator->translate('i.fax_abbr'); ?>: <?= Html::encode($userInv->getFax()); ?><?php } ?>
                    </p>
                </div>
                <div class="col-lg-2"></div>
                <div class="col-xs-12 col-md-6 col-lg-5 text-right">

                    <h4><?= Html::encode($clientHelper->format_client($client)); ?></h4>
                        <p><?php if (strlen($client->getClient_vat_id()) > 0) {
                                echo $translator->translate('i.vat_id_short') . ": " . ($client->getClient_vat_id()) . '<br>';
                            } ?>
                            <?php if (strlen($client->getClient_tax_code() ?? '') > 0) {
                                echo $translator->translate('i.tax_code_short') . ": " . ($client->getClient_tax_code() ?? '') . '<br>';
                            } ?>
                            <?php if (strlen($client->getClient_address_1() ?? '') > 0) {
                                echo Html::encode($client->getClient_address_1()) . '<br>';
                            } ?>
                            <?php if (strlen($client->getClient_address_2() ?? '') > 0) {
                                echo Html::encode($client->getClient_address_2()) . '<br>';
                            } ?>
                            <?php if (strlen($client->getClient_city() ?? '') > 0) {
                                echo Html::encode($client->getClient_city()) . ' ';
                            } ?>
                            <?php if (strlen($client->getClient_state() ?? '') > 0) {
                                echo Html::encode($client->getClient_state()) . ' ';
                            } ?>
                            <?php if (strlen($client->getClient_zip() ?? '') > 0) {
                                echo Html::encode($client->getClient_zip()) . '<br>';
                            } ?>
                            <?php if (strlen($clientPhone = $client->getClient_phone() ?? '') > 0) {
                                echo $translator->translate('i.phone_abbr') . ': ' . Html::encode($clientPhone); ?>
                                <br>
                            <?php } ?>
                        </p>

                    <br>
                    <table class="table table-condensed">
                        <tbody>
                        <tr>
                            <td><?= $vat == '1' ? $translator->translate('invoice.invoice.date.issued') : $translator->translate('i.quote_date'); ?></td>
                            <td style="text-align:right;"><?= $quote->getDate_created()->format($dateHelper->style()); ?></td>
                        </tr>
                        <tr class="<?= ($has_expired ? 'overdue' : '') ?>">
                            <td><?= $translator->translate('i.expires'); ?></td>
                            <td class="text-right">
                                <?= $quote->getDate_expires()->format($dateHelper->style()) ?>
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
                            <th><?= $translator->translate('i.item'); ?></th>
                            <th><?= $translator->translate('i.description'); ?></th>
                            <th class="text-right"><?= $translator->translate('i.qty'); ?></th>
                            <th class="text-right"><?= $translator->translate('i.price'); ?></th>
                            <th class="text-right"><?= $translator->translate('i.discount'); ?></th>
                            <th class="text-right"><?= $translator->translate('i.total'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            
                            /**
                             * @var App\Invoice\Entity\InvItem $item
                             */
                            foreach ($items as $item) : ?>
                            <tr>
                                <td><?= Html::encode($item->getName()); ?></td>
                                <td><?= nl2br(Html::encode($item->getDescription())); ?></td>
                                <td class="amount">
                                    <?= $numberHelper->format_amount($item->getQuantity()); ?>
                                    <?php if (strlen($item->getProduct_unit() ?? '') > 0) : ?>
                                        <br>
                                        <small><?= Html::encode($item->getProduct_unit()); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="amount"><?= $numberHelper->format_currency($item->getPrice()); ?></td>
                                <td class="amount"><?= $numberHelper->format_currency($item->getDiscount_amount()); ?></td>
                                <?php $query = $qiaR->repoQuoteItemAmountquery((int)$item->getId()); ?>
                                <td class="amount"><?= $numberHelper->format_currency(null!==$query ? $query->getSubtotal() : 0.00); ?></td>
                                
                                
                                
                            </tr>
                        <?php endforeach ?>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right"><?= $translator->translate('i.subtotal'); ?>:</td>
                            <td class="amount"><?= $numberHelper->format_currency($quote_amount->getItem_subtotal()); ?></td>
                        </tr>
                        <?php if ($quote_amount->getItem_tax_total() > 0) { ?>
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $vat === '1' ? $translator->translate('invoice.invoice.vat.break.down') : $translator->translate('i.item_tax'); ?></td>
                                <td class="amount"><?= $numberHelper->format_currency($quote_amount->getItem_tax_total()); ?></td>
                            </tr>
                        <?php } ?>
                        <?php 
                            if (!empty($quote_tax_rates) && $vat == '0') {
                                /**
                                 * @var App\Invoice\Entity\QuoteTaxRate $quote_tax_rate
                                 */
                                foreach ($quote_tax_rates as $quote_tax_rate) : ?>
                                <tr>
                                    <td class="no-bottom-border" colspan="4"></td>
                                    <td class="text-right">
                                        <?php 
                                            $taxRatePercent = $quote_tax_rate->getTaxRate()?->getTax_rate_percent();
                                            $taxRateName = $quote_tax_rate->getTaxRate()?->getTax_rate_name();
                                            if (($taxRatePercent >= 0.00) && (strlen($taxRateName ?? '') > 0)) {
                                                echo Html::encode(($taxRateName ?? '#') . ' ' . ($numberHelper->format_amount($taxRatePercent) ?? '#'));
                                            }
                                        ?>%
                                    </td>
                                    <td class="amount">
                                        <?php 
                                        $quoteTaxRate = $quote_tax_rate->getQuote_tax_rate_amount();
                                        if ($quoteTaxRate >= 0.00) {
                                            echo $numberHelper->format_currency($quoteTaxRate);
                                        } ?>
                                    </td>
                                </tr>
                            <?php endforeach; } ?>
                        <?php if ($vat === '0') { ?>          
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right"><?= $translator->translate('i.discount'); ?>:</td>
                            <td class="amount">
                                <?php
                                    $percent = $quote->getDiscount_percent();
                                    if ($percent >= 0.00) {
                                        echo (string)$numberHelper->format_amount($percent) . ' %';
                                    } else {
                                        $discountAmount = $quote->getDiscount_amount();
                                        if ($discountAmount >= 0.00) { 
                                            echo $numberHelper->format_amount($discountAmount);
                                        }
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right"><?= $translator->translate('i.total'); ?>:</td>
                            <td class="amount"><?= $numberHelper->format_currency($quote_amount->getTotal()); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div><!-- .invoice-items -->

            <hr>
            <div class="row">
                <?php if (strlen($quote->getNotes() ?? '') > 0) { ?>
                    <div class="col-xs-12 col-md-6">
                        <h4><?= $translator->translate('i.notes'); ?></h4>
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