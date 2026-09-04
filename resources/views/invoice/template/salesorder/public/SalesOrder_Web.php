<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\SalesOrderItemAllowanceCharge\{
    SalesOrderItemAllowanceCharge,
};
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Img;

/**
 * Related logic: see SalesOrderController function urlKey
 * @var App\Infrastructure\Persistence\Client\Client $client
 * @var App\Infrastructure\Persistence\SalesOrder\SalesOrder $salesorder
 * @var App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount $salesorder_amount
 * @var App\Infrastructure\Persistence\UserInv\UserInv $userInv
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
 * Related logic: see App\Invoice\Helpers\PublicDocumentAssetHelper
 * @var string $bootstrapCssUrl
 * @var string $bootstrapIconsCssUrl
 * @var string $customPdfCss
 */

$vat          = $s->getSetting('enable_vat_registration');
$container    = ['class' => 'container'];
$idContent    = ['id' => 'content'];
$wpHeader     = ['class' => 'webpreview-header'];
$btnGroup     = ['class' => 'btn-group'];
$divInvoice   = ['class' => 'invoice'];
$row          = ['class' => 'row'];
$col12Md6Lg5  = ['class' => 'col-12 col-md-6 col-lg-5'];
$colLg2       = ['class' => 'col-lg-2'];
$col12Md6Lg5R = ['class' => 'col-12 col-md-6 col-lg-5 text-right'];
$tableCondensed  = ['class' => 'table table-condensed'];
$tableStriped    = ['class' => 'table table-striped table-bordered'];
$invoiceItems    = ['class' => 'invoice-items'];
$tableResponsive = ['class' => 'table-responsive'];
$amtClass     = ['class' => 'amount'];
$textRight    = ['class' => 'text-right'];
$noBB4        = ['class' => 'no-bottom-border', 'colspan' => '4'];

echo '<!DOCTYPE html>';
echo H::openTag('html', ['lang' => $translator->translate('cldr')]); //0
echo H::openTag('head'); //1
echo H::tag('meta', '', ['charset' => 'utf-8']);
echo H::tag('meta', '', ['http-equiv' => 'X-UA-Compatible', 'content' => 'IE=edge,chrome=1']);
echo H::openTag('title'); //2
echo $s->getSetting('custom_title');
echo ' - ';
echo $translator->translate('salesorder');
echo ' ';
echo $salesorder->getNumber() ?? '#';
echo H::closeTag('title'); //2
echo H::tag('link', '', ['rel' => 'stylesheet', 'href' => $bootstrapCssUrl]);
echo H::tag('link', '', ['rel' => 'stylesheet', 'href' => $bootstrapIconsCssUrl]);
echo H::style($customPdfCss);
echo H::tag('meta', '', ['name' => 'viewport', 'content' => 'width=device-width,initial-scale=1']);
echo H::closeTag('head'); //1
echo H::openTag('body'); //1
echo H::openTag('div', $container); //2
echo H::openTag('div', $idContent); //3
echo H::openTag('div', $wpHeader); //4
echo H::openTag('div', $row); //5
echo H::tag('h1', $translator->translate('term'));
echo H::openTag('div', ['class' => 'col-12 col-sm-6 badge text-bg-info']); //6
echo H::openTag('div'); //7
echo H::tag('textarea', $terms_and_conditions_file, ['class' => 'form-control form-control-lg', 'rows' => '20', 'cols' => '20']);
echo H::closeTag('div'); //7
echo H::closeTag('div'); //6
echo H::closeTag('div'); //5
echo H::tag('br', '');
echo H::closeTag('div'); //4
echo H::openTag('div', $btnGroup); //4
if (in_array($salesorder->getStatusId(), [2, 8]) && $salesorder->reqQuoteId() !== 0 && !$salesorder->hasLinkedInvoice()) :
    echo H::openTag('a', [ //5
     'href' => $urlGenerator->generate('salesorder/agreeToTerms', ['url_key' => $salesorder_url_key]),
     'class' => 'btn btn-success',
     'data-bs-toggle' => 'tooltip',
     'title' => 'Goods and Services will now be assembled/packaged/prepared',
    ]); //5
    echo H::tag('i', '', ['class' => 'bi bi-check-lg']);
    echo $translator->translate('salesorder.agree.to.terms');
    echo H::closeTag('a'); //5
