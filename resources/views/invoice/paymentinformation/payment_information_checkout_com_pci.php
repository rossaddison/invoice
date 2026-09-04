<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Related logic: see CheckoutComPaymentController function checkoutComInForm
 * @var App\Infrastructure\Persistence\Client\Client $client_on_invoice
 * @var App\Infrastructure\Persistence\Inv\Inv $invoice
 *
 * Related logic: see config\common\params 'yiisoft/view' => ['parameters' => ['numberHelper' => Reference::to(NumberHelper::class)]]
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $is_overdue
 * @var float $balance
 * @var float $total
 * @var string $alert
 * @var string $companyLogo
 * @var string $inv_url_key
 * @var string $partial_client_address
 * @var string $title
 * @var ?string $checkoutComUrl
 */

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
    'href' => $urlGenerator->generate('inv/pdfDownloadIncludeCf', ['url_key' => $inv_url_key]),
    'class' => 'btn btn-sm btn-primary fw-normal h3 text-center text-decoration-none',
]);
echo H::openTag('i', ['class' => 'bi bi-file-pdf']);
echo H::closeTag('i');
echo ' ' . $translator->translate('download.pdf') . '=>' . $translator->translate('yes') . ' ' . $translator->translate('custom.fields');
echo H::closeTag('a');
echo H::openTag('a', [
    'href' => $urlGenerator->generate('inv/pdfDownloadExcludeCf', ['url_key' => $inv_url_key]),
    'class' => 'btn btn-sm btn-danger fw-normal h3 text-center text-decoration-none',
]);
echo H::openTag('i', ['class' => 'bi bi-file-pdf']);
echo H::closeTag('i');
echo ' ' . $translator->translate('download.pdf') . '=>' . $translator->translate('no') . ' ' . $translator->translate('custom.fields');
echo H::closeTag('a');
echo H::closeTag('div');
echo H::tag('br');
echo H::tag('Div', H::tag('H4', $title));
echo H::tag('br');
echo H::openTag('div', ['class' => 'card-body p-5 text-center']);
echo $alert;
if (null !== $checkoutComUrl) {
    echo H::openTag('a', [
        'href' => $checkoutComUrl,
        'class' => 'btn btn-lg btn-primary',
    ]);
    echo $translator->translate('pay.now');
    echo H::closeTag('a');
}
echo $companyLogo;
echo H::tag('br');
echo H::tag('br');
echo H::encode($client_on_invoice->getClientName()) . ' ' . H::encode($client_on_invoice->getClientSurname() ?? '');
echo $partial_client_address;
echo H::tag('br');
echo H::openTag('div', ['class' => 'table-responsive']);
echo H::openTag('table', ['class' => 'table table-bordered table-condensed m-0']);
echo H::openTag('tbody');
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
echo 'Checkout.com';
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
