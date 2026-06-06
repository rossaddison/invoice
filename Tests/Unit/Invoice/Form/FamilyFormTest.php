<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Family\Family;
use App\Invoice\Family\FamilyForm;
use PHPUnit\Framework\TestCase;

class FamilyFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new FamilyForm();

        $this->assertSame('', $form->getFormName());
        $this->assertSame('', $form->getFamilyName());
        $this->assertSame('', $form->family_commalist);
        $this->assertSame('', $form->family_productprefix);
        $this->assertNull($form->category_primary_id);
        $this->assertNull($form->category_secondary_id);
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new FamilyForm())->getFormName());
    }

    public function testShowPopulatesFromFamily(): void
    {
        $family = new Family();
        $family->setFamilyName('Electronics');
        $family->setFamilyCommalist('phones,tablets');
        $family->setFamilyProductprefix('ELEC');
        $family->setCategoryPrimaryId(3);
        $family->setCategorySecondaryId(7);

        $form = FamilyForm::show($family);

        $this->assertSame('Electronics', $form->getFamilyName());
        $this->assertSame('phones,tablets', $form->getFamilyCommalist());
        $this->assertSame('ELEC', $form->getFamilyProductprefix());
        $this->assertSame(3, $form->getCategoryPrimaryId());
        $this->assertSame(7, $form->getCategorySecondaryId());
    }

    public function testShowWithNullOptionalFields(): void
    {
        $family = new Family();
        $family->setFamilyName('General');

        $form = FamilyForm::show($family);

        $this->assertSame('General', $form->getFamilyName());
        $this->assertNull($form->getCategoryPrimaryId());
        $this->assertNull($form->getCategorySecondaryId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $family = new Family();
        $family->setFamilyName('Test');

        $form1 = FamilyForm::show($family);
        $form2 = FamilyForm::show($family);

        $this->assertNotSame($form1, $form2);
    }

    public function testFamilyNameGetter(): void
    {
        $form = new FamilyForm();
        $form->family_commalist = 'a,b,c';

        $this->assertSame('a,b,c', $form->getFamilyCommalist());
    }
}
