<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\PaymentPeppol\PaymentPeppol;
use App\Invoice\PaymentPeppol\PaymentPeppolForm;
use PHPUnit\Framework\TestCase;

class PaymentPeppolFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new PaymentPeppolForm();

        $this->assertNull($form->getInvId());
        $this->assertNull($form->getAutoReference());
        $this->assertSame('', $form->getProvider());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new PaymentPeppolForm())->getFormName());
    }

    public function testShowPopulatesInvId(): void
    {
        $entity = new PaymentPeppol();
        $entity->setInvId(8);

        $form = PaymentPeppolForm::show($entity);

        $this->assertSame(8, $form->getInvId());
    }

    public function testShowAutoReferenceIsTimestamp(): void
    {
        // PaymentPeppol constructor initializes auto_reference to DateTimeImmutable timestamp
        $entity = new PaymentPeppol();
        $entity->setInvId(1);

        $form = PaymentPeppolForm::show($entity);

        $this->assertIsInt($form->getAutoReference());
        $this->assertGreaterThan(0, $form->getAutoReference());
    }

    public function testShowPopulatesProvider(): void
    {
        $entity = new PaymentPeppol();
        $entity->setInvId(2);
        $entity->setProvider('stripe');

        $form = PaymentPeppolForm::show($entity);

        $this->assertSame('stripe', $form->getProvider());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new PaymentPeppol();
        $entity->setInvId(1);

        $this->assertNotSame(
            PaymentPeppolForm::show($entity),
            PaymentPeppolForm::show($entity)
        );
    }
}
