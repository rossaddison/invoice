<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * @psalm-suppress UnusedClass
 */
final class As4DuplicateDetector implements As4DuplicateDetectorInterface
{
    public function __construct(
        private readonly As4MessageRepositoryInterface $repository,
    ) {}

    #[\Override]
    public function isDuplicate(string $messageId): bool
    {
        return $this->repository->findByMessageId($messageId) !== null;
    }
}
