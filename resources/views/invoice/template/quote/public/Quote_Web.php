<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;

/**
 * Related logic: see QuoteController function urlKey
 * @var App\Infrastructure\Persistence\Client\Client $client
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Entity\QuoteAmount $quote_amount
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository $acqiR
 * @var App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 *
 * @var array $items
 * @var array $quote_tax_rates
 * @var bool $has_expired
 *
 * Related logic: see src\ViewInjection\LayoutViewInjection
 * @var string $companyLogoFileName
 * @var string $logoPath
 * @var int $companyLogoWidth
 * @var int $companyLogoHeight
 *
 * @var string $alert
 * @var string $modal_purchase_order_number
 */

$vat = $s->getSetting('enable_vat_registration');
?>

<!DOCTYPE html>
<html lang="<?= $translator->translate('cldr'); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>
        <?= $s->getSetting('custom_title'); ?>
        - <?= $translator->translate('quote'); ?> <?= $quote->getNumber(); ?>
    </title>

    <link rel="stylesheet" href="/assets/css/invoice-documents.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<div class="container">
    <div id="content">
        <div class="webpreview-header">
            <h2><?= $translator->translate('quote'); ?>&nbsp;<?= $quote->getNumber(); ?></h2>
            <div class="btn-group">
                <?php
                    if (in_array($quote->getStatusId(), [2, 3, 5]) && $quote->getSoId() === '0') : ?>
                    <?=
//  src/typescript/quote.ts#quote_with_purchase_order_number_confirm,
//  .quote_with_purchase_order_number_confirm ...
//  handleQuotePurchaseOrderConfirm ...
//  'quote/approve'
                        $modal_purchase_order_number;
                    ?>
                    <a href="#purchase-order-number" data-bs-toggle="modal"
                       class="btn btn-warning">
                        <i class="bi bi-check-lg"></i><?= $translator->translate('quote.approve'); ?>
                    </a>
                <?php endif; ?>
                <?php
                    // Only show the reject button if it was not previously approved ie there is no sales order id attached to this quote
                    // if there is a sales order id (ie approved previously) the client can reject the subsequent sales order only
                    if ($quote->getStatusId() !== 4 && $quote->getStatusId() !== 5 && $quote->getSoId() === '0') { ?>
                    <a href="<?= $urlGenerator->generate('quote/reject', ['url_key' => $quote->getUrlKey()]); ?>" class="btn btn-danger ajax-loader">
                        <i class="bi bi-check-lg"></i><?= $translator->translate('quote.reject'); ?>
                    </a>
                <?php } ?>
                <?php
                    // Show an approved message if the quote is approved
                    if ($quote->getStatusId()  === 4) :  ?>
                    <label class="btn btn-success" disabled><?= $translator->translate('approved'); ?></label>
                <?php endif; ?>
                <?php
                    // Show a rejected message if the quote has been rejected by the client. A new quote will have to be issued
                    if ($quote->getStatusId() === 5) :  ?>
                    <label class="btn btn-danger" disabled><?= $translator->translate('rejected'); ?></label>
                <?php endif; ?>
                <?php
                    // Show a canceled message if the quote has been canceled by the company
                    if ($quote->getStatusId() === 6) :  ?>
                    <label class="btn btn-danger" disabled><?= $translator->translate('canceled'); ?></label>
                <?php endif; ?>
            </div>

        </div>

        <hr>

        <?= $alert; ?>

        <div class="invoice">

            <?php
                /**
                 * Related logic: see src\ViewInjection\LayoutViewInjection.php $logoPath, $companyLogoWidth, $companyLogoHeight
                 */
                echo  new Img()
                    ->width($companyLogoWidth)
                    ->height($companyLogoHeight)
                    ->src($logoPath)
