<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Company\Company;
use App\Invoice\Company\CompanyForm;
use PHPUnit\Framework\TestCase;

class CompanyFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new CompanyForm();

        $this->assertSame(0, $form->current);
        $this->assertSame('', $form->name);
        $this->assertSame('', $form->address_1);
        $this->assertSame('', $form->address_2);
        $this->assertSame('', $form->city);
        $this->assertSame('', $form->state);
        $this->assertSame('', $form->zip);
        $this->assertSame('', $form->country);
        $this->assertSame('', $form->phone);
        $this->assertSame('', $form->fax);
        $this->assertSame('', $form->email);
        $this->assertSame('', $form->web);
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new CompanyForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new Company();
        $entity->setCurrent(1);
        $entity->setName('Acme Ltd');
        $entity->setAddress1('1 High Street');
        $entity->setAddress2('Floor 2');
        $entity->setCity('London');
        $entity->setState('England');
        $entity->setZip('EC1A 1BB');
        $entity->setCountry('GB');
        $entity->setPhone('+44 20 7946 0958');
        $entity->setFax('+44 20 7946 0959');
        $entity->setEmail('info@acme.com');
        $entity->setWeb('https://acme.com');
        $entity->setSeoDescription('Professional invoicing services');
        $entity->setSlack('acme-team');
        $entity->setFacebook('acmeltd');
        $entity->setTwitter('@acmeltd');
        $entity->setLinkedIn('acme-ltd');
        $entity->setWhatsapp('+447700900000');
        $entity->setArbitrationBody('LCIA');
        $entity->setArbitrationJurisdiction('England and Wales');

        $form = CompanyForm::show($entity);

        $this->assertSame(1, $form->current);
        $this->assertSame('Acme Ltd', $form->name);
        $this->assertSame('1 High Street', $form->address_1);
        $this->assertSame('London', $form->city);
        $this->assertSame('EC1A 1BB', $form->zip);
        $this->assertSame('GB', $form->country);
        $this->assertSame('info@acme.com', $form->email);
        $this->assertSame('https://acme.com', $form->web);
        $this->assertSame('acme-ltd', $form->linkedin);
        $this->assertSame('LCIA', $form->arbitration_body);
        $this->assertSame('England and Wales', $form->arbitration_jurisdiction);
    }

    public function testShowWithMinimalEntity(): void
    {
        $entity = new Company();
        $entity->setName('Solo');
        $entity->setCurrent(0);

        $form = CompanyForm::show($entity);

        $this->assertSame('Solo', $form->name);
        $this->assertSame(0, $form->current);
        $this->assertSame('', $form->city);
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Company();
        $entity->setName('Corp');
        $entity->setCurrent(1);

        $this->assertNotSame(
            CompanyForm::show($entity),
            CompanyForm::show($entity)
        );
    }
}
