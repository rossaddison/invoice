<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Related logic: see id="modal-copy-inv-multiple" triggered by
 *  <a href="#modal-copy-inv-multiple" data-bs-toggle="modal">
 * Related logic: see InvController index function
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var iterable $clients
 * @var string $csrf
 */

echo H::openTag('div', ['id' => 'modal-copy-inv-multiple', 'class' => 'modal',
    'tabindex' => '-1']); //0
 echo H::openTag('div', ['class' => 'modal-dialog']); //1
  echo H::openTag('div', ['class' => 'modal-content']); //2
   echo H::openTag('div', ['class' => 'modal-header']); //3
    echo H::openTag('h5', ['class' => 'modal-title']); //4
     echo $translator->translate('copy.invoice');
    echo H::closeTag('h5'); //4
    echo H::tag('button', '', [
     'type'             => 'button',
     'class'            => 'btn-close',
     'data-bs-dismiss'  => 'modal',
     'aria-label'       => 'Close',
    ]);
   echo H::closeTag('div'); //3
   echo H::openTag('div', ['class' => 'modal-body']); //3
    echo H::openTag('form'); //4
     echo H::hiddenInput('_csrf', $csrf);
     echo H::openTag('div', ['class' => 'mb-3']); //5
      echo H::openTag('label', ['for' => 'modal_created_date']); //6
       echo $translator->translate('date.created');
      echo H::closeTag('label'); //6
      echo H::tag('input', '', [
       'name'         => 'modal_created_date',
       'id'           => 'modal_created_date',
       'class'        => 'form-control form-control-lg',
       'type'         => 'date',
       'autocomplete' => 'off',
       'onclick'      => 'this.showPicker()',
      ]);
     echo H::closeTag('div'); //5
     echo H::openTag('div', ['class' => 'mb-2']); //5
      echo H::tag('input', '', [
       'type'         => 'text',
       'id'           => 'copy-inv-multiple-client-search',
       'class'        => 'form-control form-control-sm',
       'placeholder'  => $translator->translate('search') . '…',
       'autocomplete' => 'off',
      ]);
     echo H::closeTag('div'); //5
     echo H::openTag('div', ['id' => 'copy-inv-multiple-client-list',
         'style' => 'max-height:280px;overflow-y:auto;']); //5
      /**
       * @var App\Infrastructure\Persistence\Client\Client $client
       */
      foreach ($clients as $client) {
       $id = $client->reqId();
       echo H::openTag('div', ['class' => 'form-check']); //6
        echo H::tag('input', '', [
         'class' => 'form-check-input',
         'type'  => 'checkbox',
         'name'  => 'copy_inv_multiple_client_ids[]',
         'value' => $id,
         'id'    => 'copy_inv_multiple_client_' . $id,
        ]);
        echo H::openTag('label', ['class' => 'form-check-label',
            'for' => 'copy_inv_multiple_client_' . $id]); //7
         echo H::encode($client->getClientName());
        echo H::closeTag('label'); //7
       echo H::closeTag('div'); //6
      }
     echo H::closeTag('div'); //5
    echo H::closeTag('form'); //4
   echo H::closeTag('div'); //3
   echo H::openTag('div', ['class' => 'modal-footer']); //3
    echo H::tag('button', $translator->translate('cancel'), [
     'type'            => 'button',
     'class'           => 'btn btn-secondary',
     'data-bs-dismiss' => 'modal',
    ]);
    echo H::openTag('button', [
     'type'  => 'button',
     'class' => 'modal_copy_inv_multiple_confirm btn btn-success',
     'id'    => 'modal_copy_inv_multiple_confirm',
    ]); //3
     echo H::tag('i', '', ['class' => 'bi bi-check-lg']);
     echo ' ' . $translator->translate('submit');
    echo H::closeTag('button'); //3
   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0
