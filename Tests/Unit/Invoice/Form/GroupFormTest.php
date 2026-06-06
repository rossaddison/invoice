<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Group\Group;
use App\Invoice\Group\GroupForm;
use PHPUnit\Framework\TestCase;

class GroupFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new GroupForm();

        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getIdentifierFormat());
        $this->assertNull($form->getNextId());
        $this->assertNull($form->getLeftPad());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new GroupForm())->getFormName());
    }

    public function testShowPopulatesFromGroup(): void
    {
        $group = new Group();
        $group->setName('Default');
        $group->setIdentifierFormat('{{{sequential_number}}}');
        $group->setNextId(1);
        $group->setLeftPad(0);

        $form = GroupForm::show($group);

        $this->assertSame('Default', $form->getName());
        $this->assertSame('{{{sequential_number}}}', $form->getIdentifierFormat());
        $this->assertSame(1, $form->getNextId());
        $this->assertSame(0, $form->getLeftPad());
    }

    public function testShowWithHighSequenceNumber(): void
    {
        $group = new Group();
        $group->setName('Sales');
        $group->setIdentifierFormat('INV-{{{sequential_number}}}');
        $group->setNextId(999);
        $group->setLeftPad(4);

        $form = GroupForm::show($group);

        $this->assertSame(999, $form->getNextId());
        $this->assertSame(4, $form->getLeftPad());
    }

    public function testShowReturnsNewInstance(): void
    {
        $group = new Group();
        $group->setName('Test');
        $group->setIdentifierFormat('{{{sequential_number}}}');
        $group->setNextId(1);
        $group->setLeftPad(0);

        $this->assertNotSame(GroupForm::show($group), GroupForm::show($group));
    }

    public function testGettersReturnCorrectTypes(): void
    {
        $group = new Group();
        $group->setName('Type Test');
        $group->setIdentifierFormat('TT-{{{sequential_number}}}');
        $group->setNextId(5);
        $group->setLeftPad(3);

        $form = GroupForm::show($group);

        $this->assertIsString($form->getName());
        $this->assertIsString($form->getIdentifierFormat());
        $this->assertIsInt($form->getNextId());
        $this->assertIsInt($form->getLeftPad());
    }
}
