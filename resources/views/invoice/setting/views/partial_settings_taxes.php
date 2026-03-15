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
$formControl = ['class' => 'form-control'];
$minSearch = ['data-minimum-results-for-search' => 'Infinity'];

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
       $body['settings[default_invoice_tax_rate]'] = 
       $s->getSetting('default_invoice_tax_rate');
       echo H::openTag('select', [
        'name' => 'settings[default_invoice_tax_rate]',
        'id' => 'settings[default_invoice_tax_rate]',
        'class' => 'form-control'
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
          $body['settings[default_invoice_tax_rate]']
          == $taxRateId
         )
         ->content($content) . "\n";
        }
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7

      echo H::openTag('div', $formGroup) . "\n"; //7
       echo H::openTag('label', [
        'for' => 'settings[default_item_tax_rate]'
       ]) . "\n";
        echo $translator->translate(
         'default.item.tax.rate'
        ) . "\n";
       echo H::closeTag('label') . "\n";
       $body['settings[default_item_tax_rate]'] = 
       $s->getSetting('default_item_tax_rate');
       echo H::openTag('select', [
        'name' => 'settings[default_item_tax_rate]',
        'id' => 'settings[default_item_tax_rate]',
        'class' => 'form-control'
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
          $body['settings[default_item_tax_rate]']
          == $taxRateId
         )
         ->content($content) . "\n";
        }
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7

     echo H::closeTag('div') . "\n"; //6
     echo H::openTag('div', $colMd6) . "\n"; //6

      echo H::openTag('div', $formGroup) . "\n"; //7
       echo H::openTag('label', [
        'for' => 'settings[default_include_item_tax]'
       ]) . "\n";
        echo $translator->translate(
         'default.invoice.tax.rate.placement'
        ) . "\n";
       echo H::closeTag('label') . "\n";
       $body['settings[default_include_item_tax]'] = 
       $s->getSetting('default_include_item_tax');
       echo H::openTag('select', array_merge($formControl, 
        $minSearch, [
        'name' => 'settings[default_include_item_tax]',
        'id' => 'settings[default_include_item_tax]'
       ]
        )) . "\n";
        echo  new Option()
         ->value('')
         ->content($translator->translate('none'))
        . "\n";
        echo  new Option()
         ->value('0')
         ->selected(
          $body['settings[default_include_item_tax]']
          == '0'
         )
         ->content($translator->translate(
          'apply.before.item.tax'
         )) . "\n";
        echo  new Option()
         ->value('1')
         ->selected(
          $body['settings[default_include_item_tax]']
          == '1'
         )
         ->content($translator->translate(
          'apply.after.item.tax'
         )) . "\n";
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7
      echo H::openTag('div', $formGroup) . "\n"; //7
       echo H::openTag('label', [
        'for' => 'settings[this_tax_year_from_date_year]'
       ]) . "\n";
        echo $translator->translate('tax') . ' ' . 
        $translator->translate('start') . ' ' . 
        $translator->translate('date') . ' ' . 
        $translator->translate('year') . "\n";
       echo H::closeTag('label') . "\n";
       $body['settings[this_tax_year_from_date_year]'] = 
       $s->getSetting('this_tax_year_from_date_year');
       echo H::openTag('select', [
        'name' => 'settings[this_tax_year_from_date_year]',
        'id' => 'settings[this_tax_year_from_date_year]',
        'class' => 'form-control'
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
         $body['settings[this_tax_year_from_date_year]']
         == (string) $year['value']
        )
         ->content((string) $year['value']) 
        . "\n";
        }
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7
      echo H::openTag('div', $formGroup) . "\n"; //7
       echo H::openTag('label', [
        'for' => 'settings[this_tax_year_from_date_month]'
       ]) . "\n";
        echo $translator->translate('tax') . ' ' . 
        $translator->translate('start') . ' ' . 
        $translator->translate('date') . ' ' . 
        $translator->translate('month') . "\n";
       echo H::closeTag('label') . "\n";
       $body['settings[this_tax_year_from_date_month]'] = 
       $s->getSetting('this_tax_year_from_date_month');
       echo H::openTag('select', [
        'name' => 
        'settings[this_tax_year_from_date_month]',
        'id' => 'settings[this_tax_year_from_date_month]',
        'class' => 'form-control'
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
          $body['settings[this_tax_year_from_date_month]']
          == $month
         )
         ->content($month) . "\n";
        }
       echo H::closeTag('select') . "\n";
      echo H::closeTag('div') . "\n"; //7
      echo H::openTag('div', $formGroup) . "\n"; //7
       echo H::openTag('label', [
        'for' => 'settings[this_tax_year_from_date_day]'
       ]) . "\n";
        echo $translator->translate('tax') . ' ' . 
        $translator->translate('start') . ' ' . 
        $translator->translate('date') . ' ' . 
        rtrim($translator->translate('days'), 's') 
        . "\n";
       echo H::closeTag('label') . "\n";
       $body['settings[this_tax_year_from_date_day]'] = 
       $s->getSetting('this_tax_year_from_date_day');
       echo H::openTag('select', [
        'name' => 'settings[this_tax_year_from_date_day]',
        'id' => 'settings[this_tax_year_from_date_day]',
        'class' => 'form-control'
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
          $body['settings[this_tax_year_from_date_day]']
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
