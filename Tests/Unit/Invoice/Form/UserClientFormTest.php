<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Invoice\UserClient\UserClientForm;
use PHPUnit\Framework\TestCase;

class UserClientFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new UserClientForm();

        $this->assertNull($form->getUserId());
        $this->assertNull($form->getClientId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new UserClientForm())->getFormName());
    }

    public function testShowPopulatesUserIdAndClientId(): void
    {
        $entity = new UserClient();
        $entity->setUserId(7);
        $entity->setClientId(42);

        $form = UserClientForm::show($entity);

        $this->assertSame(7, $form->getUserId());
        $this->assertSame(42, $form->getClientId());
    }

    public function testShowSetsUserAllClientsToZeroString(): void
    {
        $entity = new UserClient();
        $entity->setUserId(1);
        $entity->setClientId(1);

        // user_all_clients is always hardcoded to '0' in show()
        $form = UserClientForm::show($entity);

        $this->assertSame(1, $form->getUserId());
        $this->assertSame(1, $form->getClientId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new UserClient();
        $entity->setUserId(2);
        $entity->setClientId(3);

        $this->assertNotSame(
            UserClientForm::show($entity),
            UserClientForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new UserClient();
        $entity->setUserId(10);
        $entity->setClientId(20);

        $form = UserClientForm::show($entity);

        $this->assertIsInt($form->getUserId());
        $this->assertIsInt($form->getClientId());
    }
}
