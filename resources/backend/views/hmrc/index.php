<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var string $vrn
 * @var string $fphConnectionMethod
 * @var string $govVendorProductName
 * @var string $govVendorVersion
 */

$vrnSet = $vrn !== '';
$fphSet = $fphConnectionMethod !== '';

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']);

   echo H::openTag('div', ['class' => 'card mb-3']);
    echo H::openTag('div', ['class' => 'card-header']);
     echo H::tag('strong', 'HMRC Making Tax Digital — Status');
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'card-body']);
     echo H::openTag('table', ['class' => 'table table-sm']);
      echo H::openTag('tbody');

       echo H::openTag('tr');
        echo H::tag('td', 'VAT Registration Number (VRN)');
        echo H::tag('td', $vrnSet
            ? H::tag('span', $vrn, ['class' => 'text-success'])
            : H::tag('span', 'Not set — configure in Settings → Making Tax Digital', ['class' => 'text-danger'])
        );
       echo H::closeTag('tr');

       echo H::openTag('tr');
        echo H::tag('td', 'FPH Connection Method');
        echo H::tag('td', $fphSet
            ? H::tag('span', $fphConnectionMethod, ['class' => 'text-success'])
            : H::tag('span', 'Not set — run Generate in Settings → Making Tax Digital', ['class' => 'text-warning'])
        );
       echo H::closeTag('tr');

       echo H::openTag('tr');
        echo H::tag('td', 'Vendor Product');
        echo H::tag('td', $govVendorProductName !== '' ? $govVendorProductName : H::tag('span', 'Not set', ['class' => 'text-muted']));
       echo H::closeTag('tr');

       echo H::openTag('tr');
        echo H::tag('td', 'Vendor Version');
        echo H::tag('td', $govVendorVersion !== '' ? $govVendorVersion : H::tag('span', 'Not set', ['class' => 'text-muted']));
       echo H::closeTag('tr');

      echo H::closeTag('tbody');
     echo H::closeTag('table');

     echo H::openTag('div', ['class' => 'd-flex gap-2 flex-wrap']);
      echo H::a('Test FPH Headers', '/backend/hmrc/fphValidate', ['class' => 'btn btn-sm btn-outline-primary']);
      echo H::a('FPH Feedback (VAT)', '/backend/hmrc/fphFeedback/vat', ['class' => 'btn btn-sm btn-outline-secondary']);
      echo H::a('VAT Obligations', '/backend/hmrc/vatObligations', ['class' => 'btn btn-sm btn-outline-info']);
     echo H::closeTag('div');

    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
