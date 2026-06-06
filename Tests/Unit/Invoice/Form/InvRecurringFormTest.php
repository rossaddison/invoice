<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
use App\Invoice\InvRecurring\InvRecurringForm;
use PHPUnit\Framework\TestCase;

class InvRecurringFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new InvRecurringForm();

        $this->assertSame('', $form->getFrequency());
        $this->assertSame('', $form->getStart());
        $this->assertSame('', $form->getNext());
        $this->assertSame('', $form->getEnd());
        $this->assertNull($form->getInvId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new InvRecurringForm())->getFormName());
    }

    public function testShowUsesPassedInvId(): void
    {
        // show() takes ?int $inv_id as 2nd param; entity inv_id is NOT used
        $entity = new InvRecurring();
        $entity->setFrequency('monthly');

        $form = InvRecurringForm::show($entity, 9);

        $this->assertSame(9, $form->getInvId());
        $this->assertSame('monthly', $form->getFrequency());
    }

    public function testShowWithEntityDefaultDateFieldsAreEmpty(): void
    {
        // InvRecurring entity defaults: start='', end='', next=''
        $entity = new InvRecurring();
        $entity->setFrequency('weekly');

        $form = InvRecurringForm::show($entity, 3);

        $this->assertSame('', $form->getStart());
        $this->assertSame('', $form->getEnd());
        $this->assertSame('', $form->getNext());
    }

    public function testShowWithNullInvId(): void
    {
        $entity = new InvRecurring();
        $entity->setFrequency('annual');

        $form = InvRecurringForm::show($entity, null);

        $this->assertNull($form->getInvId());
        $this->assertSame('annual', $form->getFrequency());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new InvRecurring();

        $this->assertNotSame(
            InvRecurringForm::show($entity, 1),
            InvRecurringForm::show($entity, 1)
        );
    }
}
