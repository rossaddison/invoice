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

        $this->assertSame('', $form->getInvId());
        $this->assertSame('', $form->getSoItemId());
        $this->assertSame('', $form->getTaxRateId());
        $this->assertNull($form->getQuantity());
        $this->assertNull($form->getPrice());
        $this->assertNull($form->getDate());
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

        $this->assertSame('12', $form->getInvId());
        $this->assertSame('3', $form->getTaxRateId());
    }

    public function testShowWithNullRelationsGivesNullProductAndTask(): void
    {
        // getProduct() and getTask() are null (relations not loaded)
        $entity = new InvItem();
        $entity->setTaxRateId(1);

        $form = InvItemForm::show($entity, 5);

        $this->assertNull($form->getProductId());
        $this->assertNull($form->getTaskId());
    }

    public function testShowDateIsFormattedFromEntityDateTimeImmutable(): void
    {
        // InvItem constructor initializes date to new DateTimeImmutable()
        // show() formats it as 'Y-m-d' string
        $entity = new InvItem();
        $entity->setTaxRateId(1);

        $form = InvItemForm::show($entity, 1);

        $this->assertIsString($form->getDate());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $form->getDate());
    }

    public function testShowWithDefaultBelongsToVatInvoiceIsZero(): void
    {
        // getBelongsToVatInvoice() returns (string) null = ''; (int) '' = 0
        $entity = new InvItem();
        $entity->setTaxRateId(1);

        $form = InvItemForm::show($entity, 1);

        $this->assertSame(0, $form->getBelongsToVatInvoice());
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

        $this->assertSame('7', $form->getInvId());
        $this->assertSame('2', $form->getTaxRateId());
        $this->assertSame('Widget A', $form->getName());
        $this->assertSame('Standard widget', $form->getDescription());
        $this->assertSame(5.0, $form->getQuantity());
        $this->assertSame(10.0, $form->getPrice());
    }
}
