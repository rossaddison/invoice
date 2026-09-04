<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Related logic: see App\Invoice\Helpers\PdfHelper function generateSalesorderPdf
 *
 * @var App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount $so_amount
 * @var App\Infrastructure\Persistence\SalesOrder\SalesOrder $salesorder
 * @var App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate $salesorder_tax_rate
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository $acsoiR
 * @var App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository $soiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $items
 * @var bool $show_custom_fields            show both top_custom_fields and view_custom_fields
 * @var bool $show_item_discounts
 * @var string $cldr
 * @var string $company_logo_and_address
 * @var string $top_custom_fields           appear at the top of salesorder.pdf
 * @var string $view_custom_fields          appear at the bottom of salesorder.pdf
 */

$vat            = $s->getSetting('enable_vat_registration');
$h100           = ['class' => 'h-100'];
$clearfix       = ['class' => 'clearfix'];
$idClient       = ['id' => 'client'];
$invDetails     = ['class' => 'invoice-details clearfix'];
$invTitle       = ['class' => 'invoice-title'];
$itemsTable     = ['class' => 'items item-table table m-0'];
$thItemName     = ['class' => 'item-name'];
$thItemDesc     = ['class' => 'item-desc'];
$thItemAmt      = ['class' => 'item-amount text-end'];
$thItemPrice    = ['class' => 'item-price text-end'];
$thItemDiscount = ['class' => 'item-discount text-end'];
$thItemTotal    = ['class' => 'item-total text-end'];
$tdEnd          = ['class' => 'text-end'];
$invSums        = ['class' => 'invoice-sums'];
$colSpanVal     = $show_item_discounts ? '6' : '5';

