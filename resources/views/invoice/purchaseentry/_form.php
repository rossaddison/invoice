<?php

declare(strict_types=1);

use App\Invoice\PurchaseEntry\PurchaseEntryForm;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;

/**
 * Purpose: Enter purchase invoices for the purposes of VAT Report Boxes 4 and 7
 * Note: This index will be upgraded
 * @var App\Widget\Button $button
 * @var PurchaseEntryForm $form
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $title
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @var array<string, string[]> $errors
 */

$invalid = ' is-invalid';

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-7 offset-md-2']);

   echo H::openTag('div', ['class' => 'card']);
    echo H::openTag('div', ['class' =>
        'card-header d-flex justify-content-between align-items-center']);
     echo H::tag('strong', H::encode($title));
     echo H::a('← Back', '/entry',
        ['class' => 'btn btn-sm btn-outline-secondary']);
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'card-body']);

    echo new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('EntryForm')
    ->open();
            
      // date
      echo H::openTag('div', ['class' => 'mb-3']);
       echo H::label('Invoice Date', 'date')->addClass('form-label');
       echo H::input('date', 'date', $form->getDate() ?? '')->addAttributes([
           'id' => 'date',
           'class' => 'form-control' . (isset($errors['date']) ? $invalid : ''),
           'required' => true,
           'role' => 'presentation',
           'autocomplete' => 'off',
           'onclick' => 'this.showPicker()'
       ]);
       if (isset($errors['date'])) {
           echo H::tag('div', implode(' ', $errors['date']),
                ['class' => 'invalid-feedback']);
       }
      echo H::closeTag('div');

      // supplier
      echo H::openTag('div', ['class' => 'mb-3']);
       echo H::label('Supplier', 'supplier')->addClass('form-label');
       echo H::input(
               'text',
               'supplier',
               $form->getSupplier() ?? '')->addAttributes([
           'id' => 'supplier', 'class' => 'form-control'
                . (isset($errors['supplier']) ? $invalid : ''),
           'maxlength' => '200', 'required' => true,
       ]);
       if (isset($errors['supplier'])) {
           echo H::tag('div', implode(' ', $errors['supplier']),
                ['class' => 'invalid-feedback']);
       }
      echo H::closeTag('div');

      // description
      echo H::openTag('div', ['class' => 'mb-3']);
       echo H::label(
               'Description / Reference',
               'description')->addClass('form-label');
       echo H::textarea('description',
               $form->getDescription() ?? '')->addAttributes([
            'id' => 'description',
            'class' => 'form-control',
            'rows' => '2',
            'maxlength' => '500',
       ]);
      echo H::closeTag('div');

      // amount_ex_vat
      echo H::openTag('div', ['class' => 'mb-3']);
       echo H::label('Amount ex-VAT (£)',
            'amount_ex_vat')->addClass('form-label');
       echo H::input(
                'number',
                'amount_ex_vat',
                (string) ($form->getAmountExVat() ?? '0.00'))->addAttributes([
                    'id' => 'amount_ex_vat',
                    'class' => 'form-control'
                         . (isset($errors['amount_ex_vat']) ? $invalid : ''),
                    'step' => '0.01',
                    'min' => '0',
                    'required' => true,
       ]);
       if (isset($errors['amount_ex_vat'])) {
           echo H::tag('div', implode(' ',
                $errors['amount_ex_vat']),
                   ['class' => 'invalid-feedback']);
       }
      echo H::closeTag('div');

      // vat_amount
      echo H::openTag('div', ['class' => 'mb-3']);
       echo H::label('VAT Amount (£)', 'vat_amount')->addClass('form-label');
       echo H::input(
               'number',
               'vat_amount',
               (string) ($form->getVatAmount() ?? '0.00'))->addAttributes([
                    'id' => 'vat_amount',
                   'class' => 'form-control'
                        . (isset($errors['vat_amount']) ? $invalid : ''),
                'step' => '0.01',
                'min' => '0',
                'required' => true,
       ]);
       if (isset($errors['vat_amount'])) {
           echo H::tag('div', implode(' ', $errors['vat_amount']),
                ['class' => 'invalid-feedback']);
       }
      echo H::closeTag('div');

      echo H::openTag('div', ['class' => 'd-flex gap-2']);
        echo $button::backSave();
       echo H::closeTag('div');
    echo new Form()->close();
    echo H::closeTag('div');
   echo H::closeTag('div');
  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
