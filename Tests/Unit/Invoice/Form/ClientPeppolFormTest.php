<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Invoice\ClientPeppol\ClientPeppolForm;
use PHPUnit\Framework\TestCase;

class ClientPeppolFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new ClientPeppolForm();

        $this->assertNull($form->getClientId());
        $this->assertSame('', $form->getEndpointid());
        $this->assertSame('', $form->getEndpointidSchemeid());
        $this->assertSame('', $form->getTaxschemecompanyid());
        $this->assertSame('', $form->getTaxschemeid());
        $this->assertSame('', $form->getLegalEntityRegistrationName());
        $this->assertSame('', $form->getLegalEntityCompanyid());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ClientPeppolForm())->getFormName());
    }

    public function testShowPopulatesPeppolIdentifiers(): void
    {
        $entity = new ClientPeppol();
        $entity->setClientId(5);
        $entity->setEndpointid('GB-HMRC-VAT-123456789');
        $entity->setEndpointidSchemeid('9925');
        $entity->setIdentificationid('GB-COMPANIES-HOUSE-12345678');
        $entity->setIdentificationidSchemeid('0060');
        $entity->setTaxschemecompanyid('GB123456789');
        $entity->setTaxschemeid('VAT');
        $entity->setLegalEntityRegistrationName('Acme Ltd');
        $entity->setLegalEntityCompanyid('12345678');
        $entity->setLegalEntityCompanyidSchemeid('0060');
        $entity->setLegalEntityCompanyLegalForm('Private Limited Company');
        $entity->setFinancialInstitutionBranchid('20-00-00');
        $entity->setSupplierAssignedAccountId('ACC-001');

        $form = ClientPeppolForm::show($entity);

        $this->assertSame(5, $form->getClientId());
        $this->assertSame('GB-HMRC-VAT-123456789', $form->getEndpointid());
        $this->assertSame('9925', $form->getEndpointidSchemeid());
        $this->assertSame('GB123456789', $form->getTaxschemecompanyid());
        $this->assertSame('VAT', $form->getTaxschemeid());
        $this->assertSame('Acme Ltd', $form->getLegalEntityRegistrationName());
        $this->assertSame('12345678', $form->getLegalEntityCompanyid());
    }

    public function testShowWithBlankFields(): void
    {
        $entity = new ClientPeppol();
        $entity->setClientId(3);

        $form = ClientPeppolForm::show($entity);

        $this->assertSame(3, $form->getClientId());
        $this->assertSame('', $form->getEndpointid());
        $this->assertSame('', $form->getTaxschemeid());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new ClientPeppol();
        $entity->setClientId(1);

        $this->assertNotSame(
            ClientPeppolForm::show($entity),
            ClientPeppolForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new ClientPeppol();
        $entity->setClientId(4);

        $form = ClientPeppolForm::show($entity);

        $this->assertIsInt($form->getClientId());
        $this->assertIsString($form->getEndpointid());
    }
}
