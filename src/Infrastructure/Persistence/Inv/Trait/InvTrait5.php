<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Inv\Trait;

use App\Infrastructure\Persistence\InvAmount\InvAmount;

/**
 * @method int reqStatusId()
 */
trait InvTrait5
{

    public function getUrlKey(): string
    {
        return $this->url_key;
    }

    public function setUrlKey(string $url_key): void
    {
        $this->url_key = $url_key;
    }

    public function getPaymentMethod(): ?int
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(int $payment_method): void
    {
        $this->payment_method = $payment_method;
    }

    public function getPostalAddressId(): ?int
    {
        return $this->postal_address_id;
    }

    public function setPostalAddressId(int $postal_address_id): void
    {
        $this->postal_address_id = $postal_address_id;
    }

    public function getCreditinvoiceParentId(): ?int
    {
        return $this->creditinvoice_parent_id;
    }

    public function setCreditinvoiceParentId(?int $creditinvoice_parent_id): void
    {
        $this->creditinvoice_parent_id = $creditinvoice_parent_id;
    }

    public function isOverdue(): bool
    {
        return $this->reqStatusId() === 5;
    }

    /**
     * This code is used if a VAT rate for the tax period is not known yet and
     * is mutually exclusive to the tax point date.
     * So if if you cannot determine a tax point date because you do not know what
     * the VAT rate is, use this code instead of a tax point date.
     * If you have a string value for this, you should not have a value for your tax point date
     * The two are mutually exclusive.
     * Related logic: see src/resources/views/invoice/info/deutschebahn.php
     * @return string
     */
    public function getStandInCode(): string
    {
        return $this->stand_in_code;
    }

    public function setStandInCode(string $stand_in_code): void
    {
        $this->stand_in_code = $stand_in_code;
    }

    public function getInvAmount(): InvAmount
    {
        return $this->invAmount;
    }

    public function getClientPoNumber(): ?string
    {
        return $this->client_po_number;
    }

    public function setClientPoNumber(string $client_po_number): void
    {
        $this->client_po_number = $client_po_number;
    }

    public function getClientPoPerson(): ?string
    {
        return $this->client_po_person;
    }

    public function setClientPoPerson(string $client_po_person): void
    {
        $this->client_po_person = $client_po_person;
    }
}
