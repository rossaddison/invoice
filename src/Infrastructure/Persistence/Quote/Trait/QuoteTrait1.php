<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Quote\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Infrastructure\Persistence\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait QuoteTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Quote');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): void
    {
        $this->group = $group;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getQuoteAmount(): ?QuoteAmount
    {
        return $this->quoteAmount;
    }

    public function reqUserId(): int
    {
        return $this->requireId($this->user_id, 'User');
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    // copying a quote: so_id will be null
    public function getSoId(): ?int
    {
        return $this->so_id;
    }

    public function setSoId(int|null $so_id): void
    {
        $this->so_id = $so_id;
    }

    // copying a quote: inv_id will be null
    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function setInvId(int|null $inv_id): void
    {
        $this->inv_id = $inv_id;
    }
}
