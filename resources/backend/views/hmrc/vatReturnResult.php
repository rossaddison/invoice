<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var string $alert
 * @var int $statusCode
 * @var array<string, mixed> $result
 * @var string $periodKey
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

$success = $statusCode === 200 || $statusCode === 201;

// Live-testing fix 2026-09-10: the failure banner used to read only
// "HMRC returned HTTP {statusCode}." -- reported live as not
// meaningful on its own. Maps every error code documented for this
// endpoint in HMRC's own vat-api/1.0 reference guide (fetched live,
// not guessed) to a plain-English translation key; unmapped codes
// still fall back to the raw HTTP status rather than showing nothing.
$vatReturnErrorMessageKey = static function (string $code): ?string {
    return match ($code) {
        'VRN_INVALID' => 'mtd.vat.return.error.vrn_invalid',
        'PERIOD_KEY_INVALID' => 'mtd.vat.return.error.period_key_invalid',
        'INVALID_REQUEST' => 'mtd.vat.return.error.invalid_request',
        'VAT_TOTAL_VALUE' => 'mtd.vat.return.error.vat_total_value',
        'VAT_NET_VALUE' => 'mtd.vat.return.error.vat_net_value',
        'INVALID_NUMERIC_VALUE' =>
            'mtd.vat.return.error.invalid_numeric_value',
        'INVALID_MONETARY_AMOUNT' =>
            'mtd.vat.return.error.invalid_monetary_amount',
        'NOT_FINALISED' => 'mtd.vat.return.error.not_finalised',
        'DUPLICATE_SUBMISSION' =>
            'mtd.vat.return.error.duplicate_submission',
        'TAX_PERIOD_NOT_ENDED' =>
            'mtd.vat.return.error.tax_period_not_ended',
        'CLIENT_OR_AGENT_NOT_AUTHORISED' =>
            'mtd.vat.return.error.client_or_agent_not_authorised',
        'RULE_INSOLVENT_TRADER' =>
            'mtd.vat.return.error.rule_insolvent_trader',
        default => null,
    };
};

echo $s->getSetting('disable_flash_messages') === '0' ? $alert : '';

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

         // Prefer the most specific known code: the first nested
         // errors[] entry when present (BUSINESS_ERROR/HTTP 403 is only
         // ever HMRC's generic wrapper around that), falling back to
         // the top-level code.
         $primaryCode = '';
         if ($errors !== [] && isset($errors[0]['code'])) {
             $primaryCode = (string) $errors[0]['code'];
         } elseif (isset($result['code'])) {
             $primaryCode = (string) $result['code'];
         }
         $errorMessageKey = $vatReturnErrorMessageKey($primaryCode);

         echo H::tag(
             'div',
             $errorMessageKey !== null
                 ? $translator->translate($errorMessageKey)
                 : 'HMRC returned HTTP ' . $statusCode . '.',
             ['class' => 'alert alert-danger'],
         );
         if ($errorMessageKey !== null) {
             echo H::tag(
                 'p',
                 H::tag('small', 'HTTP ' . $statusCode . ' — '
                     . H::encode($primaryCode)),
                 ['class' => 'text-muted'],
             );
         }

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
