<?php

use App\Invoice\Setting\SettingRepository as sR;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Html as H;
use Yiisoft\Translator\Translator;
use Yiisoft\Router\FastRoute\UrlGenerator;

/**
 * @var sR           $s
 * @var Translator   $translator
 * @var UrlGenerator $urlGenerator
 * @var string       $alert
 * @var string       $title
 * @var float        $balance
 * @var float        $total
 * @var string       $authUrl
 * @var string       $client_chosen_gateway
 * @var array|object $client_on_invoice
 * @var object|array $invoice
 * @var string       $inv_url_key
 * @var bool         $authToken
 * @var bool         $is_overdue
 * @var bool         $disable_form
 * @var string       $companyLogo
 * @var string       $partial_client_address
 * @var string       $payment_method
 * @var string       $provider
 * @var string       $json_encoded_items
 * @var string       $wonderfulId
 * @var string       $amountFormatted
 * @var string       $reference
 * @var string       $createdAt
 * @var string       $updatedAt
 * @var string       $status
 * @var string       $paymentLink
 */

$clientName = '';
if (is_object($client_on_invoice)
        && method_exists($client_on_invoice, 'getClient_name')) {
    /** @var string|null $maybeName */
    $maybeName = $client_on_invoice->getClient_name();
    if (is_string($maybeName)) {
        $clientName = $maybeName;
    }
} elseif (is_array($client_on_invoice)
        && isset($client_on_invoice['client_name'])
        && is_string($client_on_invoice['client_name'])) {
    $clientName = $client_on_invoice['client_name'];
}

echo H::openTag('div', ['class' => 'container py-4']);
 // Alert message
 if (!empty($alert)) {
     echo H::openTag('div', ['class' => 'mb-3']);
      echo $alert;
     echo H::closeTag('div');
 }
 echo H::openTag('div', ['class' => 'card shadow-sm mb-4']);
  echo H::openTag('div', ['class' => 'card-header bg-primary text-white']);
   echo H::openTag('div', ['class' => 'd-flex align-items-center']);
    if (!empty($companyLogo)) {
        echo H::openTag('span', ['class' => 'me-3']);
         echo $companyLogo;
        echo H::closeTag('span');
    }
    echo H::openTag('h2', ['class' => 'mb-0']);
     echo H::encode($title);
    echo H::closeTag('h2');
   echo H::closeTag('div');
  echo H::closeTag('div');
  echo H::openTag('div', ['class' => 'card-body']);
   echo H::openTag('div', ['class' => 'row mb-3']);
    echo H::openTag('div', ['class' => 'col-md-6']);
     echo H::openTag('p', ['class' => 'fs-4 mb-0']);
      echo H::openTag('strong');
       echo H::encode($translator->translate('amount.payment'));
      echo H::closeTag('strong');
      echo ': ';
      echo H::openTag('span',
              ['class' => 'text-success fw-bold display-6',
                  'style' => 'letter-spacing:1px;']);
       echo H::encode($s->format_currency($balance));
      echo H::closeTag('span');
     echo H::closeTag('p');
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => 'col-md-6']);
     echo H::openTag('p');
      echo H::openTag('strong');
       echo H::encode($translator->translate('client'));
      echo H::closeTag('strong');
      echo ': ';
      echo H::encode($clientName);
     echo H::closeTag('p');
    echo H::closeTag('div');
   echo H::closeTag('div');
   if (!empty($partial_client_address)) {
       echo H::openTag('div', ['class' => 'mb-2']);
        echo $partial_client_address;
       echo H::closeTag('div');
   }
   echo H::openTag('p');
    echo H::openTag('strong');
     echo H::encode($translator->translate('invoice'));
    echo H::closeTag('strong');
    echo ': ';
    echo H::encode($inv_url_key);
   echo H::closeTag('p');
   if ($is_overdue) {
       echo H::openTag('div', ['class' => 'alert alert-warning mb-3']);
        echo H::openTag('i', ['class' => 'bi bi-exclamation-triangle-fill']);
        echo H::closeTag('i');
        echo ' ';
        echo H::encode($translator->translate('invoice.is.overdue'));
       echo H::closeTag('div');
   }
   if ($disable_form) {
       echo H::openTag('div', ['class' => 'alert alert-info mb-3']);
        echo H::openTag('i', ['class' => 'bi bi-info-circle-fill']);
        echo H::closeTag('i');
        echo ' ';
        echo H::encode($translator->translate('form.disabled.already.paid'));
       echo H::closeTag('div');
   }
   // Payment Actions
   echo H::openTag('div', ['class' => 'mt-4']);
    if (!empty($authUrl) && !$disable_form) {
        echo A::tag()
        ->addAttributes([
            'class' => 'btn btn-primary btn-lg',
            'rel' => 'noopener noreferrer',
            'target' => '_blank',
        ])
        ->href($authUrl)
        ->content(H::encode(
            $translator->translate('open.banking.pay.with')
            . $provider))
        ->render();
    } elseif ($disable_form) {
        echo H::openTag('p', ['class' => 'text-muted']);
         echo H::encode($translator->translate('open.banking.payment.not.required'));
        echo H::closeTag('p');
    } elseif ($authToken) {
        echo H::openTag('div', ['class' => 'wonderful-payment-summary mb-3']);
         echo H::openTag('h4', ['class' => 'mb-3']);
          echo H::encode($translator->translate('details'));
         echo H::closeTag('h4');
         echo H::openTag('div', ['class' => 'table-responsive']);
          echo H::openTag('table', ['class' => 'table table-striped align-middle']);
           echo H::openTag('tbody');
            echo H::openTag('tr');
             echo H::openTag('th', ['scope' => 'row']);
              echo 'Wonderful Id';
             echo H::closeTag('th');
             echo H::openTag('td');
              echo H::encode($wonderfulId);
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr');
             echo H::openTag('th', ['scope' => 'row']);
              echo H::encode($translator->translate('Amount'));
             echo H::closeTag('th');
             echo H::openTag('td');
              echo H::openTag('span', ['class' => 'text-success fw-bold fs-5']);
               echo H::encode($amountFormatted);
              echo H::closeTag('span');
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr');
             echo H::openTag('th', ['scope' => 'row']);
              echo H::encode($translator->translate('Status'));
             echo H::closeTag('th');
             echo H::openTag('td');
              echo H::encode(ucfirst($status));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr');
             echo H::openTag('th', ['scope' => 'row']);
              echo H::encode($translator->translate('Reference'));
             echo H::closeTag('th');
             echo H::openTag('td');
              echo H::encode($reference);
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr');
             echo H::openTag('th', ['scope' => 'row']);
              echo H::encode($translator->translate('Created At'));
             echo H::closeTag('th');
             echo H::openTag('td');
              echo H::encode($createdAt);
             echo H::closeTag('td');
            echo H::closeTag('tr');
           echo H::closeTag('tbody');
          echo H::closeTag('table');
         echo H::closeTag('div');
        echo H::closeTag('div');
        if ('created' == $status) {
            echo A::tag()
              ->addAttributes([
                    'class' => 'btn btn-success btn-lg',
                    'rel' => 'noopener noreferrer',
                    'target' => '_blank'
              ])
              ->href(H::encode($paymentLink))
              ->content(H::encode($translator->translate('open.banking.pay.with')
                     . ' Wonderful'))
              ->render();
        }
    } else {
        echo H::openTag('p', ['class' => 'text-muted']);
         echo H::encode($translator->translate('open.banking.not.configured'));
        echo H::closeTag('p');
    }
   echo H::closeTag('div');
  echo H::closeTag('div');
 echo H::closeTag('div');
echo H::closeTag('div');
