<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Family\Family;
use Yiisoft\Html\Html as H;

/**
 * Cleaning run — drag-and-drop street order.
 *
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $streets
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $alert
 * @var string $csrf
 * @var string $reorderUrl
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo H::openTag('div', ['class' => 'row']);
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-8 col-md-offset-2']);
  echo H::openTag('div', ['class' => 'card']);
   echo H::openTag('div', ['class' => 'card-header bg-info text-black']);
    echo H::openTag('h4', ['class' => 'mb-0']);
     echo H::tag('i', '', ['class' => 'bi bi-signpost-split me-2']);
     echo H::encode($translator->translate('street.order'));
    echo H::closeTag('h4');
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => 'card-body']);
    echo H::openTag('p', ['class' => 'text-muted mb-3']);
     echo H::encode($translator->translate('street.order.drag.hint'));
    echo H::closeTag('p');

    // CSRF token read by family-street-order.ts
    echo H::input('hidden', '_csrf', $csrf, ['id' => 'street-order-csrf']);

    // Status feedback div updated by TypeScript
    echo H::openTag('div', [
        'id'    => 'street-order-status',
        'style' => 'display:none',
    ]);
    echo H::closeTag('div');

    // Drag-and-drop list
    echo H::openTag('ul', [
        'id'               => 'street-order-list',
        'class'            => 'list-group mt-3',
        'data-reorder-url' => $reorderUrl,
    ]);

    $position = 1;
    /** @var Family $street */
    foreach ($streets as $street) {
        echo H::openTag('li', [
            'class'     => 'list-group-item d-flex align-items-center gap-2',
            'draggable' => 'true',
            'data-id'   => (string) $street->reqId(),
            'style'     => 'cursor:grab',
        ]);
         echo H::tag('i', '', ['class' => 'bi bi-grip-vertical text-muted fs-5']);
         echo H::openTag('span', ['class' => 'badge bg-secondary street-position me-2']);
          echo (string) $position;
         echo H::closeTag('span');
         echo H::encode($street->getFamilyName() ?? '');
        echo H::closeTag('li');
        $position++;
    }

    echo H::closeTag('ul');

   echo H::closeTag('div'); // card-body
   echo H::openTag('div', ['class' => 'card-footer']);
    echo H::openTag('a', [
        'href'  => $urlGenerator->generate('family/index'),
        'class' => 'btn btn-secondary btn-sm',
    ]);
     echo H::encode($translator->translate('street.order.back.to.families'));
    echo H::closeTag('a');
   echo H::closeTag('div'); // card-footer
  echo H::closeTag('div'); // card
 echo H::closeTag('div'); // col
echo H::closeTag('div'); // row
