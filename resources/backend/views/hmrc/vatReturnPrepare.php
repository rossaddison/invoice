<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Button;

/**
 * @var string $vrn
 * @var string $periodKey
 * @var string $periodStart
 * @var string $periodEnd
 * @var float  $box1  output VAT (auto from InvAmount.tax_total)
 * @var float  $box4  input VAT reclaimed (auto from PurchaseEntry.vat_amount)
 * @var float  $box6  sales ex-VAT (auto from InvAmount.item_subtotal)
 * @var float  $box7  purchases ex-VAT (auto from PurchaseEntry.amount_ex_vat)
 */

$submitAction = '/backend/hmrc/vatReturnSubmit';

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-9 offset-md-1']);

   echo H::openTag('div', ['class' => 'card']);
    echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
     echo H::tag('strong', 'VAT Return — ' . H::encode($periodStart) . ' to ' . H::encode($periodEnd));
     echo H::a('← Obligations', '/backend/hmrc/vatObligations', ['class' => 'btn btn-sm btn-outline-secondary']);
    echo H::closeTag('div');

    echo H::openTag('div', ['class' => 'card-body']);
     echo H::openTag('form', ['method' => 'post', 'action' => $submitAction, 'id' => 'vat100-form']);
      echo H::input('hidden', 'periodKey', H::encode($periodKey));

      /**
       * Columns: box label | description | amount input
       * Boxes 1, 6 pre-filled from DB (editable override)
       * Boxes 3, 5 read-only — auto-calculated by JS
       * Boxes 2, 4, 7, 8, 9 manual
       */
      $rows = [
          // [name, box, label, type, prefilledValue, readonly, helpText]
          ['vatDueSales',                  'Box 1', 'VAT due on sales and other outputs',
              '2dp', number_format($box1, 2, '.', ''), false,
              'Pre-filled from your invoices for this period — verify before submitting'],
          ['vatDueAcquisitions',           'Box 2', 'VAT due on EC acquisitions (post-Brexit: typically 0)',
              '2dp', '0.00', false, ''],
          ['totalVatDue',                  'Box 3', 'Total VAT due (Box 1 + Box 2 — auto-calculated)',
              '2dp', number_format($box1, 2, '.', ''), true,
              'Calculated automatically'],
          ['vatReclaimedCurrPeriod',       'Box 4', 'VAT reclaimed on purchases',
              '2dp', number_format($box4, 2, '.', ''), false,
              'Pre-filled from your purchase entries — verify before submitting'],
          ['netVatDue',                    'Box 5', 'Net VAT payable or reclaimable (|Box 3 − Box 4| — auto-calculated)',
              '2dp', number_format($box1, 2, '.', ''), true,
              'Calculated automatically'],
          ['totalValueSalesExVAT',         'Box 6', 'Total sales ex-VAT, whole pounds',
              'int', (string) (int) $box6, false,
              'Pre-filled from your invoices — verify before submitting'],
          ['totalValuePurchasesExVAT',     'Box 7', 'Total purchases ex-VAT, whole pounds',
              'int', (string) (int) $box7, false,
              'Pre-filled from your purchase entries — verify before submitting'],
          ['totalValueGoodsSuppliedExVAT', 'Box 8', 'EC goods supplied ex-VAT, whole pounds (post-Brexit: typically 0)',
              'int', '0', false, ''],
          ['totalAcquisitionsExVAT',       'Box 9', 'EC acquisitions ex-VAT, whole pounds (post-Brexit: typically 0)',
              'int', '0', false, ''],
      ];

      echo H::openTag('table', ['class' => 'table table-sm table-bordered']);
       echo H::openTag('thead', ['class' => 'table-light']);
        echo H::openTag('tr');
         echo H::tag('th', 'Box', ['style' => 'width:4rem']);
         echo H::tag('th', 'Description');
         echo H::tag('th', 'Amount', ['style' => 'width:11rem']);
        echo H::closeTag('tr');
       echo H::closeTag('thead');
       echo H::openTag('tbody');

       foreach ($rows as [$name, $box, $label, $fmt, $value, $readonly, $hint]) {
           $step = $fmt === '2dp' ? '0.01' : '1';
           $trClass = $readonly ? 'table-secondary' : '';
           echo H::openTag('tr', $trClass !== '' ? ['class' => $trClass] : []);
            echo H::tag('td', H::tag('small', $box, ['class' => 'fw-bold']));
            echo H::openTag('td');
             echo H::tag('small', $label);
             if ($hint !== '') {
                 echo H::tag('div', $hint, ['class' => 'text-muted', 'style' => 'font-size:.75rem']);
             }
            echo H::closeTag('td');
            echo H::openTag('td');
             $attrs = [
                 'id' => $name,
                 'class' => 'form-control form-control-sm',
                 'step' => $step,
                 'min' => '0',
             ];
             if ($readonly) {
                 $attrs['readonly'] = true;
                 $attrs['class'] .= ' bg-light';
             } else {
                 $attrs['oninput'] = 'recalcVat()';
                 $attrs['required'] = true;
             }
             echo H::input('number', $name, $value)->addAttributes($attrs);
            echo H::closeTag('td');
           echo H::closeTag('tr');
       }

       echo H::closeTag('tbody');
      echo H::closeTag('table');

      echo H::openTag('div', ['class' => 'form-check mb-3']);
       echo H::input('checkbox', 'finalised', '1')
           ->addAttributes(['class' => 'form-check-input', 'id' => 'finalised', 'required' => true]);
       echo H::label(
           'I declare the information provided is true and complete to the best of my knowledge and belief.',
           'finalised',
       )->addClass('form-check-label small');
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
echo H::script(<<<'JS'
(function () {
    function val(id) { return parseFloat(document.getElementById(id).value) || 0; }

    globalThis.recalcVat = function () {
        const b3 = val('vatDueSales') + val('vatDueAcquisitions');
        document.getElementById('totalVatDue').value = b3.toFixed(2);
        const b5 = Math.abs(b3 - val('vatReclaimedCurrPeriod'));
        document.getElementById('netVatDue').value = b5.toFixed(2);
    };

    // Run once on load to set Box 3 from the pre-filled Box 1
    recalcVat();
})();
JS);
