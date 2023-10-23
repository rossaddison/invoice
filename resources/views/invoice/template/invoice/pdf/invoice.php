<?php
declare(strict_types=1);

use App\Invoice\Helpers\DateHelper;
use App\Invoice\Entity\Sumex;
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
            <b><?= Html::encode($inv->getClient()->getClient_name()); ?></b>
        </div>
        <?php if ($inv->getClient()->getClient_vat_id()) {
            echo '<div>' .$tranlator->translate('invoice.invoice.vat.reg.no') . ': ' . $inv->getClient()->getClient_vat_id() . '</div>';
        }
        if ($inv->getClient()->getClient_tax_code()) {
            echo '<div>' .$s->trans('tax_code_short') . ': ' . $inv->getClient()->getClient_tax_code() . '</div>';
        }
        echo '<div>' . Html::encode($inv->getClient()->getClient_address_1() ?: $s->trans('street_address')) . '</div>';        
        echo '<div>' . Html::encode($inv->getClient()->getClient_address_2() ?: $s->trans('street_address_2')) . '</div>';        
        if ($inv->getClient()->getClient_city() || $inv->getClient()->getClient_state() || $inv->getClient()->getClient_zip()) {
            echo '<div>';
            if ($inv->getClient()->getClient_city()) {
                echo Html::encode($inv->getClient()->getClient_city()) . ' ';
            }
            if ($inv->getClient()->getClient_state()) {
                echo Html::encode($inv->getClient()->getClient_state()) . ' ';
            }
            if ($inv->getClient()->getClient_zip()) {
                echo Html::encode($inv->getClient()->getClient_zip());
            }
            echo '</div>';
        }
        if ($inv->getClient()->getClient_state()) {
            echo '<div>' . Html::encode($inv->getClient()->getClient_state()) . '</div>';
        }
        if ($inv->getClient()->getClient_country()) {
            echo '<div>' . $countryhelper->get_country_name($s->trans('cldr'), $inv->getClient()->getClient_country()) . '</div>';
        }

        echo '<br/>';

        if ($inv->getClient()->getClient_phone()) {
            echo '<div>' .$s->trans('phone_abbr') . ': ' . Html::encode($inv->getClient()->getClient_phone()) . '</div>';
        } ?>

    </div>
