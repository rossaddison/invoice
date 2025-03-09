<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @see App\Invoice\Helpers\PdfHelper function generate_inv_html
 *
 * @var App\Invoice\Entity\InvAmount $inv_amount
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Entity\InvTaxRate $inv_tax_rate
 * @var App\Invoice\Entity\Sumex|null $sumex
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\InvItemAmount\InvItemAmountRepository $iiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $items
 * @var bool $show_custom_fields            show both top_custom_fields and view_custom_fields
 * @var bool $show_item_discounts
 * @var string $company_logo_and_address    setting/company_logo_and_address.php
 * @var string $top_custom_fields           appear at the top of quote.pdf
 * @var string $view_custom_fields          appear at the bottom of quote.pdf
 */

$vat = $s->getSetting('enable_vat_registration');
?>

<!DOCTYPE html>
<html class="h-100" lang="<?= $translator->translate('i.cldr'); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1"> 
</head>    
<body>
<header class="clearfix">
    <?= $company_logo_and_address; ?>    
    <div id="client">
        <div>
            <b><?= Html::encode($inv->getClient()?->getClient_name()); ?></b>
        </div>
        <?php if (strlen($clientVatId = $inv->getClient()?->getClient_vat_id() ?? '') > 0) {
            echo '<div>' .$translator->translate('invoice.invoice.vat.reg.no')
                         .': '
                         . $clientVatId
                         . '</div>';
        }
if (strlen($clientTaxCode = $inv->getClient()?->getClient_tax_code() ?? '') > 0) {
    echo '<div>' .$translator->translate('i.tax_code_short') . ': ' . $clientTaxCode . '</div>';
}
echo '<div>' . Html::encode(strlen($inv->getClient()?->getClient_address_1() ?? '') > 0 ?: $translator->translate('i.street_address')) . '</div>';
echo '<div>' . Html::encode(strlen($inv->getClient()?->getClient_address_2() ?? '') > 0 ?: $translator->translate('i.street_address_2')) . '</div>';
if (strlen($inv->getClient()?->getClient_city() ?? '') > 0 || strlen($inv->getClient()?->getClient_state() ?? '') > 0 || strlen($inv->getClient()?->getClient_zip() ?? '') > 0) {
    echo '<div>';
    if (strlen($inv->getClient()?->getClient_city() ?? '') > 0) {
        echo Html::encode($inv->getClient()?->getClient_city()) . ' ';
    }
    if (strlen($inv->getClient()?->getClient_state() ?? '') > 0) {
        echo Html::encode($inv->getClient()?->getClient_state()) . ' ';
    }
    if (strlen($inv->getClient()?->getClient_zip() ?? '') > 0) {
        echo Html::encode($inv->getClient()?->getClient_zip());
    }
    echo '</div>';
}
if (strlen($inv->getClient()?->getClient_state() ?? '') > 0) {
    echo '<div>' . Html::encode($inv->getClient()?->getClient_state()) . '</div>';
}
if (strlen($clientCountry = $inv->getClient()?->getClient_country() ?? '') > 0) {
    echo '<div>' . $countryHelper->get_country_name($translator->translate('i.cldr'), $clientCountry) . '</div>';
}

echo '<br/>';

if (strlen($inv->getClient()?->getClient_phone() ?? '') > 0) {
    echo '<div>' .$translator->translate('i.phone_abbr') . ': ' . Html::encode($inv->getClient()?->getClient_phone()) . '</div>';
} ?>

    </div>
