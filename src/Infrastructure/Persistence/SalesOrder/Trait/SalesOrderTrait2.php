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
trait SalesOrderTrait2
{

    public function hasLinkedInvoice(): bool
    {
        return null !== $this->inv_id && 0 !== $this->inv_id;
    }

    public function setInvId(?int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function reqClientId(): int
    {
        return $this->requireId($this->client_id, 'Client');
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function reqGroupId(): int
    {
        return $this->requireId($this->group_id, 'Group');
    }

    public function setGroupId(int $group_id): void
    {
        $this->group_id = $group_id;
    }

    public function getStatusId(): ?int
    {
        return $this->status_id;
    }

    public function getStatus(int $status_id): string
    {
        return match ($status_id) {
            1 => 'draft',
            2 => 'sent',
            3 => 'viewed',
            4 => 'approved',
            5 => 'rejected',
            6 => 'cancelled',
            default => '',
        };
    }

    public function setStatusId(int $status_id): void
    {
        $this->status_id = in_array($status_id, [1,2,3,4,5,6,7,8,9])
            ? $status_id
            : 1;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function setDateCreated(
        DateTimeImmutable $date_created
    ): void {
        $this->date_created = $date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function setDateExpires(): void
    {
        $this->date_expires = (new DateTimeImmutable('now'))
            ->add(new \DateInterval('P1D'));
    }

    public function getDateExpires(): DateTimeImmutable
    {
        return $this->date_expires;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }
}