</header>
<main>
    <div class="invoice-details clearfix">
        <table>
            <tr>
                <td><?php echo $translator->translate('invoice.invoice.date.issued') . ':'; ?></td>
                    <?php
                        $date = $inv->getDate_created();
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
            <?php if ($vat === '1') { ?>
            <tr>
                <td><?php echo $translator->translate('invoice.invoice.date.supplied') . ':'; ?></td>
                    <?php
                        $date_sp = $inv->getDate_supplied();
                        if ($date_sp && $date_sp != "0000-00-00") {
                            //use the DateHelper
                            $datehelper = new DateHelper($s);
                            $date = $datehelper->date_from_mysql($date_sp);
                        } else {
                            $date = null;
                        }
                    ?> 
                <td><?php echo Html::encode($date); ?></td>
            </tr>
            <?php } ?>
            <tr>
                <td><?php echo $s->trans('expires') . ': '; ?></td>
                <?php
                        $date_due = $inv->getDate_due();
                        if ($date_due && $date_due != "0000-00-00") {
                            //use the DateHelper
                            $datehelper = new DateHelper($s);
                            $date_due_next = $datehelper->date_from_mysql($date_due);
                        } else {
                            $date_due_next = null;
                        }
                    ?> 
                <td><?php echo Html::encode($date_due_next); ?></td>
            </tr>
            <tr><?= $show_custom_fields ? $top_custom_fields : ''; ?></tr>    
        </table>
    </div>

    <h3 class="invoice-title"><b><?= $vat === '0' ? Html::encode($s->trans('invoice') . ' ' . $inv->getNumber()) : ''; ?></b></h3>

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
            $inv_item_amount = $iiaR->repoInvItemAmountquery((string)$item->getId());
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
                        echo Html::encode($s->format_currency($inv_item_amount?->getTax_total())); 
                    ?>
                </td>
                <td class="text-right">
                    <?php  
                        echo Html::encode($item->getTaxRate()?->getTax_rate_percent()); 
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
                            $s->trans('subtotal'))." (".Html::encode($s->trans('price'))."-".Html::encode($s->trans('discount')).") x ".Html::encode($s->trans('qty')); ?></td>
            <?php } else { ?>
            <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?>
                    class="text-right"><?= Html::encode(
                            $s->trans('subtotal')); ?></td> 
            <?php } ?> 
            <td class="text-right"><?php echo Html::encode($s->format_currency($inv_amount->getItem_subtotal())); ?></td>
        </tr>

        <?php if ($inv_amount->getItem_tax_total() > 0) { ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode( $vat === '1' ? $translator->translate('invoice.invoice.vat.break.down') : $s->trans('item_tax')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($inv_amount->getItem_tax_total())); ?>
                </td>
            </tr>
        <?php } ?>
            
        <?php if (!empty($inv_tax_rates) && ($vat === '0')) { ?>    
        <?php  foreach ($inv_tax_rates as $inv_tax_rate) : ?>
            <tr>
                <td <?php echo ($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?php echo Html::encode($inv_tax_rate->getTaxRate()->getTax_rate_name()) . ' (' . Html::encode($s->format_amount($inv_tax_rate->getTaxRate()->getTax_rate_percent())) . '%)'; ?>
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
                    <?= Html::encode($s->trans('discount')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_amount($inv->getDiscount_percent())); ?>%
                </td>
            </tr>
        <?php } elseif ($inv->getDiscount_amount() !== 0.00) { ?>
            <tr>
                <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                    <?= Html::encode($s->trans('discount')); ?>
                </td>
                <td class="text-right">
                    <?php echo Html::encode($s->format_currency($inv->getDiscount_amount())); ?>
                </td>
            </tr>
        <?php } ?>
        <?php } ?>
        <tr>
            <td <?php echo($show_item_discounts ? 'colspan="7"' : 'colspan="6"'); ?> class="text-right">
                <b><?= Html::encode($s->trans('total')); ?></b>
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
        <b><?= Html::encode($s->trans('terms')); ?></b><br>
        <?php echo nl2br(Html::encode($inv->getTerms())); ?>
    </div>
    <br>
    <?php } ?>
    <div>
    <?php if ($show_custom_fields) {        
        echo $view_custom_fields; 
    } ?>
    </div>    
    <?php if (($s->get_setting('sumex') == '1') && ($sumex instanceof Sumex)) { ?>
    <div>
        <?php            
            $reason = ['disease','accident','maternity','prevention','birthdefect','unknown']; 
        ?>
        <b><?= Html::encode($s->trans('reason')); ?></b><br>
        <p><?= Html::encode($s->trans('reason_'.(string)$reason[$sumex->getReason() ?: 5])); ?></p>       
    </div>
    <div>            
        <b><?= Html::encode($s->trans('sumex_observations')); ?></b><br>
        <p><?= $sumex->getObservations() ?: ''; ?></p>
    </div>    
    <div>            
        <b><?= Html::encode($s->trans('invoice_sumex_diagnosis')); ?></b><br>
        <p><?= $sumex->getDiagnosis() ?: ''; ?></p>
    </div>
    <div>            
        <b><?= Html::encode($s->trans('case_date')); ?></b><br>
        <p><?= $sumex->getCasedate()->format($datehelper->style()) ?: ''; ?></p>
    </div>
    <div>            
        <b><?= Html::encode($s->trans('case_number')); ?></b><br>
        <p><?= $sumex->getCasenumber() ?: ''; ?></p>
    </div>
    <div>
        <b><?= Html::encode($s->trans('treatment_start')); ?></b><br>
        <p><?= $sumex->getTreatmentstart()->format($datehelper->style()) ?: ''; ?></p>
    </div> 
    <div>    
        <b><?= Html::encode($s->trans('treatment_end')); ?></b><br>
        <p><?= $sumex->getTreatmentend()->format($datehelper->style()) ?: ''; ?></p>
    </div>
    <?php } ?>
</footer>
</body>
</html>
