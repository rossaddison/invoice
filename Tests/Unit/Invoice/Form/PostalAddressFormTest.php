<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\PostalAddress\PostalAddress;
use App\Invoice\PostalAddress\PostalAddressForm;
use PHPUnit\Framework\TestCase;

class PostalAddressFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new PostalAddressForm();

        $this->assertSame('', $form->getStreetName());
        $this->assertSame('', $form->getAdditionalStreetName());
        $this->assertSame('', $form->getBuildingNumber());
        $this->assertSame('', $form->getCityName());
        $this->assertSame('', $form->getPostalzone());
        $this->assertSame('', $form->getCountrysubentity());
        $this->assertSame('', $form->getCountry());
        $this->assertNull($form->getClientId());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new PostalAddressForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new PostalAddress();
        $entity->setClientId(6);
        $entity->setStreetName('King Street');
        $entity->setAdditionalStreetName('Suite 101');
        $entity->setBuildingNumber('42B');
        $entity->setCityName('Manchester');
        $entity->setPostalzone('M1 1AA');
        $entity->setCountrysubentity('England');
        $entity->setCountry('GB');

        $form = PostalAddressForm::show($entity, null);

        $this->assertSame(6, $form->getClientId());
        $this->assertSame('King Street', $form->getStreetName());
        $this->assertSame('Suite 101', $form->getAdditionalStreetName());
        $this->assertSame('42B', $form->getBuildingNumber());
        $this->assertSame('Manchester', $form->getCityName());
        $this->assertSame('M1 1AA', $form->getPostalzone());
        $this->assertSame('England', $form->getCountrysubentity());
        $this->assertSame('GB', $form->getCountry());
    }

    public function testShowFallsBackToParamClientIdWhenEntityClientIdIsNull(): void
    {
        // entity has no client_id set (null) → falls back to second param
        $entity = new PostalAddress();
        $entity->setStreetName('Main Road');
        $entity->setCityName('Leeds');
        $entity->setCountry('GB');

        $form = PostalAddressForm::show($entity, 99);

        $this->assertSame(99, $form->getClientId());
    }

    public function testShowPrefersEntityClientIdWhenPositive(): void
    {
        // entity client_id = 5 (> 0) → entity value wins
        $entity = new PostalAddress();
        $entity->setClientId(5);
        $entity->setStreetName('Oak Lane');
        $entity->setCityName('Bristol');
        $entity->setCountry('GB');

        $form = PostalAddressForm::show($entity, 99);

        $this->assertSame(5, $form->getClientId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new PostalAddress();
        $entity->setClientId(1);
        $entity->setStreetName('A');
        $entity->setCityName('B');
        $entity->setCountry('GB');

        $this->assertNotSame(
            PostalAddressForm::show($entity, null),
            PostalAddressForm::show($entity, null)
        );
    }
}
