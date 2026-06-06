<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use App\Invoice\CompanyPrivate\CompanyPrivateForm;
use PHPUnit\Framework\TestCase;

class CompanyPrivateFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new CompanyPrivateForm();

        $this->assertNull($form->getCompanyId());
        $this->assertSame('', $form->getVatId());
        $this->assertSame('', $form->getIban());
        $this->assertSame('', $form->getBacsSortCode());
        $this->assertSame('', $form->getBacsAccountNumber());
        $this->assertSame('', $form->getLogoFilename());
        $this->assertSame('', $form->getLogoWidth());
        $this->assertSame('', $form->getStartDate());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new CompanyPrivateForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new CompanyPrivate();
        $entity->setCompanyId(2);
        $entity->setVatId('GB123456789');
        $entity->setTaxCode('UTR12345678');
        $entity->setIban('GB29NWBK60161331926819');
        $entity->setBacsSortCode('20-00-00');
        $entity->setBacsAccountNumber('12345678');
        $entity->setGln('5790000435951');
        $entity->setRcc('5790000');
        $entity->setLogoFilename('logo.png');

        $form = CompanyPrivateForm::show($entity);

        $this->assertSame(2, $form->getCompanyId());
        $this->assertSame('GB123456789', $form->getVatId());
        $this->assertSame('GB29NWBK60161331926819', $form->getIban());
        $this->assertSame('20-00-00', $form->getBacsSortCode());
        $this->assertSame('12345678', $form->getBacsAccountNumber());
        $this->assertSame('logo.png', $form->getLogoFilename());
        // logo_width/height/margin are null in entity, cast to '' by show()
        $this->assertSame('', $form->getLogoWidth());
        $this->assertSame('', $form->getLogoHeight());
        $this->assertSame('', $form->getLogoMargin());
        // company relation not loaded, so company_public_name is null
        $this->assertNull($form->getStartDate());
        $this->assertNull($form->getEndDate());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new CompanyPrivate();
        $entity->setCompanyId(1);

        $this->assertNotSame(
            CompanyPrivateForm::show($entity),
            CompanyPrivateForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new CompanyPrivate();
        $entity->setCompanyId(3);
        $entity->setVatId('GB987654321');

        $form = CompanyPrivateForm::show($entity);

        $this->assertIsInt($form->getCompanyId());
        $this->assertIsString($form->getVatId());
        $this->assertIsString($form->getLogoWidth());
    }
}
