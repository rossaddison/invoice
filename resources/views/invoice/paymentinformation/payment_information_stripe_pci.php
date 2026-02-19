<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Related logic: see PaymentInformationController function stripeInForm
 * @var App\Invoice\Entity\Client $client_on_invoice
 * @var App\Invoice\Entity\Inv $invoice
 *
 * Related logic: see config\common\params 'yiisoft/view'
 *  => ['parameters' => ['clientHelper' => Reference::to(ClientHelper::class)]]
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 *
 * Related logic: see config\common\params 'yiisoft/view'
 *  => ['parameters' => ['dateHelper' => Reference::to(DateHelper::class)]]
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 *
 * Related logic: see config\common\params 'yiisoft/view'
 *  => ['parameters' => ['numberHelper' => Reference::to(NumberHelper::class)]]
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 *
 * Related logic: see config\common\params 'yiisoft/view'
 *  => ['parameters' => ['s' => Reference::to(SettingRepository::class)]]
 * @var App\Invoice\Setting\SettingRepository $s
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $disable_form
 * @var bool $is_overdue
 * @var float $balance
 * @var float $total
 * @var string $alert
 * @var string $client_secret
 * @var string $companyLogo
 * @var string $json_encoded_items
 * @var string $inv_url_key
 * @var string $partial_client_address
 * @var string $payment_method
 * @var string $pci_client_publishable_key
 * @var string $title
 */

if ($disable_form === false) {
    echo H::openTag('div', ['class' => 'container py-5 h-100']);
     echo H::openTag('div',
        ['class' => 'row d-flex justify-content-center align-items-center h-100']);
      echo H::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']);
       echo H::openTag('div',
               ['class' => 'card border border-dark shadow-2-strong rounded-3']);
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
             'href' => $urlGenerator->generate('inv/pdf_download_include_cf',
                     ['url_key' => $inv_url_key]),
             'class' => 'btn btn-sm btn-primary fw-normal h3 text-center',
             'style' => 'text-decoration:none'
         ]);
          echo H::openTag('i', ['class' => 'fa fa-file-pdf-o']);
          echo H::closeTag('i');
          echo ' ' . $translator->translate('download.pdf')
                  . '=>' . $translator->translate('yes')
                  . ' ' . $translator->translate('custom.fields');
         echo H::closeTag('a');
         echo H::openTag('a', [
             'href' => $urlGenerator->generate('inv/pdf_download_exclude_cf',
                     ['url_key' => $inv_url_key]),
             'class' => 'btn btn-sm btn-danger fw-normal h3 text-center',
             'style' => 'text-decoration:none'
         ]);
          echo H::openTag('i', ['class' => 'fa fa-file-pdf-o']);
          echo H::closeTag('i');
          echo ' ' . $translator->translate('download.pdf')
                  . '=>' . $translator->translate('no')
                  . ' ' . $translator->translate('custom.fields');
         echo H::closeTag('a');
        echo H::closeTag('div');
        echo H::tag('br');
        echo H::tag('Div', H::tag('H4', $title));
        echo H::tag('br');
        echo H::openTag('div', ['class' => 'card-body p-5 text-center']);
         echo H::openTag('form', ['method' => 'post',
             'enctype' => 'multipart/form-data', 'id' => 'payment-form']);
          echo $alert;
          // Stripe injects the payment element here
          echo H::tag('Div', '', ['id' => 'payment-element']);
          // Stripe payment message
          echo H::tag('Div', '', ['id' => 'payment-message', 'class' => 'hidden']);
          echo H::openTag('button', [
              'type' => 'submit',
              'id' => 'submit',
              'class' => 'btn btn-lg btn-success fa fa-credit-card fa-margin'
          ]);
           echo H::openTag('div', ['class' => 'spinner hidden', 'id' => 'spinner']);
           echo H::closeTag('div');
           echo H::openTag('span', ['id' => 'button-text']);
            echo ' ' . $translator->translate('pay.now')
                    . ': ' . $numberHelper->format_currency($balance);
           echo H::closeTag('span');
          echo H::closeTag('button');
          echo H::encode($clientHelper->format_client($client_on_invoice));
          echo $partial_client_address;
          echo H::tag('br');
          echo H::openTag('div', ['class' => 'table-responsive']);
           echo H::openTag('table',
                   ['class' => 'table table-bordered table-condensed no-margin']);
            echo H::openTag('tbody');
             echo H::openTag('tr');
              echo H::openTag('th', ['scope' => 'col']);
                echo $translator->translate('item');
              echo H::closeTag('th');
              echo H::openTag('th', ['scope' => 'col']);
                echo $translator->translate('value');
              echo H::closeTag('th');
             echo H::closeTag('tr');
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
               $paymentTermArray = $s->get_payment_term_array($translator);
               echo H::openTag('div');
                echo nl2br(H::encode($paymentTermArray[$invoice->getTerms()] ?? ''));
               echo H::closeTag('div');
              echo H::closeTag('div');
          }
         echo H::closeTag('form');
        echo H::closeTag('div');
       echo H::closeTag('div');
      echo H::closeTag('div');
     echo H::closeTag('div');
    echo H::closeTag('div');
}
// This is your test publishable API key.
$js18
= 'const stripe = Stripe("' . $pci_client_publishable_key . '");'
. 'let elements;'
. 'const items = [' . $json_encoded_items . '];'
. 'initialize();'
. 'checkStatus();'
. 'document.querySelector("#payment-form").addEventListener("submit", handleSubmit);'
. 'async function initialize() {'
    // To avoid Error 422 Unprocessible entity
    // const { clientSecret } = await fetch("/create.php", {
    // method: "POST",
    // headers: { "Content-Type": "application/json" },
    // body: JSON.stringify({ items }),
    // }).then((r) => r.json());
    . 'const { clientSecret } = {"clientSecret": "' . $client_secret . '"};'
    . 'elements = stripe.elements({ clientSecret });'
    . 'const paymentElementOptions = {'
        . 'layout: "tabs"'
    . '};'
    . 'const paymentElement = elements.create("payment", paymentElementOptions);'
    . 'paymentElement.mount("#payment-element");'
