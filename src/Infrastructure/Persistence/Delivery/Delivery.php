<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Delivery;

use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;
use App\Infrastructure\Persistence\Delivery\Trait\DeliveryTrait1;
use App\Infrastructure\Persistence\Delivery\Trait\DeliveryTrait2;

#[Entity(repository: \App\Invoice\Delivery\DeliveryRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class Delivery
{
    use RequireId;
    use DeliveryTrait1;
    use DeliveryTrait2;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_created;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_modified;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $start_date;

    #[Column(type: 'datetime', nullable: true)]
    private ?DateTimeImmutable $actual_delivery_date;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $end_date;

    #[BelongsTo(target: DeliveryLocation::class, nullable: true, fkAction: 'NO ACTION')]
    private ?DeliveryLocation $delivery_location = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        // nullable
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $inv_item_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $delivery_location_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $delivery_party_id = null,
    ) {
        $this->actual_delivery_date = new DateTimeImmutable();
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable();
        $this->start_date = new DateTimeImmutable(date('Y-m-01'));
        $this->end_date = new DateTimeImmutable(date('Y-m-t'));
    }
}
