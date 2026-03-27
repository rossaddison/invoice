<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\ORM\Entity\Behavior;
use Doctrine\Common\Collections\ArrayCollection;
use App\User\User;
use DateTimeImmutable;

#[Entity(repository: SOR::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]

class SalesOrder
{
    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    #[BelongsTo(target: Group::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Group $group = null;

    #[BelongsTo(target: User::class, nullable: false)]
    private ?User $user = null;
    
    #[BelongsTo(target: Quote::class, nullable: false)]
    private ?Quote $quote = null;
    
    /**
     * Note: HasOne will default to fkAction: CASCADE & Camelcase salesOrder_id
     * foreign key which creates a conflict with snake case sales_order_id.
     *
     * Solution: Specify the outerKey (the foreign key in the table)
     * explicitly here to avoid conflicts between automatically inserted
     * Camelcase foreign keys in tables during schema building after Entity
     * changes. If not using 'outerKey:' always check your table and
     * runtime/schema.php for evidence perhaps of this conflict.
     *
     * Related logic:
     * https://cycle-orm.dev/        ...
     * docs/relation-has-one/current/en#differences-from-belongsto
     *
     * QuoteController function quoteToSoQuoteAmount uses
     * $salesOrder->getSalesOrderAmount()
     */
    #[HasOne(target: SalesOrderAmount::class, outerKey: 'sales_order_id')]
    private readonly SalesOrderAmount $sales_order_amount;

    /**
     * @var ArrayCollection<array-key, SalesOrderItem>
     */
    #[HasMany(target: SalesOrderItem::class, outerKey: 'sales_order_id')]
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
        // The purchase order is derived from the quote => quote_id
        // If a contract has been established between the supplier and the
        // client, use the contract reference
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
        #[Column(type: 'tinyInteger(2)', nullable: false, default: 1)]
        private ?int $status_id = null,
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $number = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_po_number = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_po_line_number = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $client_po_person = '',
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
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

    public function getPaymentTerm(): ?string
    {
        return $this->payment_term;
    }

    public function setPaymentTerm(string $payment_term): void
    {
        $this->payment_term = $payment_term;
    }

    /**
     * @return numeric-string|null
     */
    public function getId(): ?string
    {
        return $this->id === null ? null : (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): string
    {
        return (string) $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    /**
     * @param int|string|null $quote_id
     */
    public function setQuoteId(string|int|null $quote_id): void
    {
        $quote_id === null ? $this->quote_id = null
                           : $this->quote_id = (int) $quote_id ;
    }

    public function getQuoteId(): string
    {
        return (string) $this->quote_id;
    }

    public function getInvId(): ?string
    {
        return (string) $this->inv_id;
    }

    /**
     * @param int|string|null $inv_id
     */
    public function setInvId(string|int|null $inv_id): void
    {
        $inv_id === null ? $this->inv_id = null : $this->inv_id = (int) $inv_id ;
    }

    public function getClientId(): string
    {
        return (string) $this->client_id;
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getGroupId(): string
    {
        return (string) $this->group_id;
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
        $status = '';
        return match ($status_id) {
            1 => 'draft',
            2 => 'sent',
            3 => 'viewed',
            4 => 'approved',
            5 => 'rejected',
            6 => 'cancelled',
            default => $status,
        };
    }

    public function setStatusId(int $status_id): void
    {
        !in_array($status_id, [1,2,3,4,5,6,7,8,9]) ?
                $this->status_id = 1 : $this->status_id = $status_id ;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->date_created;
    }

    public function setDateCreated(DateTimeImmutable $date_created): void
    {
        $this->date_created = $date_created;
    }

    public function getDateModified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function setDateExpires(): void
    {
        $days = (string) 1;
        $this->date_expires =
        (new DateTimeImmutable('now'))->add(new \DateInterval('P' . $days . 'D'));
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

    public function setClientPoLineNumber(string $client_po_line_number): void
    {
        $this->client_po_line_number = $client_po_line_number;
    }

    public function getClientPoPerson(): ?string
    {
        return $this->client_po_person;
    }

    public function setClientPoPerson(string $client_po_person): void
    {
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
    
    public function getItems(): ArrayCollection
    {
        return $this->items;
    }
    
    public function getSalesOrderAmount(): SalesOrderAmount
    {
        return $this->sales_order_amount;
    }

    public function isNewRecord(): bool
    {
        return null === $this->getId() ? true : false;
    }
}
