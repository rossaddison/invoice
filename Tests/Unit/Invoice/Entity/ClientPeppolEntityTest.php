<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Entity\ClientPeppol;
use PHPUnit\Framework\TestCase;

class ClientPeppolEntityTest extends TestCase
{
    public string $sequenceOfNumbers = '1234567890123';
    
    public string $oneToNine = '123456789';
    
    public string $oneToEight = '12345678';
    
    public function testConstructorWithDefaults(): void
    {
        $clientPeppol = new ClientPeppol();
        
        $this->assertSame('', $clientPeppol->getId());
        $this->assertSame('', $clientPeppol->getClientId());
        $this->assertSame('', $clientPeppol->getEndpointid());
        $this->assertSame('', $clientPeppol->getEndpointidSchemeid());
        $this->assertSame('', $clientPeppol->getIdentificationid());
        $this->assertSame('', $clientPeppol->getIdentificationidSchemeid());
        $this->assertSame('', $clientPeppol->getTaxschemecompanyid());
        $this->assertSame('', $clientPeppol->getTaxschemeid());
        $this->assertSame('', $clientPeppol->getLegalEntityRegistrationName());
        $this->assertSame('', $clientPeppol->getLegalEntityCompanyid());
        $this->assertSame('', $clientPeppol->getLegalEntityCompanyidSchemeid());
        $this->assertSame('', $clientPeppol->getLegalEntityCompanyLegalForm());
        $this->assertSame('', $clientPeppol->getFinancialInstitutionBranchid());
        $this->assertSame('', $clientPeppol->getAccountingCost());
        $this->assertSame('', $clientPeppol->getSupplierAssignedAccountId());
        $this->assertSame('', $clientPeppol->getBuyerReference());
        $this->assertNull($clientPeppol->getClient());
    }

    public function testConstructorWithAllParameters(): void
    {
        $clientPeppol = new ClientPeppol(
            id: 1,
            client_id: 100,
            endpointid: $this->sequenceOfNumbers,
            endpointid_schemeid: '0088',
            identificationid: 'COMPANY123',
            identificationid_schemeid: '0002',
            taxschemecompanyid: 'VAT123456',
            taxschemeid: 'VAT',
            legal_entity_registration_name: 'Test Company Ltd',
            legal_entity_companyid: 'REG123456',
            legal_entity_companyid_schemeid: '0002',
            legal_entity_company_legal_form: 'LTD',
            financial_institution_branchid: 'BRANCH123',
            accounting_cost: 'COST001',
            supplier_assigned_accountid: 'SUPP001',
            buyer_reference: 'REF001'
        );
        
        $this->assertSame('1', $clientPeppol->getId());
        $this->assertSame('100', $clientPeppol->getClientId());
        $this->assertSame($this->sequenceOfNumbers, $clientPeppol->getEndpointid());
        $this->assertSame('0088', $clientPeppol->getEndpointidSchemeid());
        $this->assertSame('COMPANY123', $clientPeppol->getIdentificationid());
        $this->assertSame('0002', $clientPeppol->getIdentificationidSchemeid());
        $this->assertSame('VAT123456', $clientPeppol->getTaxschemecompanyid());
        $this->assertSame('VAT', $clientPeppol->getTaxschemeid());
        $this->assertSame('Test Company Ltd', $clientPeppol->getLegalEntityRegistrationName());
        $this->assertSame('REG123456', $clientPeppol->getLegalEntityCompanyid());
        $this->assertSame('0002', $clientPeppol->getLegalEntityCompanyidSchemeid());
        $this->assertSame('LTD', $clientPeppol->getLegalEntityCompanyLegalForm());
        $this->assertSame('BRANCH123', $clientPeppol->getFinancialInstitutionBranchid());
        $this->assertSame('COST001', $clientPeppol->getAccountingCost());
        $this->assertSame('SUPP001', $clientPeppol->getSupplierAssignedAccountId());
        $this->assertSame('REF001', $clientPeppol->getBuyerReference());
    }

    public function testIdSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setId(50);
        
