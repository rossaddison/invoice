<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use Yiisoft\Html\Html as H;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * @var EntityReader $entries
 * @var string       $alert
 */

echo $alert;

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12']);

   echo H::openTag('div', ['class' => 'card mb-3']);
    echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
     echo H::tag('strong', 'Purchase Entries');
     echo H::openTag('div', ['class' => 'd-flex gap-2']);
      echo H::a('+ Add Entry', '/purchase-entry/add', ['class' => 'btn btn-sm btn-primary']);
      echo H::a('CSV Import', '/purchase-entry/csv-import', ['class' => 'btn btn-sm btn-outline-secondary']);
     echo H::closeTag('div');
    echo H::closeTag('div');

    echo H::openTag('div', ['class' => 'card-body p-0']);
     echo H::openTag('table', ['class' => 'table table-sm table-bordered table-hover mb-0']);
      echo H::openTag('thead', ['class' => 'table-light']);
       echo H::openTag('tr');
        echo H::tag('th', 'Date');
        echo H::tag('th', 'Supplier');
        echo H::tag('th', 'Description');
        echo H::tag('th', 'Amount ex-VAT', ['class' => 'text-end']);
        echo H::tag('th', 'VAT Amount', ['class' => 'text-end']);
        echo H::tag('th', '');
       echo H::closeTag('tr');
      echo H::closeTag('thead');
      echo H::openTag('tbody');

      $hasRows = false;
      /** @var PurchaseEntry $entry */
      foreach ($entries->read() as $entry) {
          $hasRows = true;
          echo H::openTag('tr');
           echo H::tag('td', H::encode($entry->getDate()));
           echo H::tag('td', H::encode($entry->getSupplier()));
           echo H::tag('td', H::encode((string) $entry->getDescription()));
           echo H::tag('td', number_format($entry->getAmountExVat(), 2), ['class' => 'text-end']);
           echo H::tag('td', number_format($entry->getVatAmount(), 2), ['class' => 'text-end']);
           echo H::openTag('td', ['class' => 'd-flex gap-1']);
            echo H::a('Edit', '/purchase-entry/edit/' . $entry->reqId(), ['class' => 'btn btn-xs btn-outline-primary']);
            echo H::a('Delete', '/purchase-entry/delete/' . $entry->reqId(), [
                'class'                => 'btn btn-xs btn-outline-danger',
                'onclick'              => 'return confirm(\'Delete this entry?\');',
            ]);
           echo H::closeTag('td');
          echo H::closeTag('tr');
      }

      if (!$hasRows) {
          echo H::openTag('tr');
           echo H::tag('td', 'No purchase entries yet. Add one or import a CSV.', ['colspan' => '6', 'class' => 'text-muted text-center py-3']);
          echo H::closeTag('tr');
      }

      echo H::closeTag('tbody');
     echo H::closeTag('table');
    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
