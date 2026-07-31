<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Exception;

use App\Invoice\Inv\Exception\PlaywrightRenderFailedException;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Translator\TranslatorInterface;

/**
 * getSolution() must always return the translated generic message, never
 * the raw process detail — that may contain internal paths or the render
 * account's login flow, which getDetail() exposes separately for logging.
 */
#[Test]
final class PlaywrightRenderFailedExceptionTest
{
    private function makeTranslator(): TranslatorInterface
    {
        $translator = m::mock(TranslatorInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $translator->shouldReceive('translate');
        $e->andReturnUsing(static fn (string $key): string => $key);
        return $translator;
    }

    public function getNameReturnsTranslatedName(): void
    {
        $exception = new PlaywrightRenderFailedException($this->makeTranslator(), 'node: command not found');

        Assert::same('pdf.render.playwright.failed', $exception->getName());
    }

    public function getSolutionReturnsGenericMessageNotRawDetail(): void
    {
        $exception = new PlaywrightRenderFailedException($this->makeTranslator(), 'node: command not found');

        Assert::same('pdf.render.playwright.failed.solution', $exception->getSolution());
    }

    public function getDetailReturnsRawDetailForLoggingOnly(): void
    {
        $exception = new PlaywrightRenderFailedException($this->makeTranslator(), 'node: command not found');

        Assert::same('node: command not found', $exception->getDetail());
    }

    public function getDetailDefaultsToEmptyString(): void
    {
        $exception = new PlaywrightRenderFailedException($this->makeTranslator());

        Assert::same('', $exception->getDetail());
    }
}
