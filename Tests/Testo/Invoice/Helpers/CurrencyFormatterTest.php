<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Invoice\Helpers\CurrencyFormatter;
use Testo\Assert;
use Testo\Test;

#[Test]
final class CurrencyFormatterTest
{
    private readonly CurrencyFormatter $formatter;

    public function __construct()
    {
        $this->formatter = new CurrencyFormatter();
    }

    public function formatsWithTwoDecimalsWhenDecimalPointGiven(): void
    {
        Assert::same($this->formatter->format(1234.56, '.', ','), '1,234.56');
    }

    public function formatsWithZeroDecimalsWhenDecimalPointIsEmpty(): void
    {
        Assert::same($this->formatter->format(1234.56, '', ','), '1,235');
    }

    public function formatsNullAsZero(): void
    {
        Assert::same($this->formatter->format(null, '.', ','), '0.00');
    }

    public function formatsNegativeAmounts(): void
    {
        Assert::same($this->formatter->format(-1234.56, '.', ','), '-1,234.56');
    }

    public function formatsNumericStringInput(): void
    {
        Assert::same($this->formatter->format('1234.56', '.', ','), '1,234.56');
    }

    public function fallsBackToZeroForNonNumericStringInput(): void
    {
        Assert::same($this->formatter->format('not-a-number', '.', ','), '0.00');
    }

    public function fallsBackToZeroWhenBigDecimalRejectsAnIsNumericString(): void
    {
        // PHP's is_numeric() accepts a leading-space numeric string (the
        // `!is_numeric($raw)` guard above lets it through unchanged), but
        // Brick\Math\BigDecimal::of() does not -- it throws
        // NumberFormatException (a MathException) for " 123". Exercises
        // the catch branch directly rather than leaving it untested as
        // dead-looking code.
        Assert::same($this->formatter->format(' 123', '.', ','), '0.00');
    }

    public function roundsHalfUpAtTheDecimalBoundary(): void
    {
        Assert::same($this->formatter->format(1.005, '.', ','), '1.01');
        Assert::same($this->formatter->format(0.145, '.', ','), '0.15');
    }

    public function honoursCustomSeparators(): void
    {
        // European-style: '.' as thousands separator, ',' as decimal point.
        Assert::same($this->formatter->format(1234.56, ',', '.'), '1.234,56');
    }
}
