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

    // Unlike reqUserId() above, quote_id and inv_id are genuinely optional
    // on a SalesOrder -- a direct SO has no originating quote, and a fresh
    // one has no invoice yet. requireId()'s throw-on-null was wrong here:
    // every real call site (SalesOrderController's already-converted
    // checks, SalesOrdersColumnBuilder's hasLinkedInvoice() guard, ...)
    // already treats 0 as "not set", so the throw could only ever crash
    // a legitimate call. Found 2026-08-30 converting a fresh Sales Order
    // to an Invoice -- reqInvId() threw "Inv not persisted" on the very
    // check meant to confirm the SO hadn't already been converted.
    public function reqQuoteId(): int
    {
        return $this->quote_id ?? 0;
    }

    public function setQuoteId(?int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function reqInvId(): int
    {
        return $this->inv_id ?? 0;
    }
}