echo '<!DOCTYPE html>';
echo H::openTag('html', array_merge($h100, ['lang' => $cldr])); //0
$translator->setLocale($cldr);
echo H::openTag('head'); //1
echo H::tag('meta', '', ['charset' => 'utf-8']);
echo H::tag('meta', '', ['http-equiv' => 'X-UA-Compatible', 'content' => 'IE=edge']);
echo H::tag('meta', '', ['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']);
echo H::closeTag('head'); //1
echo H::openTag('body'); //1
echo H::openTag('header', $clearfix); //2
echo $company_logo_and_address;
echo H::openTag('div', $idClient); //3
echo H::tag(
    'div',
    H::tag(
        'b',
        H::encode($salesorder->getClient()?->getClientName())
    )
);
if (strlen($clientVatId = $salesorder->getClient()?->getClientVatId() ?? '') > 0) {
    echo H::tag(
        'div',
        $translator->translate('vat.reg.no') . ': ' . H::encode($clientVatId)
    );
}
if (strlen($clientTaxCode = $salesorder->getClient()?->getClientTaxCode() ?? '') > 0) {
    echo H::tag(
        'div',
        $translator->translate('tax.code.short') . ': ' . H::encode($clientTaxCode)
    );
}
echo H::tag(
    'div',
    H::encode(($addr1 = $salesorder->getClient()?->getClientAddress1() ?? '') !== ''
      ? $addr1 : $translator->translate('street.address'))
);
echo H::tag(
    'div',
    H::encode(($addr2 = $salesorder->getClient()?->getClientAddress2() ?? '') !== ''
      ? $addr2 : $translator->translate('street.address.2'))
);
if (strlen($salesorder->getClient()?->getClientCity() ?? '') > 0 || strlen($salesorder->getClient()?->getClientState() ?? '') > 0 || strlen($salesorder->getClient()?->getClientZip() ?? '') > 0) {
    echo H::openTag('div'); //4
    if (strlen($salesorder->getClient()?->getClientCity() ?? '') > 0) {
        echo H::encode($salesorder->getClient()?->getClientCity()) . ' ';
    }
    if (strlen($salesorder->getClient()?->getClientState() ?? '') > 0) {
        echo H::encode($salesorder->getClient()?->getClientState()) . ' ';
    }
    if (strlen($salesorder->getClient()?->getClientZip() ?? '') > 0) {
        echo H::encode($salesorder->getClient()?->getClientZip());
    }
    echo H::closeTag('div'); //4
}
if (strlen($salesorder->getClient()?->getClientState() ?? '') > 0) {
    echo H::tag(
        'div',
        H::encode($salesorder->getClient()?->getClientState())
    );
}
if (strlen($clientCountry = $salesorder->getClient()?->getClientCountry() ?? '') > 0) {
    echo H::tag('div', $countryHelper->getCountryName($translator->translate('cldr'), $clientCountry));
}
echo H::tag('br', '');
if (strlen($clientPhone = $salesorder->getClient()?->getClientPhone() ?? '') > 0) {
    echo H::tag(
        'div',
        $translator->translate('phone.abbr') . ': ' . H::encode($clientPhone)
    );
}
echo H::closeTag('div'); //3
echo H::closeTag('header'); //2
echo H::openTag('main'); //2
echo H::openTag('div', $invDetails); //3
echo H::openTag('table'); //4
echo H::openTag('tr'); //5
echo H::tag('td', $translator->translate('date.issued') . ':');
echo H::tag(
    'td',
    H::encode(!is_string($dateCreated = $salesorder->getDateCreated()) ? $dateCreated->format('Y-m-d') : '')
);
echo H::closeTag('tr'); //5
echo H::openTag('tr'); //5
echo H::tag('td', $translator->translate('expires') . ':');
echo H::tag('td', $salesorder->getDateExpires()->format('Y-m-d'));
echo H::closeTag('tr'); //5
echo H::tag('tr', $show_custom_fields ? $top_custom_fields : '');
echo H::closeTag('table'); //4
echo H::closeTag('div'); //3
echo H::openTag('h3', $invTitle); //3
echo H::tag(
    'b',
    H::encode($translator->translate('salesorder') . ' ' . ($salesorder->getNumber() ?? '#'))
);
echo H::closeTag('h3'); //3
echo H::openTag('table', $itemsTable); //3
echo H::openTag('thead'); //4
echo H::openTag('tr'); //5
echo H::tag(
    'th',
    H::encode($translator->translate('item')),
    $thItemName
);
echo H::tag(
    'th',
    H::encode($translator->translate('description')),
    $thItemDesc
);
echo H::tag(
    'th',
    H::encode($translator->translate('qty')),
    $thItemAmt
);
echo H::tag(
    'th',
    H::encode($translator->translate('price')),
    $thItemPrice
);
if ($show_item_discounts) :
    echo H::tag(
        'th',
        H::encode($translator->translate('discount')),
        $thItemDiscount
    );
endif;
if ($vat === '0') :
    echo H::tag(
        'th',
        H::encode($translator->translate('tax')),
        $thItemPrice
    );
else :
    echo H::tag(
        'th',
        H::encode($translator->translate('vat.abbreviation')),
        $thItemPrice
    );
