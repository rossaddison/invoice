<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Img;

/**
 * Related logic: see QuoteController function urlKey
 * @var App\Infrastructure\Persistence\Client\Client $client
 * @var App\Infrastructure\Persistence\Quote\Quote $quote
 * @var App\Infrastructure\Persistence\QuoteAmount\QuoteAmount $quote_amount
 * @var App\Infrastructure\Persistence\UserInv\UserInv $userInv
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository $acqiR
 * @var App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 *
 * @var array $items
 * @var array $quote_tax_rates
 * @var bool $has_expired
 *
 * Related logic: see src\ViewInjection\LayoutViewInjection
 * @var string $companyLogoFileName
 * @var string $logoPath
 * @var int $companyLogoWidth
 * @var int $companyLogoHeight
 *
 * @var string $alert
 * @var string $modal_purchase_order_number
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
echo $translator->translate('quote');
echo ' ';
echo $quote->getNumber();
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
echo H::openTag('h2'); //5
echo $translator->translate('quote');
echo '&nbsp;';
echo $quote->getNumber();
echo H::closeTag('h2'); //5
echo H::openTag('div', $btnGroup); //5
if (in_array($quote->reqStatusId(), [2, 3, 5]) && $quote->getSoId() === 0) :
    echo $modal_purchase_order_number;
    echo H::openTag('a', ['href' => '#purchase-order-number', 'data-bs-toggle' => 'modal', 'class' => 'btn btn-warning']); //6
    echo H::tag('i', '', ['class' => 'bi bi-check-lg']);
    echo $translator->translate('quote.approve');
    echo H::closeTag('a'); //6
endif;
if ($quote->reqStatusId() !== 4 && $quote->reqStatusId() !== 5 && $quote->getSoId() === 0) :
    echo H::openTag('a', ['href' => $urlGenerator->generate('quote/reject', ['url_key' => $quote->getUrlKey()]), 'class' => 'btn btn-danger ajax-loader']); //6
    echo H::tag('i', '', ['class' => 'bi bi-check-lg']);
    echo $translator->translate('quote.reject');
    echo H::closeTag('a'); //6
endif;
if ($quote->reqStatusId() === 4) :
    echo H::tag('label', $translator->translate('approved'), ['class' => 'btn btn-success', 'disabled' => true]);
endif;
if ($quote->reqStatusId() === 5) :
    echo H::tag('label', $translator->translate('rejected'), ['class' => 'btn btn-danger', 'disabled' => true]);
endif;
if ($quote->reqStatusId() === 6) :
    echo H::tag('label', $translator->translate('canceled'), ['class' => 'btn btn-danger', 'disabled' => true]);
endif;
echo H::closeTag('div'); //5
echo H::closeTag('div'); //4
echo H::tag('hr', '');
echo $alert;
echo H::openTag('div', $divInvoice); //4
echo new Img()->width($companyLogoWidth)->height($companyLogoHeight)->src($logoPath);
echo H::tag('br', '');
echo H::tag('br', '');
echo H::openTag('div', $row); //5
echo H::openTag('div', $col12Md6Lg5); //6
echo H::tag(
    'h4',
    H::encode($userInv->getName())
);
echo H::openTag('p'); //7
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
echo H::closeTag('p'); //7
echo H::closeTag('div'); //6
echo H::tag('div', '', $colLg2);
echo H::openTag('div', $col12Md6Lg5R); //6
echo H::tag(
    'h4',
    H::encode($clientHelper->formatClient($client))
);
echo H::openTag('p'); //7
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
echo H::closeTag('p'); //7
echo H::tag('br', '');
echo H::openTag('table', $tableCondensed); //7
echo H::openTag('tbody'); //8
echo H::openTag('tr'); //9
echo H::tag('td', $vat == '1' ? $translator->translate('date.issued') : $translator->translate('quote.date'));
echo H::tag('td', $quote->getDateCreated()->format('Y-m-d'), ['style' => 'text-align:right;']);
echo H::closeTag('tr'); //9
echo H::openTag('tr', ['class' => $has_expired ? 'overdue' : '']); //9
echo H::tag('td', $translator->translate('expires'));
echo H::tag('td', $quote->getDateExpires()->format('Y-m-d'), $textRight);
echo H::closeTag('tr'); //9
echo H::closeTag('tbody'); //8
echo H::closeTag('table'); //7
echo H::closeTag('div'); //6
echo H::closeTag('div'); //5
echo H::tag('br', '');
echo H::openTag('div', $invoiceItems); //5
echo H::openTag('div', $tableResponsive); //6
echo H::openTag('table', $tableStriped); //7
echo H::openTag('thead'); //8
echo H::openTag('tr'); //9
echo H::tag('th', $translator->translate('item'));
echo H::tag('th', $translator->translate('description'));
echo H::tag('th', $translator->translate('qty'), $textRight);
echo H::tag('th', $translator->translate('price'), $textRight);
echo H::tag('th', $translator->translate('discount'), $textRight);
echo H::tag('th', $translator->translate('total'), $textRight);
echo H::closeTag('tr'); //9
echo H::closeTag('thead'); //8
echo H::openTag('tbody'); //8
/**
 * @var App\Infrastructure\Persistence\InvItem\InvItem $item
 */
