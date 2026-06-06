<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Button;

/**
 * @var string $vrn
 * @var string $periodKey
 * @var string $periodStart
 * @var string $periodEnd
 */

$action = '/backend/hmrc/vatReturnSubmit';

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']);

   echo H::openTag('div', ['class' => 'card']);
    echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
     echo H::tag('strong', 'VAT Return — ' . H::encode($periodStart) . ' to ' . H::encode($periodEnd));
     echo H::a('← Obligations', '/backend/hmrc/vatObligations', ['class' => 'btn btn-sm btn-outline-secondary']);
    echo H::closeTag('div');

    echo H::openTag('div', ['class' => 'card-body']);
     echo H::openTag('form', ['method' => 'post', 'action' => $action]);

      echo H::input('hidden', 'periodKey', H::encode($periodKey));

      $rows = [
          ['vatDueSales',                  'Box 1',  'VAT due on sales and other outputs', '2dp'],
          ['vatDueAcquisitions',           'Box 2',  'VAT due on acquisitions from other EC member states', '2dp'],
          ['totalVatDue',                  'Box 3',  'Total VAT due (Box 1 + Box 2)', '2dp'],
          ['vatReclaimedCurrPeriod',       'Box 4',  'VAT reclaimed in this period', '2dp'],
          ['netVatDue',                    'Box 5',  'Net VAT to pay HMRC or reclaim (difference of Box 3 and Box 4)', '2dp'],
          ['totalValueSalesExVAT',         'Box 6',  'Total value of sales and all other outputs, excl VAT (whole pounds)', 'int'],
          ['totalValuePurchasesExVAT',     'Box 7',  'Total value of purchases and all other inputs, excl VAT (whole pounds)', 'int'],
          ['totalValueGoodsSuppliedExVAT', 'Box 8',  'Total value of goods supplied to other EC member states, excl VAT (whole pounds)', 'int'],
          ['totalAcquisitionsExVAT',       'Box 9',  'Total acquisitions from other EC member states, excl VAT (whole pounds)', 'int'],
      ];

      echo H::openTag('table', ['class' => 'table table-sm']);
       echo H::openTag('thead', ['class' => 'table-light']);
        echo H::openTag('tr');
         echo H::tag('th', 'Box');
         echo H::tag('th', 'Description');
         echo H::tag('th', 'Amount');
        echo H::closeTag('tr');
       echo H::closeTag('thead');
       echo H::openTag('tbody');
       foreach ($rows as [$name, $box, $label, $format]) {
           $step = $format === '2dp' ? '0.01' : '1';
           $placeholder = $format === '2dp' ? '0.00' : '0';
           echo H::openTag('tr');
            echo H::tag('td', H::tag('small', $box, ['class' => 'fw-bold']));
            echo H::tag('td', H::tag('small', $label, ['class' => 'text-muted']));
            echo H::openTag('td');
             echo H::input('number', $name, '0')
                 ->addAttributes(['class' => 'form-control form-control-sm', 'step' => $step,
                     'min' => '0', 'placeholder' => $placeholder, 'required' => true]);
            echo H::closeTag('td');
           echo H::closeTag('tr');
       }
       echo H::closeTag('tbody');
      echo H::closeTag('table');

      echo H::openTag('div', ['class' => 'form-check mb-3']);
       echo H::input('checkbox', 'finalised', '1')->addAttributes(['class' => 'form-check-input', 'id' => 'finalised', 'required' => true]);
       echo H::label('I declare the information provided is true and complete to the best of my knowledge and belief.', 'finalised')
           ->addClass('form-check-label small');
      echo H::closeTag('div');

      echo H::openTag('div', ['class' => 'd-flex gap-2']);
       echo new Button()
           ->addAttributes(['type' => 'submit'])
           ->addClass('btn btn-danger')
           ->content('Submit VAT Return to HMRC')
           ->render();
       echo H::a('Cancel', '/backend/hmrc/vatObligations', ['class' => 'btn btn-outline-secondary']);
      echo H::closeTag('div');

     echo H::closeTag('form');
    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
