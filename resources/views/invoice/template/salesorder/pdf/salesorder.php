<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see App\Invoice\Helpers\PdfHelper function generate_salesorder_pdf.
 *
 * @var App\Invoice\Entity\SalesOrderAmount                             $so_amount
 * @var App\Invoice\Entity\SalesOrder                                   $salesorder
 * @var App\Invoice\Entity\SalesOrderTaxRate                            $salesorder_tax_rate
 * @var App\Invoice\Helpers\CountryHelper                               $countryHelper
 * @var App\Invoice\Helpers\DateHelper                                  $dateHelper
 * @var App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository $soiaR
 * @var App\Invoice\Setting\SettingRepository                           $s
 * @var Yiisoft\Router\UrlGeneratorInterface                            $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface                          $translator
 * @var array                                                           $items
 * @var bool                                                            $show_custom_fields            show both top_custom_fields and view_custom_fields
 * @var bool                                                            $show_item_discounts
 * @var string                                                          $cldr
 * @var string                                                          $company_logo_and_address
 * @var string                                                          $top_custom_fields           appear at the top of quote.pdf
 * @var string                                                          $view_custom_fields          appear at the bottom of quote.pdf
 */
$vat = $s->getSetting('enable_vat_registration');
?>

<!DOCTYPE html>
<html class="h-100" lang="<?php echo $cldr; ?>">
<?php
    /** Set the locale when the view is being rendered partially i.e. without a layout */
    $translator->setLocale($cldr);
?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
</head>    
<body>
<header class="clearfix">    
    <?php echo $company_logo_and_address; ?>
    <div id="client">
        <div>
            <b><?php echo Html::encode($salesorder->getClient()?->getClient_name()); ?></b>
        </div>
        <?php if (strlen($clientVatId = $salesorder->getClient()?->getClient_vat_id() ?? '') > 0) {
            echo '<div>'.$translator->translate('vat.reg.no')
                         .': '
                         .$clientVatId
                         .'</div>';
        }
if (strlen($clientTaxCode = $salesorder->getClient()?->getClient_tax_code() ?? '') > 0) {
    echo '<div>'.$translator->translate('tax.code.short').': '.$clientTaxCode.'</div>';
}
echo '<div>'.Html::encode(strlen($salesorder->getClient()?->getClient_address_1() ?? '') > 0 ?: $translator->translate('street.address')).'</div>';
echo '<div>'.Html::encode(strlen($salesorder->getClient()?->getClient_address_2() ?? '') > 0 ?: $translator->translate('street.address.2')).'</div>';
if (strlen($salesorder->getClient()?->getClient_city() ?? '') > 0 || strlen($salesorder->getClient()?->getClient_state() ?? '') > 0 || strlen($salesorder->getClient()?->getClient_zip() ?? '') > 0) {
    echo '<div>';
    if (strlen($salesorder->getClient()?->getClient_city() ?? '') > 0) {
        echo Html::encode($salesorder->getClient()?->getClient_city()).' ';
    }
    if (strlen($salesorder->getClient()?->getClient_state() ?? '') > 0) {
        echo Html::encode($salesorder->getClient()?->getClient_state()).' ';
    }
    if (strlen($salesorder->getClient()?->getClient_zip() ?? '') > 0) {
        echo Html::encode($salesorder->getClient()?->getClient_zip());
    }
    echo '</div>';
}
if (strlen($salesorder->getClient()?->getClient_state() ?? '') > 0) {
    echo '<div>'.Html::encode($salesorder->getClient()?->getClient_state()).'</div>';
}
if (strlen($clientCountry = $salesorder->getClient()?->getClient_country() ?? '') > 0) {
    echo '<div>'.$countryHelper->get_country_name($translator->translate('cldr'), $clientCountry).'</div>';
}

echo '<br/>';

if (strlen($clientPhone = $salesorder->getClient()?->getClient_phone() ?? '') > 0) {
    echo '<div>'.$translator->translate('phone.abbr').': '.Html::encode($clientPhone).'</div>';
} ?>

    </div>
</header>
<main>
    <div class="invoice-details clearfix">
        <table>
            <tr>
                <!-- date issued -->
                <td><?php echo $translator->translate('date.issued').':'; ?></td>
                <td><?php echo Html::encode(!is_string($dateCreated = $salesorder->getDate_created()) ?
                                               $dateCreated->format('Y-m-d') : ''); ?></td>
            </tr>
            <tr>
                <td><?php echo $translator->translate('expires').': '; ?></td>
                <td>
                    <?php echo $salesorder->getDate_expires()->format('Y-m-d'); ?>
                </td>
            </tr>
            <tr><?php echo $show_custom_fields ? $top_custom_fields : ''; ?></tr>    
            }
        </table>
    </div>

    <h3 class="invoice-title"><b><?php echo Html::encode($translator->translate('salesorder').' '.($salesorder->getNumber() ?? '#')); ?></b></h3>

    <table class="items table-primary table table-borderless no-margin">
        <thead style="display: none">
        <tr>
            <th class="item-name"><?php echo Html::encode($translator->translate('item')); ?></th>
            <th class="item-desc"><?php echo Html::encode($translator->translate('description')); ?></th>
            <th class="item-amount text-right"><?php echo Html::encode($translator->translate('qty')); ?></th>
            <th class="item-price text-right"><?php echo Html::encode($translator->translate('price')); ?></th>
            <?php if ($show_item_discounts) { ?>
                <th class="item-discount text-right"><?php echo Html::encode($translator->translate('discount')); ?></th>
            <?php } ?>
            <?php if ('0' === $vat) { ?>     
            <th class="item-price text-right"><?php echo Html::encode($translator->translate('tax')); ?></th>    
            <?php } else { ?>
                <th class="item-price text-right"><?php echo Html::encode($translator->translate('vat.abbreviation')); ?></th>    
            <?php } ?> 
            <th class="item-total text-right"><?php echo Html::encode($translator->translate('total')); ?></th>
        </tr>
        </thead>
        <tbody>

        <?php
