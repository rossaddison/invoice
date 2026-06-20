<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Inv\Trait;

use DateTimeImmutable;

trait InvTrait4
{

    public function setDateDue(\App\Invoice\Setting\SettingRepository $sR): void
    {
        if (empty($sR->getSetting('invoices_due_after'))) {
            $days = 30;
        } else {
            $days = $sR->getSetting('invoices_due_after');
        }

        $this->date_due = $this->date_created->add(new \DateInterval('P'
                . (string) $days . 'D'));
    }

    public function getDateDue(): DateTimeImmutable
    {
        return $this->date_due;
    }

    public function getDateSupplied(): DateTimeImmutable
    {
        return $this->date_supplied;
    }

    public function setDateSupplied(DateTimeImmutable $date_supplied): void
    {
        $this->date_supplied = $date_supplied;
    }

    public function getDateTaxPoint(): DateTimeImmutable
    {
        return $this->date_tax_point;
    }

    public function setDateTaxPoint(DateTimeImmutable $date_tax_point): void
    {
        $this->date_tax_point = $date_tax_point;
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

    public function getTerms(): string
    {
        return $this->terms;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function getDocumentDescription(): ?string
    {
        return $this->document_description;
    }

    public function setDocumentDescription(string $document_description): void
    {
        $this->document_description = $document_description;
    }

    public function setTerms(string $terms): void
    {
        $this->terms = $terms;
    }
}
