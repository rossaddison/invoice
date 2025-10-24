<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Bootstrap5\Modal;
use Yiisoft\Bootstrap5\Utility\Responsive;
use Yiisoft\Bootstrap5\ModalDialogFullScreenSize;

/**
 * Related logic: see The usage of the refactored Modal has been put on hold
 * Related logic: see App\Widget\Bootstrap5ModalInv $this->layoutParameters['form']
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $form
 * @var string $type
 */

echo Modal::widget()
    ->bodyAttributes(['style' => 'text-align:center;'])
    ->body($form)
    ->fullscreen(ModalDialogFullScreenSize::FULLSCREEN_SM_DOWN)
    ->id('modal-add-' . $type)
    ->responsive(Responsive::LG)
    ->scrollable()
    ->triggerButton()
    ->footerAttributes(['class' => 'text-dark'])
    ->footer(Button::tag()->addClass('btn btn-danger')->attribute('data-bs-dismiss', 'modal')->content($translator->translate('close')))
    ->title('Modal title')
    ->verticalCentered()
    ->render();

/** @psalm-var array<string,mixed>|null $autoTemplate */

$body = '';
if (is_array($autoTemplate) && array_key_exists('body', $autoTemplate) && is_string($autoTemplate['body'])) {
    $body = $autoTemplate['body'];
}

$bodyJson = json_encode($body);
$inert = <<<JS
    document.addEventListener('DOMContentLoaded', function () {
        "use strict";
        var textContent = {$bodyJson};
        var el = document.getElementById('mailerinvform-body');
        if (el) {
            el.value = textContent;
        }
    });
    JS;

echo Html::script($inert)->type('module');
