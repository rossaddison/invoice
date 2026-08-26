<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Input;
use App\Invoice\Ubl\OrderResponseCode;

/**
 * Whole-order "Acknowledge" (AB) shortcut -- "order received, not yet
 * processed", no per-line decisions needed. See
 * modal_send_order_response_per_line.php for the real per-line review/
 * response flow (the primary action once staff are ready to decide).
 *
 * Related logic: see id="acknowledge-order-response" triggered by
 * <a href="#acknowledge-order-response" data-bs-toggle="modal"> on
 * views/salesorder/view.php.
 * @var App\Infrastructure\Persistence\SalesOrder\SalesOrder $so
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 */

echo H::openTag('div', ['id' => 'acknowledge-order-response', 'class' => 'modal', 'tabindex' => '-1']); //0
 echo H::openTag('div', ['class' => 'modal-dialog']); //1
  echo H::openTag('div', ['class' => 'modal-content']); //2
   echo H::openTag('div', ['class' => 'modal-header']); //3
    echo H::tag('h5', $translator->translate('salesorder.peppol.response.acknowledge'), ['class' => 'modal-title']);
    echo H::tag('button', '', [
     'type' => 'button',
     'class' => 'btn-close',
     'data-bs-dismiss' => 'modal',
     'aria-label' => 'Close',
    ]);
   echo H::closeTag('div'); //3
   echo H::openTag('div', ['class' => 'modal-body']); //3
    echo H::tag('p', $translator->translate('salesorder.peppol.response.acknowledge.description'));
    echo H::openTag('form', [
     'method' => 'post',
     'action' => $urlGenerator->generate('salesorder/sendOrderResponse', ['id' => $so->reqId()]),
    ]); //4
     echo new Input()->type('hidden')->name('_csrf')->value($csrf);
     echo new Input()
      ->type('hidden')
      ->name('peppol_order_response_code')
      ->value(OrderResponseCode::Acknowledged->value);
     echo H::openTag('div', ['class' => 'modal-footer']); //5
      // Submits the same form as a GET to the preview action in a new tab
      // -- formmethod/formaction/formtarget override the enclosing
      // <form>'s method="post" for just this button, no JS needed (see
      // feedback_no_raw_script_tags).
      echo H::openTag('button', [
       'class' => 'btn btn-secondary',
       'type' => 'submit',
       'formmethod' => 'get',
       'formaction' => $urlGenerator->generate('salesorder/previewOrderResponse', ['id' => $so->reqId()])
        . '?peppol_order_response_code=' . OrderResponseCode::Acknowledged->value,
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
