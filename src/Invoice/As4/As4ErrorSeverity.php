<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/** ebMS3 Error/@severity — the only two legal values in the specification. */
enum As4ErrorSeverity: string
{
    case Failure = 'failure';
    case Warning = 'warning';
}
