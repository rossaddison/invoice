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
     }

    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
