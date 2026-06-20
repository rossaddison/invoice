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
trait SalesOrderTrait3
{

    public function getClientPoNumber(): ?string
    {
        return $this->client_po_number;
    }

    public function setClientPoNumber(string $client_po_number): void
    {
        $this->client_po_number = $client_po_number;
    }

    public function getClientPoLineNumber(): ?string
    {
        return $this->client_po_line_number;
    }

    public function setClientPoLineNumber(
        string $client_po_line_number
    ): void {
        $this->client_po_line_number = $client_po_line_number;
    }

    public function getClientPoPerson(): ?string
    {
        return $this->client_po_person;
    }

    public function setClientPoPerson(
        string $client_po_person
    ): void {
        $this->client_po_person = $client_po_person;
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

    public function getPaymentTerm(): ?string
    {
        return $this->payment_term;
    }

    public function setPaymentTerm(string $payment_term): void
    {
        $this->payment_term = $payment_term;
    }
}
