<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Contract\Contract;
use App\Invoice\Contract\ContractForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ContractFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new ContractForm();

        $this->assertNull($form->getReference());
        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getPeriodStart());
        $this->assertSame('', $form->getPeriodEnd());
        $this->assertNull($form->getClientId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ContractForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new Contract();
        $entity->setClientId(5);
        $entity->setReference('CTR-001');
        $entity->setName('Annual Support');
        $entity->setPeriodStart(new DateTimeImmutable('2026-01-01'));
        $entity->setPeriodEnd(new DateTimeImmutable('2026-12-31'));

        $form = ContractForm::show($entity);

        $this->assertSame('CTR-001', $form->getReference());
        $this->assertSame('Annual Support', $form->getName());
        $this->assertSame('2026-01-01', $form->getPeriodStart());
        $this->assertSame('2026-12-31', $form->getPeriodEnd());
        $this->assertSame(5, $form->getClientId());
    }

    public function testPeriodStartAndEndAreFormattedAsYmd(): void
    {
        $entity = new Contract();
        $entity->setClientId(1);
        $entity->setReference('REF');
        $entity->setName('Q1');
        $entity->setPeriodStart(new DateTimeImmutable('2026-03-01'));
        $entity->setPeriodEnd(new DateTimeImmutable('2026-03-31'));

        $form = ContractForm::show($entity);

        $this->assertSame('2026-03-01', $form->getPeriodStart());
        $this->assertSame('2026-03-31', $form->getPeriodEnd());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Contract();
        $entity->setClientId(1);
        $entity->setReference('R');
        $entity->setName('N');
        $entity->setPeriodStart(new DateTimeImmutable('2026-01-01'));
        $entity->setPeriodEnd(new DateTimeImmutable('2026-12-31'));

        $this->assertNotSame(
            ContractForm::show($entity),
            ContractForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new Contract();
        $entity->setClientId(3);
        $entity->setReference('TYPE-TEST');
        $entity->setName('Type contract');
        $entity->setPeriodStart(new DateTimeImmutable('2026-06-01'));
        $entity->setPeriodEnd(new DateTimeImmutable('2026-06-30'));

        $form = ContractForm::show($entity);

        $this->assertIsString($form->getReference());
        $this->assertIsString($form->getName());
        $this->assertIsString($form->getPeriodStart());
        $this->assertIsString($form->getPeriodEnd());
        $this->assertIsInt($form->getClientId());
    }
}
