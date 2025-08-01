<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\ORM\Entity\Behavior;
use Doctrine\Common\Collections\ArrayCollection;
use App\User\User;
use DateTimeImmutable;

#[Entity(repository: \App\Invoice\Inv\InvRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]

class Inv
{
    // Users
    #[BelongsTo(target: User::class, nullable: false)]
    private ?User $user = null;

    // Group
    #[BelongsTo(target: Group::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Group $group = null;

    // Client
    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    #[HasOne(target: InvAmount::class)]
    private readonly InvAmount $invAmount;

    /**
     * @var ArrayCollection<array-key, InvItem>
     */
    #[HasMany(target: InvItem::class)]
    private readonly ArrayCollection $items;

    /**
     * Related logic: see Used to determine how many times an email has been sent for this specific invoice to the client
     * @var ArrayCollection<array-key, InvSentLog>
     */
    #[HasMany(target: InvSentLog::class)]
    private ArrayCollection $invsentlogs;


    /**
     * Related logic: see Used to determine the number of recurring invoices that have been made out for this particular invoice.
     * @var ArrayCollection<array-key, InvRecurring>
     */
    #[HasMany(target: InvRecurring::class)]
    private ArrayCollection $invrecurring;

    #[Column(type: 'primary')]
    private ?int $id = null;

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
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $discount_percent = 0.00,
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

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setGroup(?Group $group): void
    {
        $this->group = $group;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setInvRecurring(): void
    {
        $this->invrecurring = new ArrayCollection();
    }

    public function getInvRecurring(): ArrayCollection
    {
        return $this->invrecurring;
    }

    public function addInvRecurring(InvRecurring $invrecurring): void
    {
        $this->invrecurring[] = $invrecurring;
    }

    public function setInvSentLogs(): void
    {
        $this->invsentlogs = new ArrayCollection();
    }

    public function getInvSentLogs(): ArrayCollection
    {
        return $this->invsentlogs;
    }

    public function addInvSentLog(InvSentLog $invSentLog): void
    {
        $this->invsentlogs[] = $invSentLog;
    }

    public function getItems(): ArrayCollection
    {
        return $this->items;
    }

    /**
     * @return numeric-string|null
     */
    public function getId(): string|null
    {
        return $this->id === null ? null : (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUser_id(): string
    {
        return (string) $this->user_id;
    }

    public function setUser_id(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getClient_id(): string
    {
        return (string) $this->client_id;
    }

    public function setClient_id(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getGroup_id(): string
    {
        return (string) $this->group_id;
    }

    public function setGroup_id(int $group_id): void
    {
        $this->group_id = $group_id;
    }

    public function getSo_id(): string
    {
        return (string) $this->so_id;
    }

    public function setSo_id(int $so_id): void
    {
        $this->so_id = $so_id;
    }

    public function getQuote_id(): string
    {
        return (string) $this->quote_id;
    }

    public function setQuote_id(int $quote_id): void
    {
        $this->quote_id = $quote_id;
    }

    public function getDelivery_id(): string
    {
        return (string) $this->delivery_id;
    }

    public function setDelivery_id(int $delivery_id): void
    {
        $this->delivery_id = $delivery_id;
    }

    public function getDelivery_location_id(): string
    {
        return (string) $this->delivery_location_id;
    }

    public function setDelivery_location_id(int $delivery_location_id): void
    {
        $this->delivery_location_id = $delivery_location_id;
    }

    public function getContract_id(): string
    {
        return (string) $this->contract_id;
    }

    public function setContract_id(int $contract_id): void
    {
        $this->contract_id = $contract_id;
    }

    public function getStatus_id(): int|null
    {
        return $this->status_id;
    }

    public function setStatus_id(int $status_id): void
    {
        $this->status_id = (!in_array($status_id, [1, 2, 3, 4, 5, 6, 7, 8 ,9, 10, 11, 12, 13]) ? 1 : $status_id);
    }

    public function getIs_read_only(): bool
    {
        return $this->is_read_only ? true : false;
    }

    public function setIs_read_only(bool $is_read_only): void
    {
        $this->is_read_only = $is_read_only;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Same as date issued
     * @return DateTimeImmutable
     */
    public function getDate_created(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->date_created */
        return $this->date_created;
    }

    public function setDate_created(string $date_created): void
    {
        $this->date_created = (new DateTimeImmutable())->createFromFormat('Y-m-d', $date_created) ?: new DateTimeImmutable('now');
    }

    public function setTime_created(string $time_created): void
    {
        $this->time_created = $time_created;
    }

    public function getTime_created(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $this->time_created */
        return $this->time_created;
    }

    public function getDate_modified(): DateTimeImmutable
    {
        return $this->date_modified;
    }

    public function setDate_due(\App\Invoice\Setting\SettingRepository $sR): void
    {
        if (empty($sR->getSetting('invoices_due_after'))) {
            $days = 30;
        } else {
            $days = $sR->getSetting('invoices_due_after');
        }

        $this->date_due = $this->date_created->add(new \DateInterval('P' . (string) $days . 'D'));
    }

    public function getDate_due(): DateTimeImmutable
    {
        return $this->date_due;
    }

    public function getDate_supplied(): DateTimeImmutable
    {
        return $this->date_supplied;
    }

    public function setDate_supplied(DateTimeImmutable $date_supplied): void
    {
        $this->date_supplied = $date_supplied;
    }

    public function getDate_tax_point(): DateTimeImmutable
    {
        return $this->date_tax_point;
    }

    public function setDate_tax_point(DateTimeImmutable $date_tax_point): void
    {
        $this->date_tax_point = $date_tax_point;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getDiscount_amount(): ?float
    {
        return $this->discount_amount;
    }

    public function setDiscount_amount(float $discount_amount): void
    {
        $this->discount_amount = $discount_amount;
    }

    public function getDiscount_percent(): ?float
    {
        return $this->discount_percent;
    }

    public function setDiscount_percent(float $discount_percent): void
    {
        $this->discount_percent = $discount_percent;
    }

    public function getTerms(): string
    {
        return $this->terms;
    }

    public function getNote(): string|null
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function getDocumentDescription(): string|null
    {
        return $this->document_description;
    }

    public function setDocumentDescription(string $document_description): void
    {
        $this->document_description = $document_description;
    }

    public function setTerms(string $terms): void
    {
        $this->terms = $terms;
    }

    public function getUrl_key(): string
    {
        return $this->url_key;
    }

    public function setUrl_key(string $url_key): void
    {
        $this->url_key = $url_key;
    }

    public function getPayment_method(): int|null
    {
        return $this->payment_method;
    }

    public function setPayment_method(int $payment_method): void
    {
        $this->payment_method = $payment_method;
    }

    public function getPostal_address_id(): string
    {
        return (string) $this->postal_address_id;
    }

    public function setPostal_address_id(int $postal_address_id): void
    {
        $this->postal_address_id = $postal_address_id;
    }

    public function getCreditinvoice_parent_id(): string
    {
        return (string) $this->creditinvoice_parent_id;
    }

    public function setCreditinvoice_parent_id(int|null $creditinvoice_parent_id): void
    {
        $this->creditinvoice_parent_id = $creditinvoice_parent_id;
    }

    public function isOverdue(): bool
    {
        return $this->getStatus_id() === 5;
    }

    // https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL2005/

    /**
     * This code is used if a VAT rate for the tax period is not known yet and
     * is mutually exclusive to the tax point date.
     * So if if you cannot determine a tax point date because you do not know what
     * the VAT rate is, use this code instead of a tax point date.
     * If you have a string value for this, you should not have a value for your tax point date
     * The two are mutually exclusive.
     * Related logic: see src/resources/views/invoice/info/deutschebahn.php
     * @return string
     */
    public function getStand_in_code(): string
    {
        return $this->stand_in_code;
    }

    public function setStand_in_code(string $stand_in_code): void
    {
        $this->stand_in_code = $stand_in_code;
    }

    public function getInvAmount(): InvAmount
    {
        return $this->invAmount;
    }

    /**
     * NB! Make sure you have the correct sequence of parameters between the brackets
     * Related logic: see https://github.com/yiisoft/demo/issues/462
     * @param int $group_id
     * @param int $client_id
     */
    public function nullifyRelationOnChange(int $group_id, int $client_id): void
    {
        if ($this->group_id != $group_id) {
            $this->group = null;
        }
        if ($this->client_id != $client_id) {
            $this->client = null;
        }
        // the user_id will always be attached to the client therefore will not change
    }

    public function isNewRecord(): bool
    {
        return null === $this->getId() ? true : false;
    }
}
