<?php

declare(strict_types=1);

namespace App\Bookkeeping\Application;

/**
 * A port the Invoice module implements, so the Bookkeeping module can ask
 * "who was invoice N for?" without depending on the Invoice module itself.
 */
interface BookkeepingDocumentSourceInterface
{
    public function forInvoice(int $invId): ?BookkeepingDocument;
}
