<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;

interface PeppolMessageRepositoryInterface
{
    public function save(PeppolMessage $message): void;

    public function repoByMessageId(string $message_id): ?PeppolMessage;
}
