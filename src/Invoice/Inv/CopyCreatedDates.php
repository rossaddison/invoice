<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

/**
 * Parses and bounds the "copy to these days" request of inv/index's Copy
 * Invoice modal (see MultipleCopy::multiplecopy()).
 */
final class CopyCreatedDates
{
    public const int MAX_DATES = 62;
    public const int MAX_COPIES = 500;

    /**
     * Valid, unique Y-m-d dates from modal_created_dates, falling back to the
     * legacy single modal_created_date. [''] (= today, see
     * MultipleCopy::resolveCopyCreatedDate()) when none are valid.
     *
     * @param array<array-key, mixed> $data
     * @return list<string>
     */
    public static function fromRequest(array $data): array
    {
        $dates = array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $date): string => is_scalar($date) ? (string) $date : '',
                (array) ($data['modal_created_dates'] ?? $data['modal_created_date'] ?? [])
            ),
            static function (string $date): bool {
                $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                // Reject dates PHP rolls over, e.g. 2026-02-31 -> 2026-03-03.
                return $parsed !== false && $parsed->format('Y-m-d') === $date;
            }
        )));
        return $dates === [] ? [''] : $dates;
    }

    /**
     * @param list<string> $dates
     */
    public static function withinLimits(int $invoiceCount, array $dates, int $clientCount): bool
    {
        return count($dates) <= self::MAX_DATES
            && $invoiceCount * count($dates) * max(1, $clientCount) <= self::MAX_COPIES;
    }
}