</header>
<main>
    <div class="invoice-details clearfix">
        <table>
            <tr>
                <td><?php echo $translator->translate('invoice.invoice.date.issued') . ':'; ?></td>
                     
                <td><?php echo Html::encode(!is_string($dateCreated = $inv->getDate_created()) ?
                                               $dateCreated->format('Y-m-d') : ''); ?></td>
            </tr>
            <?php if ($vat === '1') { ?>
            <tr>
                <td><?php echo $translator->translate('invoice.invoice.date.supplied') . ':'; ?></td>
                <td><?php echo Html::encode(!is_string($dateSupplied = $inv->getDate_supplied()) ?
                                               $dateSupplied->format('Y-m-d') : ''); ?></td>
            </tr>
            <?php } ?>
            <tr>
                <td><?php echo $translator->translate('i.expires') . ': '; ?></td>
                <td><?php echo Html::encode(!is_string($dateDueNext = $inv->getDate_due()) ?
                                               $dateDueNext->format('Y-m-d') : ''); ?></td>
            </tr>
            <tr><?= $show_custom_fields ? $top_custom_fields : ''; ?></tr>    
        </table>
    </div>

    <h3 class="invoice-title"><b><?= $vat === '0' ? Html::encode($translator->translate('i.invoice') . ' ' . ($inv->getNumber() ?? '#')) : ''; ?></b></h3>

    <table class="items table-primary table table-borderless no-margin">
        <thead style="display: none">
        <tr>
            <th class="item-name"><?= Html::encode($translator->translate('i.item')); ?></th>
            <th class="item-desc"><?= Html::encode($translator->translate('i.description')); ?></th>
            <th class="item-amount text-right"><?= Html::encode($translator->translate('i.qty')); ?></th>
            <th class="item-price text-right"><?= Html::encode($translator->translate('i.price')); ?></th>
            <?php if ($show_item_discounts) : ?>
                <th class="item-discount text-right"><?= Html::encode($translator->translate('i.discount')); ?></th>
            <?php endif; ?>
            <?php if ($vat === '0') { ?>     
            <th class="item-price text-right"><?= Html::encode($translator->translate('i.tax')); ?></th>
            <th class="item-price text-right">%</th>
            <?php } else { ?>
                <th class="item-price text-right"><?= Html::encode($translator->translate('invoice.invoice.vat.abbreviation')); ?></th>    
                <th class="item-price text-right">%</th>
            <?php } ?> 
            <th class="item-total text-right"><?= Html::encode($translator->translate('i.total')); ?></th>
        </tr>
        </thead>
        <tbody>

        <?php
if ($items) {
    /**
     * @var App\Invoice\Entity\InvItem $item
     */
    foreach ($items as $item) {
        $inv_item_amount = $iiaR->repoInvItemAmountquery((string)$item->getId());
        ?>
            <tr>
                <td><?= Html::encode($item->getName()); ?></td>
                <td><?php echo nl2br(Html::encode($item->getDescription())); ?></td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_amount($item->getQuantity())); ?>
                    <?php if (strlen($item->getProduct_unit() ?? '') > 0) : ?>
                        <br>
                        <small><?= Html::encode($item->getProduct_unit()); ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($item->getPrice())); ?>
                </td>
                <?php if ($show_item_discounts) : ?>
                    <td class="text-right">
                        <?php echo Html::encode($s->format_currency($item->getDiscount_amount())); ?>
                    </td>
                <?php endif; ?>
                <td class="text-right">
                    <?php
                    echo Html::encode($s->format_currency($inv_item_amount?->getTax_total()));
        ?>
                </td>
                <td class="text-right">
                    <?php
            echo Html::encode($item->getTaxRate()?->getTaxRatePercent());
        ?>
                </td>
                <td class="text-right">
                    <?php
            echo Html::encode($s->format_currency($inv_item_amount?->getTotal()));
        ?>
                </td>
            </tr>
        <?php
    }
} ?>

        </tbody>
        <tbody class="invoice-sums">

        <tr>
            <?php if ($vat === '0') { ?>
            <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?>
                    class="text-right"><?= Html::encode(
                        $translator->translate('i.subtotal')
                    )." (".Html::encode($translator->translate('i.price'))."-".Html::encode($translator->translate('i.discount')).") x ".Html::encode($translator->translate('i.qty')); ?></td>
            <?php } else { ?>
            <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?>
                    class="text-right"><?= Html::encode(
                        $translator->translate('i.subtotal')
                    ); ?></td> 
            <?php } ?> 
            <td class="text-right"><?php echo Html::encode($s->format_currency($inv_amount->getItem_subtotal())); ?></td>
        </tr>

        <?php if ($inv_amount->getItem_tax_total() > 0) { ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode($vat === '1' ? $translator->translate('invoice.invoice.vat.break.down') : $translator->translate('i.item_tax')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($inv_amount->getItem_tax_total())); ?>
                </td>
            </tr>
        <?php } ?>
            
        <?php if (!empty($inv_tax_rates) && ($vat === '0')) { ?>    
        <?php
                        /**
                         * @var App\Invoice\Entity\InvTaxRate $inv_tax_rate
                         */
                        foreach ($inv_tax_rates as $inv_tax_rate) : ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?php echo Html::encode($inv_tax_rate->getTaxRate()?->getTaxRateName()) .
                                           ' (' .
                                           Html::encode($s->format_amount($inv_tax_rate->getTaxRate()?->getTaxRatePercent())) . '%)'; ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($inv_tax_rate->getInv_tax_rate_amount())); ?>
                </td>
            </tr>
        <?php endforeach ?>
        <?php } ?>   
        <?php if ($vat === '0') { ?>    
        <?php if ($inv->getDiscount_percent() !== 0.00) { ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode($translator->translate('i.discount')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_amount($inv->getDiscount_percent())); ?>%
                </td>
            </tr>
        <?php } elseif ($inv->getDiscount_amount() !== 0.00) { ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode($translator->translate('i.discount')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($inv->getDiscount_amount())); ?>
                </td>
            </tr>
        <?php } ?>
        <?php } ?>
        <tr>
            <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                <b><?= Html::encode($translator->translate('i.total')); ?></b>
            </td>
            <td class="text-right">
                <b><?php echo Html::encode($s->format_currency($inv_amount->getTotal())); ?></b>
            </td>
        </tr>
        </tbody>
    </table>
