<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ClientPeppol;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Infrastructure\Persistence\ClientPeppol\Trait\ClientPeppolTrait1;
use App\Infrastructure\Persistence\ClientPeppol\Trait\ClientPeppolTrait2;
use App\Infrastructure\Persistence\ClientPeppol\Trait\ClientPeppolTrait3;

#[Entity(repository: ClientPeppolRepository::class)]
class ClientPeppol
{
    use RequireId;
    use ClientPeppolTrait1;
    use ClientPeppolTrait2;
    use ClientPeppolTrait3;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $client_id = null,
        #[Column(type: 'string(100)', nullable: false)]
        private string $endpointid = '',
        #[Column(type: 'string(4)', nullable: false)]
        private string $endpointid_schemeid = '',
        #[Column(type: 'string(100)', nullable: false)]
        private string $identificationid = '',
        #[Column(type: 'string(4)', nullable: false)]
        private string $identificationid_schemeid = '',
        #[Column(type: 'string(100)', nullable: false)]
        private string $taxschemecompanyid = '',
        #[Column(type: 'string(7)', nullable: false)]
        private string $taxschemeid = '',
        #[Column(type: 'string(100)', nullable: false)]
        private string $legal_entity_registration_name = '',
        #[Column(type: 'string(100)', nullable: false)]
        private string $legal_entity_companyid = '',
        #[Column(type: 'string(5)', nullable: false)]
        private string $legal_entity_companyid_schemeid = '',
        #[Column(type: 'string(50)', nullable: false)]
        private string $legal_entity_company_legal_form = '',
        #[Column(type: 'string(20)', nullable: false)]
        private string $financial_institution_branchid = '',
        #[Column(type: 'string(30)', nullable: false)]
        private string $accounting_cost = '',
        #[Column(type: 'string(20)', nullable: false)]
        private string $supplier_assigned_accountid = '',
        #[Column(type: 'string(20)', nullable: false)]
        private string $buyer_reference = '',
    ) {
    }
}
