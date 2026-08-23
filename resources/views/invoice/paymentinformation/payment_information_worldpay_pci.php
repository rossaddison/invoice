<?php

declare(strict_types=1);

use App\Invoice\Asset\pciAsset\WorldpayAsset;
use Yiisoft\Html\Html as H;

/**
 * Related logic: see WorldpayPaymentController::worldpayInForm
 * @var App\Infrastructure\Persistence\Client\Client $client_on_invoice
 * @var App\Infrastructure\Persistence\Inv\Inv $invoice
 *
 * Related logic: see config\common\params 'yiisoft/view'
 *  => ['parameters' => ['clientHelper' => Reference::to(ClientHelper::class)]]
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 *
 * Related logic: see config\common\params 'yiisoft/view'
 *  => ['parameters' => ['numberHelper' => Reference::to(NumberHelper::class)]]
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 *
 * Related logic: see config\common\params 'yiisoft/view'
 *  => ['parameters' => ['s' => Reference::to(SettingRepository::class)]]
 * @var App\Invoice\Setting\SettingRepository $s
 *
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var bool $disable_form
 * @var bool $is_overdue
 * @var float $balance
 * @var float $total
 * @var float $worldpayAmount
 * @var string $alert
 * @var string $companyLogo
 * @var string $inv_url_key
 * @var string $partial_client_address
 * @var string $title
 * @var string $worldpayLogo
 * @var string $worldpayCheckoutId
 * @var string $worldpayCompleteUrl
 * @var string $worldpayCreatePaymentUrl
 * @var string $worldpayCurrency
 * @var string $worldpayEnvironment
 * @var string $worldpaySupply3dsDeviceDataUrl
 */

$assetManager->register(WorldpayAsset::class);

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
        echo H::closeTag('div');
        echo H::tag('br');
        echo H::tag('Div', H::tag('H4', $title));
        echo H::openTag('div', ['class' => 'text-center']);
         echo $worldpayLogo;
        echo H::closeTag('div');
        echo H::tag('br');
        echo H::openTag('div', ['class' => 'card-body p-5 text-center']);
         echo $alert;
         echo H::openTag('form', ['id' => 'worldpay-payment-form']);
          echo H::openTag('div', ['class' => 'mb-3 text-start']);
           echo H::tag('label', $translator->translate('online.payment.cardholder.name'), ['for' => 'worldpay-cardholder-name', 'class' => 'form-label']);
           echo H::input('text', 'cardHolderName', '', ['id' => 'worldpay-cardholder-name', 'class' => 'form-control', 'autocomplete' => 'cc-name']);
          echo H::closeTag('div');
          // Hosted-fields containers — Worldpay's Access Checkout SDK
          // mounts genuine cross-origin iframes into these three
          // selectors (Worldpay.checkout.init({fields: {pan, expiry,
          // cvv}})); the PAN/expiry/CVV never touch this page's own DOM
          // or JS context, which is what earns SAQ-A. Nothing here is a
          // real <input> for card data — the iframe fills the space.
          echo H::openTag('div', ['class' => 'mb-3 text-start']);
           echo H::tag('label', $translator->translate('online.payment.card.number'), ['class' => 'form-label']);
           echo H::tag('div', '', ['id' => 'worldpay-pan']);
          echo H::closeTag('div');
          echo H::openTag('div', ['class' => 'row mb-3']);
           echo H::openTag('div', ['class' => 'col-6 text-start']);
            echo H::tag('label', $translator->translate('online.payment.card.expiry'), ['class' => 'form-label']);
            echo H::tag('div', '', ['id' => 'worldpay-expiry']);
           echo H::closeTag('div');
           echo H::openTag('div', ['class' => 'col-6 text-start']);
            echo H::tag('label', $translator->translate('online.payment.card.cvv'), ['class' => 'form-label']);
            echo H::tag('div', '', ['id' => 'worldpay-cvv']);
           echo H::closeTag('div');
          echo H::closeTag('div');
          // A visible container the 3DS challenge iframe mounts into if
          // Worldpay's response requires one — hidden by default,
          // shown by payment-worldpay.ts only when actually needed.
          echo H::tag('div', '', ['id' => 'worldpay-3ds-container', 'class' => 'd-none mb-3']);
          echo H::tag('div', '', ['id' => 'worldpay-error', 'class' => 'text-danger small mb-3']);
          echo H::openTag('button', ['type' => 'submit', 'id' => 'worldpay-pay-button', 'class' => 'btn btn-primary']);
           echo $translator->translate('online.payment.pay') . ' ' . $numberHelper->formatCurrency($balance);
          echo H::closeTag('button');
          echo H::openTag('input', [
              'type' => 'hidden',
              'name' => $csrf->getParameterName(),
              'value' => $csrf->getToken(),
          ]);
          echo H::closeTag('input');
         echo H::closeTag('form');
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
// Supply server-side values to the TypeScript payment-worldpay module via
// data-* attributes — never a raw <script> tag, per project convention.
echo H::tag('div', '', [
    'id' => 'worldpay-payment-config',
    'data-checkout-id' => $worldpayCheckoutId,
    'data-environment' => $worldpayEnvironment,
    'data-currency' => $worldpayCurrency,
    'data-amount' => (string) $worldpayAmount,
    'data-create-payment-url' => $worldpayCreatePaymentUrl,
    'data-supply-3ds-device-data-url' => $worldpaySupply3dsDeviceDataUrl,
    'data-complete-url' => $worldpayCompleteUrl,
    'class' => 'd-none',
]);