        $this->assertSame('50', $clientPeppol->getId());
    }

    public function testClientIdSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setClientId(200);
        
        $this->assertSame('200', $clientPeppol->getClientId());
    }

    public function testEndpointidSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setEndpointid('9876543210987');
        
        $this->assertSame('9876543210987', $clientPeppol->getEndpointid());
    }

    public function testEndpointidSchemeidSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setEndpointidSchemeid('0192');
        
        $this->assertSame('0192', $clientPeppol->getEndpointidSchemeid());
    }

    public function testIdentificationidSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setIdentificationid('NEWCOMPANY456');
        
        $this->assertSame('NEWCOMPANY456', $clientPeppol->getIdentificationid());
    }

    public function testIdentificationidSchemeidSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setIdentificationidSchemeid('0007');
        
        $this->assertSame('0007', $clientPeppol->getIdentificationidSchemeid());
    }

    public function testTaxschemecompanyidSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setTaxschemecompanyid('GB987654321');
        
        $this->assertSame('GB987654321', $clientPeppol->getTaxschemecompanyid());
    }

    public function testTaxschemeidSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setTaxschemeid('GST');
        
        $this->assertSame('GST', $clientPeppol->getTaxschemeid());
    }

    public function testLegalEntityRegistrationNameSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setLegalEntityRegistrationName('New Business Corporation');
        
        $this->assertSame('New Business Corporation', $clientPeppol->getLegalEntityRegistrationName());
    }

    public function testLegalEntityCompanyidSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setLegalEntityCompanyid('NEWREG789');
        
        $this->assertSame('NEWREG789', $clientPeppol->getLegalEntityCompanyid());
    }

    public function testLegalEntityCompanyidSchemeidSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setLegalEntityCompanyidSchemeid('0009');
        
        $this->assertSame('0009', $clientPeppol->getLegalEntityCompanyidSchemeid());
    }

    public function testLegalEntityCompanyLegalFormSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setLegalEntityCompanyLegalForm('PLC');
        
        $this->assertSame('PLC', $clientPeppol->getLegalEntityCompanyLegalForm());
    }

    public function testFinancialInstitutionBranchidSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setFinancialInstitutionBranchid('NEWBRANCH456');
        
        $this->assertSame('NEWBRANCH456', $clientPeppol->getFinancialInstitutionBranchid());
    }

    public function testAccountingCostSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setAccountingCost('NEWCOST789');
        
        $this->assertSame('NEWCOST789', $clientPeppol->getAccountingCost());
    }

    public function testSupplierAssignedAccountIdSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setSupplierAssignedAccountId('NEWSUPPLIER123');
        
        $this->assertSame('NEWSUPPLIER123', $clientPeppol->getSupplierAssignedAccountId());
    }

    public function testBuyerReferenceSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setBuyerReference('NEWREF456');
        
        $this->assertSame('NEWREF456', $clientPeppol->getBuyerReference());
    }

    public function testClientRelationshipSetterAndGetter(): void
    {
        $clientPeppol = new ClientPeppol();
        $client = $this->createMock(Client::class);
        
        $clientPeppol->setClient($client);
        $this->assertSame($client, $clientPeppol->getClient());
        
        $clientPeppol->setClient(null);
        $this->assertNull($clientPeppol->getClient());
    }

    public function testIdTypeConversion(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setId(999);
        
        $this->assertIsString($clientPeppol->getId());
        $this->assertSame('999', $clientPeppol->getId());
    }

    public function testClientIdTypeConversion(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setClientId(777);
        
        $this->assertIsString($clientPeppol->getClientId());
        $this->assertSame('777', $clientPeppol->getClientId());
    }

    public function testZeroIds(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setId(0);
        $clientPeppol->setClientId(0);
        
        $this->assertSame('0', $clientPeppol->getId());
        $this->assertSame('0', $clientPeppol->getClientId());
    }

    public function testNegativeIds(): void
    {
        $clientPeppol = new ClientPeppol();
        $clientPeppol->setId(-1);
        $clientPeppol->setClientId(-5);
        
        $this->assertSame('-1', $clientPeppol->getId());
        $this->assertSame('-5', $clientPeppol->getClientId());
    }

    public function testLargeIds(): void
    {
        $clientPeppol = new ClientPeppol();
        $largeId = PHP_INT_MAX;
        
        $clientPeppol->setId($largeId);
        $clientPeppol->setClientId($largeId - 1);
        
        $this->assertSame((string)$largeId, $clientPeppol->getId());
        $this->assertSame((string)($largeId - 1), $clientPeppol->getClientId());
    }

    public function testEmptyStringFields(): void
    {
        $clientPeppol = new ClientPeppol();
        
        // All string fields should accept empty strings
        $clientPeppol->setEndpointid('');
        $clientPeppol->setEndpointidSchemeid('');
        $clientPeppol->setIdentificationid('');
        $clientPeppol->setIdentificationidSchemeid('');
        $clientPeppol->setTaxschemecompanyid('');
        $clientPeppol->setTaxschemeid('');
        $clientPeppol->setLegalEntityRegistrationName('');
        $clientPeppol->setLegalEntityCompanyid('');
        $clientPeppol->setLegalEntityCompanyidSchemeid('');
        $clientPeppol->setLegalEntityCompanyLegalForm('');
        $clientPeppol->setFinancialInstitutionBranchid('');
        $clientPeppol->setAccountingCost('');
        $clientPeppol->setSupplierAssignedAccountId('');
        $clientPeppol->setBuyerReference('');
        
        $this->assertSame('', $clientPeppol->getEndpointid());
        $this->assertSame('', $clientPeppol->getEndpointidSchemeid());
        $this->assertSame('', $clientPeppol->getIdentificationid());
        $this->assertSame('', $clientPeppol->getIdentificationidSchemeid());
        $this->assertSame('', $clientPeppol->getTaxschemecompanyid());
        $this->assertSame('', $clientPeppol->getTaxschemeid());
        $this->assertSame('', $clientPeppol->getLegalEntityRegistrationName());
        $this->assertSame('', $clientPeppol->getLegalEntityCompanyid());
        $this->assertSame('', $clientPeppol->getLegalEntityCompanyidSchemeid());
        $this->assertSame('', $clientPeppol->getLegalEntityCompanyLegalForm());
        $this->assertSame('', $clientPeppol->getFinancialInstitutionBranchid());
        $this->assertSame('', $clientPeppol->getAccountingCost());
        $this->assertSame('', $clientPeppol->getSupplierAssignedAccountId());
        $this->assertSame('', $clientPeppol->getBuyerReference());
    }

    public function testPeppolEndpointScenarios(): void
    {
        $clientPeppol = new ClientPeppol();
        
        // GLN endpoint
        $clientPeppol->setEndpointid($this->sequenceOfNumbers);
        $clientPeppol->setEndpointidSchemeid('0088');
        $this->assertSame($this->sequenceOfNumbers, $clientPeppol->getEndpointid());
        $this->assertSame('0088', $clientPeppol->getEndpointidSchemeid());
        
        // DUNS endpoint
        $clientPeppol->setEndpointid($this->oneToNine);
        $clientPeppol->setEndpointidSchemeid('0060');
        $this->assertSame($this->oneToNine, $clientPeppol->getEndpointid());
        $this->assertSame('0060', $clientPeppol->getEndpointidSchemeid());
        
        // IT IPA Code
        $clientPeppol->setEndpointid('ABCDEF');
        $clientPeppol->setEndpointidSchemeid('0201');
        $this->assertSame('ABCDEF', $clientPeppol->getEndpointid());
        $this->assertSame('0201', $clientPeppol->getEndpointidSchemeid());
    }

    public function testPeppolIdentificationScenarios(): void
    {
        $clientPeppol = new ClientPeppol();
        
        // UK Companies House
        $clientPeppol->setIdentificationid($this->oneToEight);
        $clientPeppol->setIdentificationidSchemeid('0002');
        $this->assertSame($this->oneToEight, $clientPeppol->getIdentificationid());
        $this->assertSame('0002', $clientPeppol->getIdentificationidSchemeid());
        
        // DUNS
        $clientPeppol->setIdentificationid('987654321');
        $clientPeppol->setIdentificationidSchemeid('0060');
        $this->assertSame('987654321', $clientPeppol->getIdentificationid());
        $this->assertSame('0060', $clientPeppol->getIdentificationidSchemeid());
        
        // German Handelsregister
        $clientPeppol->setIdentificationid('HRB123456');
        $clientPeppol->setIdentificationidSchemeid('0204');
        $this->assertSame('HRB123456', $clientPeppol->getIdentificationid());
        $this->assertSame('0204', $clientPeppol->getIdentificationidSchemeid());
    }

    public function testTaxSchemeScenarios(): void
    {
        $clientPeppol = new ClientPeppol();
        
        // UK VAT
        $clientPeppol->setTaxschemecompanyid('GB123456789');
        $clientPeppol->setTaxschemeid('VAT');
        $this->assertSame('GB123456789', $clientPeppol->getTaxschemecompanyid());
        $this->assertSame('VAT', $clientPeppol->getTaxschemeid());
        
        // German VAT
        $clientPeppol->setTaxschemecompanyid('DE987654321');
        $clientPeppol->setTaxschemeid('VAT');
        $this->assertSame('DE987654321', $clientPeppol->getTaxschemecompanyid());
        $this->assertSame('VAT', $clientPeppol->getTaxschemeid());
        
        // US Sales Tax
        $clientPeppol->setTaxschemecompanyid($this->oneToNine);
        $clientPeppol->setTaxschemeid('GST');
        $this->assertSame($this->oneToNine, $clientPeppol->getTaxschemecompanyid());
        $this->assertSame('GST', $clientPeppol->getTaxschemeid());
    }

    public function testLegalEntityScenarios(): void
    {
        $clientPeppol = new ClientPeppol();
        
        // UK Limited Company
        $clientPeppol->setLegalEntityRegistrationName('Example Limited');
        $clientPeppol->setLegalEntityCompanyid($this->oneToEight);
        $clientPeppol->setLegalEntityCompanyidSchemeid('0002');
        $clientPeppol->setLegalEntityCompanyLegalForm('Ltd');
        
        $this->assertSame('Example Limited', $clientPeppol->getLegalEntityRegistrationName());
        $this->assertSame($this->oneToEight, $clientPeppol->getLegalEntityCompanyid());
        $this->assertSame('0002', $clientPeppol->getLegalEntityCompanyidSchemeid());
        $this->assertSame('Ltd', $clientPeppol->getLegalEntityCompanyLegalForm());
        
        // German GmbH
        $clientPeppol->setLegalEntityRegistrationName('Beispiel GmbH');
        $clientPeppol->setLegalEntityCompanyid('HRB987654');
        $clientPeppol->setLegalEntityCompanyidSchemeid('0204');
        $clientPeppol->setLegalEntityCompanyLegalForm('GmbH');
        
        $this->assertSame('Beispiel GmbH', $clientPeppol->getLegalEntityRegistrationName());
        $this->assertSame('HRB987654', $clientPeppol->getLegalEntityCompanyid());
        $this->assertSame('0204', $clientPeppol->getLegalEntityCompanyidSchemeid());
        $this->assertSame('GmbH', $clientPeppol->getLegalEntityCompanyLegalForm());
    }

    public function testSpecialCharactersInFields(): void
    {
        $clientPeppol = new ClientPeppol();
        
        $clientPeppol->setLegalEntityRegistrationName('Société Française & Co.');
        $clientPeppol->setAccountingCost('COST-123_ABC');
        $clientPeppol->setBuyerReference('PO#2024-001');
        
        $this->assertSame('Société Française & Co.', $clientPeppol->getLegalEntityRegistrationName());
        $this->assertSame('COST-123_ABC', $clientPeppol->getAccountingCost());
        $this->assertSame('PO#2024-001', $clientPeppol->getBuyerReference());
    }

    public function testUnicodeInFields(): void
    {
        $clientPeppol = new ClientPeppol();
        
        $clientPeppol->setLegalEntityRegistrationName('中文公司名称');
        $clientPeppol->setAccountingCost('成本代码123');
        $clientPeppol->setBuyerReference('采购参考456');
        
        $this->assertSame('中文公司名称', $clientPeppol->getLegalEntityRegistrationName());
        $this->assertSame('成本代码123', $clientPeppol->getAccountingCost());
        $this->assertSame('采购参考456', $clientPeppol->getBuyerReference());
    }

    public function testCompleteClientPeppolSetup(): void
    {
        $clientPeppol = new ClientPeppol();
        $client = $this->createMock(Client::class);
        
        $clientPeppol->setId(1);
        $clientPeppol->setClientId(100);
        $clientPeppol->setClient($client);
        $clientPeppol->setEndpointid($this->sequenceOfNumbers);
        $clientPeppol->setEndpointidSchemeid('0088');
        $clientPeppol->setIdentificationid('COMPANY123');
        $clientPeppol->setIdentificationidSchemeid('0002');
        $clientPeppol->setTaxschemecompanyid('GB123456789');
        $clientPeppol->setTaxschemeid('VAT');
        $clientPeppol->setLegalEntityRegistrationName('Complete Test Company Ltd');
        $clientPeppol->setLegalEntityCompanyid('REG123456');
        $clientPeppol->setLegalEntityCompanyidSchemeid('0002');
        $clientPeppol->setLegalEntityCompanyLegalForm('Ltd');
        $clientPeppol->setFinancialInstitutionBranchid('BRANCH123');
        $clientPeppol->setAccountingCost('COST001');
        $clientPeppol->setSupplierAssignedAccountId('SUPP001');
        $clientPeppol->setBuyerReference('REF001');
        
        $this->assertSame('1', $clientPeppol->getId());
        $this->assertSame('100', $clientPeppol->getClientId());
        $this->assertSame($client, $clientPeppol->getClient());
        $this->assertSame($this->sequenceOfNumbers, $clientPeppol->getEndpointid());
        $this->assertSame('0088', $clientPeppol->getEndpointidSchemeid());
        $this->assertSame('COMPANY123', $clientPeppol->getIdentificationid());
        $this->assertSame('0002', $clientPeppol->getIdentificationidSchemeid());
        $this->assertSame('GB123456789', $clientPeppol->getTaxschemecompanyid());
        $this->assertSame('VAT', $clientPeppol->getTaxschemeid());
        $this->assertSame('Complete Test Company Ltd', $clientPeppol->getLegalEntityRegistrationName());
        $this->assertSame('REG123456', $clientPeppol->getLegalEntityCompanyid());
        $this->assertSame('0002', $clientPeppol->getLegalEntityCompanyidSchemeid());
        $this->assertSame('Ltd', $clientPeppol->getLegalEntityCompanyLegalForm());
        $this->assertSame('BRANCH123', $clientPeppol->getFinancialInstitutionBranchid());
        $this->assertSame('COST001', $clientPeppol->getAccountingCost());
        $this->assertSame('SUPP001', $clientPeppol->getSupplierAssignedAccountId());
        $this->assertSame('REF001', $clientPeppol->getBuyerReference());
    }

    public function testMethodReturnTypes(): void
    {
        $clientPeppol = new ClientPeppol(
            id: 1,
            client_id: 100,
            endpointid: $this->sequenceOfNumbers,
            endpointid_schemeid: '0088',
            identificationid: 'COMPANY123',
            identificationid_schemeid: '0002',
            taxschemecompanyid: 'VAT123456',
            taxschemeid: 'VAT',
            legal_entity_registration_name: 'Test Company',
            legal_entity_companyid: 'REG123',
            legal_entity_companyid_schemeid: '0002',
            legal_entity_company_legal_form: 'Ltd',
            financial_institution_branchid: 'BRANCH123',
            accounting_cost: 'COST001',
            supplier_assigned_accountid: 'SUPP001',
            buyer_reference: 'REF001'
        );
        
        $this->assertIsString($clientPeppol->getId());
        $this->assertIsString($clientPeppol->getClientId());
        $this->assertIsString($clientPeppol->getEndpointid());
        $this->assertIsString($clientPeppol->getEndpointidSchemeid());
        $this->assertIsString($clientPeppol->getIdentificationid());
        $this->assertIsString($clientPeppol->getIdentificationidSchemeid());
        $this->assertIsString($clientPeppol->getTaxschemecompanyid());
        $this->assertIsString($clientPeppol->getTaxschemeid());
        $this->assertIsString($clientPeppol->getLegalEntityRegistrationName());
        $this->assertIsString($clientPeppol->getLegalEntityCompanyid());
        $this->assertIsString($clientPeppol->getLegalEntityCompanyidSchemeid());
        $this->assertIsString($clientPeppol->getLegalEntityCompanyLegalForm());
        $this->assertIsString($clientPeppol->getFinancialInstitutionBranchid());
        $this->assertIsString($clientPeppol->getAccountingCost());
        $this->assertIsString($clientPeppol->getSupplierAssignedAccountId());
        $this->assertIsString($clientPeppol->getBuyerReference());
        $this->assertNull($clientPeppol->getClient());
    }

    public function testClientRelationshipWorkflow(): void
    {
        $clientPeppol = new ClientPeppol();
        $client1 = $this->createMock(Client::class);
        $client2 = $this->createMock(Client::class);
        
        // Initially null
        $this->assertNull($clientPeppol->getClient());
        
        // Set first client
        $clientPeppol->setClientId(100);
        $clientPeppol->setClient($client1);
        $this->assertSame($client1, $clientPeppol->getClient());
        
        // Set new client
        $clientPeppol->setClientId(200);
        $clientPeppol->setClient($client2);
        $this->assertSame($client2, $clientPeppol->getClient());
    }

    public function testEntityStateConsistency(): void
    {
        $clientPeppol = new ClientPeppol(
            id: 999,
            client_id: 888,
            endpointid: 'initial_endpoint',
            endpointid_schemeid: '0001',
            identificationid: 'initial_id',
            identificationid_schemeid: '0001'
        );
        
        // Verify initial state
        $this->assertSame('999', $clientPeppol->getId());
        $this->assertSame('888', $clientPeppol->getClientId());
        $this->assertSame('initial_endpoint', $clientPeppol->getEndpointid());
        $this->assertSame('0001', $clientPeppol->getEndpointidSchemeid());
        
        // Modify and verify changes
        $clientPeppol->setId(111);
        $clientPeppol->setClientId(222);
        $clientPeppol->setEndpointid('modified_endpoint');
        $clientPeppol->setEndpointidSchemeid('0002');
        
        $this->assertSame('111', $clientPeppol->getId());
        $this->assertSame('222', $clientPeppol->getClientId());
        $this->assertSame('modified_endpoint', $clientPeppol->getEndpointid());
        $this->assertSame('0002', $clientPeppol->getEndpointidSchemeid());
    }

    public function testRealWorldPeppolExamples(): void
    {
        $clientPeppol = new ClientPeppol();
        
        // Norwegian organization
        $clientPeppol->setEndpointid('9908:123456789');
        $clientPeppol->setEndpointidSchemeid('0192');
        $clientPeppol->setTaxschemecompanyid('NO123456789MVA');
        $clientPeppol->setTaxschemeid('VAT');
        
        $this->assertSame('9908:123456789', $clientPeppol->getEndpointid());
        $this->assertSame('0192', $clientPeppol->getEndpointidSchemeid());
        $this->assertSame('NO123456789MVA', $clientPeppol->getTaxschemecompanyid());
        $this->assertSame('VAT', $clientPeppol->getTaxschemeid());
        
        // Italian organization
        $clientPeppol->setEndpointid('ABCDEF');
        $clientPeppol->setEndpointidSchemeid('0201');
        $clientPeppol->setTaxschemecompanyid('IT12345678901');
        $clientPeppol->setTaxschemeid('VAT');
        
        $this->assertSame('ABCDEF', $clientPeppol->getEndpointid());
        $this->assertSame('0201', $clientPeppol->getEndpointidSchemeid());
        $this->assertSame('IT12345678901', $clientPeppol->getTaxschemecompanyid());
        $this->assertSame('VAT', $clientPeppol->getTaxschemeid());
    }
}
