<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;
use App\Widget\QrCode as QrCodeWidget;

/**
 * Related logic: see App\Invoice\Helpers\PdfHelper
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $company
 * @var string $client_number
 * @var string $client_purchase_order_number
 * @var string $date_tax_point
 * @var string $document_number
 * @var string $inv_id
 * @var string $_language
 * @var bool $isInvoice
 * @var bool $isQuote
 * @var bool $isSalesOrder
 * @var string $company['address_1']
 * @var string $company['address_2']
 * @var string $company['city']
 * @var string $company['state']
 * @var string $company['zip']
 * @var string $company['country']
 * @var string $company['phone']
 * @var string $company['fax']
 */

?>
<div style="width:100%;height:175px;overflow:auto;">
    <table style="width:100%">
        <tr> 
            <td style="width:33%;text-align:left">
                <div id="logo">
                    <?php
                        /**
                         * Related logic: see src/Invoice/Setting/SettingRepository function get_company_private_logos_folder_aliases()
                         * Related logic: see CompanyPrivateController function add()
                         *
                         * The private logo filename which exists between a start and end date is modified with Random::string(4)
                         * and transferred to the public logo location i.e destination.public.logo
                         *
                         * If the destination.public.logo does not exist, the default.public.site logo will take precedence
                         *
                         * Aliases @base, @company_private_logos, @public, @public\logo
                         */
                        $aliases = $s->get_company_private_logos_folder_aliases();
/**
 * @var string $company['logofilenamewithsuffix']
 */
$filenameWithSuffix = $company['logofilenamewithsuffix'] ?? 'logo.png';
$destinationPublicLogo = $aliases->get('@public_logo') . DIRECTORY_SEPARATOR . $filenameWithSuffix;
$destinationPublicSite = $aliases->get('@public') . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . $filenameWithSuffix;
/**
 * The public folder source can be either the 'site' folder ('default') or the 'logo' folder ('private')
 * @var string $company['logopublicsource']
 * @var string $logoPublicSource]
 */
$logoPublicSource = $company['logopublicsource'] ?? 'default.public.site';
$logoFileNameWithPath = match ($logoPublicSource) {
    // default public site folder i.e. permanent 'globe' logo sitting in @base/public/site
    'default.public.site' => $destinationPublicSite,
    // public logo folder i.e. modified private logo transferred from @company_private_logos to
    // @base/public/logo
    'destination.public.logo' => $destinationPublicLogo,
};
echo Img::tag()
->height(100)
->width(150)
->src($logoFileNameWithPath)
->render();
?>    
                </div>
            </td>
            <?php if ($isInvoice) { ?>
            <td style="width:33%;text-align:left">
                <?= Html::openTag('div', ['id' => 'qr_code']);
                QrCodeWidget::absoluteUrl($urlGenerator->generateAbsolute('inv/view', [
                    'id' => $inv_id,
                    '_language' => $_language,
                ]), $translator->translate('qr.code'), 150);
                Html::closeTag('div');
                ?>
            </td> 
            <?php } ?>
            <td style="width:33%;text-align:left">
                <?php
                    if ($s->getSetting('enable_vat_registration') === '1' && $isInvoice) {
                        echo '<div><b>' . Html::encode($translator->translate('vat.invoice')) . '</b></div>';
                        echo '<div><br><b>' . $translator->translate('number') . '</b> : ' . Html::encode($document_number) . '</div>';
                        // echo '<div><br><b>'. $translator->translate('client.number').'</b> : '.Html::encode($client_number) .'</div>';
                        echo '<div><b>' . $translator->translate('client.purchase.order.number') . '</b> : ' . Html::encode($client_purchase_order_number) . '</div>';
                        echo '<div><br><b>' . $translator->translate('tax.point') . '</b> : ' . Html::encode($date_tax_point) . '</div>';
                    }
if ($s->getSetting('enable_vat_registration') === '1' && $isQuote) {
    echo '<div><b>' . Html::encode($translator->translate('quote.vat.quote')) . '</b></div>';
    echo '<div><br><b>' . $translator->translate('quote.number') . '</b> : ' . Html::encode($document_number) . '</div>';
    echo '<div><b>' . $translator->translate('client.number') . '</b> : ' . Html::encode($client_number) . '</div>';
}
if ($s->getSetting('enable_vat_registration') === '1' && $isSalesOrder) {
    echo '<div><b>' . Html::encode($translator->translate('salesorder.vat.salesorder')) . '</b></div>';
    echo '<div><br><b>' . $translator->translate('salesorder.number') . '</b> : ' . Html::encode($document_number) . '</div>';
    echo '<div><b>' . $translator->translate('client.number') . '</b> : ' . Html::encode($client_number) . '</div>';
}
echo '<div><br></div>';
echo '<div><b>' . Html::encode($company['name']) . '</b></div>';
echo '<div><br></div>';
echo '<div>' . $translator->translate('vat.reg.no') . ': ' . Html::encode($company['vat_id']) . '</div>';
echo '<div>' . $translator->translate('tax.code.short') . ': ' . Html::encode($company['tax_code']) . '</div>';
echo '<div><br></div>';
echo '<div>' . Html::encode($company['address_1'] ? $translator->translate('street.address') . ': ' . $company['address_1'] : '') . '</div>';
echo '<div>' . Html::encode($company['address_2'] ? $translator->translate('street.address.2') . ': ' . $company['address_2'] : '') . '</div>';
echo '<div>' . Html::encode($company['city'] ? $translator->translate('city') . ': ' . $company['city'] : '') . '</div>';
echo '<div>' . Html::encode($company['state'] ? $translator->translate('state') . ': ' . $company['state'] : '') . '</div>';
echo '<div>' . Html::encode($company['zip'] ? $translator->translate('zip') . ': ' . $company['zip'] : '') . '</div>';
echo '</div>';
echo '<div>' . $countryHelper->get_country_name($translator->translate('cldr'), ($company['country'] ?? 'United Kingdom')) . '</div>';
echo '<br/>';
echo '<div>' . $translator->translate('phone.abbr') . ': ' . Html::encode($company['phone'] ?? '') . '</div>';
echo '<div>' . $translator->translate('fax.abbr') . ': ' . Html::encode($company['fax'] ?? '') . '</div>';
?>
            </td>
        </tr>
    </table>    
</div>