<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SalesOrder\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Infrastructure\Persistence\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;

/**
 * @method int requireId(?int $id, string $context)
 */
trait SalesOrderTrait1
{

    public function reqId(): int
    {
        return $this->requireId($this->id, 'SalesOrder');
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

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function reqUserId(): int
    {
        return $this->requireId($this->user_id, 'User');
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function reqQuoteId(): int
    {
        return $this->requireId($this->quote_id, 'Quote');
    }

    public function setQuoteId(?int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function reqInvId(): int
    {
        return $this->requireId($this->inv_id, 'Inv');
    }
}
