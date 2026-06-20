<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Inv;

use App\Infrastructure\Persistence\{
    Client\Client, Group\Group, InvAmount\InvAmount, InvItem\InvItem,
    InvSentLog\InvSentLog, InvRecurring\InvRecurring, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\ORM\Entity\Behavior;
use Doctrine\Common\Collections\ArrayCollection;
use App\Infrastructure\Persistence\User\User;
use DateTimeImmutable;
use App\Infrastructure\Persistence\Inv\Trait\InvTrait1;
use App\Infrastructure\Persistence\Inv\Trait\InvTrait2;
use App\Infrastructure\Persistence\Inv\Trait\InvTrait3;
use App\Infrastructure\Persistence\Inv\Trait\InvTrait4;
use App\Infrastructure\Persistence\Inv\Trait\InvTrait5;

#[Entity(repository: \App\Invoice\Inv\InvRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
#[Behavior\SoftDelete(field: 'deleted_at', column: 'deleted_at')]
// Priority 1 — sort targets and heavy filters
#[Index(columns: ['status_id'])]
#[Index(columns: ['client_id'])]
#[Index(columns: ['date_created'])]
#[Index(columns: ['date_due'])]
#[Index(columns: ['number'], unique: true)]
#[Index(columns: ['url_key'], unique: true)]
// Priority 2 — FK joins and occasional filters
#[Index(columns: ['user_id'])]
#[Index(columns: ['group_id'])]
#[Index(columns: ['creditinvoice_parent_id'])]
// Priority 3 — nullable FK lookups
#[Index(columns: ['contract_id'])]
#[Index(columns: ['so_id'])]
#[Index(columns: ['quote_id'])]

class Inv
{
    use RequireId;
    use InvTrait1;
    use InvTrait2;
    use InvTrait3;
    use InvTrait4;
    use InvTrait5;

    // Users
    #[BelongsTo(target: User::class, nullable: false)]
    private ?User $user = null;

    // Group
    #[BelongsTo(target: Group::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Group $group = null;

    // Client
    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    #[HasOne(target: InvAmount::class, outerKey: 'inv_id')]
    private readonly InvAmount $invAmount;

    /**
     * @var ArrayCollection<array-key, InvItem>
     */
    #[HasMany(target: InvItem::class)]
    private readonly ArrayCollection $items;

    /**
     * Related logic: see Used to determine how many times an email
     * has been sent for this specific invoice to the client
     * @var ArrayCollection<array-key, InvSentLog>
     */
    #[HasMany(target: InvSentLog::class)]
    private ArrayCollection $invsentlogs;


    /**
     * Related logic: see Used to determine the number of recurring
     * invoices that have been made out for this particular invoice.
     * @var ArrayCollection<array-key, InvRecurring>
     */
    #[HasMany(target: InvRecurring::class)]
    private ArrayCollection $invrecurring;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'datetime', nullable: true)]
    private ?DateTimeImmutable $deleted_at = null;

    #[Column(type: 'datetime')]
    private DateTimeImmutable $date_created;

    #[Column(type: 'time', nullable: false)]
    private mixed $time_created;

    #[Column(type: 'datetime', nullable: false)]
    private readonly DateTimeImmutable $date_modified;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_tax_point;

    // Actual Delivery Date
    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_supplied;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_due;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $client_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $user_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $group_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $so_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $quote_id = null,
        #[Column(type: 'tinyInteger', nullable: false, default: 1)]
        private ?int $status_id = 1,
        #[Column(type: 'boolean', nullable: false)]
        private bool $is_read_only = false,
        #[Column(type: 'string(90)', nullable: true)]
        private ?string $password = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $number = '',
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $discount_amount = 0.00,
        #[Column(type: 'longText', nullable: false)]
        private string $terms = '',
        // src/Invoice/Ubl/Invoice - A UBL invoice must have a note
        #[Column(type: 'longText', nullable: true)]
        private ?string $note = '',
        // src/Invoice/Ubl/Invoice - A UBL invoice must have a document description
        #[Column(type: 'string(32)', nullable: true)]
        private ?string $document_description = '',
        #[Column(type: 'string(32)', nullable: false)]
        private string $url_key = '',
        #[Column(type: 'integer(11)', nullable: false, default: 6)]
        private ?int $payment_method = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $creditinvoice_parent_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $delivery_id = null,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $delivery_location_id = null,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $postal_address_id = null,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $contract_id = null,
        //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/cbc-DescriptionCode/
        #[Column(type: 'string(3)', nullable: false)]
        private string $stand_in_code = '',
        // https://docs.peppol.eu/poacc/billing/3.0/bis/#orderref
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_po_number = '',
        // https://docs.peppol.eu/poacc/billing/3.0/bis/#buyerref
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_po_person = '',
    ) {
        $this->items = new ArrayCollection();
        // create also the invoice amount when the invoice is created.
        $this->invAmount = new InvAmount();
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable('now');
        $this->date_due = new DateTimeImmutable('2024/01/01');
        $this->date_supplied = new DateTimeImmutable('2024/01/01');
        $this->date_tax_point = new DateTimeImmutable('2024/01/01');
        $this->time_created = new DateTimeImmutable('now');
        $this->invsentlogs = new ArrayCollection();
        $this->invrecurring = new ArrayCollection();
    }
}
