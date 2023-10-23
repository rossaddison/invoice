<?php
declare(strict_types=1);

use App\Invoice\Helpers\DateHelper;
use Yiisoft\Html\Html;

$vat = $s->get_setting('enable_vat_registration');
?>

<!DOCTYPE html>
<html class="h-100" lang="<?= $s->trans('cldr'); ?>">
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
            <b><?= Html::encode($quote->getClient()->getClient_name()); ?></b>
        </div>
        <?php if ($quote->getClient()->getClient_vat_id()) {
            echo '<div>' .$s->trans('vat_id_short') . ': ' . $quote->getClient()->getClient_vat_id() . '</div>';
        }
        if ($quote->getClient()->getClient_tax_code()) {
            echo '<div>' .$s->trans('tax_code_short') . ': ' . $quote->getClient()->getClient_tax_code() . '</div>';
        }
        if ($quote->getClient()->getClient_address_1()) {
            echo '<div>' . Html::encode($quote->getClient()->getClient_address_1()) . '</div>';
        }
        if ($quote->getClient()->getClient_address_2()) {
            echo '<div>' . Html::encode($quote->getClient()->getClient_address_2()) . '</div>';
        }
        if ($quote->getClient()->getClient_city() || $quote->getClient()->getClient_state() || $quote->getClient()->getClient_zip()) {
            echo '<div>';
            if ($quote->getClient()->getClient_city()) {
                echo Html::encode($quote->getClient()->getClient_city()) . ' ';
            }
            if ($quote->getClient()->getClient_state()) {
                echo Html::encode($quote->getClient()->getClient_state()) . ' ';
            }
            if ($quote->getClient()->getClient_zip()) {
                echo Html::encode($quote->getClient()->getClient_zip());
            }
            echo '</div>';
        }
        if ($quote->getClient()->getClient_state()) {
            echo '<div>' . Html::encode($quote->getClient()->getClient_state()) . '</div>';
        }
        if ($quote->getClient()->getClient_country()) {
            echo '<div>' . $countryhelper->get_country_name($s->trans('cldr'), $quote->getClient()->getClient_country()) . '</div>';
        }

        echo '<br/>';

        if ($quote->getClient()->getClient_phone()) {
            echo '<div>' .$s->trans('phone_abbr') . ': ' . Html::encode($quote->getClient()->getClient_phone()) . '</div>';
        } ?>

    </div>
