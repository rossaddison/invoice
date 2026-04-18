<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\SalesOrder;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Invoice\Entity\Group;
use App\Invoice\Entity\Quote;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\User\User;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\ORM\Entity\Behavior;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;

#[Entity(repository: SOR::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class SalesOrder
{
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

    /**
     * @throws \LogicException if the entity has not been persisted yet.
     */
    public function reqId(): int
    {
        if ($this->id === null) {
            throw new \LogicException(
                'SalesOrder has no ID (not persisted yet)'
            );
        }
        return $this->id;
    }

    public function isPersisted(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): void
    {
        $this->group = $group;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getQuoteId(): ?int
    {
        return $this->quote_id;
    }

    public function setQuoteId(?int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function setInvId(?int $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getGroupId(): ?int
    {
        return $this->group_id;
    }

    public function setGroupId(int $group_id): void
    {
        $this->group_id = $group_id;
    }

    public function getStatusId(): ?int
    {
        return $this->status_id;
    }

    public function getStatus(int $status_id): string
    {
        return match ($status_id) {
            1 => 'draft',
            2 => 'sent',
            3 => 'viewed',
            4 => 'approved',
            5 => 'rejected',
            6 => 'cancelled',
            default => '',
        };
    }

    public function setStatusId(int $status_id): void
    {
        $this->status_id = in_array($status_id, [1,2,3,4,5,6,7,8,9])
            ? $status_id
            : 1;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function setDateCreated(
        DateTimeImmutable $date_created
    ): void {
        $this->date_created = $date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function setDateExpires(): void
    {
        $this->date_expires = (new DateTimeImmutable('now'))
            ->add(new \DateInterval('P1D'));
    }

    public function getDateExpires(): DateTimeImmutable
    {
        return $this->date_expires;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getClientPoNumber(): ?string
    {
        return $this->client_po_number;
    }

    public function setClientPoNumber(string $client_po_number): void
    {
        $this->client_po_number = $client_po_number;
    }

    public function getClientPoLineNumber(): ?string
    {
        return $this->client_po_line_number;
    }

    public function setClientPoLineNumber(
        string $client_po_line_number
    ): void {
        $this->client_po_line_number = $client_po_line_number;
    }

    public function getClientPoPerson(): ?string
    {
        return $this->client_po_person;
    }

    public function setClientPoPerson(
        string $client_po_person
    ): void {
        $this->client_po_person = $client_po_person;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discount_amount;
    }

    public function setDiscountAmount(float $discount_amount): void
    {
        $this->discount_amount = $discount_amount;
    }

    public function getUrlKey(): string
    {
        return $this->url_key;
    }

    public function setUrlKey(string $url_key): void
    {
        $this->url_key = $url_key;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function getPaymentTerm(): ?string
    {
        return $this->payment_term;
    }

    public function setPaymentTerm(string $payment_term): void
    {
        $this->payment_term = $payment_term;
    }

    public function getItems(): ArrayCollection
    {
        return $this->items;
    }

    public function getSalesOrderAmount(): SalesOrderAmount
    {
        return $this->sales_order_amount;
    }
}
