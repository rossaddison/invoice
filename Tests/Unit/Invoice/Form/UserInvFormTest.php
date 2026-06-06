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

        $this->assertNull($form->getUserId());
        $this->assertNull($form->getType());
        $this->assertFalse($form->getActive());
        $this->assertSame('', $form->getLanguage());
        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getCompany());
        $this->assertFalse($form->getAllClients());
        $this->assertSame(10, $form->getListLimit());
        $this->assertFalse($form->getConsentPeriodicInvoice());
        $this->assertFalse($form->getConsentTelegramOutstanding());
        $this->assertNull($form->getTelegramChatId());
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

        $this->assertSame(12, $form->getUserId());
        $this->assertSame(0, $form->getType());
        $this->assertTrue($form->getActive());
        $this->assertSame('en', $form->getLanguage());
        $this->assertSame('Alice Admin', $form->getName());
        $this->assertNull($form->getUser());
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

        $this->assertSame('', $form->getAddress1());
        $this->assertSame('', $form->getCity());
        $this->assertSame('', $form->getCountry());
        $this->assertNull($form->getGln());
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

        $this->assertIsInt($form->getUserId());
        $this->assertIsInt($form->getType());
        $this->assertIsBool($form->getActive());
        $this->assertIsString($form->getLanguage());
        $this->assertIsString($form->getName());
    }
}
