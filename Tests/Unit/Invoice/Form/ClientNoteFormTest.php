<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\ClientNote\ClientNote;
use App\Invoice\ClientNote\ClientNoteForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ClientNoteFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new ClientNoteForm();

        $this->assertNull($form->getClientId());
        $this->assertNull($form->getDateNote());
        $this->assertSame('', $form->getNote());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ClientNoteForm())->getFormName());
    }

    public function testShowWithDateTimeImmutableFormatsAsYmd(): void
    {
        $entity = new ClientNote();
        $entity->setClientId(4);
        $entity->setNote('Delayed payment discussed');
        $entity->setDateNote(new DateTimeImmutable('2026-03-15'));

        $form = ClientNoteForm::show($entity);

        $this->assertSame(4, $form->getClientId());
        $this->assertSame('2026-03-15', $form->getDateNote());
        $this->assertSame('Delayed payment discussed', $form->getNote());
    }

    public function testShowWithNullDateNoteReturnsNull(): void
    {
        $entity = new ClientNote();
        $entity->setClientId(2);
        $entity->setNote('General note');

        $form = ClientNoteForm::show($entity);

        $this->assertNull($form->getDateNote());
        $this->assertSame('General note', $form->getNote());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new ClientNote();
        $entity->setClientId(1);
        $entity->setNote('Note');

        $this->assertNotSame(
            ClientNoteForm::show($entity),
            ClientNoteForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new ClientNote();
        $entity->setClientId(5);
        $entity->setNote('Type check note');
        $entity->setDateNote(new DateTimeImmutable('2026-06-01'));

        $form = ClientNoteForm::show($entity);

        $this->assertIsInt($form->getClientId());
        $this->assertIsString($form->getDateNote());
        $this->assertIsString($form->getNote());
    }
}
