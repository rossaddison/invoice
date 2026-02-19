<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Related logic: see PaymentInformationController function braintreeInForm
 * @var App\Invoice\Entity\Client $client_on_invoice
 * @var App\Invoice\Entity\Inv $invoice
 *
 * Related logic: see config\common\params 'yiisoft/view' => ['parameters' => ['clientHelper' => Reference::to(ClientHelper::class)]]
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 *
 * Related logic: see config\common\params 'yiisoft/view' => ['parameters' => ['dateHelper' => Reference::to(DateHelper::class)]]
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 *
 * Related logic: see config\common\params 'yiisoft/view' => ['parameters' => ['numberHelper' => Reference::to(NumberHelper::class)]]
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 *
 * Related logic: see config\common\params 'yiisoft/view' => ['parameters' => ['s' => Reference::to(SettingRepository::class)]]
 * @var App\Invoice\Setting\SettingRepository $s
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $disable_form
 * @var bool $is_overdue
 * @var float $balance
 * @var float $total
 * @var string $alert
 * @var string $client_token
 * @var string $companyLogo
 * @var string $inv_url_key
 * @var string $partial_client_address
 * @var string $payment_method
 * @var string $title
 */

if ($disable_form === false) {
    echo H::openTag('div', ['class' => 'container py-5 h-100']);
     echo H::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']);
      echo H::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']);
       echo H::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']);
        echo H::openTag('div', ['class' => 'card-header bg-dark text-white']);
         echo H::openTag('h2', ['class' => 'fw-normal h3 text-center']);
          echo H::openTag('div', ['class' => 'row gy-4']);
           echo H::openTag('div', ['class' => 'col-4']);
            echo H::tag('br');
            echo $companyLogo;
           echo H::closeTag('div');
           echo H::openTag('div', ['class' => 'col-8']);
            echo $translator->translate('online.payment.for.invoice') . ' # ';
            echo H::encode($invoice->getNumber() ?? '') . ' => '
             . H::encode($invoice->getClient()?->getClient_name() ?? '') . ' '
             . H::encode($invoice->getClient()?->getClient_surname() ?? '') . ' '
             . $numberHelper->format_currency($balance);
           echo H::closeTag('div');
          echo H::closeTag('div');
         echo H::closeTag('h2');
         echo H::openTag('a', [
             'href' => $urlGenerator->generate('inv/pdf_download_include_cf', ['url_key' => $inv_url_key]),
             'class' => 'btn btn-sm btn-primary fw-normal h3 text-center',
             'style' => 'text-decoration:none'
         ]);
          echo H::openTag('i', ['class' => 'fa fa-file-pdf-o']);
          echo H::closeTag('i');
          echo ' ' . $translator->translate('download.pdf') . '=>' . $translator->translate('yes') . ' ' . $translator->translate('custom.fields');
         echo H::closeTag('a');
         echo H::openTag('a', [
             'href' => $urlGenerator->generate('inv/pdf_download_exclude_cf', ['url_key' => $inv_url_key]),
             'class' => 'btn btn-sm btn-danger fw-normal h3 text-center',
             'style' => 'text-decoration:none'
         ]);
          echo H::openTag('i', ['class' => 'fa fa-file-pdf-o']);
          echo H::closeTag('i');
          echo ' ' . $translator->translate('download.pdf') . '=>' . $translator->translate('no') . ' ' . $translator->translate('custom.fields');
         echo H::closeTag('a');
        echo H::closeTag('div');
        echo H::tag('br');
        echo H::tag('Div', H::tag('H4', $title, ['data-toggle' => 'tooltip','title' => 'Test card: 4111 1111 1111 1111 Expiry-date: 06/34']));
        echo H::tag('br');
        echo H::openTag('div', ['class' => 'card-body p-5 text-center']);
         echo $alert;
         echo H::openTag('div', ['id' => 'dropin-container']);
         echo H::closeTag('div');
         echo H::openTag('input', ['type' => 'submit']);
         echo H::closeTag('input');
         echo H::openTag('input', ['type' => 'hidden', 'id' => 'nonce', 'name' => 'payment_method_nonce']);
         echo H::closeTag('input');
         echo $companyLogo;
         echo H::tag('br');
         echo H::tag('br');
         echo H::encode($clientHelper->format_client($client_on_invoice));
         echo $partial_client_address;
         echo H::tag('br');
         echo H::openTag('div', ['class' => 'table-responsive']);
          echo H::openTag('table', ['class' => 'table table-bordered table-condensed no-margin']);
           echo H::openTag('tbody');
            echo H::openTag('tr');
             echo H::openTag('td');
              echo $translator->translate('date');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-right']);
              echo H::encode($invoice->getDate_created()->format('Y-m-d'));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr', ['class' => ($is_overdue ? 'overdue' : '')]);
             echo H::openTag('td');
              echo $translator->translate('due.date');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-right']);
              echo H::encode($invoice->getDate_due()->format('Y-m-d'));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr', ['class' => ($is_overdue ? 'overdue' : '')]);
             echo H::openTag('td');
              echo $translator->translate('total');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-right']);
              echo H::encode($numberHelper->format_currency($total));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr', ['class' => ($is_overdue ? 'overdue' : '')]);
             echo H::openTag('td');
              echo $translator->translate('balance');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-right']);
              echo H::encode($numberHelper->format_currency($balance));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            if ($payment_method) {
                echo H::openTag('tr');
                 echo H::openTag('td');
                  echo $translator->translate('payment.method') . ': ';
                 echo H::closeTag('td');
                 echo H::openTag('td', ['class' => 'text-right']);
                  echo $payment_method;
                 echo H::closeTag('td');
                echo H::closeTag('tr');
            }
           echo H::closeTag('tbody');
          echo H::closeTag('table');
         echo H::closeTag('div');
         if (!empty($invoice->getTerms())) {
             echo H::openTag('div', ['class' => 'col-xs-12 text-muted']);
              echo H::tag('br');
              echo H::openTag('h4');
               echo $translator->translate('terms');
              echo H::closeTag('h4');
              echo H::openTag('div');
               echo nl2br(H::encode($invoice->getTerms()));
              echo H::closeTag('div');
             echo H::closeTag('div');
         }
        echo H::closeTag('div');
       echo H::closeTag('div');
      echo H::closeTag('div');
     echo H::closeTag('div');
    echo H::closeTag('div');
}
$js22 = 'const form = document.getElementById("payment-form");'
        . 'braintree.dropin.create('
        . '{'
        . 'authorization: "' . $client_token . '",'
        . 'container: "#dropin-container"'
        . '}, '
        . '(error, dropinInstance) => {'
        . '    if (error) console.error(error);'
        . '    form.addEventListener("submit", event => {'
        . '       event.preventDefault();'
        . '       dropinInstance.requestPaymentMethod((error, payload) => {'
        . '          if (error) console.error(error);'
        . '          document.getElementById("nonce").value = payload.nonce;'
        . '          form.submit();'
        . '       });'
        . '    });'
        . '}'
        . ');';
echo H::script($js22)->type('module')->charset('utf-8');

