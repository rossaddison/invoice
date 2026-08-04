<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus;

/**
 * Thrown when GatewayStatusService can't read gateways.json or
 * composer.lock (e.g. missing file, unreadable path).
 */
class GatewayStatusException extends \RuntimeException
{
}
