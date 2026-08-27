<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheet\Exception;

use RuntimeException;

/**
 * Thrown by HomeCareRunSheetExportService::writeCsv() when `fopen('php://temp', 'r+')`
 * itself fails — an environment-level failure (out of memory/temp storage),
 * not a user-actionable one; there's no caller-side recovery for it, this
 * just gives the export a specific, self-documenting failure mode instead of
 * a bare RuntimeException.
 */
final class RunSheetCsvWriteException extends RuntimeException
{
}
