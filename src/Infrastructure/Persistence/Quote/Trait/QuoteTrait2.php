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
trait QuoteTrait2
{

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

    public function getDeliveryLocationId(): ?int
    {
        return $this->delivery_location_id;
    }

    public function setDeliveryLocationId(int $delivery_location_id): void
    {
        $this->delivery_location_id = $delivery_location_id;
    }

    public function reqContractId(): int
    {
        return $this->requireId($this->contract_id, 'Contract');
    }

    public function setContractId(int $contract_id): void
    {
        $this->contract_id = $contract_id;
    }

    public function reqStatusId(): int
    {
        return $this->requireId($this->status_id, 'Status');
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
        !in_array($status_id, [1, 2, 3, 4, 5, 6, 7])
            ? $this->status_id = 1
            : $this->status_id = $status_id;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function setDateCreated(DateTimeImmutable $date_created): void
    {
        $this->date_created = $date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function setDateExpires(sR $sR): void
    {
        $days = 30;
        if ($sR->repoCount('quotes_expire_after') == 0) {
            $days = 30;
        } else {
            $setting = $sR->withKey('quotes_expire_after');
            if ($setting) {
                $days = $setting->getSettingValue() ?: 30;
            }
        }
        $this->date_expires = (new DateTimeImmutable('now'))
            ->add(new \DateInterval('P' . (string) $days . 'D'));
    }

    public function getDateExpires(): DateTimeImmutable
    {
        return $this->date_expires;
    }
}
