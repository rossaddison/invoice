<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class Bootstrap5ModalPdf
{
    public function __construct(
        private readonly Translator $translator,
        private readonly ViewRenderer $viewRenderer,
        private readonly string $type
    ) {
    }

    public function renderPartialLayoutWithPdfAsString(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/inv/modal_layout_modal_pdf',
            [
                'type' => $this->type,
                'iframeWithPdf' => $this->viewRenderer->renderPartialAsString(
                    '//invoice/' . $this->type . '/modal_view_' . $this->type . '_pdf',
                    [
                        'title' => $this->translator->translate('i.view'),
                    ]
                ),
            ]
        );
    }
}
