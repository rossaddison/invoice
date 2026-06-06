<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Setting\Setting;
use App\Invoice\Setting\SettingForm;
use PHPUnit\Framework\TestCase;

class SettingFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new SettingForm();

        $this->assertNull($form->getSettingKey());
        $this->assertNull($form->getSettingValue());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new SettingForm())->getFormName());
    }

    public function testShowPopulatesKeyAndValue(): void
    {
        $entity = new Setting();
        $entity->setSettingKey('currency_symbol');
        $entity->setSettingValue('£');

        $form = SettingForm::show($entity);

        $this->assertSame('currency_symbol', $form->getSettingKey());
        $this->assertSame('£', $form->getSettingValue());
    }

    public function testShowWithEmptyValue(): void
    {
        $entity = new Setting();
        $entity->setSettingKey('some_flag');
        $entity->setSettingValue('');

        $form = SettingForm::show($entity);

        $this->assertSame('some_flag', $form->getSettingKey());
        $this->assertSame('', $form->getSettingValue());
    }

    public function testGetRulesContainsSettingKey(): void
    {
        $form = new SettingForm();
        $rules = $form->getRules();

        $this->assertArrayHasKey('setting_key', $rules);
        $this->assertNotEmpty($rules['setting_key']);
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Setting();
        $entity->setSettingKey('k');
        $entity->setSettingValue('v');

        $this->assertNotSame(
            SettingForm::show($entity),
            SettingForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new Setting();
        $entity->setSettingKey('date_format');
        $entity->setSettingValue('d/m/Y');

        $form = SettingForm::show($entity);

        $this->assertIsString($form->getSettingKey());
        $this->assertIsString($form->getSettingValue());
    }
}
