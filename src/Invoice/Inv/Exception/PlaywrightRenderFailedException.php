<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

final class PlaywrightRenderFailedException extends \RuntimeException implements FriendlyExceptionInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $detail = '',
    ) {
    }

    /**
     * Raw process stderr — for logging only, never shown to the browser
     * (it may contain internal paths or the render account's login flow).
     */
    public function getDetail(): string
    {
        return $this->detail;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->translator->translate('pdf.render.playwright.failed');
    }

    #[\Override]
    public function getSolution(): string
    {
        return $this->translator->translate('pdf.render.playwright.failed.solution');
    }
}
