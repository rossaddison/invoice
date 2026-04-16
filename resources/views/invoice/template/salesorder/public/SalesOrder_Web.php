<?php

declare(strict_types=1);

use App\Invoice\Entity\SalesOrderItemAllowanceCharge;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;

/**
 * Related logic: see SalesOrderController function urlKey
 * @var App\Infrastructure\Persistence\Client\Client $client
 * @var App\Invoice\Entity\SalesOrder $salesorder
 * @var App\Invoice\Entity\SalesOrderAmount $salesorder_amount
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository $acsoiR
 * @var App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository $soiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 *
 * @var array $items
 * @var array $salesorder_tax_rates
 *
 * Related logic: see src\ViewInjection\LayoutViewInjection
 * @var string $companyLogoFileName
 * @var string $logoPath
 * @var int $companyLogoWidth
 * @var int $companyLogoHeight
 *
 * @var string $alert
 * @var string $salesorder_url_key
 * @var string $terms_and_conditions_file
 *
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
        - <?= $translator->translate('salesorder'); ?>
         <?= $salesorder->getNumber() ?? '#'; ?>
    </title>

    <link rel="stylesheet" href="/assets/css/invoice-documents.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<div class="container">
    <div id="content">
        <div class="webpreview-header">
            <div class='row'>
                <h1><?= $translator->translate('term'); ?></h1>
                <div class="col-xs-12 col-sm-6 badge text-bg-info">
                    <div>
                        <textarea  class="form-control form-control-lg" rows="20" cols="20">
                            <?= $terms_and_conditions_file; ?></textarea>
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
                if (in_array($salesorder->getStatusId(),
                    [2, 8]) && $salesorder->getQuoteId() !== '0'
                        && $salesorder->getInvId() === '0') : ?>
                <a href="<?= $urlGenerator->generate('salesorder/agreeToTerms',
                    ['url_key' => $salesorder_url_key]); ?>"
                   class="btn btn-success"
                   data-bs-toggle = "tooltip"
                   title=
                   "Goods and Services will now be assembled/packaged/prepared">
                    <i class="bi bi-check-lg"></i>
                     <?= $translator->translate('salesorder.agree.to.terms'); ?>
                </a>
            <?php endif; ?>
            <?php if (in_array($salesorder->getStatusId(), [2])
                    && $salesorder->getQuoteId() !== '0'
                    && $salesorder->getInvId() === '0') :  ?>
                <a href="<?= $urlGenerator->generate('salesorder/reject',
                        ['url_key' => $salesorder_url_key]); ?>"
                   class="btn btn-danger">
                    <i class="bi bi-x-circle"></i>
                        <?= $translator->translate('salesorder.reject'); ?>
                </a>
            <?php endif; ?>
            <?php if (in_array($salesorder->getStatusId(), [3])
                    && $salesorder->getQuoteId() !== '0'
                    && $salesorder->getInvId() === '0') :  ?>
                <label class="btn btn-success">
             <?= $translator->translate('salesorder.client.confirmed.terms'); ?>
                </label>
            <?php endif; ?>
        </div>
        <br>

        <?php
        // Show Peppol form for guests when terms are agreed (status 3 or 4) and before invoice is generated
        if (in_array($salesorder->getStatusId(), [3, 4])
            && $salesorder->getInvId() === '0'
            && isset($isGuest) && $isGuest === true) :
        ?>
            <div class="alert alert-info" role="alert">
                <h4 class="alert-heading">
                    <i class="bi bi-file-text"></i>
                    <?= $translator->translate('invoice.peppol.information.required'); ?>
                </h4>
                <p><?= $translator->translate('invoice.peppol.guest.instructions'); ?></p>
                <hr>
                <p class="mb-0">
                    <?= $translator->translate('invoice.peppol.click.items.below'); ?>
                </p>
            </div>
        <?php endif; ?>

        <br>
        <h2><?= $translator->translate('salesorder'); ?>&nbsp;
            <?= $salesorder->getNumber(); ?>
        </h2>
    </div>
    <hr>

        <?= $alert; ?>

        <div class="invoice">

            <?php
                /**
                 * Related logic: see src\ViewInjection\LayoutViewInjection.php
                 * $logoPath, $companyLogoWidth, $companyLogoHeight
                 */
                echo  new Img()
                    ->width($companyLogoWidth)
                    ->height($companyLogoHeight)
                    ->src($logoPath)
