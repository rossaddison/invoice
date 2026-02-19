<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Related logic: see App\Invoice\Client\ClientController function
 *  view 'payment_table'
 *
 * @var App\Invoice\Entity\Client $client
 *
 * Related logic: see There is no need to declare ClientHelper in the
 * ClientController because it is declared in:
 * Related logic: see config\common\params 'yiisoft/view' =>
 *  ['parameters' => 'clientHelper' Reference::to(ClientHelper::class),
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 *
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $payments
 * @var string $csrf
 */

echo H::openTag('div', ['class' => 'table-responsive']);
 echo H::openTag('table', ['class' => 'table table-hover table-striped']);
  echo H::openTag('thead');
   echo H::openTag('tr');
    echo H::openTag('th');
     echo $translator->translate('payment.date');
    echo H::closeTag('th');
    echo H::openTag('th');
     echo $translator->translate('date');
    echo H::closeTag('th');
    echo H::openTag('th');
     echo $translator->translate('invoice');
    echo H::closeTag('th');
    echo H::openTag('th');
     echo $translator->translate('client');
    echo H::closeTag('th');
    echo H::openTag('th');
     echo $translator->translate('amount');
    echo H::closeTag('th');
    echo H::openTag('th');
     echo $translator->translate('payment.method');
    echo H::closeTag('th');
    echo H::openTag('th');
     echo $translator->translate('note');
    echo H::closeTag('th');
    echo H::openTag('th');
     echo $translator->translate('options');
    echo H::closeTag('th');
   echo H::closeTag('tr');
  echo H::closeTag('thead');
  echo H::openTag('tbody');
   /**
    * @var App\Invoice\Entity\Payment $payment
    */
   foreach ($payments as $payment) {
       if ($payment->getInv()?->getClient_id() === $client->getClient_id()) {
           echo H::openTag('tr');
            echo H::openTag('td');
             echo (!is_string($paymentDate = $payment->getPayment_date()) ?
                                        ($paymentDate->format('Y-m-d')) : '');
            echo H::closeTag('td');
            echo H::openTag('td');
             echo ($payment->getInv()?->getDate_created())->format('Y-m-d');
            echo H::closeTag('td');
            echo H::openTag('td');
             echo H::openTag('a', [
                 'href' => $urlGenerator->generate('inv/view',
                         ['id' => $payment->getInv_id()])
             ]);
              echo H::encode($payment->getInv()?->getNumber() ?? '#');
             echo H::closeTag('a');
            echo H::closeTag('td');
            echo H::openTag('td');
             echo H::openTag('a', [
                 'href' => $urlGenerator->generate('client/view',
                         ['id' => $payment->getInv()?->getClient_id()]),
                 'title' => $translator->translate('view.client')
             ]);
              echo H::encode($clientHelper->format_client(
                      $payment->getInv()?->getClient()));
             echo H::closeTag('a');
            echo H::closeTag('td');
            echo H::openTag('td', ['class' => 'amount']);
             echo $s->format_currency($payment->getAmount() ?? 0.00);
            echo H::closeTag('td');
            echo H::openTag('td');
             echo H::encode($payment->getPaymentMethod()?->getName() ?? '');
            echo H::closeTag('td');
            echo H::openTag('td');
             echo H::encode($payment->getNote() ?: '');
            echo H::closeTag('td');
            echo H::openTag('td');
             echo H::openTag('div', ['class' => 'options btn-group']);
              echo H::openTag('a', [
                  'class' => 'btn btn-default btn-sm dropdown-toggle',
                  'data-bs-toggle' => 'dropdown',
                  'href' => '#'
              ]);
               echo H::openTag('i', ['class' => 'fa fa-cog']);
               echo H::closeTag('i');
               echo ' ' . $translator->translate('options');
              echo H::closeTag('a');
              echo H::openTag('ul', ['class' => 'dropdown-menu']);
               echo H::openTag('li');
                echo H::openTag('a', [
                    'href' => $urlGenerator->generate('client/view',
                            ['id' => $payment->getInv()?->getClient_id()]),
                    'title' => $translator->translate('view.client')
                ]);
                 echo H::encode($clientHelper->format_client(
                         $payment->getInv()?->getClient()));
                echo H::closeTag('a');
                echo H::openTag('a', [
                    'href' => $urlGenerator->generate('payment/edit',
                            ['id' => $payment->getId()])
                ]);
                 echo H::openTag('i', ['class' => 'fa fa-edit fa-margin']);
                 echo H::closeTag('i');
                 echo $translator->translate('edit');
                echo H::closeTag('a');
               echo H::closeTag('li');
               echo H::openTag('li');
                echo H::openTag('form', [
                    'action' => $urlGenerator->generate('payment/delete',
                            ['id' => $payment->getId()]),
                    'method' => 'POST'
                ]);
                 echo H::openTag('input', [
                     'type' => 'hidden',
                     'id' => '_csrf',
                     'name' => '_csrf',
                     'value' => $csrf
                 ]);
                 echo H::closeTag('input');
                 echo H::openTag('button', [
                     'type' => 'submit',
                     'class' => 'dropdown-button',
                     'onclick' => "return confirm('"
                     . $translator->translate('delete.record.warning') . "');"
                 ]);
                  echo H::openTag('i', ['class' => 'fa fa-trash-o fa-margin']);
                  echo H::closeTag('i');
                  echo ' ' . $translator->translate('delete');
                 echo H::closeTag('button');
                echo H::closeTag('form');
               echo H::closeTag('li');
              echo H::closeTag('ul');
             echo H::closeTag('div');
            echo H::closeTag('td');
           echo H::closeTag('tr');
       }
   }
  echo H::closeTag('tbody');
 echo H::closeTag('table');
echo H::closeTag('div');