?>
            <br>
            <br>
            <div class='row'>
                <div class="col-xs-12 col-md-6 col-lg-5">
                    <h4><?= Html::encode($userInv->getName()); ?></h4>
                    <p><?php if (strlen($userInv->getVatId() ?: '') > 0) {
                        echo $translator->translate('vat.id.short') . ": " . ($userInv->getVatId() ?: '') . '<br>';
                    } ?>
                        <?php if (strlen($userInv->getTaxCode() ?? '') > 0) {
                            echo $translator->translate('tax.code.short') . ": " . ($userInv->getTaxCode() ?? '') . '<br>';
                        } ?>
                        <?php if (strlen($userInv->getAddress1() ?? '') > 0) {
                            echo Html::encode($userInv->getAddress1()) . '<br>';
                        } ?>
                        <?php if (strlen($userInv->getAddress2() ?? '') > 0) {
                            echo Html::encode($userInv->getAddress2()) . '<br>';
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
                        <?php if (strlen($userInv->getPhone() ?? '') > 0) { ?><?= $translator->translate('phone.abbr'); ?>: <?= Html::encode($userInv->getPhone()); ?>
                            <br><?php } ?>
                        <?php if (strlen($userInv->getFax() ?? '') > 0) { ?><?= $translator->translate('fax.abbr'); ?>: <?= Html::encode($userInv->getFax()); ?><?php } ?>
                    </p>
                </div>
                <div class="col-lg-2"></div>
                <div class="col-xs-12 col-md-6 col-lg-5 text-right">

                    <h4><?= Html::encode($clientHelper->formatClient($client)); ?></h4>
                        <p><?php if (strlen($client->getClientVatId()) > 0) {
                            echo $translator->translate('vat.id.short') . ": " . ($client->getClientVatId()) . '<br>';
                        } ?>
                            <?php if (strlen($client->getClientTaxCode() ?? '') > 0) {
                                echo $translator->translate('tax.code.short') . ": " . ($client->getClientTaxCode() ?? '') . '<br>';
                            } ?>
                            <?php if (strlen($client->getClientAddress1() ?? '') > 0) {
                                echo Html::encode($client->getClientAddress1()) . '<br>';
                            } ?>
                            <?php if (strlen($client->getClientAddress2() ?? '') > 0) {
                                echo Html::encode($client->getClientAddress2()) . '<br>';
                            } ?>
                            <?php if (strlen($client->getClientCity() ?? '') > 0) {
                                echo Html::encode($client->getClientCity()) . ' ';
                            } ?>
                            <?php if (strlen($client->getClientState() ?? '') > 0) {
                                echo Html::encode($client->getClientState()) . ' ';
                            } ?>
                            <?php if (strlen($client->getClientZip() ?? '') > 0) {
                                echo Html::encode($client->getClientZip()) . '<br>';
                            } ?>
                            <?php if (strlen($clientPhone = $client->getClientPhone() ?? '') > 0) {
                                echo $translator->translate('phone.abbr') . ': ' . Html::encode($clientPhone); ?>
                                <br>
                            <?php } ?>
                        </p>

                    <br>
                    <table class="table table-condensed">
                        <tbody>
                        <tr>
                            <td><?= $vat == '1' ? $translator->translate('date.issued') : $translator->translate('quote.date'); ?></td>
                            <td style="text-align:right;"><?= $quote->getDateCreated()->format('Y-m-d'); ?></td>
                        </tr>
                        <tr class="<?= ($has_expired ? 'overdue' : '') ?>">
                            <td><?= $translator->translate('expires'); ?></td>
                            <td class="text-right">
                                <?= $quote->getDateExpires()->format('Y-m-d') ?>
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
                            <th><?= $translator->translate('item'); ?></th>
                            <th><?= $translator->translate('description'); ?></th>
                            <th class="text-right"><?= $translator->translate('qty'); ?></th>
                            <th class="text-right"><?= $translator->translate('price'); ?></th>
                            <th class="text-right"><?= $translator->translate('discount'); ?></th>
                            <th class="text-right"><?= $translator->translate('total'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php

                            /**
                             * @var App\Invoice\Entity\InvItem $item
                             */
                            foreach ($items as $item) :
                        // Display item-level allowances/charges BEFORE the item
                        // if Peppol is enabled
                        if ($s->getSetting('enable_peppol') == '1') {
                            $itemId = $item->getId();
                            if (null !== $itemId) {
                            $quoteItemAllowanceCharges =
                                $acqiR->repoQuoteItemquery(
                                    (string)$itemId
                                );
                            /**
                             * @var App\Invoice\Entity\QuoteItemAllowanceCharge $quoteItemAllowanceCharge
                             */
                            foreach (
                                $quoteItemAllowanceCharges
                                as $quoteItemAllowanceCharge
                            ) {
                                $isCharge = (
                                    $quoteItemAllowanceCharge
                                        ->getAllowanceCharge()
                                            ?->getIdentifier() == 1
                                            ? true : false
                                );
                        ?>
                            <tr>
                                <td colspan="5">
                                    <?=
                                        $quoteItemAllowanceCharge
                                            ->getAllowanceCharge()
                                                ?->getIdentifier()
                                                    == '1'
                                        ? $translator->translate(
                                            'allowance.or.charge.charge'
                                        )
                                        : '(' .
                                            $translator->translate(
                                                'allowance.or.charge.allowance'
                                            ) . ')'; ?>
                                    <?=
                                        $translator->translate(
                                            'allowance.or.charge.reason.code'
                                        ) . ': ' . (
                                            $quoteItemAllowanceCharge
                                                ->getAllowanceCharge()
                                                    ?->getReasonCode()
                                                        ?? '#'
                                        ); ?>
                                    -
                                    <?=
                                        $translator->translate(
                                            'allowance.or.charge.reason'
                                        ) . ': ' . (
                                            $quoteItemAllowanceCharge
                                                ->getAllowanceCharge()
                                                    ?->getReason() ?? '#'
                                        ); ?>
                                </td>
                                <td class="amount">
                                    <?= ($isCharge ? '' : '(')
                                        . $numberHelper
                                            ->formatCurrency(
                                                $quoteItemAllowanceCharge
                                                    ->getAmount()
                                            ) . ($isCharge ? '' : ')'); ?>
                                </td>
                                <td class="amount">
                                    <?php $vatQuoteItem = $quoteItemAllowanceCharge->getVatOrTax();
                                        echo ($isCharge ? '' : '(')
                                            . $numberHelper->formatCurrency($vatQuoteItem)
                                            . ($isCharge ? '' : ')'); ?>
                                </td>
                            </tr>
                        <?php
                            }
                            }
                        }
                        ?>
                            <tr>
                                <td><?= Html::encode($item->getName()); ?></td>
                                <td><?= nl2br(Html::encode($item->getDescription())); ?></td>
                                <td class="amount">
                                    <?= $numberHelper->formatAmount($item->getQuantity()); ?>
                                    <?php if (strlen($item->getProductUnit() ?? '') > 0) : ?>
                                        <br>
                                        <small><?= Html::encode($item->getProductUnit()); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="amount"><?= $numberHelper->formatCurrency($item->getPrice()); ?></td>
                                <td class="amount"><?= $numberHelper->formatCurrency($item->getDiscountAmount()); ?></td>
                                <?php
                                    $query =
                                        $qiaR
                                            ->repoQuoteItemAmountquery(
                                                (string) $item->getId()
                                            );
                                ?>
                                <td class="amount">
                                    <b>
                                    <?= $numberHelper->formatCurrency(
                                        null !== $query
                                            ? $query->getSubtotal()
                                            : 0.00
                                    ); ?>
                                    </b>
                                </td>
                            </tr>
                        <?php
                        endforeach ?>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right"><?= $translator->translate('subtotal'); ?>:</td>
                            <td class="amount"><b><?= $numberHelper->formatCurrency($quote_amount->getItemSubtotal()); ?></b></td>
                        </tr>
                        <?php if ($quote_amount->getItemTaxTotal() > 0) { ?>
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $vat === '1' ? $translator->translate('vat.break.down') : $translator->translate('item.tax'); ?></td>
                                <td class="amount"><b><?= $numberHelper->formatCurrency($quote_amount->getItemTaxTotal()); ?></b></td>
                            </tr>
                        <?php } ?>
                        <?php
                        if ($s->getSetting('enable_peppol') == '1') {
                            if ($quote_amount->getPackhandleshipTotal()
                                != 0.00
                            ) { ?>
                            <tr>
                                <td class="no-bottom-border"
                                    colspan="4"></td>
                                <td class="text-right">
                                    <?= $translator->translate(
                                        'allowance.or.charge.shipping.handling.packaging'
                                    ); ?>
                                </td>
                                <td class="amount">
                                    <b><?= $numberHelper->formatCurrency(
                                        $quote_amount
                                            ->getPackhandleshipTotal()
                                    ); ?></b>
                                </td>
                            </tr>
                        <?php }
                            if ($quote_amount->getPackhandleshipTax()
                                != 0.00
                            ) { ?>
                            <tr>
                                <td class="no-bottom-border"
                                    colspan="4"></td>
                                <td class="text-right">
                                    <?= $vat == '1'
                                        ? $translator->translate(
                                            'allowance.or.charge.shipping.handling.packaging.vat'
                                        )
                                        : $translator->translate(
                                            'allowance.or.charge.shipping.handling.packaging.tax'
                                        ); ?>
                                </td>
                                <td class="amount">
                                    <b><?= $numberHelper->formatCurrency(
                                        $quote_amount
                                            ->getPackhandleshipTax()
                                    ); ?></b>
                                </td>
                            </tr>
                        <?php }
                        } ?>
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
                                            $taxRatePercent = $quote_tax_rate->getTaxRate()?->getTaxRatePercent();
                                    $taxRateName = $quote_tax_rate->getTaxRate()?->getTaxRateName();
                                    if (($taxRatePercent >= 0.00) && (strlen($taxRateName ?? '') > 0)) {
                                        echo Html::encode(($taxRateName ?? '#') . ' ' . ($numberHelper->formatAmount($taxRatePercent) ?? '#'));
                                    }
                                    ?>%
                                    </td>
                                    <td class="amount">
                                        <b><?php
                                    $quoteTaxRate = $quote_tax_rate->getQuoteTaxRateAmount();
                                    if ($quoteTaxRate >= 0.00) {
                                        echo $numberHelper->formatCurrency($quoteTaxRate);
                                    } ?></b>
                                    </td>
                                </tr>
                            <?php endforeach;
                            } ?>

                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right"><?= $translator->translate('discount'); ?>:</td>
                            <td class="amount">
                                <b><?php echo $numberHelper->formatAmount($quote->getDiscountAmount()); ?></b>
                            </td>
                        </tr>
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right"><?= $translator->translate('total'); ?>:</td>
                            <td class="amount">
                                <b>
              <?= $numberHelper->formatCurrency($quote_amount->getTotal()); ?>
                                </b>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div><!-- .invoice-items -->

            <hr>
            <div class="row">
                <?php if (strlen($quote->getNotes() ?? '') > 0) { ?>
                    <div class="col-xs-12 col-md-6">
                        <h4><?= $translator->translate('notes'); ?></h4>
                        <p><?= nl2br(Html::encode($quote->getNotes())); ?></p>
                    </div>
                <?php } ?>
            </div>
        </div><!-- .quote-items -->
    </div><!-- #content -->
</div>

</body>
</html>