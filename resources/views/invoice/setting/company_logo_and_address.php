<?php 
declare(strict_types=1);

use Yiisoft\Html\Html;
?>
<div style="width:100%;height:175px;overflow:auto;">
    <table style="width:100%">
        <tr> 
            <td style="width:60%;text-align:left">
                <div id="logo">
                    <?php 
                        /**
                         * Use the default site logo unless the CompanyPrivate details logo linked to a CompanyPublic
                         * details has been set
                         * @see src\Invoice\Helpers\PdfHelper public function generate_quote/inv_pdf 
                         */
                    ?>
                    <?php if (isset($company['logo_path']) && !empty($company['logo_path'])) { ?> 
                        <img src="<?= $company['logo_path']; ?>" height="100" width="150"/>
                    <?php } else { ?>
                        <img src="<?= '/site/'. $s->public_logo().'.png'; ?>" height="100" width="150"/>
                    <?php } ?>
                </div>
            </td>
            <td style="width:40%;text-align:left">
                <?php 
                    if ($s->get_setting('enable_vat_registration') === '1' && $isInvoice) { 
                        echo '<div><b>'.Html::encode($translator->translate('invoice.invoice.vat.invoice')). '</b></div>';
                        echo '<div><br><b>'. $translator->translate('invoice.invoice.number').'</b> : '.Html::encode($document_number) .'</div>';
                       // echo '<div><br><b>'. $translator->translate('invoice.client.number').'</b> : '.Html::encode($client_number) .'</div>';
                        echo '<div><b>'. $translator->translate('invoice.client.purchase.order.number').'</b> : '.Html::encode($client_purchase_order_number) .'</div>';
                        echo '<div><br><b>'. $translator->translate('invoice.invoice.tax.point').'</b> : '.Html::encode($date_tax_point) .'</div>';
                    }
                    if ($s->get_setting('enable_vat_registration') === '1' && $isQuote) { 
                        echo '<div><b>'.Html::encode($translator->translate('invoice.quote.vat.quote')). '</b></div>';
                        echo '<div><br><b>'. $translator->translate('invoice.quote.number').'</b> : '.Html::encode($document_number) .'</div>';
                        echo '<div><b>'. $translator->translate('invoice.client.number').'</b> : '.Html::encode($client_number) .'</div>';
                    } 
                    if ($s->get_setting('enable_vat_registration') === '1' && $isSalesOrder) {
                        echo '<div><b>'.Html::encode($translator->translate('invoice.salesorder.vat.salesorder')). '</b></div>';
                        echo '<div><br><b>'. $translator->translate('invoice.salesorder.number').'</b> : '.Html::encode($document_number) .'</div>';
                        echo '<div><b>'. $translator->translate('invoice.client.number').'</b> : '.Html::encode($client_number) .'</div>';
                    }
                    echo '<div><br></div>';
                    echo '<div><b>'.Html::encode($company['name']).'</b></div>';
                    echo '<div><br></div>';
                    echo '<div>' . $translator->translate('invoice.invoice.vat.reg.no'). ': ' . Html::encode($company['vat_id']) . '</div>';
                    echo '<div>' . $translator->translate('i.tax_code_short') . ': ' . Html::encode($company['tax_code']) . '</div>';
                    echo '<div><br></div>';
                    echo '<div>' . Html::encode($company['address_1'] ? $translator->translate('i.street_address') .': '. $company['address_1'] : ''). '</div>';
                    echo '<div>' . Html::encode($company['address_2'] ? $translator->translate('i.street_address_2') .': '. $company['address_2'] : ''). '</div>';
                    echo '<div>' . Html::encode($company['city'] ? $translator->translate('i.city') .': '. $company['city'] : ''). '</div>';
                    echo '<div>' . Html::encode($company['state'] ? $translator->translate('i.state') .': '. $company['state'] : ''). '</div>';
                    echo '<div>' . Html::encode($company['zip'] ? $translator->translate('i.zip') .': '. $company['zip'] : ''). '</div>';
                    echo '</div>';
                    echo '<div>' . $countryhelper->get_country_name($translator->translate('i.cldr'), $company['country'] ?? 'United Kingdom') . '</div>';
                    echo '<br/>';
                    echo '<div>' .$translator->translate('i.phone_abbr') . ': ' . Html::encode($company['phone'] ?? '') . '</div>';
                    echo '<div>' .$translator->translate('i.fax_abbr') . ': ' . Html::encode($company['fax'] ?? '') . '</div>';
                ?>
            </td>
        </tr>
    </table>    
</div>