<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Thrown by As4Receiver when an inbound AS4 message cannot be parsed.
 */
final class As4ParseException extends \RuntimeException {}
