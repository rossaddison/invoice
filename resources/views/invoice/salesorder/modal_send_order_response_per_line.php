<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Option;
use App\Invoice\Ubl\OrderResponseLineStatusCode;

/**
 * Real per-line OrderResponseAdvanced review: staff decide each
 * SalesOrderItem independently (Accepted/Rejected/Changed/Added/Already
 * delivered). The header OrderResponseCode is derived automatically from
 * these on the server (see OrderResponseAdvancedService::sendPerLine()) --
 * there's no header code to pick here.
 *
 * Related logic: see id="send-order-response" triggered by
 * <a href="#send-order-response" data-bs-toggle="modal"> on
 * views/salesorder/view.php.
 * @var App\Infrastructure\Persistence\SalesOrder\SalesOrder $so
 * @var App\Invoice\SalesOrderItem\SalesOrderItemRepository $soiR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 */

echo H::openTag('div', ['id' => 'send-order-response', 'class' => 'modal', 'tabindex' => '-1']); //0
echo H::openTag('div', ['class' => 'modal-dialog modal-lg']); //1
echo H::openTag('div', ['class' => 'modal-content']); //2
echo H::openTag('div', ['class' => 'modal-header']); //3
echo H::tag('h5', $translator->translate('salesorder.peppol.response.perline'), ['class' => 'modal-title']);
echo H::tag('button', '', [
 'type' => 'button',
 'class' => 'btn-close',
 'data-bs-dismiss' => 'modal',
 'aria-label' => 'Close',
]);
echo H::closeTag('div'); //3
echo H::openTag('div', ['class' => 'modal-body']); //3
echo H::openTag('form', [
 'method' => 'post',
 'action' => $urlGenerator->generate('salesorder/sendOrderResponsePerLine', ['id' => $so->reqId()]),
]); //4
echo new Input()->type('hidden')->name('_csrf')->value($csrf);
echo H::openTag('table', ['class' => 'table table-sm align-middle']); //5
echo H::openTag('thead'); //6
echo H::openTag('tr'); //7
echo H::tag('th', $translator->translate('item'));
echo H::tag('th', $translator->translate('quantity'));
echo H::tag('th', $translator->translate('salesorder.peppol.response.perline'));
echo H::closeTag('tr'); //7
echo H::closeTag('thead'); //6
echo H::openTag('tbody'); //6
/** @var App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem $item */
foreach ($soiR->repoSalesOrderquery($so->reqId()) as $item) {
    echo H::openTag('tr'); //7
    echo H::tag('td', H::encode($item->getName() ?? ''));
    echo H::tag('td', H::encode((string) $item->getQuantity()));
    echo H::openTag('td'); //8
    echo H::openTag('select', [
     'name' => 'line_response_code[' . $item->reqId() . ']',
     'class' => 'form-control form-control-sm',
    ]); //9
    foreach (OrderResponseLineStatusCode::cases() as $case) {
        echo new Option()
         ->value($case->value)
         ->selected($case === OrderResponseLineStatusCode::Accepted)
         ->content(H::encode($translator->translate($case->translationKey())));
    }
    echo H::closeTag('select'); //9
    echo H::closeTag('td'); //8
    echo H::closeTag('tr'); //7
}
echo H::closeTag('tbody'); //6
echo H::closeTag('table'); //5
echo H::openTag('div', ['class' => 'modal-footer']); //5
// Submits the same form+selections as a GET to the preview action in
// a new tab -- formmethod/formaction/formtarget override the
// enclosing <form>'s method="post" for just this button, no JS
// needed (see feedback_no_raw_script_tags).
echo H::openTag('button', [
 'class' => 'btn btn-secondary',
 'type' => 'submit',
 'formmethod' => 'get',
 'formaction' => $urlGenerator->generate('salesorder/previewOrderResponsePerLine', ['id' => $so->reqId()]),
 'formtarget' => '_blank',
]); //6
echo H::openTag('i', ['class' => 'bi bi-eye']); //7
echo H::closeTag('i'); //7
echo ' ' . $translator->translate('salesorder.peppol.response.preview');
echo H::closeTag('button'); //6
echo H::openTag('button', [
 'class' => 'btn btn-success',
 'type' => 'submit',
]); //6
echo H::openTag('i', ['class' => 'bi bi-check-lg']); //7
echo H::closeTag('i'); //7
echo ' ' . $translator->translate('submit');
echo H::closeTag('button'); //6
echo H::openTag('button', [
 'class' => 'btn btn-danger',
 'type' => 'button',
 'data-bs-dismiss' => 'modal',
]); //6
echo H::openTag('i', ['class' => 'bi bi-x-lg']); //7
echo H::closeTag('i'); //7
echo ' ' . $translator->translate('cancel');
echo H::closeTag('button'); //6
echo H::closeTag('div'); //5
echo H::closeTag('form'); //4
echo H::closeTag('div'); //3
echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
echo H::closeTag('div'); //0
