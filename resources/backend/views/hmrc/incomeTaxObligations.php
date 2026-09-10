<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Renders both halves of HMRC's real obligations-api/3.0 response
 * (see HmrcController::incomeTaxObligations()'s own docblock for the
 * live-confirmed shape this mirrors): the income-and-expenditure
 * endpoint (quarterly-update/EOPS deadlines, grouped by business) and
 * the crystallisation endpoint (the once-per-NINO final declaration
 * deadline, already flat -- no business grouping).
 *
 * @var string $alert
 * @var string $nino
 * @var int $incomeStatusCode
 * @var list<array{
 *     typeOfBusiness: string,
 *     businessId: string,
 *     periodStartDate: string,
 *     periodEndDate: string,
 *     dueDate: string,
 *     status: string,
 *     receivedDate: string,
 * }> $incomeObligations
 * @var int $crystallisationStatusCode
 * @var list<array{
 *     periodStartDate: string,
 *     periodEndDate: string,
 *     dueDate: string,
 *     status: string,
 *     receivedDate: string,
 * }> $crystallisationObligations
 * @var App\Invoice\Setting\SettingRepository $s
 */

$statusBadge = static fn (string $status): array => match ($status) {
    'open' => ['badge bg-warning text-dark', 'Open'],
    'fulfilled' => ['badge bg-success', 'Fulfilled'],
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
         'Income Tax Obligations — NINO ' . H::encode($nino),
     );
     echo H::a('← Back', '/backend/hmrc', [
         'class' => 'btn btn-sm btn-outline-secondary',
     ]);
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'card-body']);

     echo H::tag(
         'p',
         H::tag('strong', 'Quarterly Updates & End of Period Statements'),
         ['class' => 'mb-2'],
     );
     if ($incomeStatusCode !== 200) {
         echo H::tag(
             'div',
             'HMRC API returned HTTP ' . $incomeStatusCode . '.',
             ['class' => 'alert alert-danger'],
         );
     } elseif ($incomeObligations === []) {
         echo H::tag(
             'div',
             'No income-and-expenditure obligations found for this'
                 . ' NINO.',
             ['class' => 'alert alert-info'],
         );
     } else {
         echo H::openTag('div', ['class' => 'table-responsive']);
         echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
          echo H::openTag('thead', ['class' => 'table-light']);
           echo H::openTag('tr');
            $columns = ['Type', 'Business ID', 'Start', 'End', 'Due', 'Status'];
            foreach ($columns as $col) {
                echo H::tag('th', $col);
            }
           echo H::closeTag('tr');
          echo H::closeTag('thead');
          echo H::openTag('tbody');
          foreach ($incomeObligations as $ob) {
              [$badgeClass, $badgeText] = $statusBadge($ob['status']);
              echo H::openTag('tr');
               echo H::tag('td', H::encode($ob['typeOfBusiness']));
               echo H::tag(
                   'td',
                   H::tag(
                       'code',
                       H::encode($ob['businessId']),
                       ['class' => 'small'],
                   ),
               );
               echo H::tag('td', H::encode($ob['periodStartDate']));
               echo H::tag('td', H::encode($ob['periodEndDate']));
               echo H::tag('td', H::encode($ob['dueDate']));
               echo H::tag(
                   'td',
                   H::tag('span', $badgeText, ['class' => $badgeClass]),
               );
              echo H::closeTag('tr');
          }
          echo H::closeTag('tbody');
         echo H::closeTag('table');
         echo H::closeTag('div');
     }

     echo H::tag('hr', '');
     echo H::tag('p', H::tag('strong', 'Final Declaration'), ['class' => 'mb-2']);
     if ($crystallisationStatusCode !== 200) {
         echo H::tag(
             'div',
             'HMRC API returned HTTP ' . $crystallisationStatusCode . '.',
             ['class' => 'alert alert-danger'],
         );
     } elseif ($crystallisationObligations === []) {
         echo H::tag(
             'div',
             'No final declaration obligation found for this NINO.',
             ['class' => 'alert alert-info'],
         );
     } else {
         echo H::openTag('div', ['class' => 'table-responsive']);
         echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
          echo H::openTag('thead', ['class' => 'table-light']);
           echo H::openTag('tr');
            foreach (['Start', 'End', 'Due', 'Status'] as $col) {
                echo H::tag('th', $col);
            }
           echo H::closeTag('tr');
          echo H::closeTag('thead');
          echo H::openTag('tbody');
          foreach ($crystallisationObligations as $ob) {
              [$badgeClass, $badgeText] = $statusBadge($ob['status']);
              echo H::openTag('tr');
               echo H::tag('td', H::encode($ob['periodStartDate']));
               echo H::tag('td', H::encode($ob['periodEndDate']));
               echo H::tag('td', H::encode($ob['dueDate']));
               echo H::tag(
                   'td',
                   H::tag('span', $badgeText, ['class' => $badgeClass]),
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