. '}'
. 'async function handleSubmit(e) {'
    . 'e.preventDefault();'
    . 'setLoading(true);'
    . 'const { error } = await stripe.confirmPayment({'
        . 'elements,'
        . 'confirmParams: {'
            . 'return_url: "'
        . $urlGenerator->generateAbsolute('paymentinformation/stripe_complete',
                ['url_key' => $inv_url_key]) . '"'
        . '},'
    . '});'
    . 'if (error.type === "card_error" || error.type === "validation_error") {'
        . 'showMessage(error.message);'
    . '} else {'
        . 'showMessage("An unexpected error occurred.");'
    . '}'
    . 'setLoading(false);'
. '}'
. 'async function checkStatus() {'
. 'const clientSecret ='
. ' new URLSearchParams(window.location.search).get("payment_intent_client_secret");'
. 'if (!clientSecret) {'
    . 'return;'
. '}'
. 'const { paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);'
. 'switch (paymentIntent.status) {'
    . '  case "succeeded":'
    . '    showMessage("Payment succeeded!");'
    . '    break;'
    . '  case "processing":'
    . '    showMessage("Your payment is processing.");'
    . '    break;'
    . '  case "requires_payment_method":'
    . '    showMessage("Your payment was not successful, please try again.");'
    . '    break;'
    . '  default:'
    . '    showMessage("Something went wrong.");'
    . '    break;'
. '}'
. '}'
. 'function showMessage(messageText) {'
. 'const messageContainer = document.querySelector("#payment-message");'
. 'messageContainer.classList.remove("hidden");'
. 'messageContainer.textContent = messageText;'
. 'setTimeout(function () {'
. 'messageContainer.classList.add("hidden");'
. 'messageText.textContent = "";'
. '}, 4000);'
. '}'
. 'function setLoading(isLoading) {'
. 'if (isLoading) {'
. 'document.querySelector("#submit").disabled = true;'
. 'document.querySelector("#spinner").classList.remove("hidden");'
. 'document.querySelector("#button-text").classList.add("hidden");'
. '} else {'
. 'document.querySelector("#submit").disabled = false;'
. 'document.querySelector("#spinner").classList.add("hidden");'
. 'document.querySelector("#button-text").classList.remove("hidden");'
. '}'
. '};';
echo H::script($js18)->type('module');
