<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Error;

final readonly class As4ErrorParams
{
    public function __construct(
        public string $errorMessageId,
        public string $refToMessageId,
        public string $errorCode,
        public string $category,
        public string $shortDescription,
        public string $originSender,
        public string $originReceiver,
        public string $errorXml,
    ) {}
}
