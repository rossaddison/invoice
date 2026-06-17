<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\UserInv\UserInvForm;
use PHPUnit\Framework\TestCase;

class UserInvFormTest extends TestCase
{
    public function testDefaultsAreSet(): void
    {
        $form = new UserInvForm();

        $this->assertNull($form->user_id);
        $this->assertNull($form->type);
        $this->assertFalse($form->active);
        $this->assertSame('', $form->language);
        $this->assertSame('', $form->name);
        $this->assertSame('', $form->company);
        $this->assertFalse($form->all_clients);
        $this->assertSame(10, $form->list_limit);
        $this->assertFalse($form->consent_periodic_invoice);
        $this->assertFalse($form->consent_telegram_outstanding);
        $this->assertNull($form->telegram_chat_id);
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new UserInvForm())->getFormName());
    }

    public function testShowPopulatesCoreFields(): void
    {
        $entity = new UserInv();
        $entity->setUserId(12);
        $entity->setType(0);
        $entity->setActive(true);
        $entity->setLanguage('en');
        $entity->setName('Alice Admin');

        $form = UserInvForm::show($entity);

        $this->assertSame(12, $form->user_id);
        $this->assertSame(0, $form->type);
        $this->assertTrue($form->active);
        $this->assertSame('en', $form->language);
        $this->assertSame('Alice Admin', $form->name);
        $this->assertNull($form->user);
    }

    public function testShowPopulatesAddressFields(): void
    {
        $entity = new UserInv();
        $entity->setUserId(3);
        $entity->setType(1);
        $entity->setActive(false);
        $entity->setLanguage('fr');
        $entity->setName('Bob');

        $form = UserInvForm::show($entity);

        $this->assertSame('', $form->address_1);
        $this->assertSame('', $form->city);
        $this->assertSame('', $form->country);
        $this->assertNull($form->gln);
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new UserInv();
        $entity->setUserId(1);
        $entity->setType(0);
        $entity->setActive(true);
        $entity->setLanguage('en');
        $entity->setName('Test');

        $this->assertNotSame(
            UserInvForm::show($entity),
            UserInvForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new UserInv();
        $entity->setUserId(7);
        $entity->setType(1);
        $entity->setActive(false);
        $entity->setLanguage('de');
        $entity->setName('Hans');

        $form = UserInvForm::show($entity);

        $this->assertIsInt($form->user_id);
        $this->assertIsInt($form->type);
        $this->assertIsBool($form->active);
        $this->assertIsString($form->language);
        $this->assertIsString($form->name);
    }
}
