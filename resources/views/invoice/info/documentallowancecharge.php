<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

echo H::openTag('div', ['style' => 'font-size: calc(var(--inv-form-fs) + 2px)']); //0
 echo H::tag('h1', 'Tax Point: (...views/invoice/info/taxpoint.php)',
         ['align' => 'center', 'id' => 'taxpoint']);
 echo H::tag('p', H::tag('b', '25th June 2023'), ['align' => 'center']);
 echo H::tag('p', 'Aim: To Understand How to Establish a Tax Point for'
         . ' VAT Reporting purposes', ['align' => 'center']);
 echo H::tag('h2', 'How do I know what the tax point is?', ['align' => 'center']);
 echo H::openTag('div', ['class' => 'wysiwyg']); //1
  echo H::tag('p',
   H::a('Reference',
           'https://informi.co.uk/finance/what-tax-point-transaction-including-vat-1'),
   ['align' => 'center']
  );
  echo H::tag('p', 'The tax point will vary depending on the circumstances.',
          ['align' => 'center']);
  echo H::openTag('table', [
   'border' => '1',
   'align' => 'center',
   'cellpadding' => '1',
   'cellspacing' => '1',
   'style' => 'border-collapse:collapse; width:500px',
  ]); //2
   echo H::openTag('thead'); //3
    echo H::openTag('tr', ['border' => '1',
        'style' => 'border-collapse:collapse']); //4
     echo H::tag('th', 'Invoice?', ['scope' => 'col']);
     echo H::tag('th', 'Tax point', ['scope' => 'col']);
    echo H::closeTag('tr'); //4
   echo H::closeTag('thead'); //3
   echo H::openTag('tbody'); //3
    echo H::openTag('tr'); //4
     echo H::tag('td', 'No invoice needed');
     echo H::tag('td', 'If no invoice is needed, the tax point will be the '
             . H::tag('b', 'date of supply') . '.');
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::tag('td', 'VAT invoice issued (within 14 days)');
     echo H::tag('td', 'If a VAT invoice has been issued within 14 days of the'
             . ' supply, the tax point will be the ' . H::tag('b',
                     'date of the invoice.'));
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::tag('td', 'VAT invoice issued (after 14 days)');
     echo H::tag('td', 'If a VAT invoice has been issued 15 days or more after'
             . ' the date of supply, the tax point will be the '
             . H::tag('b', 'date of supply') . '.');
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::tag('td', 'Payment received or VAT invoice issued in advance');
     echo H::tag('td', 'If a payment is received or a VAT invoice is issued'
             . ' in advance of the supply being made, the tax point will be the '
             . H::tag('b', 'earlier of either the date payment is received or'
                     . ' the invoice date') . '.');
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::tag('td', 'Payment received in advance');
     echo H::tag('td', 'If a payment is received in advance of the supply'
             . ' being made and a VAT invoice has yet to be issued, the tax'
             . ' point will be the '
             . H::tag('b', 'date the payment is received') . '.');
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::tag('td', 'Cash accounting scheme');
     echo H::tag('td', "If you use the VAT ,\u{00A0}the tax point is always"
             . " the date the payment is received.");
    echo H::closeTag('tr'); //4
   echo H::closeTag('tbody'); //3
  echo H::closeTag('table'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0
