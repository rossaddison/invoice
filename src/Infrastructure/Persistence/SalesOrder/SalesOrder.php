<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SalesOrder;

use App\Infrastructure\Persistence\{
    Client\Client, SalesOrderItem\SalesOrderItem, Group\Group,
    Quote\Quote, SalesOrderAmount\SalesOrderAmount, Trait\RequireId};
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Infrastructure\Persistence\User\User;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;
use App\Infrastructure\Persistence\SalesOrder\Trait\SalesOrderTrait1;
use App\Infrastructure\Persistence\SalesOrder\Trait\SalesOrderTrait2;
use App\Infrastructure\Persistence\SalesOrder\Trait\SalesOrderTrait3;
use App\Infrastructure\Persistence\SalesOrder\Trait\SalesOrderTrait4;

#[Entity(repository: SOR::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
// Priority 1 — sort targets and heavy filters
#[Index(columns: ['status_id'])]
#[Index(columns: ['client_id'])]
#[Index(columns: ['date_created'])]
#[Index(columns: ['number'], unique: true)]
#[Index(columns: ['url_key'], unique: true)]
// Priority 2 — FK joins
#[Index(columns: ['user_id'])]
#[Index(columns: ['group_id'])]
#[Index(columns: ['quote_id'])]
// Priority 3 — nullable FK
#[Index(columns: ['inv_id'])]
class SalesOrder
{
    use RequireId;
    use SalesOrderTrait1;
    use SalesOrderTrait2;
    use SalesOrderTrait3;
    use SalesOrderTrait4;
 
    #[BelongsTo(
        target: Client::class,
        nullable: false,
        fkAction: 'NO ACTION'
    )]
    private ?Client $client = null;

    #[BelongsTo(
        target: Group::class,
        nullable: false,
        fkAction: 'NO ACTION'
    )]
    private ?Group $group = null;

    #[BelongsTo(target: User::class, nullable: false)]
    private ?User $user = null;

    #[BelongsTo(target: Quote::class, nullable: false)]
    private ?Quote $quote = null;

    #[HasOne(
        target: SalesOrderAmount::class,
        outerKey: 'sales_order_id'
    )]
    private readonly SalesOrderAmount $sales_order_amount;

    /**
     * @var ArrayCollection<array-key, SalesOrderItem>
     */
    #[HasMany(
        target: SalesOrderItem::class,
        outerKey: 'sales_order_id'
    )]
    private readonly ArrayCollection $items;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_created;

    #[Column(type: 'datetime', nullable: false)]
    private readonly DateTimeImmutable $date_modified;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_expires;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false, default: 0)]
        private ?int $quote_id = null,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $inv_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $client_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $user_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $group_id = null,
        #[Column(
            type: 'tinyInteger(2)',
            nullable: false,
            default: 1
        )]
        private ?int $status_id = null,
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $number = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_po_number = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_po_line_number = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_po_person = '',
        #[Column(
            type: 'decimal(20,2)',
            nullable: false,
            default: 0.00
        )]
        private ?float $discount_amount = 0.00,
        #[Column(type: 'string(32)', nullable: true)]
        private string $url_key = '',
        #[Column(type: 'string(90)', nullable: true)]
        private ?string $password = '',
        #[Column(type: 'longText', nullable: true)]
        private ?string $notes = '',
        #[Column(type: 'longText', nullable: true)]
        private ?string $payment_term = '',
    ) {
        $this->items = new ArrayCollection();
        $this->sales_order_amount = new SalesOrderAmount();
        $this->date_modified = new DateTimeImmutable();
        $this->date_created = new DateTimeImmutable();
        $this->date_expires = new DateTimeImmutable();
    }
}
