<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\PurchaseEntry\PurchaseEntry;
use App\Invoice\PurchaseEntry\PurchaseEntryForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PurchaseEntryFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new PurchaseEntryForm();

        $this->assertNull($form->getDate());
        $this->assertSame('', $form->getSupplier());
        $this->assertNull($form->getDescription());
        $this->assertSame(0.00, $form->getAmountExVat());
        $this->assertSame(0.00, $form->getVatAmount());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new PurchaseEntryForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entry = new PurchaseEntry();
        $entry->setDate(new DateTimeImmutable('2026-01-15'));
        $entry->setSupplier('ACME Supplies Ltd');
        $entry->setDescription('Invoice #1234');
        $entry->setAmountExVat(500.00);
        $entry->setVatAmount(100.00);

        $form = PurchaseEntryForm::show($entry);

        $this->assertSame('2026-01-15', $form->getDate());
        $this->assertSame('ACME Supplies Ltd', $form->getSupplier());
        $this->assertSame('Invoice #1234', $form->getDescription());
        $this->assertSame(500.00, $form->getAmountExVat());
        $this->assertSame(100.00, $form->getVatAmount());
    }

    public function testShowWithNullDate(): void
    {
        $entry = new PurchaseEntry();
        $entry->setDate(null);
        $entry->setSupplier('No-Date Supplier');
        $entry->setAmountExVat(50.00);
        $entry->setVatAmount(10.00);

        $form = PurchaseEntryForm::show($entry);

        $this->assertNull($form->getDate());
    }

    public function testShowWithNullDescription(): void
    {
        $entry = new PurchaseEntry();
        $entry->setDate(new DateTimeImmutable('2026-03-01'));
        $entry->setSupplier('Stationery Co');
        $entry->setDescription(null);
        $entry->setAmountExVat(120.00);
        $entry->setVatAmount(24.00);

        $form = PurchaseEntryForm::show($entry);

        $this->assertNull($form->getDescription());
        $this->assertSame(120.00, $form->getAmountExVat());
        $this->assertSame(24.00, $form->getVatAmount());
    }

    public function testShowWithZeroAmounts(): void
    {
        $entry = new PurchaseEntry();
        $entry->setDate(new DateTimeImmutable('2026-06-01'));
        $entry->setSupplier('Zero Vat Supplier');
        $entry->setAmountExVat(0.00);
        $entry->setVatAmount(0.00);

        $form = PurchaseEntryForm::show($entry);

        $this->assertSame(0.00, $form->getAmountExVat());
        $this->assertSame(0.00, $form->getVatAmount());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entry = new PurchaseEntry();
        $entry->setDate(new DateTimeImmutable('2026-01-01'));
        $entry->setSupplier('Test');

        $this->assertNotSame(PurchaseEntryForm::show($entry), PurchaseEntryForm::show($entry));
    }

    public function testGettersReturnCorrectTypes(): void
    {
        $entry = new PurchaseEntry();
        $entry->setDate(new DateTimeImmutable('2026-01-01'));
        $entry->setSupplier('Supplier');
        $entry->setAmountExVat(200.00);
        $entry->setVatAmount(40.00);

        $form = PurchaseEntryForm::show($entry);

        $this->assertIsString($form->getDate());
        $this->assertIsString($form->getSupplier());
        $this->assertIsFloat($form->getAmountExVat());
        $this->assertIsFloat($form->getVatAmount());
    }
}
