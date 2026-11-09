<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunImport;

/**
 * What saving an imported run did, for the flash message.
 */
final readonly class RunImportResult
{
    /**
     * @param int $runId the run (category secondary) the houses were put under
     * @param bool $runCreated the run did not exist and was created
     * @param int $streetsCreated new streets (families)
     * @param int $housesCreated new houses, each with a dwelling, a client and a draft invoice
     * @param list<string> $alreadyThere houses that already had a client, left as they were
     * @param list<string> $leftOut cancelled or do-not-clean houses, not created
     */
    public function __construct(
        public int $runId,
        public bool $runCreated,
        public int $streetsCreated,
        public int $housesCreated,
        public array $alreadyThere,
        public array $leftOut,
    ) {
    }
}
