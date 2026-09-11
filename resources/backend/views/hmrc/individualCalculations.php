<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Lists MTD Self Assessment Tax Calculations for the configured NINO
 * and tax year, and offers a small form to trigger a new one -- see
 * HmrcController::individualCalculations()'s own docblock for the
 * live-confirmed shape this mirrors. Deliberately doesn't try to
 * render a single calculation's full detail (see that action's own
 * docblock for why) -- this page only ever shows the flat list.
 *
 * @var string $alert
 * @var string $nino
 * @var string $taxYear
 * @var int $statusCode
 * @var list<array<string, mixed>> $calculations
 * @var App\Invoice\Setting\SettingRepository $s
 */

$outcomeBadge = static fn (string $outcome): array => match ($outcome) {
    'PROCESSED' => ['badge bg-success', 'Processed'],
    'ERROR' => ['badge bg-danger', 'Error'],
    'REJECTED' => ['badge bg-warning text-dark', 'Rejected'],
    default => ['badge bg-secondary', $outcome === '' ? '—' : $outcome],
};

echo $s->getSetting('disable_flash_messages') === '0' ? $alert : '';

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-10 offset-md-1']);

   echo H::openTag('div', ['class' => 'card mb-3']);
    echo H::openTag('div', [
        'class' => 'card-header d-flex justify-content-between'
            . ' align-items-center',
    ]);
     echo H::tag(
         'strong',
         'Individual Calculations — NINO ' . H::encode($nino)
             . ' — Tax Year ' . H::encode($taxYear),
     );
     echo H::a('← Back', '/backend/hmrc', [
         'class' => 'btn btn-sm btn-outline-secondary',
     ]);
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'card-body']);

     echo H::openTag('form', ['method' => 'get', 'class' => 'row g-2 mb-3']);
      echo H::openTag('div', ['class' => 'col-auto']);
       echo H::input('text', 'taxYear', $taxYear, [
           'class'       => 'form-control form-control-sm',
           'placeholder' => 'YYYY-YY',
       ]);
      echo H::closeTag('div');
      echo H::openTag('div', ['class' => 'col-auto']);
       echo H::submitButton('Check another tax year', [
           'class' => 'btn btn-sm btn-outline-primary',
       ]);
      echo H::closeTag('div');
     echo H::closeTag('form');

     echo H::openTag('form', [
         'method' => 'post',
         'class'  => 'row g-2 mb-3 align-items-end',
     ]);
      echo H::openTag('div', ['class' => 'col-auto']);
       echo H::tag(
           'label',
           'Calculation type',
           ['for' => 'calculationType', 'class' => 'form-label small mb-0'],
       );
       echo H::openTag('select', [
           'id'    => 'calculationType',
           'name'  => 'calculationType',
           'class' => 'form-select form-select-sm',
       ]);
        foreach (['in-year', 'intent-to-finalise'] as $type) {
            echo H::tag('option', $type, ['value' => $type]);
        }
       echo H::closeTag('select');
      echo H::closeTag('div');
      echo H::openTag('div', ['class' => 'col-auto']);
       echo H::submitButton('Trigger calculation', [
           'class' => 'btn btn-sm btn-primary',
       ]);
      echo H::closeTag('div');
     echo H::closeTag('form');

     if ($statusCode !== 200) {
         echo H::tag(
             'div',
             'HMRC API returned HTTP ' . $statusCode . '.',
             ['class' => 'alert alert-danger'],
         );
     } elseif ($calculations === []) {
         echo H::tag(
             'div',
             'No calculations found for this NINO and tax year.',
             ['class' => 'alert alert-info'],
         );
     } else {
         echo H::openTag('div', ['class' => 'table-responsive']);
         echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
          echo H::openTag('thead', ['class' => 'table-light']);
           echo H::openTag('tr');
            $columns = [
                'Calculation ID', 'Timestamp', 'Type', 'Trigger',
                'Outcome', 'Liability',
            ];
            foreach ($columns as $col) {
                echo H::tag('th', $col);
            }
           echo H::closeTag('tr');
          echo H::closeTag('thead');
          echo H::openTag('tbody');
          foreach ($calculations as $calc) {
              $outcome = (string) ($calc['calculationOutcome'] ?? '');
              [$badgeClass, $badgeText] = $outcomeBadge($outcome);
              echo H::openTag('tr');
               echo H::tag(
                   'td',
                   H::tag(
                       'code',
                       H::encode((string) ($calc['calculationId'] ?? '')),
                       ['class' => 'small'],
                   ),
               );
               echo H::tag(
                   'td',
                   H::encode((string) ($calc['calculationTimestamp'] ?? '')),
               );
               echo H::tag(
                   'td',
                   H::encode((string) ($calc['calculationType'] ?? '')),
               );
               echo H::tag(
                   'td',
                   H::encode((string) ($calc['calculationTrigger'] ?? '')),
               );
               echo H::tag(
                   'td',
                   H::tag('span', $badgeText, ['class' => $badgeClass]),
               );
               echo H::tag(
                   'td',
                   H::encode((string) ($calc['liabilityAmount'] ?? '—')),
               );
              echo H::closeTag('tr');
          }
          echo H::closeTag('tbody');
         echo H::closeTag('table');
         echo H::closeTag('div');
     }

    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
