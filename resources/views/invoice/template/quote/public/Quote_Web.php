<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;

/**
 * Related logic: see QuoteController function url_key.
 *
 * @var App\Invoice\Entity\Client                             $client
 * @var App\Invoice\Entity\Quote                              $quote
 * @var App\Invoice\Entity\QuoteAmount                        $quote_amount
 * @var App\Invoice\Entity\UserInv                            $userInv
 * @var App\Invoice\Helpers\ClientHelper                      $clientHelper
 * @var App\Invoice\Helpers\DateHelper                        $dateHelper
 * @var App\Invoice\Helpers\NumberHelper                      $numberHelper
 * @var App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR
 * @var App\Invoice\Setting\SettingRepository                 $s
 * @var Yiisoft\Router\UrlGeneratorInterface                  $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface                $translator
 * @var array                                                 $items
 * @var array                                                 $quote_tax_rates
 * @var bool                                                  $has_expired
 *
 * Related logic: see src\ViewInjection\LayoutViewInjection
 * @var string $companyLogoFileName
 * @var string $logoPath
 * @var int    $companyLogoWidth
 * @var int    $companyLogoHeight
 * @var string $alert
 * @var string $modal_purchase_order_number
 */
$vat = $s->getSetting('enable_vat_registration');
?>

<!DOCTYPE html>
<html lang="<?php echo $translator->translate('cldr'); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>
        <?php echo $s->getSetting('custom_title'); ?>
        - <?php echo $translator->translate('quote'); ?> <?php echo $quote->getNumber(); ?>
    </title>

    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<div class="container">
    <div id="content">
        <div class="webpreview-header">
            <h2><?php echo $translator->translate('quote'); ?>&nbsp;<?php echo $quote->getNumber(); ?></h2>
            <div class="btn-group">
                <?php
                    if (in_array($quote->getStatus_id(), [2, 3, 5]) && '0' === $quote->getSo_id()) { ?>
                    <?php echo $modal_purchase_order_number; ?>
                    <a href="#purchase-order-number" data-bs-toggle="modal" 
                       class="btn btn-warning">
                        <i class="fa fa-check"></i><?php echo $translator->translate('quote.approve'); ?>
                    </a>
                <?php } ?>                
                <?php
                    // Only show the reject button if it was not previously approved ie there is no sales order id attached to this quote
                    // if there is a sales order id (ie approved previously) the client can reject the subsequent sales order only
                    if (4 !== $quote->getStatus_id() && 5 !== $quote->getStatus_id() && '0' === $quote->getSo_id()) { ?>
                    <a href="<?php echo $urlGenerator->generate('quote/reject', ['url_key' => $quote->getUrl_key()]); ?>" class="btn btn-danger ajax-loader">
                        <i class="fa fa-check"></i><?php echo $translator->translate('quote.reject'); ?>
                    </a>
                <?php } ?>                
                <?php
                    // Show an approved message if the quote is approved
                    if (4 === $quote->getStatus_id()) {  ?>
                    <label class="btn btn-success" disabled><?php echo $translator->translate('approved'); ?></label>
                <?php } ?>
                <?php
                    // Show a rejected message if the quote has been rejected by the client. A new quote will have to be issued
                    if (5 === $quote->getStatus_id()) {  ?>
                    <label class="btn btn-danger" disabled><?php echo $translator->translate('rejected'); ?></label>
                <?php } ?>
                <?php
                    // Show a canceled message if the quote has been canceled by the company
                    if (6 === $quote->getStatus_id()) {  ?>
                    <label class="btn btn-danger" disabled><?php echo $translator->translate('canceled'); ?></label>
                <?php } ?>    
            </div>

        </div>

        <hr>
        
        <?php echo $alert; ?>

        <div class="invoice">

            <?php
                /**
                 * Related logic: see src\ViewInjection\LayoutViewInjection.php $logoPath, $companyLogoWidth, $companyLogoHeight.
                 */
                echo Img::tag()
                    ->width($companyLogoWidth)
                    ->height($companyLogoHeight)
                    ->src($logoPath);
