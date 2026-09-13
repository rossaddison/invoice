<?php

declare(strict_types=1);

namespace App\Invoice\InvAuditLog;

/**
 * Pure before/after field comparison for InvService::saveInv()'s audit
 * logging -- extracted out of InvService itself specifically so it's
 * directly unit-testable without mocking the Cycle repository/DB stack,
 * matching this codebase's own precedent for pulling pure logic out of a
 * service method (see RunSheetPdfRowBuilder).
 */
final class InvAuditFieldDiffer
{
    /**
     * @param array<string, bool|int|float|string|null> $before
     * @param array<string, bool|int|float|string|null> $after
     * @return array<string, array{
     *     old: bool|int|float|string|null,
     *     new: bool|int|float|string|null
     * }> Only the keys present in $after whose value actually differs from
     *     $before -- a field $after doesn't mention at all is never reported,
     *     even if $before had a value for it, since saveInv() only snapshots
     *     fields it actually controls.
     */
    public function diff(array $before, array $after): array
    {
        $changes = [];
        foreach ($after as $field => $newValue) {
            $oldValue = $before[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
            }
        }
        return $changes;
    }
}
