<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Invoice\InvItem\InvItemForm;
use PHPUnit\Framework\TestCase;

class InvItemFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new InvItemForm();

        $this->assertSame('', $form->inv_id);
        $this->assertSame('', $form->so_item_id);
        $this->assertSame('', $form->tax_rate_id);
        $this->assertNull($form->quantity);
        $this->assertNull($form->price);
        $this->assertNull($form->date);
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new InvItemForm())->getFormName());
    }

    public function testShowUsesPassedInvId(): void
    {
        // show() takes int $inv_id as 2nd param (cast to string); entity provides tax_rate_id
        $entity = new InvItem();
        $entity->setTaxRateId(3);

        $form = InvItemForm::show($entity, 12);

        $this->assertSame('12', $form->inv_id);
        $this->assertSame('3', $form->tax_rate_id);
    }

    public function testShowWithNullRelationsGivesNullProductAndTask(): void
    {
        // getProduct() and getTask() are null (relations not loaded)
        $entity = new InvItem();
        $entity->setTaxRateId(1);

        $form = InvItemForm::show($entity, 5);

        $this->assertNull($form->product_id);
        $this->assertNull($form->task_id);
    }

    public function testShowDateIsFormattedFromEntityDateTimeImmutable(): void
    {
        // InvItem constructor initializes date to new DateTimeImmutable()
        // show() formats it as 'Y-m-d' string
        $entity = new InvItem();
        $entity->setTaxRateId(1);

        $form = InvItemForm::show($entity, 1);

        $this->assertIsString($form->date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $form->date);
    }

    public function testShowWithDefaultBelongsToVatInvoiceIsZero(): void
    {
        // getBelongsToVatInvoice() returns (string) null = ''; (int) '' = 0
        $entity = new InvItem();
        $entity->setTaxRateId(1);

        $form = InvItemForm::show($entity, 1);

        $this->assertSame(0, $form->belongs_to_vat_invoice);
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new InvItem();
        $entity->setTaxRateId(1);

        $this->assertNotSame(
            InvItemForm::show($entity, 1),
            InvItemForm::show($entity, 1)
        );
    }

    public function testShowPopulatesStringFields(): void
    {
        $entity = new InvItem();
        $entity->setTaxRateId(2);
        $entity->setName('Widget A');
        $entity->setDescription('Standard widget');
        $entity->setQuantity(5.0);
        $entity->setPrice(10.00);

        $form = InvItemForm::show($entity, 7);

        $this->assertSame('7', $form->inv_id);
        $this->assertSame('2', $form->tax_rate_id);
        $this->assertSame('Widget A', $form->name);
        $this->assertSame('Standard widget', $form->description);
        $this->assertSame(5.0, $form->quantity);
        $this->assertSame(10.0, $form->price);
    }
}
