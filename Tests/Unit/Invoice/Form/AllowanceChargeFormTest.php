<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeForm;
use PHPUnit\Framework\TestCase;
use Yiisoft\Translator\TranslatorInterface;

class AllowanceChargeFormTest extends TestCase
{
    private TranslatorInterface $translator;

    /** @psalm-suppress PropertyNotSetInConstructor */
    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createStub(TranslatorInterface::class);
    }

    public function testDefaultsAreSet(): void
    {
        $form = new AllowanceChargeForm($this->translator);

        $this->assertFalse($form->getIdentifier());
        $this->assertSame(0, $form->getLevel());
        $this->assertSame('', $form->getReasonCode());
        $this->assertSame('', $form->getReason());
        $this->assertNull($form->getMultiplierFactorNumeric());
        $this->assertNull($form->getAmount());
        $this->assertNull($form->getBaseAmount());
        $this->assertNull($form->getTaxRateId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new AllowanceChargeForm($this->translator))->getFormName());
    }

    public function testShowPopulatesAllowance(): void
    {
        $entity = new AllowanceCharge();
        $entity->setIdentifier(false);
        $entity->setLevel(0);
        $entity->setReasonCode('95');
        $entity->setReason('Discount');
        $entity->setMultiplierFactorNumeric(0);
        $entity->setAmount(500);
        $entity->setBaseAmount(0);

        $form = AllowanceChargeForm::show($entity, $this->translator);

        $this->assertFalse($form->getIdentifier());
        $this->assertSame(0, $form->getLevel());
        $this->assertSame('95', $form->getReasonCode());
        $this->assertSame('Discount', $form->getReason());
        $this->assertSame(0, $form->getMultiplierFactorNumeric());
        $this->assertSame(500, $form->getAmount());
        $this->assertSame(0, $form->getBaseAmount());
    }

    public function testShowPopulatesCharge(): void
    {
        $entity = new AllowanceCharge();
        $entity->setIdentifier(true);
        $entity->setLevel(1);
        $entity->setReasonCode('ZZZ');
        $entity->setReason('Freight');
        $entity->setMultiplierFactorNumeric(10);
        $entity->setAmount(0);
        $entity->setBaseAmount(1000);

        $form = AllowanceChargeForm::show($entity, $this->translator);

        $this->assertTrue($form->getIdentifier());
        $this->assertSame(1, $form->getLevel());
        $this->assertSame(10, $form->getMultiplierFactorNumeric());
        $this->assertSame(0, $form->getAmount());
        $this->assertSame(1000, $form->getBaseAmount());
    }

    public function testGetRulesContainsAmountAndBaseAmount(): void
    {
        $form = new AllowanceChargeForm($this->translator);
        $rules = $form->getRules();

        $this->assertArrayHasKey('amount', $rules);
        $this->assertArrayHasKey('base_amount', $rules);
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new AllowanceCharge();
        $entity->setReason('Discount');

        $this->assertNotSame(
            AllowanceChargeForm::show($entity, $this->translator),
            AllowanceChargeForm::show($entity, $this->translator)
        );
    }
}
