<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class Bootstrap5ModalTranslatorMessageWithAction
{
    private ViewRenderer $viewRenderer;
    private array $layoutParameters;
    private array $formParameters;

    public function __construct(
        ViewRenderer $viewRenderer,
    ) {
        $this->viewRenderer = $viewRenderer;
        $this->layoutParameters = [];
        $this->formParameters = [];
    }

    public function renderPartialLayoutWithTranslatorMessageAsString(
        string $translatedHeading,
        string $translatedMessage,
        string $origin,
        string $urlString,
        string $id
    ): string {
        $this->layoutParameters = [
            'type' => $origin,
            'form' => $this->viewRenderer->renderPartialAsString(
                '//invoice/inv/modal_message_action',
                [
                    'translatedHeading' => $translatedHeading,
                    'translatedMessage' => $translatedMessage,
                    'urlString' => $urlString,
                    'id' => $id,
                ]
            ),
        ];
        return $this->viewRenderer->renderPartialAsString('//invoice/inv/modal_message_layout', $this->layoutParameters);
    }
}
