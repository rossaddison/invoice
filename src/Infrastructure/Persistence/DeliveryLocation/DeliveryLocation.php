<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\DeliveryLocation;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\DeliveryLocation\DeliveryLocationRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;
use App\Infrastructure\Persistence\DeliveryLocation\Trait\DeliveryLocationTrait1;
use App\Infrastructure\Persistence\DeliveryLocation\Trait\DeliveryLocationTrait2;

#[Entity(repository: DeliveryLocationRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class DeliveryLocation
{
    use RequireId;
    use DeliveryLocationTrait1;
    use DeliveryLocationTrait2;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'integer', nullable: false)]
    private int $client_id = 0;

    #[Column(type: 'string(100)', nullable: true)]
    private ?string $name = '';

    #[Column(type: 'string(100)', nullable: true)]
    private ?string $address_1 = '';

    #[Column(type: 'string(100)', nullable: true)]
    private ?string $address_2 = '';

    #[Column(type: 'string(10)', nullable: true)]
    private ?string $building_number = '';

    #[Column(type: 'string(100)', nullable: true)]
    private ?string $city = '';

    #[Column(type: 'string(30)', nullable: true)]
    private ?string $state = '';

    #[Column(type: 'string(10)', nullable: true)]
    private ?string $zip = '';

    #[Column(type: 'string(30)', nullable: true)]
    private ?string $country = '';

    #[Column(type: 'string(50)', nullable: true)]
    private ?string $global_location_number = '';

    #[Column(type: 'string(10)', nullable: true)]
    private ?string $electronic_address_scheme = '';

    #[Column(type: 'datetime')]
    private DateTimeImmutable $date_created;

    #[Column(type: 'datetime')]
    private DateTimeImmutable $date_modified;

    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    public function __construct(
    )
    {
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable();
    }
}