</header>
<main>
    <div class="invoice-details clearfix">
        <table>
            <tr>
                <!-- date issued -->
                <td><?php echo $translator->translate('invoice.invoice.date.issued') . ':'; ?></td>
                    <?php
                        $date = $client->getClient_date_created();
                        if ($date && $date != "0000-00-00") {
                            //use the DateHelper
                            $datehelper = new DateHelper($s);
                            $date = $datehelper->date_from_mysql($date);
                        } else {
                            $date = null;
                        }
                    ?> 
                <td><?php echo Html::encode($date); ?></td>
            </tr>
            <tr>
                <td><?php echo $s->trans('expires') . ': '; ?></td>
                <?php
                        $date_expires = $quote->getDate_expires();
                        if ($date_expires && $date_expires != "0000-00-00") {
                            //use the DateHelper
                            $datehelper = new DateHelper($s);
                            $date_expired = $datehelper->date_from_mysql($date_expires);
                        } else {
                            $date_expired = null;
                        }
                    ?> 
                <td><?php echo Html::encode($date_expired); ?></td>
            </tr>
            <tr><?= $show_custom_fields ? $top_custom_fields : ''; ?></tr>    
            }
        </table>
    </div>

    <h3 class="invoice-title"><b><?php echo Html::encode($s->trans('quote') . ' ' . $quote->getNumber()); ?></b></h3>

    <table class="items table-primary table table-borderless no-margin">
        <thead style="display: none">
        <tr>
            <th class="item-name"><?= Html::encode($s->trans('item')); ?></th>
            <th class="item-desc"><?= Html::encode($s->trans('description')); ?></th>
            <th class="item-amount text-right"><?= Html::encode($s->trans('qty')); ?></th>
            <th class="item-price text-right"><?= Html::encode($s->trans('price')); ?></th>
            <?php if ($show_item_discounts) : ?>
                <th class="item-discount text-right"><?= Html::encode($s->trans('discount')); ?></th>
            <?php endif; ?>
            <?php if ($vat === '0') { ?>     
            <th class="item-price text-right"><?= Html::encode($s->trans('tax')); ?></th>    
            <?php } else { ?>
                <th class="item-price text-right"><?= Html::encode($translator->translate('invoice.invoice.vat.abbreviation')); ?></th>    
                <th class="item-price text-right">%</th>
            <?php } ?> 
            <th class="item-total text-right"><?= Html::encode($s->trans('total')); ?></th>
        </tr>
        </thead>
        <tbody>

        <?php
        if (null!==$items) {
        foreach ($items as $item) { 
            $quote_item_amount = $qiaR->repoQuoteItemAmountquery((string)$item->getId());
            ?>
            <tr>
                <td><?= Html::encode($item->getName()); ?></td>
                <td><?php echo nl2br(Html::encode($item->getDescription())); ?></td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_amount($item->getQuantity())); ?>
                    <?php if ($item->getProduct_unit()) : ?>
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
                        echo Html::encode($item->getTaxRate()?->getTax_rate_percent()); 
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
                            $s->trans('subtotal'))." (".Html::encode($s->trans('price'))."-".Html::encode($s->trans('discount')).") x ".Html::encode($s->trans('qty')); ?></td>
            <?php } else { ?>
            <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?>
                    class="text-right"><?= Html::encode(
                            $s->trans('subtotal')); ?></td> 
            <?php } ?> 
            <td class="text-right"><?php echo Html::encode($s->format_currency($quote_amount->getItem_subtotal())); ?></td>
        </tr>

        <?php if ($quote_amount->getItem_tax_total() > 0) { ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode( $vat === '1' ? $translator->translate('invoice.invoice.vat.break.down') : $s->trans('item_tax')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($quote_amount->getItem_tax_total())); ?>
                </td>
            </tr>
        <?php } ?>

            
        <?php if (!empty($quote_tax_rates) && ($vat === '0')) { ?>    
        <?php  foreach ($quote_tax_rates as $quote_tax_rate) : ?>
            <tr>
                <td <?php echo ($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?php echo Html::encode($quote_tax_rate->getTaxRate()->getTax_rate_name()) . ' (' . Html::encode($s->format_amount($quote_tax_rate->getTaxRate()->getTax_rate_percent())) . '%)'; ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($quote_tax_rate->getQuote_tax_rate_amount())); ?>
                </td>
            </tr>
        <?php endforeach ?>
        <?php } ?>
        <?php if ($vat == '0') { ?>    
        <?php if ($quote->getDiscount_percent() !== '0.00') : ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode($s->trans('discount')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_amount($quote->getDiscount_percent())); ?>%
                </td>
            </tr>
        <?php endif; ?>
        <?php if ($quote->getDiscount_amount() !== '0.00') : ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode($s->trans('discount')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($quote->getDiscount_amount())); ?>
                </td>
            </tr>
        <?php endif; ?>
        <?php } ?>    
        <tr>
            <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                <b><?= Html::encode($s->trans('total')); ?></b>
            </td>
            <td class="text-right">
                <b><?php echo Html::encode($s->format_currency($quote_amount->getTotal())); ?></b>
            </td>
        </tr>
        </tbody>
    </table>

</main>

<footer>
    <?php if ($quote->getNotes()) : ?>
        <div class="notes">
            <b><?= Html::encode($s->trans('notes')); ?></b><br/>
            <?php echo nl2br(Html::encode($quote->getNotes())); ?>
        </div>
    <?php endif; ?>
    <?php if ($show_custom_fields) {
        echo $view_custom_fields;
    }
    ?>   
</footer>
</body>
</html>