endif;
if (in_array($salesorder->getStatusId(), [2]) && $salesorder->reqQuoteId() !== 0 && !$salesorder->hasLinkedInvoice()) :
    echo H::openTag('a', ['href' => $urlGenerator->generate('salesorder/reject', ['url_key' => $salesorder_url_key]), 'class' => 'btn btn-danger']); //5
    echo H::tag('i', '', ['class' => 'bi bi-x-circle']);
    echo $translator->translate('salesorder.reject');
    echo H::closeTag('a'); //5
endif;
if (in_array($salesorder->getStatusId(), [3]) && $salesorder->reqQuoteId() !== 0 && !$salesorder->hasLinkedInvoice()) :
    echo H::tag('label', $translator->translate('salesorder.client.confirmed.terms'), ['class' => 'btn btn-success']);
endif;
echo H::closeTag('div'); //4
echo H::tag('br', '');
if (in_array($salesorder->getStatusId(), [3, 4]) && isset($isGuest) && $isGuest === true) :
    echo H::openTag('div', ['class' => 'alert alert-info', 'role' => 'alert']); //4
    echo H::openTag('h4', ['class' => 'alert-heading']); //5
    echo H::tag('i', '', ['class' => 'bi bi-file-text']);
    echo $translator->translate('invoice.peppol.information.required');
    echo H::closeTag('h4'); //5
    echo H::tag('p', $translator->translate('invoice.peppol.guest.instructions'));
    echo H::tag('hr', '');
    echo H::tag('p', $translator->translate('invoice.peppol.click.items.below'), ['class' => 'mb-0']);
    echo H::closeTag('div'); //4
