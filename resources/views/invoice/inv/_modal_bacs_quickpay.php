<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var App\Invoice\PaymentInformation\Service\BacsPaymentService $bacsPaymentService
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var int $decimalPlaces
 */

$bacsConfigured = $bacsPaymentService->isBacsConfigured();

// Raw values for clipboard data attributes; encoded versions for display.
$rawSortCode      = $bacsPaymentService->getSortCode();
$rawAccountNumber = $bacsPaymentService->getAccountNumber();
$rawPayeeName     = $bacsPaymentService->getBeneficiaryName();

$sortCode      = H::encode($rawSortCode);
$accountNumber = H::encode($rawAccountNumber);
$payeeName     = H::encode($rawPayeeName);
$currency      = H::encode($s->getSetting('currency_symbol') ?: '£');

// Copy-button helper — produces a small btn with data-clipboard-text.
// Yiisoft encodes the attribute value; the browser decodes it so ClipboardJS
// receives the correct raw string.
$copyBtn = static fn(string $raw, string $title): string =>
 H::openTag('button', [
  'type'                => 'button',
  'class'               => 'bacs-copy-btn btn btn-link p-0 ms-2 text-muted lh-1',
  'data-clipboard-text' => $raw,
  'data-bs-toggle'      => 'tooltip',
  'title'               => $title,
  'aria-label'          => $title,
 ])
 . H::openTag('i', ['class' => 'bi bi-clipboard fs-6'])
 . H::closeTag('i')
 . H::closeTag('button');

