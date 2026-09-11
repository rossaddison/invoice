<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Renders HMRC's real self-assessment-api/3.0 itsa-status response (see
 * HmrcController::itsaStatus()'s own docblock for the live-confirmed
 * shape this mirrors): one row per itsaStatusDetails entry, flattened
 * across whichever tax years HMRC returned.
 *
 * @var string $alert
 * @var string $nino
 * @var string $taxYear
 * @var int $statusCode
 * @var list<array{
 *     taxYear: string,
 *     submittedOn: string,
 *     status: string,
 *     statusReason: string,
 *     businessIncome2YearsPrior: string,
 * }> $itsaStatuses
 * @var App\Invoice\Setting\SettingRepository $s
 */

$statusBadge = static fn (string $status): array => match ($status) {
    'No Status' => ['badge bg-secondary', 'No Status'],
    'MTD Mandated' => ['badge bg-primary', 'MTD Mandated'],
    'MTD Voluntary' => ['badge bg-info text-dark', 'MTD Voluntary'],
    'Annual' => ['badge bg-success', 'Annual'],
    'Non Digital' => ['badge bg-warning text-dark', 'Non Digital'],
    'Dormant' => ['badge bg-dark', 'Dormant'],
    'MTD Exempt' => ['badge bg-warning text-dark', 'MTD Exempt'],
    default => ['badge bg-secondary', $status === '' ? '—' : $status],
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
         'Self Assessment (Individual) — NINO ' . H::encode($nino)
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

     if ($statusCode !== 200) {
         echo H::tag(
             'div',
             'HMRC API returned HTTP ' . $statusCode . '.',
             ['class' => 'alert alert-danger'],
         );
     } elseif ($itsaStatuses === []) {
         echo H::tag(
             'div',
             'No ITSA status found for this NINO and tax year.',
             ['class' => 'alert alert-info'],
         );
     } else {
         echo H::openTag('div', ['class' => 'table-responsive']);
         echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
          echo H::openTag('thead', ['class' => 'table-light']);
           echo H::openTag('tr');
            $columns = [
                'Tax Year', 'Status', 'Status Reason', 'Submitted On',
                'Business Income (2yrs prior)',
            ];
            foreach ($columns as $col) {
                echo H::tag('th', $col);
            }
           echo H::closeTag('tr');
          echo H::closeTag('thead');
          echo H::openTag('tbody');
          foreach ($itsaStatuses as $status) {
              [$badgeClass, $badgeText] = $statusBadge($status['status']);
              echo H::openTag('tr');
               echo H::tag('td', H::encode($status['taxYear']));
               echo H::tag(
                   'td',
                   H::tag('span', $badgeText, ['class' => $badgeClass]),
               );
               echo H::tag('td', H::encode($status['statusReason']));
               echo H::tag('td', H::encode($status['submittedOn']));
               echo H::tag(
                   'td',
                   H::encode($status['businessIncome2YearsPrior']),
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
