<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var int $statusCode
 * @var array<string, mixed> $result
 * @var string $periodKey
 */

$success = $statusCode === 200 || $statusCode === 201;

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']);

   echo H::openTag('div', ['class' => 'card']);
    echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
     echo H::tag('strong', 'VAT Return Submission — Period ' . H::encode($periodKey));
     echo H::a('← Obligations', '/backend/hmrc/vatObligations', ['class' => 'btn btn-sm btn-outline-secondary']);
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'card-body']);

     if ($success) {
         echo H::tag('div', 'VAT return accepted by HMRC (HTTP ' . $statusCode . ').', ['class' => 'alert alert-success']);
         if (isset($result['formBundleNumber'])) {
             echo H::openTag('p');
              echo H::tag('strong', 'Form Bundle Number: ');
              echo H::encode((string) $result['formBundleNumber']);
             echo H::closeTag('p');
         }
         if (isset($result['paymentIndicator'])) {
             echo H::openTag('p');
              echo H::tag('strong', 'Payment Indicator: ');
              echo H::encode((string) $result['paymentIndicator']);
             echo H::closeTag('p');
         }
         if (isset($result['chargeRefNumber'])) {
             echo H::openTag('p');
              echo H::tag('strong', 'Charge Reference: ');
              echo H::encode((string) $result['chargeRefNumber']);
             echo H::closeTag('p');
         }
         if (isset($result['processingDate'])) {
             echo H::openTag('p');
              echo H::tag('strong', 'Processing Date: ');
              echo H::encode((string) $result['processingDate']);
             echo H::closeTag('p');
         }
     } else {
         echo H::tag('div', 'HMRC returned HTTP ' . $statusCode . '.', ['class' => 'alert alert-danger']);
         if (isset($result['code'])) {
             echo H::openTag('p');
              echo H::tag('strong', 'Error Code: ');
              echo H::encode((string) $result['code']);
             echo H::closeTag('p');
         }
         if (isset($result['message'])) {
             echo H::openTag('p');
              echo H::tag('strong', 'Message: ');
              echo H::encode((string) $result['message']);
             echo H::closeTag('p');
         }

         // Live-testing fix 2026-09-10: a top-level code/message alone
         // (e.g. "BUSINESS_ERROR" / "Business validation error") is
         // just HMRC's generic wrapper -- confirmed against HMRC's own
         // reference guide that the actual per-field detail lives in a
         // nested errors[] array (each with its own code/message/path)
         // this view never rendered at all, so every real validation
         // failure looked identical and unactionable no matter what was
         // actually wrong with the submitted return.
         /** @var list<array<string, mixed>> $errors */
         $errors = (array) ($result['errors'] ?? []);
         if ($errors !== []) {
             echo H::tag('p', H::tag('strong', 'Details'), ['class' => 'mb-1 mt-3']);
             echo H::openTag('div', ['class' => 'table-responsive']);
             echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
             echo H::openTag('thead', ['class' => 'table-light']);
             echo H::openTag('tr');
             foreach (['Code', 'Field', 'Message'] as $col) {
                 echo H::tag('th', $col);
             }
             echo H::closeTag('tr');
             echo H::closeTag('thead');
             echo H::openTag('tbody');
             foreach ($errors as $err) {
                 $errCode = isset($err['code']) ? (string) $err['code'] : '—';
                 $errPath = isset($err['path']) ? (string) $err['path'] : '—';
                 $errMessage = isset($err['message'])
                     ? (string) $err['message']
                     : '—';

                 echo H::openTag('tr');
                 echo H::tag('td', H::tag('code', H::encode($errCode)));
                 echo H::tag('td', H::tag('code', H::encode($errPath)));
                 echo H::tag('td', H::encode($errMessage));
                 echo H::closeTag('tr');
             }
             echo H::closeTag('tbody');
             echo H::closeTag('table');
             echo H::closeTag('div');
         }
     }

    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
