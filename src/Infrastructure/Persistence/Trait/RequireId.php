<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Trait;

trait RequireId
{
    protected function requireId(?int $id, string $context): int
    {
        if ($id === null) {
            throw new \LogicException($context . ' not persisted');
        }

        return $id;
    }
}