foreach ($items as $item) :
    if ($s->getSetting('enable_peppol') == '1') {
        $itemId = $item->reqId();
        $quoteItemAllowanceCharges = $acqiR->repoQuoteItemquery($itemId);
        /**
         * @var App\Infrastructure\Persistence\QuoteItemAllowanceCharge\QuoteItemAllowanceCharge $quoteItemAllowanceCharge
         */
        foreach ($quoteItemAllowanceCharges as $quoteItemAllowanceCharge) {
            $isCharge = ($quoteItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == 1 ? true : false);
            echo H::openTag('tr'); //9
            echo H::openTag('td', ['colspan' => '5']); //10
            echo $quoteItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == '1'
             ? $translator->translate('allowance.or.charge.charge')
             : '(' . $translator->translate('allowance.or.charge.allowance') . ')';
            echo $translator->translate('allowance.or.charge.reason.code') . ': ' . ($quoteItemAllowanceCharge->getAllowanceCharge()?->getReasonCode() ?? '#');
            echo ' - ';
            echo $translator->translate('allowance.or.charge.reason') . ': ' . ($quoteItemAllowanceCharge->getAllowanceCharge()?->getReason() ?? '#');
            echo H::closeTag('td'); //10
            echo H::tag('td', ($isCharge ? '' : '(') . $numberHelper->formatCurrency($quoteItemAllowanceCharge->getAmount()) . ($isCharge ? '' : ')'), $amtClass);
            $vatQuoteItem = $quoteItemAllowanceCharge->getVatOrTax();
            echo H::tag('td', ($isCharge ? '' : '(') . $numberHelper->formatCurrency($vatQuoteItem) . ($isCharge ? '' : ')'), $amtClass);
            echo H::closeTag('tr'); //9
        }
    }
    echo H::openTag('tr'); //9
    echo H::tag(
        'td',
        H::encode($item->getName())
    );
    echo H::tag(
        'td',
        nl2br(H::encode($item->getDescription()))
    );
    echo H::openTag('td', $amtClass); //10
    echo $numberHelper->formatAmount($item->getQuantity());
    if (strlen($item->getProductUnit() ?? '') > 0) :
        echo H::tag('br', '');
        echo H::tag(
            'small',
            H::encode($item->getProductUnit())
        );
    endif;
    echo H::closeTag('td'); //10
    echo H::tag('td', $numberHelper->formatCurrency($item->getPrice()), $amtClass);
    echo H::tag('td', $numberHelper->formatCurrency($item->getDiscountAmount()), $amtClass);
    $query = $qiaR->repoQuoteItemAmountquery($item->reqId());
    echo H::tag(
        'td',
        H::tag('b', $numberHelper->formatCurrency(null !== $query ? $query->getSubtotal() : 0.00)),
        $amtClass
    );
    echo H::closeTag('tr'); //9
endforeach;
echo H::openTag('tr'); //9
echo H::tag('td', '', ['colspan' => '4']);
echo H::tag('td', $translator->translate('subtotal') . ':', $textRight);
echo H::tag(
    'td',
    H::tag('b', $numberHelper->formatCurrency($quote_amount->getItemSubtotal())),
    $amtClass
);
echo H::closeTag('tr'); //9
if ($quote_amount->getItemTaxTotal() > 0) :
    echo H::openTag('tr'); //9
    echo H::tag('td', '', $noBB4);
    echo H::tag('td', $vat === '1' ? $translator->translate('vat.break.down') : $translator->translate('item.tax'), $textRight);
    echo H::tag(
        'td',
        H::tag('b', $numberHelper->formatCurrency($quote_amount->getItemTaxTotal())),
        $amtClass
    );
    echo H::closeTag('tr'); //9
