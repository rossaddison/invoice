<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\InvSentLog\InvSentLog;
use App\Invoice\InvSentLog\InvSentLogForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class InvSentLogFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new InvSentLogForm();

        $this->assertNull($form->getInvId());
        $this->assertSame('', $form->getDateSent());
        $this->assertNull($form->getInv());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new InvSentLogForm())->getFormName());
    }

    public function testShowPopulatesInvId(): void
    {
        $entity = new InvSentLog();
        $entity->setInvId(6);

        $form = InvSentLogForm::show($entity);

        $this->assertSame(6, $form->getInvId());
    }

    public function testShowDateSentIsDateTimeImmutable(): void
    {
        // InvSentLog constructor sets date_sent = new DateTimeImmutable('now')
        $entity = new InvSentLog();
        $entity->setInvId(1);

        $form = InvSentLogForm::show($entity);

        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateSent());
    }

    public function testShowInvRelationIsNull(): void
    {
        // Inv relation is not loaded in tests
        $entity = new InvSentLog();
        $entity->setInvId(2);

        $form = InvSentLogForm::show($entity);

        $this->assertNull($form->getInv());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new InvSentLog();
        $entity->setInvId(1);

        $this->assertNotSame(
            InvSentLogForm::show($entity),
            InvSentLogForm::show($entity)
        );
    }
}
