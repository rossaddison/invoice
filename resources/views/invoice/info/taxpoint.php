<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

echo H::openTag('div', ['style' => 'font-size: calc(var(--inv-form-fs) + 2px);']); //0
 echo H::openTag('p'); //1
  echo 'Tax Point: (...views/invoice/info/taxpoint.php)';
 echo H::closeTag('p'); //1
 echo H::openTag('p'); //1
  echo H::openTag('b'); //2
   echo '25th June 2023';
  echo H::closeTag('b'); //2
 echo H::closeTag('p'); //1
 echo H::openTag('p'); //1
  echo 'Aim: To Understand How to Establish a Tax Point for VAT Reporting purposes';
 echo H::closeTag('p'); //1
 echo H::openTag('p'); //1
  echo 'How do I know what the tax point is?';
 echo H::closeTag('p'); //1
 echo H::openTag('div', ['class' => 'wysiwyg']); //1
  echo H::openTag('p'); //2
   echo H::a('Reference', 'https://informi.co.uk/finance/what-tax-point-transaction-including-vat-1');
  echo H::closeTag('p'); //2
  echo H::openTag('p'); //2
   echo 'The tax point will vary depending on the circumstances.';
  echo H::closeTag('p'); //2
  echo H::openTag('table', ['border' => '1', 'style' => 'border-collapse:collapse;width:500px;', 'align' => 'center', 'cellpadding' => '1', 'cellspacing' => '1']); //2
   echo H::openTag('thead'); //3
    echo H::openTag('tr', ['border' => '1', 'style' => 'border-collapse:collapse;']); //4
     echo H::tag('th', 'Invoice?', ['scope' => 'col']);
     echo H::tag('th', 'Tax point', ['scope' => 'col']);
    echo H::closeTag('tr'); //4
   echo H::closeTag('thead'); //3
   echo H::openTag('tbody'); //3
    echo H::openTag('tr'); //4
     echo H::tag('td', 'No invoice needed');
     echo H::openTag('td'); //5
      echo 'If no invoice is needed, the tax point will be the ';
      echo H::openTag('b'); //6
       echo 'date of supply';
      echo H::closeTag('b'); //6
      echo '.';
     echo H::closeTag('td'); //5
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::tag('td', 'VAT invoice issued (within 14 days)');
     echo H::openTag('td'); //5
      echo 'If a VAT invoice has been issued within 14 days of the supply, the tax point will be the ';
      echo H::openTag('b'); //6
       echo 'date of the invoice.';
      echo H::closeTag('b'); //6
     echo H::closeTag('td'); //5
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::tag('td', 'VAT invoice issued (after 14 days)');
     echo H::openTag('td'); //5
      echo 'If a VAT invoice has been issued 15 days or more after the date of supply, the tax point will be the ';
      echo H::openTag('b'); //6
       echo 'date of supply';
      echo H::closeTag('b'); //6
      echo '.';
     echo H::closeTag('td'); //5
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::tag('td', 'Payment received or VAT invoice issued in advance');
     echo H::openTag('td'); //5
      echo 'If a payment is received or a VAT invoice is issued in advance of the supply being made, the tax point will be the ';
      echo H::openTag('b'); //6
       echo 'earlier of either the date payment is received or the invoice date';
      echo H::closeTag('b'); //6
      echo '.';
     echo H::closeTag('td'); //5
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::tag('td', 'Payment received in advance');
     echo H::openTag('td'); //5
      echo 'If a payment is received in advance of the supply being made and a VAT invoice has yet to be issued, the tax point will be the ';
      echo H::openTag('b'); //6
       echo 'date the payment is received';
      echo H::closeTag('b'); //6
      echo '.';
     echo H::closeTag('td'); //5
    echo H::closeTag('tr'); //4
    echo H::openTag('tr'); //4
     echo H::tag('td', 'Cash accounting scheme');
     echo H::tag('td', 'If you use the VAT ,&nbsp;the tax point is always the date the payment is received.');
    echo H::closeTag('tr'); //4
   echo H::closeTag('tbody'); //3
  echo H::closeTag('table'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0