endif;
if ($s->getSetting('enable_peppol') == '1') {
    if ($quote_amount->getPackhandleshipTotal() != 0.00) :
        echo H::openTag('tr'); //9
        echo H::tag('td', '', $noBB4);
        echo H::tag('td', $translator->translate('allowance.or.charge.shipping.handling.packaging'), $textRight);
        echo H::tag(
            'td',
            H::tag('b', $numberHelper->formatCurrency($quote_amount->getPackhandleshipTotal())),
            $amtClass
        );
        echo H::closeTag('tr'); //9
    endif;
    if ($quote_amount->getPackhandleshipTax() != 0.00) :
        echo H::openTag('tr'); //9
        echo H::tag('td', '', $noBB4);
        echo H::openTag('td', $textRight); //10
        echo $vat == '1'
         ? $translator->translate('allowance.or.charge.shipping.handling.packaging.vat')
         : $translator->translate('allowance.or.charge.shipping.handling.packaging.tax');
        echo H::closeTag('td'); //10
        echo H::tag(
            'td',
            H::tag('b', $numberHelper->formatCurrency($quote_amount->getPackhandleshipTax())),
            $amtClass
        );
        echo H::closeTag('tr'); //9
    endif;
}
if (!empty($quote_tax_rates) && $vat == '0') :
    /**
     * @var App\Infrastructure\Persistence\QuoteTaxRate\QuoteTaxRate $quote_tax_rate
     */
    foreach ($quote_tax_rates as $quote_tax_rate) :
        echo H::openTag('tr'); //9
        echo H::tag('td', '', $noBB4);
        echo H::openTag('td', $textRight); //10
        $taxRatePercent = $quote_tax_rate->getTaxRate()?->getTaxRatePercent();
        $taxRateName    = $quote_tax_rate->getTaxRate()?->getTaxRateName();
        if (($taxRatePercent >= 0.00) && (strlen($taxRateName ?? '') > 0)) {
            echo H::encode(($taxRateName ?? '#') . ' ' . ($numberHelper->formatAmount($taxRatePercent) ?? '#'));
        }
        echo '%';
        echo H::closeTag('td'); //10
        echo H::openTag('td', $amtClass); //10
        $quoteTaxRate = $quote_tax_rate->getQuoteTaxRateAmount();
        if ($quoteTaxRate >= 0.00) {
            echo H::tag('b', $numberHelper->formatCurrency($quoteTaxRate));
        }
        echo H::closeTag('td'); //10
        echo H::closeTag('tr'); //9
    endforeach;
endif;
echo H::openTag('tr'); //9
echo H::tag('td', '', $noBB4);
echo H::tag('td', $translator->translate('discount') . ':', $textRight);
echo H::tag(
    'td',
    H::tag('b', $numberHelper->formatAmount($quote->getDiscountAmount())),
    $amtClass
);
echo H::closeTag('tr'); //9
echo H::openTag('tr'); //9
echo H::tag('td', '', $noBB4);
echo H::tag('td', $translator->translate('total') . ':', $textRight);
echo H::tag(
    'td',
    H::tag('b', $numberHelper->formatCurrency($quote_amount->getTotal())),
    $amtClass
);
echo H::closeTag('tr'); //9
echo H::closeTag('tbody'); //8
echo H::closeTag('table'); //7
echo H::closeTag('div'); //6
echo H::closeTag('div'); //5
echo H::tag('hr', '');
echo H::openTag('div', $row); //5
if (strlen($quote->getNotes() ?? '') > 0) :
    echo H::openTag('div', ['class' => 'col-12 col-md-6']); //6
    echo H::tag('h4', $translator->translate('notes'));
    echo H::tag(
        'p',
        nl2br(H::encode($quote->getNotes()))
    );
    echo H::closeTag('div'); //6
endif;
echo H::closeTag('div'); //5
echo H::closeTag('div'); //4
echo H::closeTag('div'); //3
echo H::closeTag('div'); //2
echo H::closeTag('body'); //1
echo H::closeTag('html'); //0
