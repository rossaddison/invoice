<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Thrown by As4SecurityHandler when certificate loading or cryptographic operations fail.
 */
final class As4SecurityException extends \RuntimeException {}
