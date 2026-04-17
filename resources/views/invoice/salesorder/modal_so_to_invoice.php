<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Option;

/**
 * Related logic: see id="so-to-invoice" triggered by <a href="#so-to-invoice" data-bs-toggle="modal"  style="text-decoration:none"> on views/salesorder/view.php line 86
 * @var App\Invoice\Group\GroupRepository $gR
 * @var App\Infrastructure\Persistence\SalesOrder\SalesOrder $so
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 */

$formGroup = ['class' => 'form-group'];

echo H::openTag('div', ['id' => 'so-to-invoice', 'class' => 'modal', 'tabindex' => '-1']); //0
 echo H::openTag('div', ['class' => 'modal-dialog']); //1
  echo H::openTag('div', ['class' => 'modal-content']); //2
   echo H::openTag('div', ['class' => 'modal-header']); //3
    echo H::tag('h5', $translator->translate('salesorder.to.invoice'), ['class' => 'modal-title']);
    echo H::tag('button', '', [
     'type' => 'button',
     'class' => 'btn-close',
     'data-bs-dismiss' => 'modal',
     'aria-label' => 'Close',
    ]);
   echo H::closeTag('div'); //3
   echo H::openTag('div', ['class' => 'modal-body']); //3
    echo H::openTag('form'); //4
     echo new Input()->type('hidden')->name('_csrf')->value($csrf);
     echo new Input()->type('hidden')->name('client_id')
                                     ->id('client_id')
                                     ->value($so->getClientId());
     echo new Input()->type('hidden')->name('so_id')
                                     ->id('so_id')
                                     ->value($so->reqId());
     echo new Input()->type('hidden')->name('user_id')
                                     ->id('user_id')
                                     ->value($so->getUserId());
     echo H::openTag('div', $formGroup); //5
      echo H::openTag('label', ['for' => 'invoice_password']); //6
       echo $translator->translate('password');
      echo H::closeTag('label'); //6
      $prePassword = $s->getSetting('invoice_pre_password');
      echo new Input()
       ->type('text')
       ->name('password')
       ->id('invoice_password')
       ->class('form-control form-control-lg')
       ->value($prePassword !== '' ? H::encode($prePassword) : '')
       ->addAttributes(['autocomplete' => 'off']);
     echo H::closeTag('div'); //5
     echo H::openTag('div', $formGroup); //5
      echo H::openTag('label', ['for' => 'group_id']); //6
       echo $translator->translate('group');
      echo H::closeTag('label'); //6
      echo H::openTag('select', [
       'name' => 'group_id',
       'id' => 'group_id',
       'class' => 'form-control form-control-lg',
      ]); //6
       /**
        * @var App\Invoice\Entity\Group $group
        */
       foreach ($gR->findAllPreloaded() as $group) {
        echo new Option()
         ->value($group->getId())
         ->selected($s->getSetting('default_invoice_group') === $group->getId())
         ->content(H::encode($group->getName()));
       }
      echo H::closeTag('select'); //6
     echo H::closeTag('div'); //5
    echo H::closeTag('form'); //4
   echo H::closeTag('div'); //3
   echo H::openTag('div', ['class' => 'modal-footer']); //3
    echo H::openTag('div', ['class' => 'btn-group']); //4
     echo H::openTag('button', [
      'class' => 'so_to_invoice_confirm btn btn-success',
      'id' => 'so_to_invoice_confirm',
      'type' => 'button',
     ]); //5
      echo H::openTag('i', ['class' => 'bi bi-check-lg']); //6
      echo H::closeTag('i'); //6
      echo ' ' . $translator->translate('submit');
     echo H::closeTag('button'); //5
     echo H::openTag('button', [
      'class' => 'btn btn-danger',
      'type' => 'button',
      'data-bs-dismiss' => 'modal',
     ]); //5
      echo H::openTag('i', ['class' => 'bi bi-x-lg']); //6
      echo H::closeTag('i'); //6
      echo ' ' . $translator->translate('cancel');
     echo H::closeTag('button'); //5
    echo H::closeTag('div'); //4
   echo H::closeTag('div'); //3
  echo H::closeTag('div'); //2
 echo H::closeTag('div'); //1
echo H::closeTag('div'); //0
