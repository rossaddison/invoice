<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\ClientPeppol;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\ClientPeppol\ClientPeppolService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ClientPeppolService: saveClientPeppol's client relation persistence
 * and the full Peppol endpoint/identification/legal-entity/tax-scheme field
 * assignment, and deleteClientPeppol.
 */
#[Test]
final class ClientPeppolServiceTest
{
    private function makeService(
        ?ClientPeppolRepository $repository = null,
        ?CR $cR = null,
    ): ClientPeppolService {
        /** @var ClientPeppolRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(ClientPeppolRepository::class);
        /** @var CR&m\MockInterface $cR */
        $cR = $cR ?? m::mock(CR::class);
        return new ClientPeppolService($repository, $cR);
    }

    public function saveClientPeppolSetsClientRelationAndAllFieldsAndSaves(): void
    {
        $model = new ClientPeppol();
        $array = [
            'id' => 7,
            'client_id' => 2,
            'accounting_cost' => 'AC-1',
            'buyer_reference' => 'BR-1',
            'endpointid' => '9908:123456789',
            'endpointid_schemeid' => '9908',
            'financial_institution_branchid' => 'FIB-1',
            'identificationid' => 'ID-1',
            'identificationid_schemeid' => '9908',
            'supplier_assigned_accountid' => 'SUP-1',
            'legal_entity_companyid' => 'COMP-1',
            'legal_entity_companyid_schemeid' => '9908',
            'legal_entity_company_legal_form' => 'Ltd',
            'legal_entity_registration_name' => 'Acme Ltd',
            'taxschemecompanyid' => 'TAX-1',
            'taxschemeid' => 'VAT',
        ];

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $e = $cR->expects('repoClientquery');
        $e->once()->with(2)->andReturn($client);

        /** @var ClientPeppolRepository&m\MockInterface $repository */
        $repository = m::mock(ClientPeppolRepository::class);
        $e2 = $repository->expects('save');
        $e2->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveClientPeppol($model, $array);

        Assert::same($client, $model->getClient());
        Assert::same(7, $model->reqId());
        Assert::same(2, $model->reqClientId());
        Assert::same('AC-1', $model->getAccountingCost());
        Assert::same('BR-1', $model->getBuyerReference());
        Assert::same('9908:123456789', $model->getEndpointid());
        Assert::same('9908', $model->getEndpointidSchemeid());
        Assert::same('FIB-1', $model->getFinancialInstitutionBranchid());
        Assert::same('ID-1', $model->getIdentificationid());
        Assert::same('9908', $model->getIdentificationidSchemeid());
        Assert::same('SUP-1', $model->getSupplierAssignedAccountId());
        Assert::same('COMP-1', $model->getLegalEntityCompanyid());
        Assert::same('9908', $model->getLegalEntityCompanyidSchemeid());
        Assert::same('Ltd', $model->getLegalEntityCompanyLegalForm());
        Assert::same('Acme Ltd', $model->getLegalEntityRegistrationName());
        Assert::same('TAX-1', $model->getTaxschemecompanyid());
        Assert::same('VAT', $model->getTaxschemeid());
    }

    public function saveClientPeppolSkipsClientRelationWhenIdMissing(): void
    {
        $model = new ClientPeppol();

        /** @var CR&m\MockInterface $cR */
        $cR = m::mock(CR::class);
        $cR->shouldNotReceive('repoClientquery');

        /** @var ClientPeppolRepository&m\MockInterface $repository */
        $repository = m::mock(ClientPeppolRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository, $cR);
        $service->saveClientPeppol($model, []);

        Assert::null($model->getClient());
    }

    public function deleteClientPeppolCallsRepositoryDelete(): void
    {
        $model = new ClientPeppol();

        /** @var ClientPeppolRepository&m\MockInterface $repository */
        $repository = m::mock(ClientPeppolRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteClientPeppol($model);
    }
}