endif;
echo H::tag('br', '');
echo H::openTag('h2'); //4
echo $translator->translate('salesorder');
echo '&nbsp;';
echo $salesorder->getNumber();
echo H::closeTag('h2'); //4
echo H::closeTag('div'); //3
echo H::tag('hr', '');
echo $alert;
echo H::openTag('div', $divInvoice); //3
echo new Img()->width($companyLogoWidth)->height($companyLogoHeight)->src($logoPath);
echo H::openTag('div', $row); //4
echo H::openTag('div', $col12Md6Lg5); //5
echo H::tag(
    'h4',
    H::encode($userInv->getName())
);
echo H::openTag('p'); //6
if (strlen($userInv->getVatId() ?: '') > 0) {
    echo $translator->translate('vat.id.short') . ': ' . ($userInv->getVatId() ?: '');
    echo H::tag('br', '');
}
if (strlen($userInv->getTaxCode() ?? '') > 0) {
    echo $translator->translate('tax.code.short') . ': ' . ($userInv->getTaxCode() ?? '');
    echo H::tag('br', '');
}
if (strlen($userInv->getAddress1() ?? '') > 0) {
    echo H::encode($userInv->getAddress1());
    echo H::tag('br', '');
}
if (strlen($userInv->getAddress2() ?? '') > 0) {
    echo H::encode($userInv->getAddress2());
    echo H::tag('br', '');
}
if (strlen($userInv->getCity() ?? '') > 0) {
    echo H::encode($userInv->getCity()) . ' ';
}
if (strlen($userInv->getState() ?? '') > 0) {
    echo H::encode($userInv->getState()) . ' ';
}
if (strlen($userInv->getZip() ?? '') > 0) {
    echo H::encode($userInv->getZip());
    echo H::tag('br', '');
}
if (strlen($userInv->getPhone() ?? '') > 0) {
    echo $translator->translate('phone.abbr') . ': ' . H::encode($userInv->getPhone());
    echo H::tag('br', '');
}
if (strlen($userInv->getFax() ?? '') > 0) {
    echo $translator->translate('fax.abbr') . ': ' . H::encode($userInv->getFax());
}
echo H::closeTag('p'); //6
echo H::closeTag('div'); //5
echo H::tag('div', '', $colLg2);
echo H::openTag('div', $col12Md6Lg5R); //5
echo H::tag(
    'h4',
    H::encode($clientHelper->formatClient($client))
);
echo H::openTag('p'); //6
if (strlen($client->getClientVatId()) > 0) {
    echo $translator->translate('vat.id.short') . ': ' . H::encode($client->getClientVatId());
    echo H::tag('br', '');
}
if (strlen($client->getClientTaxCode() ?? '') > 0) {
    echo $translator->translate('tax.code.short') . ': ' . H::encode($client->getClientTaxCode() ?? '');
    echo H::tag('br', '');
}
if (strlen($client->getClientAddress1() ?? '') > 0) {
    echo H::encode($client->getClientAddress1());
    echo H::tag('br', '');
}
if (strlen($client->getClientAddress2() ?? '') > 0) {
    echo H::encode($client->getClientAddress2());
    echo H::tag('br', '');
}
if (strlen($client->getClientCity() ?? '') > 0) {
    echo H::encode($client->getClientCity()) . ' ';
}
if (strlen($client->getClientState() ?? '') > 0) {
    echo H::encode($client->getClientState()) . ' ';
}
if (strlen($client->getClientZip() ?? '') > 0) {
    echo H::encode($client->getClientZip());
    echo H::tag('br', '');
}
if (strlen($clientPhone = $client->getClientPhone() ?? '') > 0) {
    echo $translator->translate('phone.abbr') . ': ' . H::encode($clientPhone);
    echo H::tag('br', '');
}
echo H::closeTag('p'); //6
echo H::tag('br', '');
echo H::openTag('table', $tableCondensed); //6
echo H::openTag('tbody'); //7
echo H::openTag('tr'); //8
echo H::openTag('td'); //9
echo $vat == '1' ? $translator->translate('date.issued') : $translator->translate('quote.date');
echo H::closeTag('td'); //9
echo H::tag('td', $dateHelper->dateFromMysql($salesorder->getDateCreated()), ['style' => 'text-align:right;']);
echo H::closeTag('tr'); //8
echo H::closeTag('tbody'); //7
echo H::closeTag('table'); //6
echo H::closeTag('div'); //5
echo H::closeTag('div'); //4
echo H::tag('br', '');
echo H::openTag('div', $invoiceItems); //4
echo H::openTag('div', $tableResponsive); //5
echo H::openTag('table', $tableStriped); //6
echo H::openTag('thead'); //7
echo H::openTag('tr'); //8
echo H::tag('th', $translator->translate('item'));
echo H::tag('th', $translator->translate('description'));
echo H::tag('th', $translator->translate('qty'), $textRight);
echo H::tag('th', $translator->translate('price'), $textRight);
echo H::tag('th', $translator->translate('discount'), $textRight);
echo H::tag('th', $translator->translate('total'), $textRight);
if (in_array($salesorder->getStatusId(), [3, 4]) && isset($isGuest) && $isGuest === true) :
    echo H::tag('th', $translator->translate('invoice.peppol.edit'), ['class' => 'text-center']);
endif;
echo H::closeTag('tr'); //8
echo H::closeTag('thead'); //7
echo H::openTag('tbody'); //7
/**
 * @var App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem $item
 */
