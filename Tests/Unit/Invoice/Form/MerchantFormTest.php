<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Merchant\Merchant;
use App\Invoice\Merchant\MerchantForm;
use PHPUnit\Framework\TestCase;

class MerchantFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new MerchantForm();

        $this->assertNull($form->getInvId());
        $this->assertTrue($form->getSuccessful());
        $this->assertSame('', $form->getDate());
        $this->assertSame('', $form->getDriver());
        $this->assertSame('', $form->getResponse());
        $this->assertSame('', $form->getReference());
        $this->assertNull($form->getInv());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new MerchantForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new Merchant();
        $entity->setInvId(12);
        $entity->setSuccessful(true);
        $entity->setDriver('stripe');
        $entity->setResponse('{"status":"succeeded"}');
        $entity->setReference('pi_3abc123');

        $form = MerchantForm::show($entity);

        $this->assertSame(12, $form->getInvId());
        $this->assertTrue($form->getSuccessful());
        $this->assertSame('stripe', $form->getDriver());
        $this->assertSame('{"status":"succeeded"}', $form->getResponse());
        $this->assertSame('pi_3abc123', $form->getReference());
        $this->assertSame('', $form->getDate());
        $this->assertNull($form->getInv());
    }

    public function testShowWithFailedTransaction(): void
    {
        $entity = new Merchant();
        $entity->setInvId(5);
        $entity->setSuccessful(false);
        $entity->setDriver('braintree');
        $entity->setResponse('declined');
        $entity->setReference('txn_xyz');

        $form = MerchantForm::show($entity);

        $this->assertFalse($form->getSuccessful());
        $this->assertSame('braintree', $form->getDriver());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Merchant();
        $entity->setInvId(1);
        $entity->setDriver('stripe');
        $entity->setResponse('ok');
        $entity->setReference('ref');

        $this->assertNotSame(
            MerchantForm::show($entity),
            MerchantForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new Merchant();
        $entity->setInvId(3);
        $entity->setSuccessful(true);
        $entity->setDriver('stripe');
        $entity->setResponse('ok');
        $entity->setReference('ref123');

        $form = MerchantForm::show($entity);

        $this->assertIsInt($form->getInvId());
        $this->assertIsBool($form->getSuccessful());
        $this->assertIsString($form->getDriver());
        $this->assertIsString($form->getResponse());
        $this->assertIsString($form->getReference());
    }
}
