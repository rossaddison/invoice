<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var string $alert
 */

echo $alert;

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-7 offset-md-2']);

   echo H::openTag('div', ['class' => 'card']);
    echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
     echo H::tag('strong', 'Import Purchase Entries from CSV');
     echo H::a('← Back', '/purchase-entry', ['class' => 'btn btn-sm btn-outline-secondary']);
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'card-body']);

     echo H::openTag('div', ['class' => 'alert alert-info small mb-3']);
      echo H::tag('strong', 'Expected CSV format (header row required):');
      echo H::tag('pre', "date,supplier,amount_ex_vat,vat_amount[,description]\n2026-01-05,Office Supplies Ltd,120.00,24.00\n2026-01-12,Cloud Hosting Co,200.00,40.00,Hosting Jan", ['class' => 'mb-0 mt-1 small']);
      echo H::openTag('div', ['class' => 'mt-2']);
       echo H::a('Download template spreadsheet (CSV)', '/purchase-entry/csv-template', [
           'class' => 'btn btn-sm btn-outline-primary',
       ]);
      echo H::closeTag('div');
     echo H::closeTag('div');

     echo H::openTag('form', [
         'method'  => 'post',
         'action'  => '/purchase-entry/csv-import',
         'enctype' => 'multipart/form-data',
     ]);
      echo H::openTag('div', ['class' => 'mb-3']);
       echo H::label('CSV File', 'csv_file')->addClass('form-label');
       echo H::input('file', 'csv_file')->addAttributes([
           'id' => 'csv_file', 'class' => 'form-control', 'accept' => '.csv,text/csv', 'required' => true,
       ]);
      echo H::closeTag('div');
      echo H::openTag('div', ['class' => 'd-flex gap-2']);
       echo H::submitButton('Import')->addAttributes(['class' => 'btn btn-primary']);
       echo H::a('Cancel', '/purchase-entry', ['class' => 'btn btn-outline-secondary']);
      echo H::closeTag('div');
     echo H::closeTag('form');

    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
