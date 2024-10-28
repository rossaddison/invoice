<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;

/**
 * @see SalesOrderController function url_key 
 * @var App\Invoice\Entity\Client $client 
 * @var App\Invoice\Entity\SalesOrder $salesorder
 * @var App\Invoice\Entity\SalesOrderAmount $salesorder_amount
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository $soiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * 
 * @var array $items
 * @var array $salesorder_tax_rates
 * 
 * @see src\ViewInjection\LayoutViewInjection
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
<html lang="<?= $translator->translate('i.cldr'); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <title>
        <?= $s->getSetting('custom_title'); ?>
        - <?= $translator->translate('invoice.salesorder'); ?> <?= $salesorder->getNumber() ?? '#'; ?>
    </title>

    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<div class="container">
    <div id="content">
        <div class="webpreview-header">
            <div class='row'>
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

            <?php
                /**
                 * @see src\ViewInjection\LayoutViewInjection.php $logoPath, $companyLogoWidth, $companyLogoHeight
                 */
                echo Img::tag()
                    ->width($companyLogoWidth)
                    ->height($companyLogoHeight)
                    ->src($logoPath)   
            ?>

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
                            <td style="text-align:right;"><?= $dateHelper->date_from_mysql($salesorder->getDate_created()); ?></td>
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
                                <?php $query = $soiaR->repoSalesOrderItemAmountquery((string)$item->getId()); ?>
                                <td class="amount"><?= $numberHelper->format_currency(null!==$query ? $query->getSubtotal() : 0.00); ?></td>
                            </tr>
                        <?php endforeach ?>
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-right"><?= $translator->translate('i.subtotal'); ?>:</td>
                            <td class="amount"><?= $numberHelper->format_currency($salesorder_amount->getItem_subtotal()); ?></td>
                        </tr>
                        <?php if ($salesorder_amount->getItem_tax_total() > 0) { ?>
                            <tr>
                                <td class="no-bottom-border" colspan="4"></td>
                                <td class="text-right"><?= $vat === '1' ? $translator->translate('invoice.invoice.vat.break.down') : $translator->translate('i.item_tax'); ?></td>
                                <td class="amount"><?= $numberHelper->format_currency($salesorder_amount->getItem_tax_total()); ?></td>
                            </tr>
                        <?php } ?>
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
                                                echo Html::encode(($taxRateName ?? '#') . ' ' . ($numberHelper->format_amount($taxRatePercent) ?? '#'));
                                            }
                                        ?>
                                        %
                                    </td>
                                    <td class="amount"><?= $numberHelper->format_currency($salesorder_tax_rate->getSo_tax_rate_amount()); ?></td>
                                </tr>
                            <?php endforeach; } ?>
                        <?php if ($vat === '0') { ?>          
                        <tr>
                            <td class="no-bottom-border" colspan="4"></td>
                            <td class="text-right"><?= $translator->translate('i.discount'); ?>:</td>
                            <td class="amount">
                                <?php
                                    $percent = $salesorder->getDiscount_percent();
                                    if ($percent >= 0.00) {
                                        echo (string)$numberHelper->format_amount($percent) . ' %';
                                    } else {
                                        $discountAmount = $salesorder->getDiscount_amount();
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
                            <td class="amount"><?= $numberHelper->format_currency($salesorder_amount->getTotal()); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <hr>

            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?php if (strlen($salesorder->getNotes() ?? '') > 0) { ?>
                    <div class="col-xs-12 col-md-6">
                        <h4><?= $translator->translate('i.notes'); ?></h4>
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