<?php

declare(strict_types=1);

namespace App\Auth;

use App\Infrastructure\Persistence\Client\Client;

/** The answer of HomeCareHouseClient::decide(): the outcome, and the existing client when the outcome is Link. */
final readonly class HomeCareHouseDecision
{
    private function __construct(
        public HouseClientOutcome $outcome,
        public ?Client $client,
    ) {
    }

    public static function create(): self
    {
        return new self(HouseClientOutcome::Create, null);
    }

    public static function link(Client $client): self
    {
        return new self(HouseClientOutcome::Link, $client);
    }

    public static function taken(): self
    {
        return new self(HouseClientOutcome::Taken, null);
    }

    public function isTaken(): bool
    {
        return HouseClientOutcome::Taken === $this->outcome;
    }
}
