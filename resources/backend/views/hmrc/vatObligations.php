<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\A;

/**
 * @var array<int, array<string, string>> $obligations
 * @var string $vrn
 * @var int $statusCode
 */

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-10 offset-md-1']);

   echo H::openTag('div', ['class' => 'card mb-3']);
    echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
     echo H::tag('strong', 'VAT Obligations — VRN ' . H::encode($vrn));
     echo H::a('← Back', '/backend/hmrc', ['class' => 'btn btn-sm btn-outline-secondary']);
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'card-body']);

     if ($statusCode !== 200) {
         echo H::tag('div', 'HMRC API returned HTTP ' . $statusCode . '. Check your access token and VRN.', ['class' => 'alert alert-danger']);
     } elseif (count($obligations) === 0) {
         echo H::tag('div', 'No open VAT obligations found for this VRN.', ['class' => 'alert alert-info']);
     } else {
         echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
          echo H::openTag('thead', ['class' => 'table-light']);
           echo H::openTag('tr');
            echo H::tag('th', 'Period Key');
            echo H::tag('th', 'Start');
            echo H::tag('th', 'End');
            echo H::tag('th', 'Due');
            echo H::tag('th', 'Status');
            echo H::tag('th', '');
           echo H::closeTag('tr');
          echo H::closeTag('thead');
          echo H::openTag('tbody');
          foreach ($obligations as $ob) {
              $periodKey = (string) ($ob['periodKey'] ?? '');
              $start = (string) ($ob['start'] ?? '');
              $end = (string) ($ob['end'] ?? '');
              $due = (string) ($ob['due'] ?? '');
              $status = (string) ($ob['status'] ?? '');
              $isOpen = $status === 'O';

              echo H::openTag('tr');
               echo H::tag('td', H::encode($periodKey));
               echo H::tag('td', H::encode($start));
               echo H::tag('td', H::encode($end));
               echo H::tag('td', H::encode($due));
               echo H::tag('td', $isOpen
                   ? H::tag('span', 'Open', ['class' => 'badge bg-warning text-dark'])
                   : H::tag('span', 'Fulfilled', ['class' => 'badge bg-success'])
               );
               echo H::tag('td', $isOpen
                   ? new A()
                       ->href('/backend/hmrc/vatReturnPrepare?periodKey=' . urlencode($periodKey)
                           . '&start=' . urlencode($start) . '&end=' . urlencode($end))
                       ->addClass('btn btn-sm btn-primary')
                       ->content('Prepare Return')
                       ->render()
                   : ''
               );
              echo H::closeTag('tr');
          }
          echo H::closeTag('tbody');
         echo H::closeTag('table');
     }

    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