endif;
echo H::tag(
    'th',
    H::encode($translator->translate('total')),
    $thItemTotal
);
echo H::closeTag('tr'); //5
echo H::closeTag('thead'); //4
echo H::openTag('tbody'); //4
if ($items) {
    $rowNum = 0;
    /**
     * @var App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem $item
     */
    foreach ($items as $item) {
        $rowNum++;
        $rowClass = ($rowNum % 2 === 1) ? 'odd' : 'even';
        $salesorder_item_amount = $soiaR->repoSalesOrderItemAmountquery($item->reqId());
        if ($s->getSetting('enable_peppol') == '1') {
            $itemId = $item->reqId();
            $salesOrderItemAllowanceCharges = $acsoiR->repoSalesOrderItemquery($itemId);
            /**
             * @var App\Infrastructure\Persistence\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceCharge $salesOrderItemAllowanceCharge
             */
            foreach ($salesOrderItemAllowanceCharges as $salesOrderItemAllowanceCharge) {
                $isCharge = ($salesOrderItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == 1 ? true : false);
                echo H::openTag('tr'); //5
                echo H::openTag('td', ['colspan' => $show_item_discounts ? '5' : '4']); //6
                echo $salesOrderItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == '1'
                 ? $translator->translate('allowance.or.charge.charge')
                 : '(' . $translator->translate('allowance.or.charge.allowance') . ')';
                echo $translator->translate('allowance.or.charge.reason.code') . ': ' . ($salesOrderItemAllowanceCharge->getAllowanceCharge()?->getReasonCode() ?? '#');
                echo ' - ';
                echo $translator->translate('allowance.or.charge.reason') . ': ' . ($salesOrderItemAllowanceCharge->getAllowanceCharge()?->getReason() ?? '#');
                echo H::closeTag('td'); //6
                echo H::tag('td', ($isCharge ? '' : '(') . $numberHelper->formatCurrency($salesOrderItemAllowanceCharge->getAmount()) . ($isCharge ? '' : ')'), $tdEnd);
                $vatSalesOrderItem = $salesOrderItemAllowanceCharge->getVatOrTax();
                echo H::tag(
                    'td',
                    H::encode(($isCharge ? '' : '(') . $numberHelper->formatCurrency($vatSalesOrderItem) . ($isCharge ? '' : ')')),
                    $tdEnd
                );
                $percent = $salesOrderItemAllowanceCharge->getAllowanceCharge()?->getTaxRate()?->getTaxRatePercent();
                echo H::tag(
                    'td',
                    H::encode($percent ?? 0.00),
                    $tdEnd
                );
                echo H::closeTag('tr'); //5
            }
        }
        echo H::openTag('tr', ['class' => $rowClass]); //5
        echo H::tag(
            'td',
            H::encode($item->getName())
        );
        echo H::tag(
            'td',
            nl2br(H::encode($item->getDescription()))
        );
        echo H::openTag('td', $tdEnd); //6
        echo H::encode($s->formatAmount($item->getQuantity()));
        if (strlen($item->getProductUnit() ?? '') > 0) :
            echo H::tag('br', '');
            echo H::tag(
                'small',
                H::encode($item->getProductUnit())
            );
        endif;
        echo H::closeTag('td'); //6
        echo H::tag(
            'td',
            H::encode($s->formatCurrency($item->getPrice())),
            $tdEnd
        );
        if ($show_item_discounts) :
            echo H::tag(
                'td',
                H::encode($s->formatCurrency($item->getDiscountAmount())),
                $tdEnd
            );
        endif;
        echo H::tag(
            'td',
            H::encode($s->formatCurrency($salesorder_item_amount?->getTaxTotal())),
            $tdEnd
        );
        echo H::tag(
            'td',
            H::tag(
                'b',
                H::encode($s->formatCurrency($salesorder_item_amount?->getTotal()))
            ),
            $tdEnd
        );
        echo H::closeTag('tr'); //5
    }
}
echo H::closeTag('tbody'); //4
echo H::openTag('tbody', $invSums); //4
echo H::openTag('tr'); //5
if ($vat === '0') :
    echo H::openTag('td', array_merge($tdEnd, ['colspan' => $colSpanVal])); //6
    echo H::encode($translator->translate('subtotal'))
     . ' (' . H::encode($translator->translate('price'))
     . '-' . H::encode($translator->translate('discount'))
     . ') x ' . H::encode($translator->translate('qty'));
    echo H::closeTag('td'); //6
else :
    echo H::tag(
        'td',
        H::encode($translator->translate('subtotal')),
        array_merge($tdEnd, ['colspan' => $colSpanVal])
    );
endif;
echo H::tag(
    'td',
    H::tag(
        'b',
        H::encode($s->formatCurrency($so_amount->getItemSubtotal()))
    ),
    $tdEnd
);
echo H::closeTag('tr'); //5
if ($so_amount->getItemTaxTotal() > 0) :
    echo H::openTag('tr'); //5
    echo H::tag(
        'td',
        H::encode($vat === '1' ? $translator->translate('vat.break.down') : $translator->translate('item.tax')),
        array_merge($tdEnd, ['colspan' => $colSpanVal])
    );
    echo H::tag(
        'td',
        H::tag(
            'b',
            H::encode($s->formatCurrency($so_amount->getItemTaxTotal()))
        ),
        $tdEnd
    );
    echo H::closeTag('tr'); //5
