<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Bootstrap5ModalPdf
{
    public function __construct(
        private Translator $translator,
        private WebViewRenderer $webViewRenderer,
        private string $type,
    ) {
    }

    public function renderPartialLayoutWithPdfAsString(): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/modal_layout_modal_pdf',
            [
                'type' => $this->type,
                'iframeWithPdf' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/' . $this->type . '/modal_view_' . $this->type . '_pdf',
                    [
                        'title' => $this->translator->translate('view'),
                    ],
                ),
            ],
        );
    }
}