?>

            <div class='row'>
                <div class="col-xs-12 col-md-6 col-lg-5">
                    <h4><?= Html::encode($userInv->getName()); ?></h4>
                    <p><?php if (strlen($userInv->getVatId() ?: '') > 0) {
                        echo $translator->translate('vat.id.short')
                                . ": " . ($userInv->getVatId() ?: '') . '<br>';
                    } ?>
                        <?php if (strlen($userInv->getTaxCode() ?? '') > 0) {
                            echo $translator->translate('tax.code.short')
                               . ": " . ($userInv->getTaxCode() ?? '') . '<br>';
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
                        <?php if (strlen($userInv->getPhone() ?? '') > 0) { ?>
                            <?= $translator->translate('phone.abbr'); ?>:
                             <?= Html::encode($userInv->getPhone()); ?>
                            <br><?php } ?>
                        <?php if (strlen($userInv->getFax() ?? '') > 0) { ?>
                            <?= $translator->translate('fax.abbr'); ?>:
                             <?= Html::encode($userInv->getFax()); ?><?php } ?>
                    </p>
                </div>
                <div class="col-lg-2"></div>
                <div class="col-xs-12 col-md-6 col-lg-5 text-right">

                    <h4>
                     <?= Html::encode($clientHelper->formatClient($client)); ?>
                    </h4>
                        <p><?php if (strlen($client->getClientVatId()) > 0) {
                            echo $translator->translate('vat.id.short')
                                . ": " . ($client->getClientVatId()) . '<br>';
                        } ?>
                            <?php if (strlen($client->getClientTaxCode() ??
                                    '') > 0) {
                                echo $translator->translate('tax.code.short')
                                . ": " . ($client->getClientTaxCode() ?? '')
                                    . '<br>';
                            } ?>
                            <?php if (strlen($client->getClientAddress1() ??
                                    '') > 0) {
                                echo Html::encode($client->getClientAddress1())
                                    . '<br>';
                            } ?>
                            <?php if (strlen($client->getClientAddress2() ??
                                    '') > 0) {
                                echo Html::encode($client->getClientAddress2())
                                    . '<br>';
                            } ?>
                            <?php if (strlen($client->getClientCity() ?? '')
                                    > 0) {
                                echo Html::encode($client->getClientCity())
                                        . ' ';
                            } ?>
                            <?php if (strlen($client->getClientState() ?? '')
                                    > 0) {
                                echo Html::encode($client->getClientState())
                                    . ' ';
                            } ?>
                            <?php if (strlen($client->getClientZip() ?? '')
                                    > 0) {
                                echo Html::encode($client->getClientZip())
                                    . '<br>';
                            } ?>
                            <?php if (strlen($clientPhone =
                                        $client->getClientPhone() ?? '') > 0) {
                                echo $translator->translate('phone.abbr')
                                        . ': ' . Html::encode($clientPhone); ?>
                                <br>
                            <?php } ?>
                        </p>

                    <br>
                    <table class="table table-condensed">
                        <tbody>
                        <tr>
                            <td>
                            <?= $vat == '1' ?
                                    $translator->translate('date.issued') :
                                        $translator->translate('quote.date'); ?>
                            </td>
                            <td style="text-align:right;">
                                <?= $dateHelper->dateFromMysql(
                                    $salesorder->getDateCreated()); ?>
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
                            <th class="text-right">
                                <?= $translator->translate('qty'); ?>
                            </th>
                            <th class="text-right">
                                <?= $translator->translate('price'); ?>
                            </th>
                            <th class="text-right">
                                <?= $translator->translate('discount'); ?>
                            </th>
                            <th class="text-right">
                                <?= $translator->translate('total'); ?>
                            </th>
                            <?php
                            // Show edit column for guests when they can edit Peppol fields
                            if (in_array($salesorder->getStatusId(), [3, 4])
                                && $salesorder->getInvId() === '0'
                                && isset($isGuest) && $isGuest === true) :
                            ?>
                            <th class="text-center">
                                <?= $translator->translate('invoice.peppol.edit'); ?>
                            </th>
                            <?php endif; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                           /**
                             * @var App\Invoice\Entity\SalesOrderItem $item
                             */
                            foreach ($items as $item) :
                                // Show Peppol fields if they have been entered
                                $peppolItemId = $item->getPeppolPoItemid();
                                $peppolLineId = $item->getPeppolPoLineid();
                            ?>
                            <tr>
                                <td>
                                           <?= Html::encode($item->getName()); ?>
                                           <?php if (null !== $peppolItemId || null !== $peppolLineId) : ?>
                                               <br><small class="text-muted">
                                                   <?php if (null !== $peppolItemId) : ?>
                                                       <i class="bi bi-upc-scan"></i> <?= Html::encode($peppolItemId); ?>
                                                   <?php endif; ?>
                                                   <?php if (null !== $peppolLineId) : ?>
                                                       <br><i class="bi bi-list-ol"></i> <?= Html::encode($peppolLineId); ?>
                                                   <?php endif; ?>
                                               </small>
                                           <?php endif; ?>
                                </td>
                                <?php
                                // Show edit button for guests when they can edit Peppol fields
                                if (in_array($salesorder->getStatusId(), [3, 4])
                                    && $salesorder->getInvId() === '0'
                                    && isset($isGuest) && $isGuest === true) :
                                ?>
                                <td class="text-center">
                                    <a href="<?= $urlGenerator->generate('salesorderitem/edit', ['id' => $item->getId()]); ?>"
                                       class="btn btn-sm btn-primary"
                                       data-bs-toggle="tooltip"
                                       title="<?= $translator->translate('invoice.peppol.edit.item'); ?>">
                                        <i class="bi bi-pencil-square"></i>
                                        <?= $translator->translate('invoice.peppol.enter'); ?>
                                    </a>
                                </td>
                                <?php endif; ?>
                                <td>
                            <?= nl2br(Html::encode($item->getDescription())); ?>
                                </td>
                                <td class="amount">
                                    <?= $numberHelper->formatAmount(
                                            $item->getQuantity()); ?>
                                    <?php if (strlen($item->getProductUnit()
                                                            ?? '') > 0) : ?>
                                        <br>
                                        <small><?= Html::encode($item->getProductUnit()); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="amount">
                                    <?= $numberHelper->formatCurrency(
                                            $item->getPrice()); ?>
                                </td>
                                <td class="amount">
                                    <?= $numberHelper->formatCurrency(
                                            $item->getDiscountAmount()); ?>
                                </td>
<?php
    $query = $soiaR->repoSalesOrderItemAmountquery(
        $item->getId()
    );
?>
                                <td class="amount">
<?= $numberHelper->formatCurrency(
    null !== $query ? $query->getSubtotal() : 0.00
); ?>
                                </td>
                            </tr>
                        <?php
                        // Display item-level allowances/charges
                        // if Peppol is enabled
                        if (
                            $s->getSetting('enable_peppol') == '1'
                        ) {
                            /**
                             * @var SalesOrderItemAllowanceCharge $salesOrderItemAllowanceCharge
                             */
                            foreach (
                                $acsoiR->repoSalesOrderItemquery(
                                    $item->getId()
                                )
                                as $salesOrderItemAllowanceCharge
                            ) {
                                $isCharge = (
                                    $salesOrderItemAllowanceCharge
                                        ->getAllowanceCharge()
                                            ?->getIdentifier() == 1
                                            ? true : false
                                );
                        ?>
                            <tr>
                                <td colspan="5">
                                    <b>
                                    <?=
                                        $salesOrderItemAllowanceCharge
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
                                    </b>
                                    <?=
                                        $translator->translate(
                                            'allowance.or.charge.reason.code'
                                        ) . ': ' . (
                                            $salesOrderItemAllowanceCharge
                                                ->getAllowanceCharge()
                                                    ?->getReasonCode()
                                                        ?? '#'
                                        ); ?>
                                    -
                                    <?=
                                        $translator->translate(
                                            'allowance.or.charge.reason'
                                        ) . ': ' . (
                                            $salesOrderItemAllowanceCharge
                                                ->getAllowanceCharge()
                                                    ?->getReason()
                                                        ?? '#'
                                        ); ?>
                                </td>
                                <td class="amount">
                                    <b>
                                    <?= ($isCharge ? '' : '(')
                                        . $numberHelper
                                            ->formatCurrency(
                                                $salesOrderItemAllowanceCharge
                                                    ->getAmount()
                                            )
                                        . ($isCharge ? '' : ')'); ?>
                                    </b>
                                </td>
                                <td class="amount">
                                    <b>
                                    <?php $vatSalesOrderItem = $salesOrderItemAllowanceCharge->getVatOrTax();
                                        echo ($isCharge ? '' : '(')
                                            . $numberHelper->formatCurrency($vatSalesOrderItem)
                                            . ($isCharge ? '' : ')'); ?>
                                    </b>
                                </td>
                            </tr>
                        <?php
                            }
                        }
                        endforeach ?>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right">
                                <?= $translator->translate('subtotal'); ?>:
                            </td>
                            <td class="amount">
                            <b><?= $numberHelper->formatCurrency(
                                $salesorder_amount->getItemSubtotal()); ?></b>
                            </td>
                        </tr>
                        <?php if ($salesorder_amount->getItemTaxTotal() > 0) { ?>
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right">
<?= $vat === '1' ? $translator->translate('vat.break.down') :
        $translator->translate('item.tax'); ?>
                                </td>
                                <td class="amount">
                                <b><?= $numberHelper->formatCurrency(
                                    $salesorder_amount->getItemTaxTotal()); ?></b>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php
                        if ($s->getSetting('enable_peppol') == '1') {
                            if ($salesorder_amount->getPackhandleshipTotal()
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
                                        $salesorder_amount
                                            ->getPackhandleshipTotal()
                                    ); ?></b>
                                </td>
                            </tr>
                        <?php }
                            if ($salesorder_amount->getPackhandleshipTax()
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
                                        $salesorder_amount
                                            ->getPackhandleshipTax()
                                    ); ?></b>
                                </td>
                            </tr>
                        <?php }
                        } ?>
                        <?php
    if (!empty($salesorder_tax_rates) && $vat == '0') {
        /**
         * @var App\Invoice\Entity\SalesOrderTaxRate $salesorder_tax_rate
         */
        foreach ($salesorder_tax_rates as $salesorder_tax_rate) : ?>
                                <tr>
                                    <td class="no-bottom-border" colspan="4"></td>
                                    <td class="text-right">
    <?php
        $taxRatePercent = $salesorder_tax_rate->getTaxRate()?->getTaxRatePercent();
        $taxRateName = $salesorder_tax_rate->getTaxRate()?->getTaxRateName();
        if (($taxRatePercent >= 0.00) && (strlen($taxRateName ?? '') > 0)) {
            echo Html::encode(($taxRateName ?? '#')
                . ' ' . ($numberHelper->formatAmount($taxRatePercent) ?? '#'));
        }
        ?>
            %
                                    </td>
                                    <td class="amount">
<b><?= $numberHelper->formatCurrency(
        $salesorder_tax_rate->getSalesOrderTaxRateAmount()); ?></b>
                                    </td>
                                </tr>
                            <?php endforeach;
                            } ?>
                        <?php if ($vat === '0') { ?>
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right">
                                <?= $translator->translate('discount'); ?>:
                            </td>
                            <td class="amount">
                            <b><?php
                                $discountAmount =
                                        $salesorder->getDiscountAmount();
                                if ($discountAmount >= 0.00) {
                                    echo $numberHelper->formatAmount(
                                            $discountAmount);
                                }
                            ?></b>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right">
                                <?= $translator->translate('total'); ?>:
                            </td>
                            <td class="amount">
                                <b><?= $numberHelper->formatCurrency(
                                    $salesorder_amount->getTotal()); ?></b>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <hr>

            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?php if (strlen($salesorder->getNotes() ?? '') > 0) { ?>
                    <div class="col-xs-12 col-md-6">
                        <h4><?= $translator->translate('notes'); ?></h4>
                        <p>
                            <?= nl2br(Html::encode($salesorder->getNotes())); ?>
                        </p>
                    </div>
                <?php } ?>
            </div>

        </div><!-- .salesorder-items -->
    </div><!-- #content -->
</div>

</body>
</html>
