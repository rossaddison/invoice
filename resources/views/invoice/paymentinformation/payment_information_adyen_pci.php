<?php

declare(strict_types=1);

use App\Invoice\Asset\pciAsset\AdyenAsset;
use Yiisoft\Html\Html as H;

/**
 * Related logic: see AdyenPaymentController function adyenInForm
 * @var App\Infrastructure\Persistence\Client\Client $client_on_invoice
 * @var App\Infrastructure\Persistence\Inv\Inv $invoice
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
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var bool $disable_form
 * @var bool $is_overdue
 * @var float $balance
 * @var float $total
 * @var string $adyenClientKey
 * @var string $adyenCountryCode
 * @var string $adyenEnvironment
 * @var string $adyenLogo
 * @var string $adyenSessionData
 * @var string $adyenSessionId
 * @var string $alert
 * @var string $companyLogo
 * @var string $inv_url_key
 * @var string $partial_client_address
 * @var string $payment_method
 * @var string $title
 */

$assetManager->register(AdyenAsset::class);

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
             . H::encode($invoice->getClient()?->getClientName() ?? '') . ' '
             . H::encode($invoice->getClient()?->getClientSurname() ?? '') . ' '
             . $numberHelper->formatCurrency($balance);
           echo H::closeTag('div');
          echo H::closeTag('div');
         echo H::closeTag('h2');
         echo H::openTag('a', [
             'href' => $urlGenerator->generate('inv/pdfDownloadIncludeCf', ['url_key' => $inv_url_key, '_language' => 'en']),
             'class' => 'btn btn-sm btn-primary fw-normal h3 text-center text-decoration-none',
         ]);
          echo H::openTag('i', ['class' => 'bi bi-file-pdf']);
          echo H::closeTag('i');
          echo ' ' . $translator->translate('download.pdf') . '=>' . $translator->translate('yes')
                  . ' ' . $translator->translate('custom.fields');
         echo H::closeTag('a');
         echo H::openTag('a', [
             'href' => $urlGenerator->generate('inv/pdfDownloadExcludeCf', ['url_key' => $inv_url_key, '_language' => 'en']),
             'class' => 'btn btn-sm btn-danger fw-normal h3 text-center text-decoration-none',
         ]);
          echo H::openTag('i', ['class' => 'bi bi-file-pdf']);
          echo H::closeTag('i');
          echo ' ' . $translator->translate('download.pdf') . '=>' . $translator->translate('no')
                  . ' ' . $translator->translate('custom.fields');
         echo H::closeTag('a');
        echo H::closeTag('div');
        echo H::tag('br');
        echo H::tag('Div', H::tag('H4', $title));
        echo H::openTag('div', ['class' => 'text-center']);
         echo $adyenLogo;
        echo H::closeTag('div');
        echo H::tag('br');
        echo H::openTag('div', ['class' => 'card-body p-5 text-center']);
         echo $alert;
         // Adyen Web SDK's Drop-in component renders into this div; no
         // native form POST to our own server, so no CSRF token is needed
         // here (unlike Braintree's card-nonce form).
         echo H::tag('div', '', ['id' => 'dropin-container']);
         echo H::encode($clientHelper->formatClient($client_on_invoice));
         echo $partial_client_address;
         echo H::tag('br');
         echo H::openTag('div', ['class' => 'table-responsive']);
          echo H::openTag('table', ['class' => 'table table-bordered table-condensed m-0']);
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
             echo H::openTag('td', ['class' => 'text-end']);
              echo H::encode($invoice->getDateCreated()->format('Y-m-d'));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr', ['class' => ($is_overdue ? 'overdue' : '')]);
             echo H::openTag('td');
              echo $translator->translate('due.date');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-end']);
              echo H::encode($invoice->getDateDue()->format('Y-m-d'));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr', ['class' => ($is_overdue ? 'overdue' : '')]);
             echo H::openTag('td');
              echo $translator->translate('total');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-end']);
              echo H::encode($numberHelper->formatCurrency($total));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr', ['class' => ($is_overdue ? 'overdue' : '')]);
             echo H::openTag('td');
              echo $translator->translate('balance');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-end']);
              echo H::encode($numberHelper->formatCurrency($balance));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr');
             echo H::openTag('td');
              echo $translator->translate('payment.method') . ': ';
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-end']);
              echo H::encode($payment_method);
             echo H::closeTag('td');
            echo H::closeTag('tr');
           echo H::closeTag('tbody');
          echo H::closeTag('table');
         echo H::closeTag('div');
         if (!empty($invoice->getTerms())) {
             echo H::openTag('div', ['class' => 'col-12 text-muted']);
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
// Supply server-side values to the TypeScript payment-adyen module via
// data-* attributes. The IIFE bundle reads these on DOMContentLoaded and
// mounts AdyenCheckout's Drop-in component — no inline JS required.
echo H::tag('div', '', [
    'id'                    => 'adyen-payment-config',
    'data-client-key'       => $adyenClientKey,
    'data-session-id'       => $adyenSessionId,
    'data-session-data'     => $adyenSessionData,
    'data-environment'      => $adyenEnvironment,
    'data-country-code'     => $adyenCountryCode,
    'class'                 => 'd-none',
]);
