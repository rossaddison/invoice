<?php

declare(strict_types=1);

namespace App\Api;

use RuntimeException;

/**
 * Thrown by OrderService::addOrderItem() when a checkout would sell into a
 * product's reserved reorder_threshold buffer (see
 * App\Infrastructure\Persistence\Product\Product::$reorder_threshold's own
 * docblock) or past its stock entirely. Caught one level up in
 * OrderService::createInvoiceAndItems(), around the same
 * InvService::withTransaction() call every other failure in that method
 * already runs inside — the transaction's own rollback-on-exception undoes
 * any invoice items already written earlier in the same loop, and the
 * caught exception becomes the same `null` return CheckoutController's
 * existing checkout.failed handling already expects, not a new failure
 * path of its own.
 */
final class InsufficientStockException extends RuntimeException
{
}
