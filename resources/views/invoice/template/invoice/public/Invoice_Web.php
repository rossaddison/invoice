<?php

declare(strict_types=1);

use App\Invoice\Entity\InvItemAllowanceCharge;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;

/**
 * Related logic: see App\Invoice\Helpers\PdfHelper generate_inv_html
 * Related logic: see InvController function url_key
 * @var App\Invoice\Entity\Client $client
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Entity\InvAmount $inv_amount
 * @var App\Invoice\Entity\PaymentMethod $payment_method
 * @var App\Invoice\Entity\Sumex|null $sumex
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository $aciiR
 * @var App\Invoice\InvItemAmount\InvItemAmountRepository $iiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $custom_fields
 * @var array $inv_tax_rates
 * @var array $items
 * @var array $paymentTermsArray
 * @var bool $is_overdue
 * @var float $balance
 *
 * Related logic: see src\ViewInjection\LayoutViewInjection
 * @var string $companyLogoFileName
 * @var string $logoPath
 * @var int $companyLogoWidth
 * @var int $companyLogoHeight
 *
 * @var string $_language
 * @var string $alert
 * @var string $attachments
 * @var string $client_chosen_gateway
 * @var string $inv_url_key
 * @var string $downloadPdfNonSumexActionName
 * @var string $downloadPdfSumexActionName
 * @psalm-var array<string, Stringable|null|scalar> $downloadPdfNonSumexActionArguments
 * @psalm-var array<string, Stringable|null|scalar> $downloadPdfSumexActionArguments
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
        - <?= $translator->translate('invoice'); ?> <?= $inv->getNumber(); ?>
    </title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
<?= $alert; ?>
<section class="py-3 py-md-5">    
    <div class="container">
        <div id="content">
            <div class="webpreview-header">
                <h2><?= $translator->translate('invoice'); ?>&nbsp;<?= $inv->getNumber(); ?></h2>
                <div class="btn-group">
                    <!-- Include custom fields -->

                    <?php if (null !== $sumex) : ?>
                        <a href="<?= $urlGenerator->generate('inv/key_download_pdf_non_sumex', ['url_key' => $inv_url_key]); ?>" class="btn btn-primary" style="text-decoration:none">
                            <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('download.pdf') . '=>' . $translator->translate('yes') . ' ' . $translator->translate('custom.fields'); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?= $urlGenerator->generate('inv/pdf_download_include_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-primary" style="text-decoration:none">
                           <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('download.pdf') . '=>' . $translator->translate('yes') . ' ' . $translator->translate('custom.fields'); ?>
                        </a>
                    <?php endif; ?>

                    <!-- Exclude custom fields -->         
                    <?php if (null !== $sumex) : ?>
                        <a href="<?= $urlGenerator->generate('inv/key_download_pdf_non_sumex', ['url_key' => $inv_url_key]); ?>" class="btn btn-danger" style="text-decoration:none">
                            <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('download.pdf') . '=>' . $translator->translate('no') . ' ' . $translator->translate('custom.fields'); ?>    
                        </a>    
                    <?php else : ?>
                        <a href="<?= $urlGenerator->generate('inv/pdf_download_exclude_cf', ['url_key' => $inv_url_key]); ?>" class="btn btn-danger" style="text-decoration:none">
                            <i class="fa fa-file-pdf-o"></i> <?= $translator->translate('download.pdf') . '=>' . $translator->translate('no') . ' ' . $translator->translate('custom.fields'); ?>    
                        </a>    
                    <?php endif; ?>

                    <?php if ($s->getSetting('enable_online_payments') == 1 && $inv_amount->getBalance() > 0) { ?>
                        <a href="<?= $urlGenerator->generate(
                            'paymentinformation/inform',
                            ['url_key' => $inv_url_key,
                                'gateway' => $client_chosen_gateway],
                        ); ?>" class="btn btn-success">
                            <i class="fa fa-credit-card"></i><?= $translator->translate('pay.now') . ' ' . str_replace('_', ' ', $client_chosen_gateway); ?>
                        </a>
                    <?php } ?>
                    <?php if ($s->getSetting('enable_online_payments') == 1 && $inv_amount->getBalance() == 0) { ?>
                        <a href="" class="btn btn-success"><?= $translator->translate('paid'); ?></a>    
                    <?php } ?>
                </div>
            </div>
            <hr>

            <div class="invoice">
                <?php
                    /**
                     * Related logic: see src\ViewInjection\LayoutViewInjection.php
                     */
                    echo Img::tag()
                         ->width($companyLogoWidth)
                         ->height($companyLogoHeight)
                         ->src($logoPath);
