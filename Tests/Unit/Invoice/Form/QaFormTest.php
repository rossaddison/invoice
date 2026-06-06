<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Qa\Qa;
use App\Invoice\Qa\QaForm;
use PHPUnit\Framework\TestCase;

class QaFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new QaForm();

        $this->assertSame(0, $form->getActive());
        $this->assertSame('', $form->getQuestion());
        $this->assertSame('', $form->getAnswer());
        $this->assertNull($form->getSortOrder());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new QaForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new Qa();
        $entity->setActive(1);
        $entity->setQuestion('What is VAT?');
        $entity->setAnswer('Value Added Tax');
        $entity->setSortOrder(3);

        $form = QaForm::show($entity);

        $this->assertSame(1, $form->getActive());
        $this->assertSame('What is VAT?', $form->getQuestion());
        $this->assertSame('Value Added Tax', $form->getAnswer());
        $this->assertSame('3', $form->getSortOrder());
    }

    public function testShowWithInactiveQa(): void
    {
        $entity = new Qa();
        $entity->setActive(0);
        $entity->setQuestion('Archived question');
        $entity->setAnswer('Archived answer');
        $entity->setSortOrder(99);

        $form = QaForm::show($entity);

        $this->assertSame(0, $form->getActive());
        $this->assertSame('99', $form->getSortOrder());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Qa();
        $entity->setActive(1);
        $entity->setQuestion('Q');
        $entity->setAnswer('A');
        $entity->setSortOrder(1);

        $this->assertNotSame(QaForm::show($entity), QaForm::show($entity));
    }

    public function testSortOrderIsStringAfterShow(): void
    {
        $entity = new Qa();
        $entity->setActive(1);
        $entity->setQuestion('Q');
        $entity->setAnswer('A');
        $entity->setSortOrder(5);

        $form = QaForm::show($entity);

        $this->assertIsString($form->getSortOrder());
    }
}
