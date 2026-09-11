<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Shared view for all eight MTD "Income Received" replacement APIs
 * (Dividends/Employments/Foreign/Insurance Policies/Other/Partner/
 * Pensions/Savings Income) -- see
 * HmrcController::renderIncomeCategory()'s own docblock for why one
 * shared action+view serves all eight rather than eight near-identical
 * copies. Deliberately shows the raw JSON response rather than a
 * structured table -- each of the eight APIs' response shapes
 * genuinely differ (nested lists for some, a single object for
 * others), and guessing at a shared table structure across eight
 * independently-designed APIs risked getting field names wrong for
 * ones this page's own research didn't drill into field-by-field --
 * same reasoning selfEmploymentBusinesses()'s own view already uses
 * for an unexpected/error response, applied here as the page's primary
 * display instead of just an error fallback.
 *
 * @var string $alert
 * @var string $nino
 * @var string $taxYear
 * @var string $label
 * @var int $statusCode
 * @var array<string, mixed> $raw
 * @var App\Invoice\Setting\SettingRepository $s
 */

echo $s->getSetting('disable_flash_messages') === '0' ? $alert : '';

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-10 offset-md-1']);

   echo H::openTag('div', ['class' => 'card mb-3']);
    echo H::openTag('div', [
        'class' => 'card-header d-flex justify-content-between'
            . ' align-items-center',
    ]);
     $title = H::encode($label) . ' — NINO ' . H::encode($nino);
     if ($taxYear !== '') {
         $title .= ' — Tax Year ' . H::encode($taxYear);
     }
     echo H::tag('strong', $title);
     echo H::a('← Back', '/backend/hmrc', [
         'class' => 'btn btn-sm btn-outline-secondary',
     ]);
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'card-body']);

     echo H::openTag('p');
     echo 'HTTP ' . H::tag(
         'span',
         (string) $statusCode,
         [
             'class' => $statusCode === 200
                 ? 'badge bg-success'
                 : 'badge bg-danger',
         ],
     );
     echo H::closeTag('p');

     $rawJson = (string) json_encode(
         $raw,
         JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
     );
     echo H::tag(
         'pre',
         H::encode($rawJson),
         ['class' => 'bg-light p-3 rounded small'],
     );

    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
