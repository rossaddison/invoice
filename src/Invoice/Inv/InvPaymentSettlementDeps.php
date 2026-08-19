<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\Product\ProductRepository as pR;
use App\Invoice\StockMovement\StockMovementRepository as smR;

/**
 * Bundles InvPaymentSettlementService's dependencies — matches this
 * codebase's established "Deps" bag convention (see e.g. OrderServiceDeps,
 * IiAddProductDeps) rather than growing InvService's own constructor,
 * which every one of its many existing callers is already injected with.
 */
final class InvPaymentSettlementDeps
{
    public function __construct(
        public readonly InvRepository $iR,
        public readonly iaR $iaR,
        public readonly iiR $iiR,
        public readonly pR $pR,
        public readonly smR $smR,
        public readonly InvService $invService,
    ) {
    }
}
