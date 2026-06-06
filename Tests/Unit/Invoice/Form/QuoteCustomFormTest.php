<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\QuoteCustom\QuoteCustom;
use App\Invoice\QuoteCustom\QuoteCustomForm;
use PHPUnit\Framework\TestCase;

class QuoteCustomFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new QuoteCustomForm();

        $this->assertNull($form->getQuoteId());
        $this->assertNull($form->getCustomFieldId());
        $this->assertSame('', $form->getValue());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new QuoteCustomForm())->getFormName());
    }

    public function testShowUsesPassedQuoteId(): void
    {
        // show() takes quote_id as 2nd parameter, NOT from entity
        $entity = new QuoteCustom();
        $entity->setCustomFieldId(5);

        $form = QuoteCustomForm::show($entity, 33);

        $this->assertSame(33, $form->getQuoteId());
        $this->assertSame(5, $form->getCustomFieldId());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new QuoteCustom();
        $entity->setCustomFieldId(8);
        $entity->setValue('Quote custom value');

        $form = QuoteCustomForm::show($entity, 12);

        $this->assertSame(12, $form->getQuoteId());
        $this->assertSame(8, $form->getCustomFieldId());
        $this->assertSame('Quote custom value', $form->getValue());
    }

    public function testShowWithEmptyValueCopiesEntityDefault(): void
    {
        // QuoteCustom::getValue() returns string defaulting to ''
        $entity = new QuoteCustom();
        $entity->setCustomFieldId(1);

        $form = QuoteCustomForm::show($entity, 2);

        $this->assertSame('', $form->getValue());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new QuoteCustom();
        $entity->setCustomFieldId(1);

        $this->assertNotSame(
            QuoteCustomForm::show($entity, 1),
            QuoteCustomForm::show($entity, 1)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new QuoteCustom();
        $entity->setCustomFieldId(3);
        $entity->setValue('Test');

        $form = QuoteCustomForm::show($entity, 5);

        $this->assertIsInt($form->getQuoteId());
        $this->assertIsInt($form->getCustomFieldId());
        $this->assertIsString($form->getValue());
    }
}
