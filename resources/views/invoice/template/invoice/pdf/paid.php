<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use Yiisoft\Html\Html as H;

/**
 * Related logic: see App\Invoice\Helpers\PdfHelper function generateInvHtml
 *
 * @var App\Infrastructure\Persistence\InvAmount\InvAmount $inv_amount
 * @var App\Infrastructure\Persistence\Inv\Inv $inv
 * @var App\Infrastructure\Persistence\InvTaxRate\InvTaxRate $inv_tax_rate
 * @var App\Invoice\Helpers\CountryHelper $countryHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository $aciiR
 * @var App\Invoice\InvItemAmount\InvItemAmountRepository $iiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $inv_tax_rates
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $items
 * @var bool $show_custom_fields            show both top_custom_fields and view_custom_fields
 * @var bool $show_item_discounts
 * @var string $cldr
 * @var string $company_logo_and_address    setting/company_logo_and_address.php
 * @var string $delivery_location
 * @var string $inv_allowance_charges
 * @var string $top_custom_fields           appear at the top of invoice.pdf
 * @var string $view_custom_fields          appear at the bottom of invoice.pdf
 */

$vat            = $s->getSetting('enable_vat_registration');
$dateFmt        = 'Y-m-d';
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
$pageBreak      = ['style' => 'page-break-before: always'];
$notesClass     = ['class' => 'notes'];
$colspanVal     = $show_item_discounts ? '7' : '6';
$colspanAttr    = ['colspan' => $colspanVal, 'class' => 'text-end'];

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
    echo H::tag('div',
     H::tag('b',
      H::encode($inv->getClient()?->getClientName())));
    if (strlen($clientVatId = $inv->getClient()?->getClientVatId() ?? '') > 0) {
     echo H::tag('div',
      $translator->translate('vat.reg.no', ['term' => $s->activeTaxSchemeTerm()]) . ': ' . H::encode($clientVatId));
    }
    if (strlen($clientTaxCode = $inv->getClient()?->getClientTaxCode() ?? '') > 0) {
     echo H::tag('div',
      $translator->translate('tax.code.short') . ': ' . H::encode($clientTaxCode));
    }
    echo H::tag('div',
     H::encode(($addr1 = $inv->getClient()?->getClientAddress1() ?? '') !== ''
             ? $addr1 : $translator->translate('street.address')));
    echo H::tag('div',
     H::encode(($addr2 = $inv->getClient()?->getClientAddress2() ?? '') !== ''
             ? $addr2 : $translator->translate('street.address.2')));
    if (strlen($inv->getClient()?->getClientCity() ?? '') > 0 || strlen($inv->getClient()?->getClientState() ?? '') > 0 || strlen($inv->getClient()?->getClientZip() ?? '') > 0) {
     echo H::openTag('div'); //4
      if (strlen($inv->getClient()?->getClientCity() ?? '') > 0) {
       echo H::encode($inv->getClient()?->getClientCity()) . ' ';
      }
      if (strlen($inv->getClient()?->getClientState() ?? '') > 0) {
       echo H::encode($inv->getClient()?->getClientState()) . ' ';
      }
      if (strlen($inv->getClient()?->getClientZip() ?? '') > 0) {
       echo H::encode($inv->getClient()?->getClientZip());
      }
     echo H::closeTag('div'); //4
    }
    if (strlen($inv->getClient()?->getClientState() ?? '') > 0) {
     echo H::tag('div',
      H::encode($inv->getClient()?->getClientState()));
    }
    if (strlen($clientCountry = $inv->getClient()?->getClientCountry() ?? '') > 0) {
     echo H::tag('div', $countryHelper->getCountryName($translator->translate('cldr'), $clientCountry));
    }
    echo H::tag('br', '');
    if (strlen($inv->getClient()?->getClientPhone() ?? '') > 0) {
     echo H::tag('div',
      $translator->translate('phone.abbr') . ': ' .
       H::encode($inv->getClient()?->getClientPhone()));
    }
   echo H::closeTag('div'); //3
  echo H::closeTag('header'); //2
  echo H::openTag('main'); //2
   echo H::openTag('div', $invDetails); //3
    echo H::openTag('table'); //4
     echo H::openTag('tr'); //5
      echo H::tag('td', $translator->translate('date.issued') . ':');
      echo H::tag('td',
       H::encode(!is_string($dateCreated = $inv->getDateCreated()) ?
        $dateCreated->format($dateFmt) : ''));
     echo H::closeTag('tr'); //5
     if ($vat === '1') :
      echo H::openTag('tr'); //5
       echo H::tag('td', $translator->translate('date.supplied') . ':');
       echo H::tag('td',
        H::encode(!is_string($dateSupplied = $inv->getDateSupplied()) ?
         $dateSupplied->format($dateFmt) : ''));
      echo H::closeTag('tr'); //5
     endif;
     echo H::openTag('tr'); //5
      echo H::tag('td', $translator->translate('expires') . ':');
      echo H::tag('td',
       H::encode(!is_string($dateDueNext = $inv->getDateDue()) ?
        $dateDueNext->format($dateFmt) : ''));
     echo H::closeTag('tr'); //5
     echo H::tag('tr', $show_custom_fields ? $top_custom_fields : '')->encode(false);
    echo H::closeTag('table'); //4
   echo H::closeTag('div'); //3
   echo H::openTag('h3', $invTitle); //3
    echo H::tag('b',
     $vat === '0' ? H::encode($translator->translate('invoice') . ' ' .
      ($inv->getNumber() ?? '#')) : '');
   echo H::closeTag('h3'); //3
   echo H::openTag('table', $itemsTable); //3
    echo H::openTag('thead'); //4
     echo H::openTag('tr'); //5
      echo H::tag('th',
       H::encode($translator->translate('item')),
       $thItemName);
      echo H::tag('th',
       H::encode($translator->translate('description')),
       $thItemDesc);
      echo H::tag('th',
       H::encode($translator->translate('qty')),
       $thItemAmt);
      echo H::openTag('th', $thItemPrice); //6
       echo H::encode($translator->translate('price'));
      echo H::closeTag('th'); //6
      if ($show_item_discounts) :
       echo H::tag('th',
        H::encode($translator->translate('discount')),
        $thItemDiscount);
      endif;
      if ($vat === '0') :
       echo H::openTag('th', $thItemPrice); //6
        echo H::encode($translator->translate('tax'));
       echo H::closeTag('th'); //6
       echo H::tag('th', '', $thItemPrice);
       echo H::openTag('th', $thItemPrice); //6
        echo '%';
       echo H::closeTag('th'); //6
      else :
       echo H::openTag('th', $thItemPrice); //6
        echo H::encode($translator->translate('vat.abbreviation', ['term' => $s->activeTaxSchemeTerm()]));
       echo H::closeTag('th'); //6
       echo H::openTag('th', $thItemPrice); //6
        echo '%';
       echo H::closeTag('th'); //6
      endif;
      echo H::tag('th',
       H::encode($translator->translate('total')),
       $thItemTotal);
     echo H::closeTag('tr'); //5
    echo H::closeTag('thead'); //4
    echo H::openTag('tbody'); //4
    if ($items) {
     $rowNum = 0;
     /**
      * @var App\Infrastructure\Persistence\InvItem\InvItem $item
      */
     foreach ($items as $item) {
      $rowNum++;
      $rowClass = ($rowNum % 2 === 1) ? 'odd' : 'even';
      $inv_item_amount = $iiaR->repoInvItemAmountquery($item->reqId());
      echo H::openTag('tr', ['class' => $rowClass]); //5
       echo H::tag('td',
        H::encode($item->getName()));
       echo H::tag('td',
        nl2br(H::encode($item->getDescription())));
       echo H::openTag('td', $tdEnd); //6
        echo H::encode($s->formatAmount($item->getQuantity()));
        if (strlen($item->getProductUnit() ?? '') > 0) :
         echo H::tag('br', '');
         echo H::tag('small',
          H::encode($item->getProductUnit()));
        endif;
       echo H::closeTag('td'); //6
       echo H::tag('td',
        H::encode($s->formatCurrency($item->getPrice())),
        $tdEnd);
       if ($show_item_discounts) :
        echo H::tag('td',
         H::encode($s->formatCurrency($item->getDiscountAmount())),
         $tdEnd);
       endif;
       echo H::openTag('td', $tdEnd); //6
        $quantity   = $item->getQuantity();
        $price      = $item->getPrice();
        $taxPercent = ($item->getTaxRate()?->getTaxRatePercent() ?? 0.00) / 100;
        echo (null !== $quantity && null !== $price && $taxPercent)
         ? H::encode($s->formatCurrency($price * $taxPercent))
         : H::encode($s->formatCurrency(0.00));
       echo H::closeTag('td'); //6
       if ($vat === '0') :
        echo H::tag('td',
         H::encode($s->formatCurrency($inv_item_amount?->getTaxTotal())),
         $tdEnd);
       endif;
       echo H::tag('td',
        H::encode($item->getTaxRate()?->getTaxRatePercent()),
        $tdEnd);
       echo H::tag('td', '', $tdEnd);
      echo H::closeTag('tr'); //5
      $invItemAllowanceCharges = $aciiR->repoInvItemquery($item->reqId());
      /**
       * @var InvItemAllowanceCharge $invItemAllowanceCharge
       */
      foreach ($invItemAllowanceCharges as $invItemAllowanceCharge) {
       echo H::openTag('tr'); //5
        echo H::tag('td',
         H::encode($invItemAllowanceCharge->getAllowanceCharge()?->getReasonCode()));
        echo H::tag('td',
         nl2br(H::encode($invItemAllowanceCharge->getAllowanceCharge()?->getReason())));
        echo H::tag('td', '', $tdEnd);
        $amount   = $invItemAllowanceCharge->getAmount();
        $isCharge = ($invItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == 1 ? true : false);
        echo H::tag('td',
         H::encode(($isCharge ? '' : '(') . $s->formatCurrency($amount) .
          ($isCharge ? '' : ')')),
         $tdEnd);
        $vatInvItem = $invItemAllowanceCharge->getVatOrTax();
        echo H::tag('td',
         H::encode(($isCharge ? '' : '(') . $s->formatCurrency($vatInvItem) .
          ($isCharge ? '' : ')')),
         $tdEnd);
        echo H::tag('td', '', $tdEnd);
        $percent =
         $invItemAllowanceCharge->getAllowanceCharge()?->getTaxRate()?->getTaxRatePercent();
        echo H::tag('td',
         H::encode($percent ?? 0.00),
         $tdEnd);
        echo H::tag('td', '', $tdEnd);
        echo H::tag('td', '', $tdEnd);
       echo H::closeTag('tr'); //5
      }
     }
    }
    echo H::closeTag('tbody'); //4
    echo H::openTag('tbody', $invSums); //4
    /** Price Discount Quantity **/
    echo H::openTag('tr'); //5
     if ($vat === '0') :
      echo H::tag('td',
       H::encode($translator->translate('subtotal')) . ' (' .
        H::encode($translator->translate('price')) . '-' .
         H::encode($translator->translate('discount')) . ') x ' .
          H::encode($translator->translate('qty')),
       $colspanAttr);
     else :
      echo H::tag('td',
       H::encode($translator->translate('subtotal')),
       $colspanAttr);
     endif;
     echo H::tag('td', '', $tdEnd);
     echo H::tag('td',
      H::encode($s->formatCurrency($inv_amount->getItemSubtotal())),
      $tdEnd);
    echo H::closeTag('tr'); //5
    /** Item Tax **/
    if ($inv_amount->getItemTaxTotal() > 0) :
     echo H::openTag('tr'); //5
      echo H::tag('td',
       H::encode($vat === '1' ? $translator->translate('vat.break.down', ['term' => $s->activeTaxSchemeTerm()]) :
        $translator->translate('item.tax')),
       $colspanAttr);
      echo H::tag('td', '', $tdEnd);
      echo H::tag('td',
       H::tag('b',
        H::encode($s->formatCurrency($inv_amount->getItemTaxTotal()))),
       $tdEnd);
     echo H::closeTag('tr'); //5
    endif;
    if ($vat == '0') :
     /**
      * @var App\Infrastructure\Persistence\InvTaxRate\InvTaxRate $inv_tax_rate
      */
     foreach ($inv_tax_rates as $inv_tax_rate) :
      echo H::openTag('tr'); //5
       echo H::tag('td',
        H::encode($inv_tax_rate->getTaxRate()?->getTaxRateName()),
        $colspanAttr);
       echo H::tag('td', $inv_tax_rate->getTaxRate()?->getTaxRatePercent(), $tdEnd);
       echo H::tag('td',
        H::tag('b',
         H::encode($s->formatCurrency($inv_tax_rate->getInvTaxRateAmount()))),
        $tdEnd);
      echo H::closeTag('tr'); //5
     endforeach;
    endif;
    if ($vat == '0') :
     if ($inv->getDiscountAmount() !== 0.00) :
      echo H::openTag('tr'); //5
       echo H::tag('td',
        H::encode($translator->translate('discount')),
        $colspanAttr);
       echo H::tag('td', '', $tdEnd);
       echo H::tag('td',
        H::tag('b',
         H::encode($s->formatCurrency($inv->getDiscountAmount()))),
        $tdEnd);
      echo H::closeTag('tr'); //5
     endif;
    endif;
    echo H::openTag('tr'); //5
     echo H::tag('td',
      H::encode($translator->translate('allowance.or.charge.shipping.handling.packaging')),
      ['colspan' => '6', 'class' => 'text-end']);
     echo H::tag('td', '', $tdEnd);
     echo H::tag('td',
      H::encode($s->formatCurrency($inv_amount->getPackHandleShipTotal())),
      $tdEnd);
    echo H::closeTag('tr'); //5
    echo H::openTag('tr'); //5
     echo H::tag('td',
      H::encode($vat == '1'
       ? $translator->translate('allowance.or.charge.shipping.handling.packaging.vat', ['term' => $s->activeTaxSchemeTerm()])
       : $translator->translate('allowance.or.charge.shipping.handling.packaging.tax')),
      ['colspan' => '6', 'class' => 'text-end']);
     $total = $inv_amount->getPackHandleShipTotal();
     $tax   = $inv_amount->getPackHandleShipTax();
     echo H::tag('td',
      $total != 0 ? number_format(100 * ($tax / $total), 2) : '0.00',
      $tdEnd);
     echo H::tag('td',
      H::encode($s->formatCurrency($inv_amount->getPackHandleShipTax())),
      $tdEnd);
    echo H::closeTag('tr'); //5
    echo H::openTag('tr'); //5
     echo H::tag('td',
      H::tag('b',
       H::encode($translator->translate('total'))),
      $colspanAttr);
     echo H::tag('td', '', $tdEnd);
     echo H::tag('td',
      H::tag('b',
       H::encode($s->formatCurrency($inv_amount->getTotal()))),
      $tdEnd);
    echo H::closeTag('tr'); //5
   echo H::closeTag('tbody'); //4
   echo H::closeTag('table'); //3
   echo H::openTag('div', $pageBreak); //3
    echo $inv_allowance_charges;
   echo H::closeTag('div'); //3
   echo H::openTag('div'); //3
    echo $delivery_location;
   echo H::closeTag('div'); //3
  echo H::closeTag('main'); //2
  echo H::tag('watermarkimage', '', ['src' => basename(__FILE__, '.php') .
   '.png', 'alpha' => '0.1']);
  echo H::openTag('footer', $notesClass); //2
   echo H::tag('br', '');
   if ($inv->getTerms()) :
    echo H::openTag('div'); //3
     echo H::tag('b',
      H::encode($translator->translate('terms')));
     echo H::tag('br', '');
     $paymentTermArray = $s->getPaymentTermArray($translator);
     $termsKey = (int) $inv->getTerms() ?: 0;
     $terms = (string) $paymentTermArray[$termsKey];
     echo nl2br(H::encode($terms));
    echo H::closeTag('div'); //3
    echo H::tag('br', '');
   endif;
   if (strlen($inv->getNote() ?? '') > 0) :
    echo H::openTag('div'); //3
     echo H::tag('b',
      H::encode($translator->translate('note')));
     echo H::tag('br', '');
     echo nl2br(H::encode($inv->getNote()));
    echo H::closeTag('div'); //3
    echo H::tag('br', '');
   endif;
   echo H::tag('div', $show_custom_fields ? $view_custom_fields : '')->encode(false);
  echo H::closeTag('footer'); //2
 echo H::closeTag('body'); //1
echo H::closeTag('html'); //0
