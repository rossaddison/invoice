<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\I;

/**
 * @link https://developer.amazon.com/docs/amazon-pay-checkout/
 * add-the-amazon-pay-button.html#4-render-the-button
 * Related logic: see PaymentInformationController function amazonInForm
 * @var App\Invoice\Entity\Client $client_on_invoice
 * @var App\Invoice\Entity\Inv $invoice
 *
 * Related logic: see config\common\params 'yiisoft/view' =>
    ['parameters' => ['clientHelper' => Reference::to(ClientHelper::class)]]
 * @var App\Invoice\Helpers\ClientHelper $clientHelper
 *
 * Related logic: see config\common\params 'yiisoft/view' =>
    ['parameters' => ['dateHelper' => Reference::to(DateHelper::class)]]
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 *
 * Related logic: see config\common\params 'yiisoft/view' =>
    ['parameters' => ['numberHelper' => Reference::to(NumberHelper::class)]]
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 *
 * Related logic: see config\common\params 'yiisoft/view' =>
    ['parameters' => ['s' => Reference::to(SettingRepository::class)]]
 * @var App\Invoice\Setting\SettingRepository $s
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $amazonPayButton
 * @var bool $disable_form
 * @var bool $is_overdue
 * @var float $balance
 * @var float $total
 * @var string $alert
 * @var string $companyLogo
 * @var string $inv_url_key
 * @var string $partial_client_address
 * @var string $payment_method
 * @var string $title
 */

if ($disable_form === false) {
    echo H::openTag('div', ['class' => 'container py-5 h-100']);
     echo H::openTag('div',
             ['class' =>
                 'row d-flex justify-content-center align-items-center h-100']);
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
            echo (H::encode($invoice->getNumber() ?? '')) . ' => '
             . (H::encode($invoice->getClient()?->getClientName() ?? '')) . ' '
             . (H::encode($invoice->getClient()?->getClientSurname() ?? '')) . ' '
             . $numberHelper->formatCurrency($balance);
           echo H::closeTag('div');
          echo H::closeTag('div');
         echo H::closeTag('h2');         
         echo  new A()
              ->addAttributes([
                    'class' => 'btn btn-sm btn-primary fw-normal h3 text-center',
                    'style' => 'text-decoration:none',
              ])
              ->href($urlGenerator->generate('inv/pdfDownloadIncludeCf',
                    [
                        'url_key' => $inv_url_key
                    ]
                )
              )
              ->content( new I()->addAttributes([
                  'class' => 'bi bi-file-pdf'
              ]) . ' ' . $translator->translate('download.pdf')
                 . '=>' . $translator->translate('yes')
                 . ' ' . $translator->translate('custom.fields'))
              ->render();
         echo  new A()
              ->addAttributes([
                    'class' => 'btn btn-sm btn-danger fw-normal h3 text-center',
                    'style' => 'text-decoration:none',
              ])
              ->href($urlGenerator->generate('inv/pdfDownloadExcludeCf',
                    [
                        'url_key' => $inv_url_key
                    ]
                )
              )
              ->content( new I()->addAttributes([
                  'class' => 'bi bi-file-pdf'
              ]) . ' ' . $translator->translate('download.pdf')
                 . '=>' . $translator->translate('no')
                 . ' ' . $translator->translate('custom.fields'))
              ->render();
        echo H::closeTag('div');
        include 'vendor/autoload.php';
        $version = "using https://github.com/amzn/amazon-pay-api-sdk-php version: " . \Amazon\Pay\API\Client::SDK_VERSION . "\n";
        echo H::tag('br');
        echo H::tag('div', H::tag('h4', $title . '  ' . $version));
        echo H::tag('br');
        echo H::openTag('div', ['class' => 'card-body p-5 text-center']);
         echo $alert;
         echo H::tag('br');
         // Amazon pay button
         // https://developer.amazon.com/docs/amazon-pay-checkout/
         // add-the-amazon-pay-button.html#4-render-the-button
         echo H::tag('div', '', ['id' => 'AmazonPayButton']);
         echo H::tag('br');
         echo H::openTag('button', [
             'type' => 'submit',
             'id' => 'submit',
             'class' => 'btn btn-lg btn-success bi bi-credit-card'
         ]);
          echo H::openTag('div', ['class' => 'spinner hidden', 'id' => 'spinner']);
          echo H::closeTag('div');
          echo H::openTag('span', ['id' => 'button-text']);
           echo ' '
            . $translator->translate('pay.now')
            . ': ' . $numberHelper->formatCurrency($balance);
          echo H::closeTag('span');
         echo H::closeTag('button');
         echo H::encode($clientHelper->formatClient($client_on_invoice));
         echo $partial_client_address;
         echo H::tag('br');
         echo H::openTag('div', ['class' => 'table-responsive']);
          echo H::openTag('table', ['class' =>
              'table table-bordered table-condensed no-margin']);
           echo H::openTag('tbody');
            echo H::openTag('tr');
             echo H::openTag('td');
              echo $translator->translate('date');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-right']);
              echo H::encode($invoice->getDateCreated()->format('Y-m-d'));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr', ['class' => ($is_overdue ? 'overdue' : '')]);
             echo H::openTag('td');
              echo $translator->translate('due.date');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-right']);
              echo H::encode($invoice->getDateDue()->format('Y-m-d'));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr', ['class' => ($is_overdue ? 'overdue' : '')]);
             echo H::openTag('td');
              echo $translator->translate('total');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-right']);
              echo H::encode($numberHelper->formatCurrency($total));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr', ['class' => ($is_overdue ? 'overdue' : '')]);
             echo H::openTag('td');
              echo $translator->translate('balance');
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-right']);
              echo H::encode($numberHelper->formatCurrency($balance));
             echo H::closeTag('td');
            echo H::closeTag('tr');
            echo H::openTag('tr');
             echo H::openTag('td');
              echo $translator->translate('payment.method') . ': ';
             echo H::closeTag('td');
             echo H::openTag('td', ['class' => 'text-right']);
              echo 'Card / Direct Debit - Customer Ready for Payment';
             echo H::closeTag('td');
            echo H::closeTag('tr');
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
// Supply server-side values to the TypeScript payment-amazon module via
// data-* attributes. The IIFE bundle (invoice-typescript-iife.js) reads these
// on DOMContentLoaded and calls amazon.Pay.renderButton() — no inline JS required.
echo H::tag('div', '', [
    'id'                     => 'amazon-pay-config',
    'data-merchant-id'       => (string) $amazonPayButton['merchantId'],
    'data-public-key-id'     => (string) $amazonPayButton['publicKeyId'],
    'data-ledger-currency'   => (string) $amazonPayButton['ledgerCurrency'],
    'data-checkout-language' => (string) $amazonPayButton['checkoutLanguage'],
    'data-product-type'      => (string) $amazonPayButton['productType'],
    'data-amount'            => (string) $amazonPayButton['amount'],
    'data-payload-json'      => (string) $amazonPayButton['payloadJSON'],
    'data-signature'         => (string) $amazonPayButton['signature'],
    'style'                  => 'display:none',
]);
