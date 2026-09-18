<?php

declare(strict_types=1);

namespace App\Bookkeeping\Domain;

/**
 * A BookkeepingLine's direction. A typed enum here rather than a bare
 * bool ($isDebit) deliberately, since "true means debit" is exactly the
 * kind of silent, easy-to-invert convention accounting code shouldn't
 * rely on implicitly.
 */
enum DebitCredit: string
{
    case Debit = 'debit';
    case Credit = 'credit';
}
