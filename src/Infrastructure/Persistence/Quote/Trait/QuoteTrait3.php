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
trait QuoteTrait3
{

    public function setDateRequired(DateTimeImmutable $date_required): void
    {
        $this->date_required = $date_required;
    }

    public function getDateRequired(): DateTimeImmutable
    {
        return $this->date_required;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discount_amount;
    }

    public function setDiscountAmount(float $discount_amount): void
    {
        $this->discount_amount = $discount_amount;
    }

    public function getUrlKey(): string
    {
        return $this->url_key;
    }

    public function setUrlKey(string $url_key): void
    {
        $this->url_key = $url_key;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function getItems(): ArrayCollection
    {
        return $this->items;
    }
}
