<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use RuntimeException;

/**
 * Thrown by OrderResponseAdvancedService when a SalesOrder can't be
 * answered over Peppol -- e.g. its Client has no ClientPeppol registration,
 * so there's no Peppol participant ID to send the response to.
 */
final class As4OrderResponseException extends RuntimeException
{
}
