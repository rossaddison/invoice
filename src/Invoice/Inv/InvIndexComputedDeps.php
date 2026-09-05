<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use Yiisoft\Data\Paginator\OffsetPaginator;

/**
 * Values `Trait\Index::index()` computes once per request (paginator,
 * per-status label/summary, resolved defaults) and both its own view-data
 * array and its separate HTMX-partial-refresh branch need — bundled here,
 * the same way `InvIndexListDeps`/`InvIndexNavDeps` already bundle that
 * method's injected repositories, so extracting the two into their own
 * private methods (SonarQube php:S138 — `index()` itself was over the
 * 150-line ceiling) doesn't just trade it for a php:S107 too-many-
 * parameters violation on each of them instead.
 */
final class InvIndexComputedDeps
{
    public function __construct(
        public readonly OffsetPaginator $paginator,
        /** @var array<array-key, mixed> $inv_statuses */
        public readonly array $inv_statuses,
        public readonly string $gridSummary,
        public readonly string $defaultInvoiceGroup,
        public readonly string $defaultInvoicePaymentMethod,
        public readonly string $sortString,
        public readonly int $effectiveStatus,
        public readonly bool $visible,
        public readonly bool $visibleToggleInvSentLogColumn,
    ) {
    }
}