</main>    
<footer class="notes">
    <br>
    <?php if ($inv->getTerms()) { ?>
    <div style="page-break-before: always"></div>
    <div>
        <b><?= Html::encode($translator->translate('i.terms')); ?></b><br>
        <?php echo nl2br(Html::encode($inv->getTerms())); ?>
    </div>
    <br>
    <?php } ?>
    <div>
    <?= $show_custom_fields ? $view_custom_fields : ''; ?>
    </div>    
    <?php if ($s->getSetting('sumex') == '1') { ?>
    <div>
        <?php
                        $reason = ['disease','accident','maternity','prevention','birthdefect','unknown'];
        ?>
        <b><?= Html::encode($translator->translate('i.reason')); ?></b><br>
        <p><?= Html::encode($translator->translate('i.reason_'. $reason[is_int($sumexReason = $sumex?->getReason()) ? $sumexReason : 5])); ?></p>       
    </div>
    <div>            
        <b><?= Html::encode($translator->translate('i.sumex_observations')); ?></b><br>
        <p><?= $sumex?->getObservations() ?? ''; ?></p>
    </div>    
    <div>            
        <b><?= Html::encode($translator->translate('i.invoice_sumex_diagnosis')); ?></b><br>
        <p><?= $sumex?->getDiagnosis() ?? ''; ?></p>
    </div>
    <div>            
        <b><?= Html::encode($translator->translate('i.case_date')); ?></b><br>
        <p><?= !is_string($caseDate = $sumex?->getCasedate()) ? $caseDate?->format('Y-m-d') : ''; ?></p>
    </div>
    <div>            
        <b><?= Html::encode($translator->translate('i.case_number')); ?></b><br>
        <p><?= strlen($sumex?->getCasenumber() ?? '') > 0 ? $sumex?->getCaseNumber() : ''; ?></p>
    </div>
    <div>
        <b><?= Html::encode($translator->translate('i.treatment_start')); ?></b><br>
        <p><?= !is_string($treatmentStart = $sumex?->getTreatmentstart()) ? $treatmentStart?->format('Y-m-d') : ''; ?></p>
    </div> 
    <div>    
        <b><?= Html::encode($translator->translate('i.treatment_end')); ?></b><br>
        <p><?= !is_string($treatmentEnd = $sumex?->getTreatmentend()) ? $treatmentEnd?->format('Y-m-d') : ''; ?></p>
    </div>
    <?php } ?>
</footer>
</body>
</html>
