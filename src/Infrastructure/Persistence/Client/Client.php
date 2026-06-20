<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Client;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Invoice\Client\ClientRepository;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;
use App\Infrastructure\Persistence\Client\Trait\ClientTrait1;
use App\Infrastructure\Persistence\Client\Trait\ClientTrait2;
use App\Infrastructure\Persistence\Client\Trait\ClientTrait3;
use App\Infrastructure\Persistence\Client\Trait\ClientTrait4;
use App\Infrastructure\Persistence\Client\Trait\ClientTrait5;

#[Entity(repository: ClientRepository::class)]
#[Behavior\CreatedAt(field: 'client_date_created', column: 'client_date_created')]
#[Behavior\UpdatedAt(field: 'client_date_modified', column: 'client_date_modified')]
#[Behavior\Hook(
    callable: [self::class, 'syncFullName'],
    events: [
        \Cycle\ORM\Entity\Behavior\Event\Mapper\Command\OnCreate::class,
        \Cycle\ORM\Entity\Behavior\Event\Mapper\Command\OnUpdate::class,
    ]
)]
// Filter toolbar and join targets from inv/quote/salesorder indexes
#[Index(columns: ['client_active'])]
#[Index(columns: ['client_name'])]
#[Index(columns: ['client_surname'])]
#[Index(columns: ['client_group'])]
// Nullable FK
#[Index(columns: ['postaladdress_id'])]
class Client
{
    use RequireId;
    use ClientTrait1;
    use ClientTrait2;
    use ClientTrait3;
    use ClientTrait4;
    use ClientTrait5;

    #[Column(type: 'primary')]
    private ?int $id = null;
 
    #[Column(type: 'datetime')]
    private DateTimeImmutable $client_date_created;

    #[Column(type: 'datetime')]
    private DateTimeImmutable $client_date_modified;

    #[Column(type: 'string(151)', nullable: true)]
    private ?string $client_full_name = '';

    /**
     * @var ArrayCollection<array-key, DeliveryLocation>
     */
    #[HasMany(target: DeliveryLocation::class)]
    private readonly ArrayCollection $delivery_locations;

    /**
     * @var ArrayCollection<array-key, Inv>
     */
    #[HasMany(target: Inv::class)]
    private ArrayCollection $invs;

    /**
     * @var ArrayCollection<array-key, ProductClient>
     */
    #[HasMany(target: ProductClient::class)]
    private ArrayCollection $product_associations;

    public function __construct(
        #[Column(type: 'string(254)', nullable: true)]
        private string $client_email = '',
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $client_mobile = '',
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $client_title = '',
        // treat as firstname
        #[Column(type: 'string(50)')]
        private string $client_name = '',
        #[Column(type: 'string(151)', nullable: true)]
        private ?string $client_surname = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $client_group = '',
        #[Column(type: 'string(15)', nullable: true)]
        private ?string $client_frequency = '',
        #[Column(type: 'string(12)', nullable: true)]
        private ?string $client_number = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_address_1 = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_address_2 = '',
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $client_building_number = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_city = '',
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $client_state = '',
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $client_zip = '',
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $client_country = '',
        #[Column(type: 'string(30)', nullable: true)]
        private ?string $client_phone = '',
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $client_fax = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $client_web = '',
        #[Column(type: 'string(30)', nullable: false)]
        private string $client_vat_id = '',
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $client_tax_code = '',
        #[Column(type: 'string(151)', nullable: true)]
        private ?string $client_language = '',
        #[Column(type: 'bool', default: false)]
        private bool $client_active = false,
        #[Column(type: 'date', nullable: true)]
        private DateTimeImmutable|string|null $client_birthdate = null,
        #[Column(type: 'integer', nullable: false, default: 0)]
        private int $client_age = 0,
        #[Column(type: 'tinyInteger(4)', nullable: false, default: 0)]
        private int $client_gender = 0,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $postaladdress_id = null,
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $client_telegram_chat_id = null,
    ) {
        $this->client_full_name = ltrim(rtrim($this->client_name
                . ' '
                . ($this->client_surname ?? 'surname_unknown')));
        $this->client_date_created = new DateTimeImmutable();
        $this->client_date_modified = new DateTimeImmutable();
        $this->delivery_locations = new ArrayCollection();
        $this->invs = new ArrayCollection();
        $this->product_associations = new ArrayCollection();
    }
}
