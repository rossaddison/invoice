<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/** ebMS3 Error/@category — the fixed vocabulary defined in the specification. */
enum As4ErrorCategory: string
{
    case Content       = 'Content';
    case Unpackaging   = 'Unpackaging';
    case Processing    = 'Processing';
    case Communication = 'Communication';
    case Security      = 'Security';
}
