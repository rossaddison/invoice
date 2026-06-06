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

        $this->assertSame(0, $form->getCurrent());
        $this->assertSame('', $form->getName());
        $this->assertSame('', $form->getAddress1());
        $this->assertSame('', $form->getAddress2());
        $this->assertSame('', $form->getCity());
        $this->assertSame('', $form->getState());
        $this->assertSame('', $form->getZip());
        $this->assertSame('', $form->getCountry());
        $this->assertSame('', $form->getPhone());
        $this->assertSame('', $form->getFax());
        $this->assertSame('', $form->getEmail());
        $this->assertSame('', $form->getWeb());
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

        $this->assertSame(1, $form->getCurrent());
        $this->assertSame('Acme Ltd', $form->getName());
        $this->assertSame('1 High Street', $form->getAddress1());
        $this->assertSame('London', $form->getCity());
        $this->assertSame('EC1A 1BB', $form->getZip());
        $this->assertSame('GB', $form->getCountry());
        $this->assertSame('info@acme.com', $form->getEmail());
        $this->assertSame('https://acme.com', $form->getWeb());
        $this->assertSame('acme-ltd', $form->getLinkedin());
        $this->assertSame('LCIA', $form->getArbitrationBody());
        $this->assertSame('England and Wales', $form->getArbitrationJurisdiction());
    }

    public function testShowWithMinimalEntity(): void
    {
        $entity = new Company();
        $entity->setName('Solo');
        $entity->setCurrent(0);

        $form = CompanyForm::show($entity);

        $this->assertSame('Solo', $form->getName());
        $this->assertSame(0, $form->getCurrent());
        $this->assertSame('', $form->getCity());
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
