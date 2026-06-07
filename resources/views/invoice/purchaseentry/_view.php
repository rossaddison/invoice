<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use App\Invoice\PurchaseEntry\PurchaseEntryForm;
use Yiisoft\Html\Html as H;

/**
 * @var PurchaseEntry $entry
 * @var PurchaseEntryForm $form
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$date        = $form->getDate() ?? '';
$supplier    = $form->getSupplier() ?? '';
$description = $form->getDescription() ?? '';
$amountExVat = number_format($form->getAmountExVat() ?? 0.00, 2);
$vatAmount   = number_format($form->getVatAmount() ?? 0.00, 2);
$total       = number_format(($form->getAmountExVat() ?? 0.00) + ($form->getVatAmount() ?? 0.00), 2);
$createdAt   = $entry->getCreatedAt()->format('Y-m-d H:i');

echo H::openTag('div', ['class' => 'container mt-4']);
 echo H::openTag('div', ['class' => 'row']);
  echo H::openTag('div', ['class' => 'col-12 col-md-7 offset-md-2']);

   echo H::openTag('div', ['class' => 'card']);
    echo H::openTag('div', ['class' => 'card-header d-flex justify-content-between align-items-center']);
     echo H::tag('strong', 'Purchase Entry #' . H::encode((string) $entry->reqId()));
     echo H::openTag('div', ['class' => 'd-flex gap-2']);
      echo H::a('✎ Edit',
          $urlGenerator->generate('entry/edit', ['id' => $entry->reqId()]),
          ['class' => 'btn btn-sm btn-outline-primary']);
      echo H::a('← Back',
          $urlGenerator->generate('entry/index'),
          ['class' => 'btn btn-sm btn-outline-secondary']);
     echo H::closeTag('div');
    echo H::closeTag('div');

    echo H::openTag('div', ['class' => 'card-body']);
     echo H::openTag('dl', ['class' => 'row mb-0']);

      echo H::tag('dt', 'Invoice Date', ['class' => 'col-sm-4']);
      echo H::tag('dd', H::encode($date), ['class' => 'col-sm-8']);

      echo H::tag('dt', 'Supplier', ['class' => 'col-sm-4']);
      echo H::tag('dd', H::encode($supplier), ['class' => 'col-sm-8']);

      echo H::tag('dt', 'Description', ['class' => 'col-sm-4']);
      echo H::tag('dd',
          $description !== '' ? H::encode($description) : H::tag('span', '—', ['class' => 'text-muted']),
          ['class' => 'col-sm-8']);

      echo H::tag('dt', 'Amount ex-VAT', ['class' => 'col-sm-4']);
      echo H::tag('dd', H::encode($amountExVat), ['class' => 'col-sm-8 font-monospace']);

      echo H::tag('dt', 'VAT Amount', ['class' => 'col-sm-4']);
      echo H::tag('dd', H::encode($vatAmount), ['class' => 'col-sm-8 font-monospace']);

      echo H::tag('dt', 'Total', ['class' => 'col-sm-4']);
      echo H::tag('dd', H::encode($total), ['class' => 'col-sm-8 font-monospace fw-bold']);

      echo H::openTag('dt', ['class' => 'col-sm-4']);
       echo H::tag('hr', '');
      echo H::closeTag('dt');
      echo H::openTag('dd', ['class' => 'col-sm-8']);
       echo H::tag('hr', '');
      echo H::closeTag('dd');

      echo H::tag('dt', 'Created', ['class' => 'col-sm-4']);
      echo H::tag('dd', H::encode($createdAt), ['class' => 'col-sm-8 text-muted small']);

     echo H::closeTag('dl');
    echo H::closeTag('div');
   echo H::closeTag('div');

  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
