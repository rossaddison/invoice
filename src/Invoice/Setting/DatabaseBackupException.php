<?php

declare(strict_types=1);

namespace App\Invoice\Setting;

/**
 * Thrown when DatabaseBackupService can't produce a gzip backup file —
 * e.g. the target path can't be opened for writing, or the resulting file
 * doesn't exist afterwards.
 */
class DatabaseBackupException extends \RuntimeException
{
}
