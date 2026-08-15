<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Img;

/**
 * Related logic: see App\Invoice\Helpers\PdfHelper generate_inv_html
 * Related logic: see InvController function urlKey
 * @var App\Infrastructure\Persistence\Client\Client $client
 * @var App\Infrastructure\Persistence\Inv\Inv $inv
 * @var App\Infrastructure\Persistence\InvAmount\InvAmount $inv_amount
 * @var App\Infrastructure\Persistence\PaymentMethod\PaymentMethod $payment_method
 * @var App\Infrastructure\Persistence\UserInv\UserInv $userInv
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository $aciiR
 * @var App\Invoice\InvItemAmount\InvItemAmountRepository $iiaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $custom_fields
 * @var array $inv_tax_rates
 * @var array $items
 * @var array $paymentTermsArray
 * @var bool $is_overdue
 * @var float $balance
 *
 * Related logic: see src\ViewInjection\LayoutViewInjection
 * @var string $companyLogoFileName
 * @var string $logoPath
 * @var int $companyLogoWidth
 * @var int $companyLogoHeight
 *
 * @var string $_language
 * @var string $alert
 * @var string $attachments
 * @var string $client_chosen_gateway
 * @var string $inv_url_key
 * @var string $downloadPdfActionName
 * @psalm-var array<string, Stringable|null|scalar> $downloadPdfActionArguments
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
   echo $translator->translate('invoice');
   echo ' ';
   echo $inv->getNumber();
  echo H::closeTag('title'); //2
  echo H::tag('link', '', ['rel' => 'stylesheet', 'href' => '/assets/css/invoice-documents.css']);
  echo H::tag('meta', '', ['name' => 'viewport', 'content' => 'width=device-width,initial-scale=1']);
 echo H::closeTag('head'); //1
 echo H::openTag('body'); //1
  echo $alert;
  echo H::openTag('section', ['class' => 'py-3 py-md-5']); //2
   echo H::openTag('div', $container); //3
    echo H::openTag('div', $idContent); //4
     echo H::openTag('div', $wpHeader); //5
      echo H::openTag('h2'); //6
       echo $translator->translate('invoice');
       echo '&nbsp;';
       echo $inv->getNumber();
      echo H::closeTag('h2'); //6
      echo H::openTag('div', $btnGroup); //6
       echo H::openTag('a', ['href' => $urlGenerator->generate('inv/pdfDownloadIncludeCf', ['url_key' => $inv_url_key, '_language' => $_language ?: 'en']), 'class' => 'btn btn-primary']); //7
        echo H::tag('i', '', ['class' => 'bi bi-file-pdf']);
        echo $translator->translate('download.pdf') . '=>' . $translator->translate('yes') . ' ' . $translator->translate('custom.fields');
       echo H::closeTag('a'); //7
       echo H::openTag('a', ['href' => $urlGenerator->generate('inv/pdfDownloadExcludeCf', ['url_key' => $inv_url_key, '_language' => $_language ?: 'en']), 'class' => 'btn btn-danger']); //7
        echo H::tag('i', '', ['class' => 'bi bi-file-pdf']);
        echo $translator->translate('download.pdf') . '=>' . $translator->translate('no') . ' ' . $translator->translate('custom.fields');
       echo H::closeTag('a'); //7
       if ($s->getSetting('enable_online_payments') == 1 && $inv_amount->getBalance() > 0) :
        echo H::openTag('a', ['href' => $urlGenerator->generate('paymentinformation/inform', ['url_key' => $inv_url_key, 'gateway' => $client_chosen_gateway, '_language' => $_language ?: 'en']), 'class' => 'btn btn-success']); //7
         echo H::tag('i', '', ['class' => 'bi bi-credit-card']);
         echo $translator->translate('pay.now') . ' ' . str_replace('_', ' ', $client_chosen_gateway);
        echo H::closeTag('a'); //7
       endif;
       if ($s->getSetting('enable_online_payments') == 1 && $inv_amount->getBalance() == 0) :
        echo H::tag('a', $translator->translate('paid'), ['href' => '', 'class' => 'btn btn-success']);
       endif;
      echo H::closeTag('div'); //6
     echo H::closeTag('div'); //5
     echo H::tag('hr', '');
     echo H::openTag('div', $divInvoice); //5
      echo new Img()->width($companyLogoWidth)->height($companyLogoHeight)->src($logoPath);
      echo H::tag('br', '');
      echo H::tag('br', '');
      echo H::openTag('div', $row); //6
       echo H::openTag('div', $col12Md6Lg5); //7
        echo H::tag('h4',
         H::encode($userInv->getName()));
        echo H::openTag('p'); //8
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
        echo H::closeTag('p'); //8
       echo H::closeTag('div'); //7
       echo H::tag('div', '', $colLg2);
       echo H::openTag('div', $col12Md6Lg5R); //7
        echo H::tag('h4',
         H::encode($clientHelper->formatClient($client)));
        echo H::openTag('p'); //8
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
         if (strlen($client->getClientPhone() ?? '') > 0) {
          echo $translator->translate('phone.abbr') . ': ' . H::encode($client->getClientPhone());
          echo H::tag('br', '');
         }
        echo H::closeTag('p'); //8
        echo H::tag('br', '');
        echo H::openTag('table', $tableCondensed); //8
         echo H::openTag('tbody'); //9
          echo H::openTag('tr'); //10
           echo H::tag('td', $translator->translate('date'));
           echo H::tag('td', $inv->getDateCreated()->format('Y-m-d'), ['style' => 'text-align:right;']);
          echo H::closeTag('tr'); //10
          echo H::openTag('tr', ['class' => $is_overdue ? 'overdue' : '']); //10
           echo H::tag('td', $translator->translate('due.date'));
           echo H::tag('td', $inv->getDateDue()->format('Y-m-d'), $textRight);
          echo H::closeTag('tr'); //10
          echo H::openTag('tr', ['class' => $is_overdue ? 'overdue' : '']); //10
           echo H::tag('td', $translator->translate('amount.due'));
           echo H::tag('td', $numberHelper->formatCurrency($inv_amount->getBalance() ?? 0.00), ['style' => 'text-align:right;']);
          echo H::closeTag('tr'); //10
          echo H::openTag('tr'); //10
           echo H::tag('td', $translator->translate('payment.method') . ':');
           echo H::tag('td',
            H::encode($payment_method->getName()));
          echo H::closeTag('tr'); //10
         echo H::closeTag('tbody'); //9
        echo H::closeTag('table'); //8
       echo H::closeTag('div'); //7
      echo H::closeTag('div'); //6
      echo H::tag('br', '');
      echo H::openTag('div', $invoiceItems); //6
       echo H::openTag('div', $tableResponsive); //7
        echo H::openTag('table', $tableStriped); //8
         echo H::openTag('thead'); //9
          echo H::openTag('tr'); //10
           echo H::tag('th', $translator->translate('item'));
           echo H::tag('th', $translator->translate('description'));
           echo H::tag('th', $translator->translate('qty'), $textRight);
           echo H::tag('th', $translator->translate('price'), $textRight);
           echo H::tag('th', $translator->translate('discount'), $textRight);
           echo H::tag('th', $translator->translate('total'), $textRight);
          echo H::closeTag('tr'); //10
         echo H::closeTag('thead'); //9
         echo H::openTag('tbody'); //9
         /**
          * @var App\Infrastructure\Persistence\InvItem\InvItem $item
          */
         foreach ($items as $item) {
          echo H::openTag('tr'); //10
           echo H::tag('td',
            H::encode($item->getName()));
           echo H::tag('td',
            nl2br(H::encode($item->getDescription())));
           echo H::openTag('td', $amtClass); //11
            echo $numberHelper->formatAmount($item->getQuantity());
            if (strlen($item->getProductUnit() ?? '') > 0) :
             echo H::tag('br', '');
             echo H::tag('small',
              H::encode($item->getProductUnit()));
            endif;
           echo H::closeTag('td'); //11
           echo H::tag('td', $numberHelper->formatCurrency($item->getPrice()), $amtClass);
           echo H::tag('td', $numberHelper->formatCurrency($item->getDiscountAmount()), $amtClass);
           $query = $iiaR->repoInvItemAmountquery($item->reqId());
           echo H::tag('td', $numberHelper->formatCurrency(null !== $query ? $query->getSubtotal() : 0.00), $amtClass);
          echo H::closeTag('tr'); //10
          if ($s->getSetting('enable_peppol') == '1') {
           $invItemAllowanceCharges = $aciiR->repoInvItemquery($item->reqId());
           /**
            * @var InvItemAllowanceCharge $invItemAllowanceCharge
            */
           foreach ($invItemAllowanceCharges as $invItemAllowanceCharge) {
            $isCharge = ($invItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == 1 ? true : false);
            echo H::openTag('tr'); //10
             echo H::openTag('td', ['colspan' => '5']); //11
              echo H::openTag('b'); //12
               echo $invItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == '1'
                ? $translator->translate('allowance.or.charge.charge')
                : '(' . $translator->translate('allowance.or.charge.allowance') . ')';
              echo H::closeTag('b'); //12
              echo $translator->translate('allowance.or.charge.reason.code') . ': ' . ($invItemAllowanceCharge->getAllowanceCharge()?->getReasonCode() ?? '#');
              echo ' - ';
              echo $translator->translate('allowance.or.charge.reason') . ': ' . ($invItemAllowanceCharge->getAllowanceCharge()?->getReason() ?? '#');
             echo H::closeTag('td'); //11
             echo H::openTag('td', $amtClass); //11
              echo H::openTag('b'); //12
               echo ($isCharge ? '' : '(') . $numberHelper->formatCurrency($invItemAllowanceCharge->getAmount()) . ($isCharge ? '' : ')');
              echo H::closeTag('b'); //12
             echo H::closeTag('td'); //11
             echo H::openTag('td', $amtClass); //11
              echo H::openTag('b'); //12
               $vatInvItem = $invItemAllowanceCharge->getVatOrTax();
               echo ($isCharge ? '' : '(') . $numberHelper->formatCurrency($vatInvItem) . ($isCharge ? '' : ')');
              echo H::closeTag('b'); //12
             echo H::closeTag('td'); //11
            echo H::closeTag('tr'); //10
           }
          }
         }
         echo H::openTag('tr'); //10
          echo H::tag('td', '', ['colspan' => '4']);
          echo H::tag('td', $translator->translate('subtotal') . ':', $textRight);
          echo H::openTag('td', $amtClass); //11
           echo H::tag('b', $numberHelper->formatCurrency($inv_amount->getItemSubtotal()));
          echo H::closeTag('td'); //11
         echo H::closeTag('tr'); //10
         if ($inv_amount->getItemTaxTotal() > 0) :
          echo H::openTag('tr'); //10
           echo H::tag('td', '', $noBB4);
           echo H::openTag('td', $textRight); //11
            echo $vat === '0' ? $translator->translate('item.tax') : $translator->translate('vat.abbreviation');
           echo H::closeTag('td'); //11
           echo H::openTag('td', $amtClass); //11
            $invAmountItemTaxTotal = $inv_amount->getItemTaxTotal();
            echo H::tag('b', ($invAmountItemTaxTotal >= 0.00 ? $numberHelper->formatCurrency($invAmountItemTaxTotal) : ''));
           echo H::closeTag('td'); //11
          echo H::closeTag('tr'); //10
         endif;
         if ($s->getSetting('enable_peppol') == '1') {
          if ($inv_amount->getPackhandleshipTotal() != 0.00) :
           echo H::openTag('tr'); //10
            echo H::tag('td', '', $noBB4);
            echo H::tag('td', $translator->translate('allowance.or.charge.shipping.handling.packaging'), $textRight);
            echo H::tag('td',
             H::tag('b', $numberHelper->formatCurrency($inv_amount->getPackhandleshipTotal())),
             $amtClass);
           echo H::closeTag('tr'); //10
          endif;
          if ($inv_amount->getPackhandleshipTax() != 0.00) :
           echo H::openTag('tr'); //10
            echo H::tag('td', '', $noBB4);
            echo H::openTag('td', $textRight); //11
             echo $vat == '1'
              ? $translator->translate('allowance.or.charge.shipping.handling.packaging.vat')
              : $translator->translate('allowance.or.charge.shipping.handling.packaging.tax');
            echo H::closeTag('td'); //11
            echo H::tag('td',
             H::tag('b', $numberHelper->formatCurrency($inv_amount->getPackhandleshipTax())),
             $amtClass);
           echo H::closeTag('tr'); //10
          endif;
         }
         if ($vat === '0') :
          /**
           * @var App\Infrastructure\Persistence\InvTaxRate\InvTaxRate $inv_tax_rate
           */
          foreach ($inv_tax_rates as $inv_tax_rate) :
           echo H::openTag('tr'); //10
            echo H::tag('td', '', $noBB4);
            echo H::openTag('td', $textRight); //11
             $taxRatePercent = $inv_tax_rate->getTaxRate()?->getTaxRatePercent();
             $taxRateName    = $inv_tax_rate->getTaxRate()?->getTaxRateName();
             if (($taxRatePercent >= 0.00) && (strlen($taxRateName ?? '') > 0)) {
              echo H::encode(($taxRateName ?? '#') . ' ' . ($numberHelper->formatAmount($taxRatePercent) ?? '#'));
             }
             echo '%';
            echo H::closeTag('td'); //11
            echo H::openTag('td', $amtClass); //11
             $invTaxRate = $inv_tax_rate->getInvTaxRateAmount();
             if ($invTaxRate >= 0.00) {
              echo H::tag('b', $numberHelper->formatCurrency($invTaxRate));
             }
            echo H::closeTag('td'); //11
           echo H::closeTag('tr'); //10
          endforeach;
         endif;
         if ($vat === '0') :
          echo H::openTag('tr'); //10
           echo H::tag('td', '', $noBB4);
           echo H::tag('td', $translator->translate('discount') . ':', $textRight);
           echo H::openTag('td', $amtClass); //11
            $discountAmount = $inv->getDiscountAmount();
            if ($discountAmount >= 0.00) {
             echo H::tag('b', $numberHelper->formatAmount($discountAmount));
            }
           echo H::closeTag('td'); //11
          echo H::closeTag('tr'); //10
         endif;
         echo H::openTag('tr'); //10
          echo H::tag('td', '', $noBB4);
          echo H::tag('td', $translator->translate('total') . ':', $textRight);
          echo H::tag('td',
           H::tag('b', $numberHelper->formatCurrency($inv_amount->getTotal())),
           $amtClass);
         echo H::closeTag('tr'); //10
         echo H::openTag('tr'); //10
          echo H::tag('td', '', $noBB4);
          echo H::tag('td', $translator->translate('paid'), $textRight);
          echo H::tag('td',
           H::tag('b', $numberHelper->formatCurrency($inv_amount->getPaid())),
           $amtClass);
         echo H::closeTag('tr'); //10
         echo H::openTag('tr', ['class' => $is_overdue ? 'overdue' : 'text-success']); //10
          echo H::tag('td', '', $noBB4);
          echo H::tag('td', $translator->translate('balance'), $textRight);
          echo H::tag('td',
           H::tag('b', $numberHelper->formatCurrency($balance ?: 0.00)),
           $amtClass);
         echo H::closeTag('tr'); //10
         echo H::closeTag('tbody'); //9
        echo H::closeTag('table'); //8
       echo H::closeTag('div'); //7
       if ($inv_amount->getBalance() == 0) {
        echo H::tag('img', '', ['src' => '/img/paid.png', 'class' => 'paid-stamp']);
       }
       if ($is_overdue) {
        echo H::tag('img', '', ['src' => '/img/overdue.png', 'class' => 'overdue-stamp']);
       }
      echo H::closeTag('div'); //6
      echo H::tag('hr', '');
      echo H::openTag('div'); //6
       if ($inv->getTerms()) :
        echo H::openTag('div', ['class' => 'col-12 col-md-6']); //7
         echo H::tag('h4', $translator->translate('terms'));
         echo H::tag('p',
          nl2br(H::encode($paymentTermsArray[$inv->getTerms()] ?? '')));
        echo H::closeTag('div'); //7
       endif;
       echo $attachments;
      echo H::closeTag('div'); //6
     echo H::closeTag('div'); //5
    echo H::closeTag('div'); //4
   echo H::closeTag('div'); //3
  echo H::closeTag('section'); //2
 echo H::closeTag('body'); //1
echo H::closeTag('html'); //0