?>
                
                <br>
                <br>
                <div class="row">
                    <div class="col-xs-12 col-md-6 col-lg-5">

                        <h4><?= Html::encode($userInv->getName()); ?></h4>
                        <p><?php if (strlen($userInv->getVat_id() ?: '') > 0) {
                            echo $translator->translate('vat.id.short') . ": " . ($userInv->getVat_id() ?: '') . '<br>';
                        } ?>
                            <?php if (strlen($userInv->getTax_code() ?? '') > 0) {
                                echo $translator->translate('tax.code.short') . ": " . ($userInv->getTax_code() ?? '') . '<br>';
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
                            <?php if (strlen($userInv->getPhone() ?? '') > 0) { ?><?= $translator->translate('phone.abbr'); ?>: <?= Html::encode($userInv->getPhone()); ?>
                                <br><?php } ?>
                            <?php if (strlen($userInv->getFax() ?? '') > 0) { ?><?= $translator->translate('fax.abbr'); ?>: <?= Html::encode($userInv->getFax()); ?><?php } ?>
                        </p>

                    </div>
                    <div class="col-lg-2"></div>
                    <div class="col-xs-12 col-md-6 col-lg-5 text-right">

                        <h4><?= Html::encode($clientHelper->format_client($client)); ?></h4>
                        <p><?php if (strlen($client->getClient_vat_id()) > 0) {
                            echo $translator->translate('vat.id.short') . ": " . ($client->getClient_vat_id()) . '<br>';
                        } ?>
                            <?php if (strlen($client->getClient_tax_code() ?? '') > 0) {
                                echo $translator->translate('tax.code.short') . ": " . ($client->getClient_tax_code() ?? '') . '<br>';
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
                            <?php if (strlen($client->getClient_phone() ?? '') > 0) {
                                echo $translator->translate('phone.abbr') . ': ' . Html::encode($client->getClient_phone()); ?>
                                <br>
                            <?php } ?>
                        </p>

                        <br>

                        <table class="table table-condensed">
                            <tbody>
                            <tr>
                                <td><?= $translator->translate('date'); ?></td>
                                <td style="text-align:right;"><?= $inv->getDate_created()->format('Y-m-d'); ?></td>
                            </tr>
                            <tr class="<?=($is_overdue ? 'overdue' : '') ?>">
                                <td><?= $translator->translate('due.date'); ?></td>
                                <td class="text-right">
                                    <?= $inv->getDate_due()->format('Y-m-d'); ?>
                                </td>
                            </tr>
                            <tr class="<?=($is_overdue ? 'overdue' : '') ?>">
                                <td><?= $translator->translate('amount.due'); ?></td>
                                <td style="text-align:right;"><?= $numberHelper->format_currency($inv_amount->getBalance() ?? 0.00); ?></td>
                            </tr>                            
                            <tr>
                                <td><?= $translator->translate('payment.method') . ': '; ?></td>
                                <td><?= Html::encode($payment_method->getName()); ?></td>
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
                            foreach ($items as $item) { ?>
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
                                <?php
                                    $query =
                                        $iiaR->repoInvItemAmountquery(
                                            (string) $item->getId()
                                        );
                                ?>
                                <td class="amount">
                                    <?= $numberHelper->format_currency(
                                        null !== $query
                                            ? $query->getSubtotal()
                                            : 0.00
                                    ); ?>
                                </td>                                   
                            </tr>
                            <?php
                            // Display item-level allowances/charges
                            // if Peppol is enabled
                            if (
                                $s->getSetting('enable_peppol') == '1'
                            ) {
                                $invItemAllowanceCharges =
                                    $aciiR->repoInvItemquery(
                                        (string) $item->getId()
                                    );
                                /**
                                 * @var InvItemAllowanceCharge $invItemAllowanceCharge
                                 */
                                foreach (
                                    $invItemAllowanceCharges
                                    as $invItemAllowanceCharge
                                ) {
                                    $isCharge = (
                                        $invItemAllowanceCharge
                                            ->getAllowanceCharge()
                                                ?->getIdentifier() == 1
                                                ? true : false
                                    );
                            ?>
                            <tr>
                                <td colspan="5">
                                    <b>
                                    <?=
                                        $invItemAllowanceCharge
                                            ->getAllowanceCharge()
                                                ?->getIdentifier() == '1'
                                        ? $translator->translate(
                                            'allowance.or.charge.charge'
                                        )
                                        : '(' .
                                            $translator->translate(
                                                'allowance.or.charge.allowance'
                                            ) . ')'; ?>
                                    </b>
                                    <?=
                                        $translator->translate(
                                            'allowance.or.charge.reason.code'
                                        ) . ': ' . (
                                            $invItemAllowanceCharge
                                                ->getAllowanceCharge()
                                                    ?->getReasonCode()
                                                        ?? '#'
                                        ); ?>
                                    -
                                    <?=
                                        $translator->translate(
                                            'allowance.or.charge.reason'
                                        ) . ': ' . (
                                            $invItemAllowanceCharge
                                                ->getAllowanceCharge()
                                                    ?->getReason() ?? '#'
                                        ); ?>
                                </td>
                                <td class="amount">
                                    <b>
                                    <?= ($isCharge ? '' : '(')
                                        . $numberHelper
                                            ->format_currency(
                                                $invItemAllowanceCharge
                                                    ->getAmount()
                                            ) . ($isCharge ? '' : ')'); ?>
                                    </b>
                                </td>
                                <td class="amount">
                                    <b>
                                    <?php $vatInvItem = $invItemAllowanceCharge->getVatOrTax();
                                        echo ($isCharge ? '' : '(')
                                            . $numberHelper->format_currency($vatInvItem)
                                            . ($isCharge ? '' : ')'); ?>
                                    </b>
                                </td>
                            </tr>
                            <?php
                                }
                            }
                            } ?>

                            <tr>
                                <td colspan="4"></td>
                                <td class="text-right"><?= $translator->translate('subtotal'); ?>:</td>
                                <td class="amount"><b><?= $numberHelper->format_currency($inv_amount->getItem_subtotal()); ?></b></td>
                            </tr>                            

                            <?php if ($inv_amount->getItem_tax_total() > 0) { ?>
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                               <td class="text-right"><?= $vat === '0' ? $translator->translate('item.tax') : $translator->translate('vat.abbreviation') ?></td>
                                <td class="amount">
                                    <b><?php
                                        $invAmountItemTaxTotal = $inv_amount->getItem_tax_total();
                                echo($invAmountItemTaxTotal >= 0.00 ? $numberHelper->format_currency($invAmountItemTaxTotal) : '');
                                ?></b>
                                </td>
                            </tr>
                            <?php } ?>

                            <?php
                            if ($s->getSetting('enable_peppol') == '1') {
                                if ($inv_amount->getPackhandleship_total()
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
                                        <b><?= $numberHelper->format_currency(
                                            $inv_amount
                                                ->getPackhandleship_total()
                                        ); ?></b>
                                    </td>
                                </tr>
                            <?php }
                                if ($inv_amount->getPackhandleship_tax()
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
                                        <b><?= $numberHelper->format_currency(
                                            $inv_amount
                                                ->getPackhandleship_tax()
                                        ); ?></b>
                                    </td>
                                </tr>
                            <?php }
                            } ?>

                            <?php if ($vat  === '0') { ?>
                            <?php

                            /**
                             * @var App\Invoice\Entity\InvTaxRate $inv_tax_rate
                             */
                            foreach ($inv_tax_rates as $inv_tax_rate) : ?>
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right">
                                    <?php
                                    $taxRatePercent = $inv_tax_rate->getTaxRate()?->getTaxRatePercent();
                                $taxRateName = $inv_tax_rate->getTaxRate()?->getTaxRateName();
                                if (($taxRatePercent >= 0.00) && (strlen($taxRateName ?? '') > 0)) {
                                    echo Html::encode(($taxRateName ?? '#') . ' ' . ($numberHelper->format_amount($taxRatePercent) ?? '#'));
                                }
                                ?>
                                    %
                                </td>
                                <td class="amount">
                                    <b><?php
                                    $invTaxRate = $inv_tax_rate->getInv_tax_rate_amount();
                                if ($invTaxRate >= 0.00) {
                                    echo $numberHelper->format_currency($invTaxRate);
                                } ?></b>
                                </td>
                            </tr>
                            <?php   endforeach; ?>
                            <?php } ?>
                            <?php if ($vat  === '0') { ?>        
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $translator->translate('discount'); ?>:</td>
                                <td class="amount">
                                    <b><?php
                                    $percent = $inv->getDiscount_percent();
                                if ($percent >= 0.00) {
                                    echo (string) $numberHelper->format_amount($percent) . ' %';
                                } else {
                                    $discountAmount = $inv->getDiscount_amount();
                                    if ($discountAmount >= 0.00) {
                                        echo $numberHelper->format_amount($discountAmount);
                                    }
                                }
                                ?></b>
                                </td>
                            </tr>
                            <?php } ?>

                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $translator->translate('total'); ?>:</td>
                                <td class="amount"><b><?= $numberHelper->format_currency($inv_amount->getTotal()); ?></b></td>
                            </tr>

                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $translator->translate('paid'); ?></td>
                                <td class="amount"><b><?= $numberHelper->format_currency($inv_amount->getPaid()) ?></b></td>
                            </tr>
                            <tr class="<?= ($is_overdue) ? 'overdue' : 'text-success'; ?>">
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $translator->translate('balance'); ?></td>
                                <td class="amount">
                                    <b><?= $numberHelper->format_currency($balance ?: 0.00) ?></b>
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

                <div>

                    <?php if ($inv->getTerms()) { ?>

                        <div class="col-xs-12 col-md-6">
                            <h4><?= $translator->translate('terms'); ?></h4>
                            <p><?= nl2br(Html::encode($paymentTermsArray[$inv->getTerms()] ?? '')); ?></p>
                        </div>
                    <?php } ?>

                    <?= $attachments; ?>

                </div>

            </div><!-- invoice -->
        </div><!-- #content -->
    </div>
</section>
</body>
</html>