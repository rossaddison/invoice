<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Unit\Unit;
use App\Invoice\Unit\UnitForm;
use PHPUnit\Framework\TestCase;

class UnitFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new UnitForm();

        $this->assertNull($form->getUnitName());
        $this->assertNull($form->getUnitNamePlrl());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new UnitForm())->getFormName());
    }

    public function testShowPopulatesFromUnit(): void
    {
        $unit = new Unit();
        $unit->setUnitName('Kilogram');
        $unit->setUnitNamePlrl('Kilograms');

        $form = UnitForm::show($unit);

        $this->assertSame('Kilogram', $form->getUnitName());
        $this->assertSame('Kilograms', $form->getUnitNamePlrl());
    }

    public function testShowWithEmptyStrings(): void
    {
        $unit = new Unit();
        $unit->setUnitName('');
        $unit->setUnitNamePlrl('');

        $form = UnitForm::show($unit);

        $this->assertSame('', $form->getUnitName());
        $this->assertSame('', $form->getUnitNamePlrl());
    }

    public function testShowReturnsNewInstance(): void
    {
        $unit = new Unit();
        $unit->setUnitName('Box');
        $unit->setUnitNamePlrl('Boxes');

        $this->assertNotSame(UnitForm::show($unit), UnitForm::show($unit));
    }

    public function testCommonUnits(): void
    {
        $cases = [
            ['Piece', 'Pieces'],
            ['Hour', 'Hours'],
            ['Litre', 'Litres'],
            ['Metre', 'Metres'],
        ];

        foreach ($cases as [$singular, $plural]) {
            $unit = new Unit();
            $unit->setUnitName($singular);
            $unit->setUnitNamePlrl($plural);

            $form = UnitForm::show($unit);
            $this->assertSame($singular, $form->getUnitName());
            $this->assertSame($plural, $form->getUnitNamePlrl());
        }
    }
}
