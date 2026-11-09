<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunImport;

/**
 * One house line of a pasted run: the number and what is written beside it ("41 CANCELLED",
 * "18 fronts only", "114 new").
 */
final readonly class RunHouse
{
    public const string FLAG_CANCELLED = 'cancelled';
    public const string FLAG_DO_NOT_CLEAN = 'do_not_clean';
    public const string FLAG_FRONTS_ONLY = 'fronts_only';
    public const string FLAG_NEW = 'new';
    public const string FLAG_BI_MONTHLY = 'bi_monthly';
    public const string FLAG_CARD = 'card';
    public const string FLAG_KNOCK = 'knock_door';
    public const string FLAG_EXTRA = 'extra';
    public const string FLAG_PULL_POINT = 'pull_point';

    /**
     * @param string $number "14", "12A"
     * @param string $note everything written beside the number, as written
     * @param list<string> $flags the FLAG_* values the note was recognised as
     * @param int $line the 1-based line of the pasted text
     * @param string $clientName the client's name, when the import sheet gave one
     * @param string $phone the client's phone, when the import sheet gave one
     * @param float|null $price this house's price, when the import sheet gave one
     */
    public function __construct(
        public string $number,
        public string $note,
        public array $flags,
        public int $line,
        public string $clientName = '',
        public string $phone = '',
        public ?float $price = null,
    ) {
    }

    public function has(string $flag): bool
    {
        return in_array($flag, $this->flags, true);
    }

    /**
     * A house that should not be on the clean list (cancelled, or "do not clean").
     */
    public function isInactive(): bool
    {
        return $this->has(self::FLAG_CANCELLED) || $this->has(self::FLAG_DO_NOT_CLEAN);
    }
}
