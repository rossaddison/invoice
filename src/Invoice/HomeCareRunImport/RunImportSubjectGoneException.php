<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunImport;

use RuntimeException;

/**
 * Thrown while a run is being imported when the product or the user the import was started with can
 * no longer be found: it was removed part way through, so the invoice for the house cannot be made.
 */
final class RunImportSubjectGoneException extends RuntimeException
{
}