echo H::openTag('div', [
 'class'           => 'modal fade',
 'id'              => 'bacsQuickPayModal',
 'tabindex'        => '-1',
 'aria-labelledby' => 'bacsQuickPayModalLabel',
 'aria-hidden'     => 'true',
]); //0
 echo H::openTag('div', ['class' => 'modal-dialog modal-lg modal-dialog-centered']); //1
  echo H::openTag('div', ['class' => 'modal-content']); //2

   echo H::openTag('div', ['class' => 'modal-header bg-info text-dark']); //3
    echo H::openTag('h5', ['class' => 'modal-title', 'id' => 'bacsQuickPayModalLabel']); //4
     echo $translator->translate('bacs.quick.pay');
    echo H::closeTag('h5'); //4
    echo H::tag('button', '', [
     'type'            => 'button',
     'class'           => 'btn-close',
     'data-bs-dismiss' => 'modal',
     'aria-label'      => 'Close',
    ]);
   echo H::closeTag('div'); //3

   echo H::openTag('div', ['class' => 'modal-body']); //3

    if (!$bacsConfigured) {
     echo H::openTag('div', ['class' => 'alert alert-warning d-flex align-items-center gap-3']); //4
      echo H::openTag('div'); //5
       echo H::openTag('strong'); //6
        echo $translator->translate('bacs.not.yet.configured');
       echo H::closeTag('strong'); //6
       echo H::tag('br');
       echo H::openTag('small', ['class' => 'text-muted']); //6
        echo $translator->translate('bacs.not.yet.configured.hint');
       echo H::closeTag('small'); //6
      echo H::closeTag('div'); //5
     echo H::closeTag('div'); //4
    } else {

     // ── Bank details card ────────────────────────────────────────────────────
     echo H::openTag('div', ['class' => 'card mb-3']); //4
      echo H::openTag('div', ['class' => 'card-body']); //5
       echo H::openTag('h6', ['class' => 'card-title fw-bold']); //6
        echo $translator->translate('bacs.bank.details');
       echo H::closeTag('h6'); //6
       echo H::openTag('table', ['class' => 'table table-sm table-borderless mb-0']); //6
        echo H::openTag('tr'); //7
         echo H::openTag('td', ['class' => 'text-muted w-50']); //8
          echo $translator->translate('bacs.payee');
         echo H::closeTag('td'); //8
         echo H::openTag('td', ['class' => 'fw-bold fs-5']); //8
          echo $payeeName;
          echo $copyBtn($rawPayeeName, $translator->translate('bacs.copy'));
         echo H::closeTag('td'); //8
        echo H::closeTag('tr'); //7
        echo H::openTag('tr'); //7
         echo H::openTag('td', ['class' => 'text-muted']); //8
          echo $translator->translate('bacs.sort.code');
         echo H::closeTag('td'); //8
         echo H::openTag('td', ['class' => 'fw-bold fs-5 font-monospace']); //8
          echo $sortCode;
          echo $copyBtn($rawSortCode, $translator->translate('bacs.copy'));
         echo H::closeTag('td'); //8
        echo H::closeTag('tr'); //7
        echo H::openTag('tr'); //7
         echo H::openTag('td', ['class' => 'text-muted']); //8
          echo $translator->translate('bacs.account.number');
         echo H::closeTag('td'); //8
         echo H::openTag('td', ['class' => 'fw-bold fs-5 font-monospace']); //8
          echo $accountNumber;
          echo $copyBtn($rawAccountNumber, $translator->translate('bacs.copy'));
         echo H::closeTag('td'); //8
        echo H::closeTag('tr'); //7
       echo H::closeTag('table'); //6
      echo H::closeTag('div'); //5
     echo H::closeTag('div'); //4

     if (!empty($bacsUnpaidInvs)) {
      echo H::openTag('h6', ['class' => 'fw-bold mb-2']); //4
       echo $translator->translate('bacs.outstanding.invoices');
      echo H::closeTag('h6'); //4
      /** 
       * @var App\Infrastructure\Persistence\Inv\Inv $inv
       */ 
      foreach ($bacsUnpaidInvs as $inv) {
       $invId      = $inv->reqId();
       $invNum     = H::encode($inv->getNumber() ?? '#');
       $balance    = $inv->getInvAmount()->getBalance() ?? 0.00;
       $rawRef     = $inv->getNumber() ?? $bacsPaymentService->generateReference($invId);
       $ref        = H::encode($rawRef);
       $rawAmount  = number_format($balance, $decimalPlaces);
       $qrText     = $bacsPaymentService->buildQrContent($rawRef, $balance);
       $qrUri      = $bacsPaymentService->renderQrDataUri($qrText);
       $qrSize     = (int) ($s->getSetting('qr_height_and_width') ?: 120);

       echo H::openTag('div', ['class' => 'card mb-3']); //4
        echo H::openTag('div', ['class' => 'card-body d-flex align-items-start gap-4']); //5
         echo H::openTag('div', ['class' => 'flex-grow-1']); //6
          echo H::openTag('p', ['class' => 'mb-1']); //7
           echo H::openTag('span', ['class' => 'text-muted']); //8
            echo $translator->translate('number') . ':';
           echo H::closeTag('span'); //8
           echo H::openTag('span', ['class' => 'fw-bold ms-1']); //8
            echo $invNum;
           echo H::closeTag('span'); //8
          echo H::closeTag('p'); //7
          echo H::openTag('p', ['class' => 'mb-1']); //7
           echo H::openTag('span', ['class' => 'text-muted']); //8
            echo $translator->translate('balance') . ':';
           echo H::closeTag('span'); //8
           echo H::openTag('span', ['class' => 'fw-bold text-danger fs-5 ms-1']); //8
            echo $currency . H::encode($rawAmount);
           echo H::closeTag('span'); //8
           echo $copyBtn($rawAmount, $translator->translate('bacs.copy'));
          echo H::closeTag('p'); //7
          echo H::openTag('p', ['class' => 'mb-0']); //7
           echo H::openTag('span', ['class' => 'text-muted']); //8
            echo $translator->translate('bacs.reference') . ':';
           echo H::closeTag('span'); //8
           echo H::openTag('code', ['class' => 'fs-6 user-select-all ms-1']); //8
            echo $ref;
           echo H::closeTag('code'); //8
           echo $copyBtn($rawRef, $translator->translate('bacs.copy'));
          echo H::closeTag('p'); //7
         echo H::closeTag('div'); //6

         // ── QR code column ────────────────────────────────────────────────
         echo H::openTag('div', ['class' => 'text-center flex-shrink-0']); //6
          echo H::tag('img', '', [
           'src'    => H::encode($qrUri),
           'width'  => $qrSize,
           'height' => $qrSize,
           'alt'    => $translator->translate('qr.code'),
           'title'  => $translator->translate('bacs.scan.to.pay'),
          ]);
          echo H::openTag('div', ['class' => 'mt-1']); //7
           echo H::openTag('small', ['class' => 'text-muted d-block fw-semibold']); //8
            echo $translator->translate('bacs.scan.to.pay');
           echo H::closeTag('small'); //8
           echo H::openTag('small', ['class' => 'text-muted d-block']); //8
            echo $translator->translate('bacs.scan.qr.hint');
           echo H::closeTag('small'); //8
          echo H::closeTag('div'); //7
         echo H::closeTag('div'); //6

        echo H::closeTag('div'); //5
       echo H::closeTag('div'); //4
      }
     } else {
      echo H::openTag('div', ['class' => 'alert alert-info d-flex align-items-center gap-3']); //4
       echo H::openTag('div'); //5
        echo H::openTag('strong'); //6
         echo $translator->translate('bacs.no.outstanding.invoices');
        echo H::closeTag('strong'); //6
        echo H::tag('br');
        echo H::openTag('small', ['class' => 'text-muted']); //6
         echo $translator->translate('bacs.no.outstanding.invoices.hint');
        echo H::closeTag('small'); //6
       echo H::closeTag('div'); //5
      echo H::closeTag('div'); //4
     }

     echo H::openTag('p', ['class' => 'text-muted small mt-3 mb-0']); //4
      echo $translator->translate('bacs.payment.instructions');
     echo H::closeTag('p'); //4

    }

   echo H::closeTag('div'); //3

   echo H::openTag('div', ['class' => 'modal-footer']); //3
    echo H::openTag('button', [
     'type'            => 'button',
     'class'           => 'btn btn-secondary',
     'data-bs-dismiss' => 'modal',
    ]); //4
     echo $translator->translate('close');
    echo H::closeTag('button'); //4
   echo H::closeTag('div'); //3

  echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0

// Initialise ClipboardJS once the modal HTML is in the DOM.
// Guard prevents double-init if the partial is ever included more than once.
echo H::script(
    "if(!window.bacsClipInit){" .
    "window.bacsClipInit=true;" .
    "var bacsClip=new ClipboardJS('.bacs-copy-btn');" .
    "bacsClip.on('success',function(e){" .
    " var b=e.trigger,o=b.innerHTML;" .
    " b.innerHTML='<i class=\"bi bi-clipboard-check fs-6 text-success\"></i>';" .
    " setTimeout(function(){b.innerHTML=o;},1500);" .
    " e.clearSelection();" .
    "});}"
);
