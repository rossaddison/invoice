<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class Bootstrap5ModalTranslatorMessageWithoutAction
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

    /**
     * @param string $translatedHeading
     * @param string $translatedMessage
     * @param string $origin
     * @return string
     */
    public function renderPartialLayoutWithTranslatorMessageAsString(
        string $translatedHeading,
        string $translatedMessage,
        string $origin
    ): string {
        $this->layoutParameters = [
            'type' => $origin,
            'form' => $this->viewRenderer->renderPartialAsString(
                '//invoice/inv/modal_message',
                [
                    'translatedHeading' => $translatedHeading,
                    'translatedMessage' => $translatedMessage,
                ]
            ),
        ];
        return $this->viewRenderer->renderPartialAsString('//invoice/inv/modal_message_layout', $this->layoutParameters);
    }
}