endif;
if ($s->getSetting('enable_peppol') == '1') {
    if ($so_amount->getPackhandleshipTotal() != 0.00) :
        echo H::openTag('tr'); //5
        echo H::tag(
            'td',
            H::encode($translator->translate('allowance.or.charge.shipping.handling.packaging')),
            array_merge($tdEnd, ['colspan' => $colSpanVal])
        );
        echo H::tag(
            'td',
            H::tag(
                'b',
                H::encode($s->formatCurrency($so_amount->getPackhandleshipTotal()))
            ),
            $tdEnd
        );
        echo H::closeTag('tr'); //5
    endif;
    if ($so_amount->getPackhandleshipTax() != 0.00) :
        echo H::openTag('tr'); //5
        echo H::tag(
            'td',
            H::encode($vat == '1'
         ? $translator->translate('allowance.or.charge.shipping.handling.packaging.vat')
         : $translator->translate('allowance.or.charge.shipping.handling.packaging.tax')),
            array_merge($tdEnd, ['colspan' => $colSpanVal])
        );
        echo H::tag(
            'td',
            H::tag(
                'b',
                H::encode($s->formatCurrency($so_amount->getPackhandleshipTax()))
            ),
            $tdEnd
        );
        echo H::closeTag('tr'); //5
    endif;
}
if (!empty($so_tax_rates) && ($vat === '0')) :
    /**
     * @var App\Infrastructure\Persistence\SalesOrderTaxRate\SalesOrderTaxRate $salesorder_tax_rate
     */
    foreach ($so_tax_rates as $salesorder_tax_rate) :
        echo H::openTag('tr'); //5
        echo H::openTag('td', array_merge($tdEnd, ['colspan' => $colSpanVal])); //6
        $taxRatePercent = $salesorder_tax_rate->getTaxRate()?->getTaxRatePercent();
        $taxRateName    = $salesorder_tax_rate->getTaxRate()?->getTaxRateName();
        if (($taxRatePercent >= 0.00) && (strlen($taxRateName ?? '') > 0)) {
            echo H::encode(($taxRateName ?? '#') . ' ' . ($numberHelper->formatAmount($taxRatePercent) ?? '#'));
        }
        echo H::closeTag('td'); //6
        echo H::tag(
            'td',
            H::tag(
                'b',
                H::encode($s->formatCurrency($salesorder_tax_rate->getSalesOrderTaxRateAmount()))
            ),
            $tdEnd
        );
        echo H::closeTag('tr'); //5
    endforeach;
endif;
if ($vat == '0') :
    if ($salesorder->getDiscountAmount() !== 0.00) :
        echo H::openTag('tr'); //5
        echo H::tag(
            'td',
            H::encode($translator->translate('discount')),
            array_merge($tdEnd, ['colspan' => $colSpanVal])
        );
        echo H::tag(
            'td',
            H::encode($s->formatCurrency($salesorder->getDiscountAmount())),
            $tdEnd
        );
        echo H::closeTag('tr'); //5
    endif;
endif;
echo H::openTag('tr'); //5
echo H::tag(
    'td',
    H::tag(
        'b',
        H::encode($translator->translate('total'))
    ),
    array_merge($tdEnd, ['colspan' => $colSpanVal])
);
echo H::tag(
    'td',
    H::tag(
        'b',
        H::encode($s->formatCurrency($so_amount->getTotal()))
    ),
    $tdEnd
);
echo H::closeTag('tr'); //5
echo H::closeTag('tbody'); //4
echo H::closeTag('table'); //3
echo H::closeTag('main'); //2
echo H::openTag('footer'); //2
if (strlen($salesorder->getNotes() ?? '') > 0) :
    echo H::openTag('div', ['class' => 'notes']); //3
    echo H::tag(
        'b',
        H::encode($translator->translate('notes'))
    );
    echo H::tag('br', '');
    echo nl2br(H::encode($salesorder->getNotes()));
    echo H::closeTag('div'); //3
endif;
if ($show_custom_fields) {
    echo $view_custom_fields;
}
echo H::closeTag('footer'); //2
echo H::closeTag('body'); //1
echo H::closeTag('html'); //0