if ($items) {
    /**
     * @var App\Invoice\Entity\InvItem $item
     */
    foreach ($items as $item) {
        $salesorder_item_amount = $soiaR->repoSalesOrderItemAmountquery((string) $item->getId());
        ?>
            <tr>
                <td><?php echo Html::encode($item->getName()); ?></td>
                <td><?php echo nl2br(Html::encode($item->getDescription())); ?></td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_amount($item->getQuantity())); ?>
                    <?php if (strlen($item->getProduct_unit() ?? '') > 0) { ?>
                        <br>
                        <small><?php echo Html::encode($item->getProduct_unit()); ?></small>
                    <?php } ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($item->getPrice())); ?>
                </td>
                <?php if ($show_item_discounts) { ?>
                    <td class="text-right">
                        <?php echo Html::encode($s->format_currency($item->getDiscount_amount())); ?>
                    </td>
                <?php } ?>
                <td class="text-right">
                    <?php
                    echo Html::encode($s->format_currency($salesorder_item_amount?->getTax_total()));
        ?>
                </td>
                <td class="text-right">
                    <?php
            echo Html::encode($s->format_currency($salesorder_item_amount?->getTotal()));
        ?>
                </td>
            </tr>
        <?php }
    }?>

        </tbody>
        <tbody class="invoice-sums">

        <tr>
            <?php if ('0' === $vat) { ?>
            <td <?php echo $show_item_discounts ? 'colspan="6"' : 'colspan="5"'; ?>
                    class="text-right"><?php echo Html::encode(
                        $translator->translate('subtotal'),
                    ).' ('.Html::encode($translator->translate('price')).'-'.Html::encode($translator->translate('discount')).') x '.Html::encode($translator->translate('qty')); ?></td>
            <?php } else { ?>
            <td <?php echo $show_item_discounts ? 'colspan="6"' : 'colspan="5"'; ?>
                    class="text-right"><?php echo Html::encode(
                        $translator->translate('subtotal'),
                    ); ?></td> 
            <?php } ?> 
            <td class="text-right"><?php echo Html::encode($s->format_currency($so_amount->getItem_subtotal())); ?></td>
        </tr>

        <?php if ($so_amount->getItem_tax_total() > 0) { ?>
            <tr>
                <td <?php echo $show_item_discounts ? 'colspan="6"' : 'colspan="5"'; ?> class="text-right">
                    <?php echo Html::encode('1' === $vat ? $translator->translate('vat.break.down') : $translator->translate('item.tax')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($so_amount->getItem_tax_total())); ?>
                </td>
            </tr>
        <?php } ?>

            
        <?php if (!empty($so_tax_rates) && ('0' === $vat)) { ?>
            
        <?php
                        /**
                         * @var App\Invoice\Entity\SalesOrderTaxRate $salesorder_tax_rate
                         */
                        foreach ($so_tax_rates as $salesorder_tax_rate) { ?>
            <tr>
                <td <?php echo $show_item_discounts ? 'colspan="6"' : 'colspan="5"'; ?> class="text-right">
                    <?php echo Html::encode($salesorder_tax_rate->getTaxRate()?->getTaxRateName()).' ('.Html::encode($s->format_amount($salesorder_tax_rate->getTaxRate()?->getTaxRatePercent())).'%)'; ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($salesorder_tax_rate->getSo_tax_rate_amount())); ?>
                </td>
            </tr>
        <?php } ?>
        <?php } ?>
        <?php if ('0' == $vat) { ?>    
        <?php if (0.00 !== $salesorder->getDiscount_percent()) { ?>
            <tr>
                <td <?php echo $show_item_discounts ? 'colspan="6"' : 'colspan="5"'; ?> class="text-right">
                    <?php echo Html::encode($translator->translate('discount')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_amount($salesorder->getDiscount_percent())); ?>%
                </td>
            </tr>
        <?php } ?>
        <?php if (0.00 !== $salesorder->getDiscount_amount()) { ?>
            <tr>
                <td <?php echo $show_item_discounts ? 'colspan="6"' : 'colspan="5"'; ?> class="text-right">
                    <?php echo Html::encode($translator->translate('discount')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($salesorder->getDiscount_amount())); ?>
                </td>
            </tr>
        <?php } ?>
        <?php } ?>    
        <tr>
            <td <?php echo $show_item_discounts ? 'colspan="6"' : 'colspan="5"'; ?> class="text-right">
                <b><?php echo Html::encode($translator->translate('total')); ?></b>
            </td>
            <td class="text-right">
                <b><?php echo Html::encode($s->format_currency($so_amount->getTotal())); ?></b>
            </td>
        </tr>
        </tbody>
    </table>

</main>

<footer>
    <?php if (strlen($salesorder->getNotes() ?? '') > 0) { ?>
        <div class="notes">
            <b><?php echo Html::encode($translator->translate('notes')); ?></b><br/>
            <?php echo nl2br(Html::encode($salesorder->getNotes())); ?>
        </div>
    <?php } ?>
    <?php if ($show_custom_fields) {
        echo $view_custom_fields;
    }
?>   
</footer>
</body>
</html>
