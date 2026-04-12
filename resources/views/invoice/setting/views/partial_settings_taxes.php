<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var array $tax_rates
*/

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-xs-12 col-md-6'];
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$formControl = ['class' => 'form-control form-control-lg',];
$minSearch = ['data-minimum-results-for-search' => 'Infinity'];

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', $row) . "\n"; //1
 echo H::openTag('div', $colMd8) . "\n"; //2

  echo H::openTag('div', $panel) . "\n"; //3
   echo H::openTag('div', $panelHead) . "\n"; //4
    echo $translator->translate('taxes') . "\n";
   echo H::closeTag('div') . "\n"; //4
   echo H::openTag('div', $panelBody) . "\n"; //4

    echo H::openTag('div', $row) . "\n"; //5
     echo H::openTag('div', $colMd6) . "\n"; //6

      echo H::openTag('div', $formGroup) . "\n"; //7
       echo H::openTag('label', [
        'for' => 'settings[default_invoice_tax_rate]'
       ]) . "\n";
        echo $translator->translate(
         'default.invoice.tax.rate'
        ) . "\n";
       echo H::closeTag('label') . "\n";
       $sditr = 'settings[default_invoice_tax_rate]';
       $body[$sditr] =
       $s->getSetting('default_invoice_tax_rate');
       echo H::openTag('select', [
        'name' => $sditr,
        'id' => $sditr,
        'class' => 'form-control form-control-lg',
       ]) . "\n";
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'))
        . "\n";
        /**
        * @var App\Invoice\Entity\TaxRate $taxRate
        */
        foreach ($tax_rates as $taxRate) {
        $percent = (string) $taxRate->getTaxRatePercent();
        $sign = '% - ';
        $name = $taxRate->getTaxRateName() ?? '';
        $content = $percent . $sign . $name;
        $taxRateId = $taxRate->getTaxRateId();
        echo  new Option()
         ->value($taxRateId)
         ->selected(
          $body[$sditr]
          == $taxRateId
         )
         ->content($content) . "\n";
        }
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7

      echo H::openTag('div', $formGroup) . "\n"; //7
       $sdtr = 'settings[default_item_tax_rate]';
       echo H::openTag('label', [
        'for' => $sdtr
       ]) . "\n";
        echo $translator->translate(
         'default.item.tax.rate'
        ) . "\n";
       echo H::closeTag('label') . "\n";
       $body[$sdtr] =
       $s->getSetting('default_item_tax_rate');
       echo H::openTag('select', [
        'name' => $sdtr,
        'id' => $sdtr,
        'class' => 'form-control form-control-lg',
       ]) . "\n";
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'))
        . "\n";
        /**
        * @var App\Invoice\Entity\TaxRate $taxRate
        */
        foreach ($tax_rates as $taxRate) {
        $percent = (string) $taxRate->getTaxRatePercent();
        $sign = '% - ';
        $name = $taxRate->getTaxRateName() ?? '';
        $content = $percent . $sign . $name;
        $taxRateId = $taxRate->getTaxRateId();
        echo  new Option()
         ->value($taxRateId)
         ->selected(
          $body[$sdtr]
          == $taxRateId
         )
         ->content($content) . "\n";
        }
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7

     echo H::closeTag('div') . "\n"; //6
     echo H::openTag('div', $colMd6) . "\n"; //6

      echo H::openTag('div', $formGroup) . "\n"; //7
       $include = 'settings[default_include_item_tax]';
       echo H::openTag('label', [
        'for' => $include
       ]) . "\n";
        echo $translator->translate(
         'default.invoice.tax.rate.placement'
        ) . "\n";
       echo H::closeTag('label') . "\n";
       $body[$include] =
       $s->getSetting('default_include_item_tax');
       echo H::openTag('select', array_merge($formControl,
        $minSearch, [
        'name' => $include,
        'id' => $include
       ]
        )) . "\n";
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'))
        . "\n";
        echo  new Option()
         ->value('0')
         ->selected(
          $body[$include]
          == '0'
         )
         ->content($translator->translate(
          'apply.before.item.tax'
         )) . "\n";
        echo  new Option()
         ->value('1')
         ->selected(
          $body[$include]
          == '1'
         )
         ->content($translator->translate(
          'apply.after.item.tax'
         )) . "\n";
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7
      echo H::openTag('div', $formGroup) . "\n"; //7
       $tyf = 'settings[this_tax_year_from_date_year]';
       echo H::openTag('label', [
        'for' => $tyf
       ]) . "\n";
        echo $translator->translate('tax') . ' ' .
        $translator->translate('start') . ' ' .
        $translator->translate('date') . ' ' .
        $translator->translate('year') . "\n";
       echo H::closeTag('label') . "\n";
       $body[$tyf] =
       $s->getSetting('this_tax_year_from_date_year');
       echo H::openTag('select', [
        'name' => $tyf,
        'id' => $tyf,
        'class' => 'form-control form-control-lg',
       ]) . "\n";
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'))
        . "\n";
        $years = [];
        for ($y = 1980, $now = (int) date('Y') + 10;
         $y <= $now; ++$y) {
         $years[$y] = ['name' => $y, 'value' => $y];
         }
         foreach ($years as $year) {
         echo  new Option()
         ->value($year['value'])
         ->selected(
         $body[$tyf]
         == (string) $year['value']
        )
         ->content((string) $year['value'])
        . "\n";
        }
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7
      echo H::openTag('div', $formGroup) . "\n"; //7
       $tym = 'settings[this_tax_year_from_date_month]';
       echo H::openTag('label', [
        'for' => $tym
       ]) . "\n";
        echo $translator->translate('tax') . ' ' .
        $translator->translate('start') . ' ' .
        $translator->translate('date') . ' ' .
        $translator->translate('month') . "\n";
       echo H::closeTag('label') . "\n";
       $body[$tym] =
       $s->getSetting('this_tax_year_from_date_month');
       echo H::openTag('select', [
        'name' => $tym,
        'id' => $tym,
        'class' => 'form-control form-control-lg',
       ]) . "\n";
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'))
        . "\n";
        $months = [
         '01', '02', '03', '04', '05', '06',
         '07', '08', '09', '10', '11', '12'
        ];
        foreach ($months as $month) {
        echo  new Option()
         ->value($month)
         ->selected(
          $body[$tym]
          == $month
         )
         ->content($month) . "\n";
        }
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7
      echo H::openTag('div', $formGroup) . "\n"; //7
       $tyd = 'settings[this_tax_year_from_date_day]';
       echo H::openTag('label', [
        'for' => $tyd
       ]) . "\n";
        echo $translator->translate('tax') . ' ' .
        $translator->translate('start') . ' ' .
        $translator->translate('date') . ' ' .
        rtrim($translator->translate('days'), 's')
        . "\n";
       echo H::closeTag('label') . "\n";
       $body[$tyd] =
       $s->getSetting('this_tax_year_from_date_day');
       echo H::openTag('select', [
        'name' => $tyd,
        'id' => $tyd,
        'class' => 'form-control form-control-lg',
       ]) . "\n";
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'))
        . "\n";
        $days = [
         '01', '02', '03', '04', '05', '06', '07',
         '08', '09', '10', '11', '12', '13', '14',
         '15', '16', '17', '18', '19', '20', '21',
         '22', '23', '24', '25', '26', '27', '28',
         '29', '30', '31'
        ];
        foreach ($days as $day) {
        echo  new Option()
         ->value($day)
         ->selected(
          $body[$tyd]
          == $day
         )
         ->content($day) . "\n";
        }
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7
     echo H::closeTag('div') . "\n"; //6
    echo H::closeTag('div') . "\n"; //5
   echo H::closeTag('div') . "\n"; //4
  echo H::closeTag('div') . "\n"; //3
 echo H::closeTag('div') . "\n"; //2
echo H::closeTag('div'); //1