?>
            <br>
            <br>
            <div class='row'>
                <div class="col-xs-12 col-md-6 col-lg-5">
                    <h4><?php echo Html::encode($userInv->getName()); ?></h4>
                    <p><?php if (strlen($userInv->getVat_id() ?: '') > 0) {
                        echo $translator->translate('vat.id.short').': '.($userInv->getVat_id() ?: '').'<br>';
                    } ?>
                        <?php if (strlen($userInv->getTax_code() ?? '') > 0) {
                            echo $translator->translate('tax.code.short').': '.($userInv->getTax_code() ?? '').'<br>';
                        } ?>
                        <?php if (strlen($userInv->getAddress_1() ?? '') > 0) {
                            echo Html::encode($userInv->getAddress_1()).'<br>';
                        } ?>
                        <?php if (strlen($userInv->getAddress_2() ?? '') > 0) {
                            echo Html::encode($userInv->getAddress_2()).'<br>';
                        } ?>
                        <?php if (strlen($userInv->getCity() ?? '') > 0) {
                            echo Html::encode($userInv->getCity()).' ';
                        } ?>
                        <?php if (strlen($userInv->getState() ?? '') > 0) {
                            echo Html::encode($userInv->getState()).' ';
                        } ?>
                        <?php if (strlen($userInv->getZip() ?? '') > 0) {
                            echo Html::encode($userInv->getZip()).'<br>';
                        } ?>
                        <?php if (strlen($userInv->getPhone() ?? '') > 0) { ?><?php echo $translator->translate('phone.abbr'); ?>: <?php echo Html::encode($userInv->getPhone()); ?>
                            <br><?php } ?>
                        <?php if (strlen($userInv->getFax() ?? '') > 0) { ?><?php echo $translator->translate('fax.abbr'); ?>: <?php echo Html::encode($userInv->getFax()); ?><?php } ?>
                    </p>
                </div>
                <div class="col-lg-2"></div>
                <div class="col-xs-12 col-md-6 col-lg-5 text-right">

                    <h4><?php echo Html::encode($clientHelper->format_client($client)); ?></h4>
                        <p><?php if (strlen($client->getClient_vat_id()) > 0) {
                            echo $translator->translate('vat.id.short').': '.$client->getClient_vat_id().'<br>';
                        } ?>
                            <?php if (strlen($client->getClient_tax_code() ?? '') > 0) {
                                echo $translator->translate('tax.code.short').': '.($client->getClient_tax_code() ?? '').'<br>';
                            } ?>
                            <?php if (strlen($client->getClient_address_1() ?? '') > 0) {
                                echo Html::encode($client->getClient_address_1()).'<br>';
                            } ?>
                            <?php if (strlen($client->getClient_address_2() ?? '') > 0) {
                                echo Html::encode($client->getClient_address_2()).'<br>';
                            } ?>
                            <?php if (strlen($client->getClient_city() ?? '') > 0) {
                                echo Html::encode($client->getClient_city()).' ';
                            } ?>
                            <?php if (strlen($client->getClient_state() ?? '') > 0) {
                                echo Html::encode($client->getClient_state()).' ';
                            } ?>
                            <?php if (strlen($client->getClient_zip() ?? '') > 0) {
                                echo Html::encode($client->getClient_zip()).'<br>';
                            } ?>
                            <?php if (strlen($clientPhone = $client->getClient_phone() ?? '') > 0) {
                                echo $translator->translate('phone.abbr').': '.Html::encode($clientPhone); ?>
                                <br>
                            <?php } ?>
                        </p>

                    <br>
                    <table class="table table-condensed">
                        <tbody>
                        <tr>
                            <td><?php echo '1' == $vat ? $translator->translate('date.issued') : $translator->translate('quote.date'); ?></td>
                            <td style="text-align:right;"><?php echo $quote->getDate_created()->format('Y-m-d'); ?></td>
                        </tr>
                        <tr class="<?php echo $has_expired ? 'overdue' : ''; ?>">
                            <td><?php echo $translator->translate('expires'); ?></td>
                            <td class="text-right">
                                <?php echo $quote->getDate_expires()->format('Y-m-d'); ?>
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
                            <th><?php echo $translator->translate('item'); ?></th>
                            <th><?php echo $translator->translate('description'); ?></th>
                            <th class="text-right"><?php echo $translator->translate('qty'); ?></th>
                            <th class="text-right"><?php echo $translator->translate('price'); ?></th>
                            <th class="text-right"><?php echo $translator->translate('discount'); ?></th>
                            <th class="text-right"><?php echo $translator->translate('total'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php

                            /**
                             * @var App\Invoice\Entity\InvItem $item
                             */
                            foreach ($items as $item) { ?>
                            <tr>
                                <td><?php echo Html::encode($item->getName()); ?></td>
                                <td><?php echo nl2br(Html::encode($item->getDescription())); ?></td>
                                <td class="amount">
                                    <?php echo $numberHelper->format_amount($item->getQuantity()); ?>
                                    <?php if (strlen($item->getProduct_unit() ?? '') > 0) { ?>
                                        <br>
                                        <small><?php echo Html::encode($item->getProduct_unit()); ?></small>
                                    <?php } ?>
                                </td>
                                <td class="amount"><?php echo $numberHelper->format_currency($item->getPrice()); ?></td>
                                <td class="amount"><?php echo $numberHelper->format_currency($item->getDiscount_amount()); ?></td>
                                <?php $query = $qiaR->repoQuoteItemAmountquery((int) $item->getId()); ?>
                                <td class="amount"><?php echo $numberHelper->format_currency(null !== $query ? $query->getSubtotal() : 0.00); ?></td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right"><?php echo $translator->translate('subtotal'); ?>:</td>
                            <td class="amount"><?php echo $numberHelper->format_currency($quote_amount->getItem_subtotal()); ?></td>
                        </tr>
                        <?php if ($quote_amount->getItem_tax_total() > 0) { ?>
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?php echo '1' === $vat ? $translator->translate('vat.break.down') : $translator->translate('item.tax'); ?></td>
                                <td class="amount"><?php echo $numberHelper->format_currency($quote_amount->getItem_tax_total()); ?></td>
                            </tr>
                        <?php } ?>
                        <?php
                            if (!empty($quote_tax_rates) && '0' == $vat) {
                                /**
                                 * @var App\Invoice\Entity\QuoteTaxRate $quote_tax_rate
                                 */
                                foreach ($quote_tax_rates as $quote_tax_rate) { ?>
                                <tr>
                                    <td class="no-bottom-border" colspan="4"></td>
                                    <td class="text-right">
                                        <?php
                                            $taxRatePercent = $quote_tax_rate->getTaxRate()?->getTaxRatePercent();
                                    $taxRateName            = $quote_tax_rate->getTaxRate()?->getTaxRateName();
                                    if (($taxRatePercent >= 0.00) && (strlen($taxRateName ?? '') > 0)) {
                                        echo Html::encode(($taxRateName ?? '#').' '.($numberHelper->format_amount($taxRatePercent) ?? '#'));
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
                            <?php }
                                } ?>
                        <?php if ('0' === $vat) { ?>          
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right"><?php echo $translator->translate('discount'); ?>:</td>
                            <td class="amount">
                                <?php
                                        $percent = $quote->getDiscount_percent();
                            if ($percent >= 0.00) {
                                echo (string) $numberHelper->format_amount($percent).' %';
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
                            <td class="text-right"><?php echo $translator->translate('total'); ?>:</td>
                            <td class="amount"><?php echo $numberHelper->format_currency($quote_amount->getTotal()); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div><!-- .invoice-items -->

            <hr>
            <div class="row">
                <?php if (strlen($quote->getNotes() ?? '') > 0) { ?>
                    <div class="col-xs-12 col-md-6">
                        <h4><?php echo $translator->translate('notes'); ?></h4>
                        <p><?php echo nl2br(Html::encode($quote->getNotes())); ?></p>
                    </div>
                <?php } ?>
            </div>
            
             <?php // TODO attachments?>            
            
        </div><!-- .quote-items -->
    </div><!-- #content -->
</div>

</body>
</html>