<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Profile\Profile;
use App\Invoice\Profile\ProfileForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ProfileFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new ProfileForm();

        $this->assertNull($form->getCompanyId());
        $this->assertSame(0, $form->getCurrent());
        $this->assertSame('', $form->getMobile());
        $this->assertSame('', $form->getEmail());
        $this->assertSame('', $form->getDescription());
        $this->assertSame('', $form->getDateCreated());
        $this->assertSame('', $form->getDateModified());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ProfileForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new Profile();
        $entity->setCompanyId(2);
        $entity->setCurrent(1);
        $entity->setMobile('+44 7700 900000');
        $entity->setEmail('contact@example.com');
        $entity->setDescription('Primary billing profile');

        $form = ProfileForm::show($entity);

        $this->assertSame(2, $form->getCompanyId());
        $this->assertSame(1, $form->getCurrent());
        $this->assertSame('+44 7700 900000', $form->getMobile());
        $this->assertSame('contact@example.com', $form->getEmail());
        $this->assertSame('Primary billing profile', $form->getDescription());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateModified());
    }

    public function testShowWithEmptyContactDetails(): void
    {
        $entity = new Profile();
        $entity->setCompanyId(1);
        $entity->setCurrent(0);
        $entity->setMobile('');
        $entity->setEmail('');
        $entity->setDescription('');

        $form = ProfileForm::show($entity);

        $this->assertSame('', $form->getMobile());
        $this->assertSame('', $form->getEmail());
        $this->assertSame('', $form->getDescription());
        $this->assertSame(0, $form->getCurrent());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Profile();
        $entity->setCompanyId(1);
        $entity->setCurrent(0);
        $entity->setMobile('');
        $entity->setEmail('');
        $entity->setDescription('');

        $this->assertNotSame(
            ProfileForm::show($entity),
            ProfileForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new Profile();
        $entity->setCompanyId(3);
        $entity->setCurrent(1);
        $entity->setMobile('+44 7700 900001');
        $entity->setEmail('a@b.com');
        $entity->setDescription('desc');

        $form = ProfileForm::show($entity);

        $this->assertIsInt($form->getCompanyId());
        $this->assertIsInt($form->getCurrent());
        $this->assertIsString($form->getMobile());
        $this->assertIsString($form->getEmail());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateCreated());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getDateModified());
    }
}
