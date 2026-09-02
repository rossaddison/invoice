<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;

/**
 * Extracted out of NumberHelper (which had crossed SonarQube's 20-method
 * class-size threshold, S1448) so the rounding step it wraps is its own
 * small, independently testable unit rather than two more private methods
 * bolted onto an already-large calculation class.
 */
final readonly class CurrencyFormatter
{
    /**
     * Rounds $amount to the target scale via Brick\Math\BigDecimal --
     * already a project dependency, see
     * SettingRepository::currencyConverter() -- instead of leaving the
     * rounding to number_format()'s own float handling, then hands the
     * already-correctly-rounded value to number_format() purely for
     * separator/grouping rendering (no further rounding occurs there).
     *
     * Checked empirically on this PHP 8.4 build before writing this:
     * number_format() itself already rounds values like 1.005 or 0.145
     * correctly (it doesn't round against the raw IEEE-754 bit pattern —
     * PHP resolves the float to its shortest round-trippable decimal
     * string first). So this isn't fixing an actively-reproducing bug on
     * the current runtime. What it buys instead: an explicit, named
     * RoundingMode (HalfUp — matches number_format()'s own round-half-
     * away-from-zero behaviour) backed by a well-tested library instead
     * of an undocumented PHP-engine internal that could theoretically
     * change between versions, and one shared rounding step other
     * amount-producing code in this project (SettingRepository's own
     * currency conversion) already goes through the same library for.
     *
     * @param mixed $amount
     */
    public function format(
        mixed $amount, string $decimal_point, string $thousands_separator): string
    {
        $decimals = $decimal_point !== '' ? 2 : 0;
        return number_format(
            $this->roundToScale($amount, $decimals), $decimals,
                $decimal_point, $thousands_separator);
    }

    /**
     * @param mixed $amount
     */
    private function roundToScale(mixed $amount, int $decimals): float
    {
        $raw = match (true) {
            $amount === null => '0',
            is_string($amount) => $amount,
            default => (string) $amount,
        };
        if (!is_numeric($raw)) {
            $raw = '0';
        }
        try {
            return BigDecimal::of($raw)
                ->toScale(max(0, $decimals), RoundingMode::HalfUp)
                ->toFloat();
        } catch (MathException) {
            return 0.00;
        }
    }
}