foreach ($items as $item) :
    $peppolItemId = $item->getPeppolPoItemid();
    $peppolLineId = $item->getPeppolPoLineid();
    echo H::openTag('tr'); //8
    echo H::openTag('td'); //9
    echo H::encode($item->getName());
    if (null !== $peppolItemId || null !== $peppolLineId) :
        echo H::tag('br', '');
        echo H::openTag('small', ['class' => 'text-muted']); //10
        if (null !== $peppolItemId) :
            echo H::tag('i', '', ['class' => 'bi bi-upc-scan']);
            echo ' ';
            echo H::encode($peppolItemId);
        endif;
        if (null !== $peppolLineId) :
            echo H::tag('br', '');
            echo H::tag('i', '', ['class' => 'bi bi-list-ol']);
            echo ' ';
            echo H::encode($peppolLineId);
        endif;
        echo H::closeTag('small'); //10
    endif;
    echo H::closeTag('td'); //9
    if (in_array($salesorder->getStatusId(), [3, 4]) && isset($isGuest) && $isGuest === true) :
        echo H::openTag('td', ['class' => 'text-center']); //9
        echo H::openTag('a', [ //10
         'href'           => $urlGenerator->generate('salesorderitem/edit', ['id' => (string) $item->reqId()]),
         'class'          => 'btn btn-sm btn-primary',
         'data-bs-toggle' => 'tooltip',
         'title'          => $translator->translate('invoice.peppol.edit.item'),
        ]); //10
        echo H::tag('i', '', ['class' => 'bi bi-pencil-square']);
        echo $translator->translate('invoice.peppol.enter');
        echo H::closeTag('a'); //10
        echo H::closeTag('td'); //9
    endif;
    echo H::tag(
        'td',
        nl2br(H::encode($item->getDescription()))
    );
    echo H::openTag('td', $amtClass); //9
    echo $numberHelper->formatAmount($item->getQuantity());
    if (strlen($item->getProductUnit() ?? '') > 0) :
        echo H::tag('br', '');
        echo H::tag(
            'small',
            H::encode($item->getProductUnit())
        );
    endif;
    echo H::closeTag('td'); //9
    echo H::tag('td', $numberHelper->formatCurrency($item->getPrice()), $amtClass);
    echo H::tag('td', $numberHelper->formatCurrency($item->getDiscountAmount()), $amtClass);
    $query = $soiaR->repoSalesOrderItemAmountquery($item->reqId());
    echo H::tag('td', $numberHelper->formatCurrency(null !== $query ? $query->getSubtotal() : 0.00), $amtClass);
    echo H::closeTag('tr'); //8
    if ($s->getSetting('enable_peppol') == '1') {
        /**
         * @var SalesOrderItemAllowanceCharge $salesOrderItemAllowanceCharge
         */
        foreach ($acsoiR->repoSalesOrderItemquery($item->reqId()) as $salesOrderItemAllowanceCharge) {
            $isCharge = ($salesOrderItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == 1 ? true : false);
            echo H::openTag('tr'); //8
            echo H::openTag('td', ['colspan' => '5']); //9
            echo H::openTag('b'); //10
            echo $salesOrderItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == '1'
             ? $translator->translate('allowance.or.charge.charge')
             : '(' . $translator->translate('allowance.or.charge.allowance') . ')';
            echo H::closeTag('b'); //10
            echo $translator->translate('allowance.or.charge.reason.code') . ': ' . ($salesOrderItemAllowanceCharge->getAllowanceCharge()?->getReasonCode() ?? '#');
            echo ' - ';
            echo $translator->translate('allowance.or.charge.reason') . ': ' . ($salesOrderItemAllowanceCharge->getAllowanceCharge()?->getReason() ?? '#');
            echo H::closeTag('td'); //9
            echo H::openTag('td', $amtClass); //9
            echo H::tag('b', ($isCharge ? '' : '(') . $numberHelper->formatCurrency($salesOrderItemAllowanceCharge->getAmount()) . ($isCharge ? '' : ')'));
            echo H::closeTag('td'); //9
            echo H::openTag('td', $amtClass); //9
            $vatSalesOrderItem = $salesOrderItemAllowanceCharge->getVatOrTax();
            echo H::tag('b', ($isCharge ? '' : '(') . $numberHelper->formatCurrency($vatSalesOrderItem) . ($isCharge ? '' : ')'));
            echo H::closeTag('td'); //9
            echo H::closeTag('tr'); //8
        }
    }
endforeach;
echo H::openTag('tr'); //8
echo H::tag('td', '', ['colspan' => '4']);
echo H::tag('td', $translator->translate('subtotal') . ':', $textRight);
echo H::tag(
    'td',
    H::tag('b', $numberHelper->formatCurrency($salesorder_amount->getItemSubtotal())),
    $amtClass
);
echo H::closeTag('tr'); //8
if ($salesorder_amount->getItemTaxTotal() > 0) :
    echo H::openTag('tr'); //8
    echo H::tag('td', '', $noBB4);
    echo H::openTag('td', $textRight); //9
    echo $vat === '1' ? $translator->translate('vat.break.down') : $translator->translate('item.tax');
    echo H::closeTag('td'); //9
    echo H::tag(
        'td',
        H::tag('b', $numberHelper->formatCurrency($salesorder_amount->getItemTaxTotal())),
        $amtClass
    );
    echo H::closeTag('tr'); //8
