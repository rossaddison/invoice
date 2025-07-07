<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @see App\Invoice\Helpers\PdfHelper function generate_quote_pdf
 *
 * @var App\Invoice\Entity\QuoteAmount $quote_amount
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Entity\QuoteTaxRate $quote_tax_rate
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $items
 * @var bool $show_custom_fields            show both top_custom_fields and view_custom_fields
 * @var bool $show_item_discounts
 * @var string $cldr
 * @var string $company_logo_and_address    setting/company_logo_and_address.php
 *
 * @var string $top_custom_fields           appear at the top of quote.pdf
 * @var string $view_custom_fields          appear at the bottom of quote.pdf
 */

$vat = $s->getSetting('enable_vat_registration');
?>

<!DOCTYPE html>
<html class="h-100" lang="<?= $cldr; ?>">
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
    <?= $company_logo_and_address; ?>
    <div id="client">
        <div>
            <b><?= Html::encode($quote->getClient()?->getClient_name()); ?></b>
        </div>
        <?php if (strlen($clientVatId = $quote->getClient()?->getClient_vat_id() ?? '') > 0) {
            echo '<div>' .$translator->translate('vat.reg.no')
                         .': '
                         . $clientVatId
                         . '</div>';
        }
if (strlen($clientTaxCode = $quote->getClient()?->getClient_tax_code() ?? '') > 0) {
    echo '<div>' .$translator->translate('tax.code.short') . ': ' . $clientTaxCode . '</div>';
}
echo '<div>' . Html::encode(strlen($quote->getClient()?->getClient_address_1() ?? '') > 0 ?: $translator->translate('street.address')) . '</div>';
echo '<div>' . Html::encode(strlen($quote->getClient()?->getClient_address_2() ?? '') > 0 ?: $translator->translate('street.address.2')) . '</div>';
if (strlen($quote->getClient()?->getClient_city() ?? '') > 0 || strlen($quote->getClient()?->getClient_state() ?? '') > 0 || strlen($quote->getClient()?->getClient_zip() ?? '') > 0) {
    echo '<div>';
    if (strlen($quote->getClient()?->getClient_city() ?? '') > 0) {
        echo Html::encode($quote->getClient()?->getClient_city()) . ' ';
    }
    if (strlen($quote->getClient()?->getClient_state() ?? '') > 0) {
        echo Html::encode($quote->getClient()?->getClient_state()) . ' ';
    }
    if (strlen($quote->getClient()?->getClient_zip() ?? '') > 0) {
        echo Html::encode($quote->getClient()?->getClient_zip());
    }
    echo '</div>';
}
if (strlen($quote->getClient()?->getClient_state() ?? '') > 0) {
    echo '<div>' . Html::encode($quote->getClient()?->getClient_state()) . '</div>';
}
if (strlen($clientCountry = $quote->getClient()?->getClient_country() ?? '') > 0) {
    echo '<div>' . $countryHelper->get_country_name($translator->translate('cldr'), $clientCountry) . '</div>';
}

echo '<br/>';

if (strlen($clientPhone = $quote->getClient()?->getClient_phone() ?? '') > 0) {
    echo '<div>' .$translator->translate('phone.abbr') . ': ' . Html::encode($clientPhone) . '</div>';
} ?>

    </div>
</header>
<main>
    <div class="invoice-details clearfix">
        <table>
            <tr>
                <!-- date issued -->
                <td><?php echo $translator->translate('date.issued') . ':'; ?></td>
                <td><?php echo Html::encode(!is_string($dateCreated = $quote->getDate_created()) ?
                                               $dateCreated->format('Y-m-d') : ''); ?></td>
            </tr>
            <tr>
                <td><?php echo $translator->translate('expires') . ': '; ?></td>
                <td>
                    <?= $quote->getDate_expires()->format('Y-m-d'); ?>
                </td>
            </tr>
            <tr><?= $show_custom_fields ? $top_custom_fields : ''; ?></tr>    
            }
        </table>
    </div>

    <h3 class="invoice-title"><b><?php echo Html::encode($translator->translate('quote') . ' ' . ($quote->getNumber() ?? '')); ?></b></h3>

    <table class="items table-primary table table-borderless no-margin">
        <thead style="display: none">
        <tr>
            <th class="item-name"><?= Html::encode($translator->translate('item')); ?></th>
            <th class="item-desc"><?= Html::encode($translator->translate('description')); ?></th>
            <th class="item-amount text-right"><?= Html::encode($translator->translate('qty')); ?></th>
            <th class="item-price text-right"><?= Html::encode($translator->translate('price')); ?></th>
            <th></th>
            <?php if ($show_item_discounts) : ?>
                <th class="item-discount text-right"><?= Html::encode($translator->translate('discount')); ?></th>
            <?php endif; ?>
            <?php if ($vat === '0') { ?>     
            <th class="item-price text-right"><?= Html::encode($translator->translate('tax')); ?></th>    
            <?php } else { ?>
                <th class="item-price text-right"><?= Html::encode($translator->translate('vat.abbreviation')); ?></th>    
                <th class="item-price text-right">%</th>
            <?php } ?> 
            <th class="item-total text-right"><?= Html::encode($translator->translate('total')); ?></th>
        </tr>
        </thead>
        <tbody>

        <?php
