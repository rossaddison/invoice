<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Inv\Trait;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
use App\Infrastructure\Persistence\InvSentLog\InvSentLog;
use App\Infrastructure\Persistence\User\User;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @method int requireId(?int $id, string $context)
 */
trait InvTrait1
{

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setGroup(?Group $group): void
    {
        $this->group = $group;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setInvRecurring(): void
    {
        $this->invrecurring = new ArrayCollection();
    }

    public function getInvRecurring(): ArrayCollection
    {
        return $this->invrecurring;
    }

    public function addInvRecurring(InvRecurring $invrecurring): void
    {
        $this->invrecurring[] = $invrecurring;
    }

    public function setInvSentLogs(): void
    {
        $this->invsentlogs = new ArrayCollection();
    }

    public function getInvSentLogs(): ArrayCollection
    {
        return $this->invsentlogs;
    }

    public function addInvSentLog(InvSentLog $invSentLog): void
    {
        $this->invsentlogs[] = $invSentLog;
    }

    public function getItems(): ArrayCollection
    {
        return $this->items;
    }

    public function getFirstItemFamilyName(): string
    {
        foreach ($this->items as $item) {
            return $item->getProduct()?->getFamily()?->getFamilyName() ?? '';
        }
        return '';
    }

    public function getFirstItemFamilyProductName(): string
    {
        foreach ($this->items as $item) {
            $familyName =
                $item->getProduct()?->getFamily()?->getFamilyName() ?? '';
            $productName =
                $item->getProduct()?->getProductName() ?? '';
            return $familyName . '➡️' . $productName;
        }
        return '';
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'Inv');
    }
}