endif;
if ($s->getSetting('enable_peppol') == '1') {
    if ($salesorder_amount->getPackhandleshipTotal() != 0.00) :
        echo H::openTag('tr'); //8
        echo H::tag('td', '', $noBB4);
        echo H::tag('td', $translator->translate('allowance.or.charge.shipping.handling.packaging'), $textRight);
        echo H::tag(
            'td',
            H::tag('b', $numberHelper->formatCurrency($salesorder_amount->getPackhandleshipTotal())),
            $amtClass
        );
        echo H::closeTag('tr'); //8
    endif;
    if ($salesorder_amount->getPackhandleshipTax() != 0.00) :
        echo H::openTag('tr'); //8
        echo H::tag('td', '', $noBB4);
        echo H::openTag('td', $textRight); //9
        echo $vat == '1'
         ? $translator->translate('allowance.or.charge.shipping.handling.packaging.vat')
         : $translator->translate('allowance.or.charge.shipping.handling.packaging.tax');
        echo H::closeTag('td'); //9
        echo H::tag(
            'td',
            H::tag('b', $numberHelper->formatCurrency($salesorder_amount->getPackhandleshipTax())),
            $amtClass
        );
        echo H::closeTag('tr'); //8
    endif;
}
if (!empty($salesorder_tax_rates) && $vat == '0') :
    /**
     * @var App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate $salesorder_tax_rate
     */
    foreach ($salesorder_tax_rates as $salesorder_tax_rate) :
        echo H::openTag('tr'); //8
        echo H::tag('td', '', $noBB4);
        echo H::openTag('td', $textRight); //9
        $taxRatePercent = $salesorder_tax_rate->getTaxRate()?->getTaxRatePercent();
        $taxRateName    = $salesorder_tax_rate->getTaxRate()?->getTaxRateName();
        if (($taxRatePercent >= 0.00) && (strlen($taxRateName ?? '') > 0)) {
            echo H::encode(($taxRateName ?? '#') . ' ' . ($numberHelper->formatAmount($taxRatePercent) ?? '#'));
        }
        echo '%';
        echo H::closeTag('td'); //9
        echo H::tag(
            'td',
            H::tag('b', $numberHelper->formatCurrency($salesorder_tax_rate->getSalesOrderTaxRateAmount())),
            $amtClass
        );
        echo H::closeTag('tr'); //8
    endforeach;
endif;
if ($vat === '0') :
    echo H::openTag('tr'); //8
    echo H::tag('td', '', $noBB4);
    echo H::tag('td', $translator->translate('discount') . ':', $textRight);
    echo H::openTag('td', $amtClass); //9
    $discountAmount = $salesorder->getDiscountAmount();
    if ($discountAmount >= 0.00) {
        echo H::tag('b', $numberHelper->formatAmount($discountAmount));
    }
    echo H::closeTag('td'); //9
    echo H::closeTag('tr'); //8
endif;
echo H::openTag('tr'); //8
echo H::tag('td', '', $noBB4);
echo H::tag('td', $translator->translate('total') . ':', $textRight);
echo H::tag(
    'td',
    H::tag('b', $numberHelper->formatCurrency($salesorder_amount->getTotal())),
    $amtClass
);
echo H::closeTag('tr'); //8
echo H::closeTag('tbody'); //7
echo H::closeTag('table'); //6
echo H::closeTag('div'); //5
echo H::closeTag('div'); //4
echo H::tag('hr', '');
echo H::openTag('div', $row); //4
if (strlen($salesorder->getNotes() ?? '') > 0) :
    echo H::openTag('div', ['class' => 'col-12 col-md-6']); //5
    echo H::tag('h4', $translator->translate('notes'));
    echo H::tag(
        'p',
        nl2br(H::encode($salesorder->getNotes()))
    );
    echo H::closeTag('div'); //5
endif;
echo H::closeTag('div'); //4
echo H::closeTag('div'); //3
echo H::closeTag('div'); //2
echo H::closeTag('body'); //1
echo H::closeTag('html'); //0