if ($items) {
    /**
     * @var App\Invoice\Entity\InvItem $item
     */
    foreach ($items as $item) {
        $quote_item_amount = $qiaR->repoQuoteItemAmountquery((int)$item->getId());
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
                    echo Html::encode($s->format_currency($quote_item_amount?->getTax_total()));
        ?>
                </td>
                <td class="text-right">
                    <?php
            echo Html::encode($item->getTaxRate()?->getTaxRatePercent());
        ?>
                </td>
                <td class="text-right">
                    <?php
            echo Html::encode($s->format_currency($quote_item_amount?->getTotal()));
        ?>
                </td>
            </tr>
        <?php }
    }?>

        </tbody>
        <tbody class="invoice-sums">

        <tr>
            <?php if ($vat === '0') { ?>
            <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?>
                    class="text-right"><?= Html::encode(
                        $translator->translate('subtotal')
                    )." (".Html::encode($translator->translate('price'))."-".Html::encode($translator->translate('discount')).") x ".Html::encode($translator->translate('qty')); ?></td>
            <?php } else { ?>
            <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?>
                    class="text-right"><?= Html::encode(
                        $translator->translate('subtotal')
                    ); ?></td> 
            <?php } ?> 
            <td class="text-right"><?php echo Html::encode($s->format_currency($quote_amount->getItem_subtotal())); ?></td>
        </tr>

        <?php if ($quote_amount->getItem_tax_total() > 0) { ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode($vat === '1' ? $translator->translate('vat.break.down') : $translator->translate('item.tax')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($quote_amount->getItem_tax_total())); ?>
                </td>
            </tr>
        <?php } ?>

            
        <?php if (!empty($quote_tax_rates) && ($vat === '0')) { ?>    
        <?php
                        /**
                         * @var App\Invoice\Entity\QuoteTaxRate $quote_tax_rate
                         */
                        foreach ($quote_tax_rates as $quote_tax_rate) : ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?php echo Html::encode($quote_tax_rate->getTaxRate()?->getTaxRateName()) . ' (' . Html::encode($s->format_amount($quote_tax_rate->getTaxRate()?->getTaxRatePercent())) . '%)'; ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($quote_tax_rate->getQuote_tax_rate_amount())); ?>
                </td>
            </tr>
        <?php endforeach ?>
        <?php } ?>
        <?php if ($vat == '0') { ?>    
        <?php if ($quote->getDiscount_percent() !== 0.00) : ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode($translator->translate('discount')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_amount($quote->getDiscount_percent())); ?>%
                </td>
            </tr>
        <?php endif; ?>
        <?php if ($quote->getDiscount_amount() !== 0.00) : ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode($translator->translate('discount')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($quote->getDiscount_amount())); ?>
                </td>
            </tr>
        <?php endif; ?>
        <?php } ?>    
        <tr>
            <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                <b><?= Html::encode($translator->translate('total')); ?></b>
            </td>
            <td class="text-right">
                <b><?php echo Html::encode($s->format_currency($quote_amount->getTotal())); ?></b>
            </td>
        </tr>
        </tbody>
    </table>

</main>

<footer>
    <?php if (strlen($quote->getNotes() ?? '') > 0) : ?>
        <div class="notes">
            <b><?= Html::encode($translator->translate('notes')); ?></b><br/>
            <?php echo nl2br(Html::encode($quote->getNotes())); ?>
        </div>
    <?php endif; ?>
        
    <?= $show_custom_fields ? $view_custom_fields : ''; ?>
</footer>
</body>
</html>
