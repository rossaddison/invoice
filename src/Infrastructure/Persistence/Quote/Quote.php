<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Quote;

use App\Infrastructure\Persistence\{
    Client\Client, Group\Group, QuoteAmount\QuoteAmount, QuoteItem\QuoteItem,
    Trait\RequireId
};
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\Infrastructure\Persistence\User\User;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\ORM\Entity\Behavior;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;

#[Entity(repository: QuoteRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class Quote
{
    use RequireId;
    
    #[BelongsTo(target: Client::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Client $client = null;

    #[BelongsTo(target: Group::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Group $group = null;

    #[BelongsTo(target: User::class, nullable: false)]
    private ?User $user = null;

    #[HasOne(target: QuoteAmount::class)]
    private ?QuoteAmount $quoteAmount = null;

    /**
     * @var ArrayCollection<array-key, QuoteItem>
     */
    #[HasMany(target: QuoteItem::class)]
    private readonly ArrayCollection $items;

    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_created;

    #[Column(type: 'datetime', nullable: false)]
    private readonly DateTimeImmutable $date_modified;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_expires;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date_required;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $so_id = null,
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
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $discount_amount = 0.00,
        #[Column(type: 'string(32)', nullable: true)]
        private string $url_key = '',
        #[Column(type: 'string(90)', nullable: true)]
        private ?string $password = '',
        #[Column(type: 'longText', nullable: true)]
        private ?string $notes = '',
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $delivery_location_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $contract_id = null,
    ) {
        $this->items = new ArrayCollection();
        $this->quoteAmount = new QuoteAmount();
        $this->date_modified = new DateTimeImmutable();
        $this->date_created = new DateTimeImmutable();
        $this->date_expires = new DateTimeImmutable();
        $this->date_required = new DateTimeImmutable();
    }
    
    public function reqId(): int
    {
        return $this->requireId($this->id, 'Quote');
    }

    public function hasIdentity(): bool
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

    public function getQuoteAmount(): ?QuoteAmount
    {
        return $this->quoteAmount;
    }

    public function reqUserId(): int
    {
        return $this->requireId($this->user_id, 'User');
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    // copying a quote: so_id will be null
    public function getSoId(): ?int
    {
        return $this->so_id;
    }

    public function setSoId(int|null $so_id): void
    {
        $this->so_id = $so_id;
    }

    // copying a quote: inv_id will be null
    public function getInvId(): ?int
    {
        return $this->inv_id;
    }

    public function setInvId(int|null $inv_id): void
    {
        $this->inv_id = $inv_id;
    }

    public function reqClientId(): int
    {
        return $this->requireId($this->client_id, 'Client');
    }

    public function setClientId(int $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function reqGroupId(): int
    {
        return $this->requireId($this->group_id, 'Group');
    }

    public function setGroupId(int $group_id): void
    {
        $this->group_id = $group_id;
    }

    public function getDeliveryLocationId(): ?int
    {
        return $this->delivery_location_id;
    }

    public function setDeliveryLocationId(int $delivery_location_id): void
    {
        $this->delivery_location_id = $delivery_location_id;
    }

    public function reqContractId(): int
    {
        return $this->requireId($this->contract_id, 'Contract');
    }

    public function setContractId(int $contract_id): void
    {
        $this->contract_id = $contract_id;
    }
    
    public function reqStatusId(): int
    {
        return $this->requireId($this->status_id, 'Status');
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
        !in_array($status_id, [1, 2, 3, 4, 5, 6, 7])
            ? $this->status_id = 1
            : $this->status_id = $status_id;
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

    public function setDateExpires(sR $sR): void
    {
        $days = 30;
        if ($sR->repoCount('quotes_expire_after') == 0) {
            $days = 30;
        } else {
            $setting = $sR->withKey('quotes_expire_after');
            if ($setting) {
                $days = $setting->getSettingValue() ?: 30;
            }
        }
        $this->date_expires = (new DateTimeImmutable('now'))
            ->add(new \DateInterval('P' . (string) $days . 'D'));
    }

    public function getDateExpires(): DateTimeImmutable
    {
        return $this->date_expires;
    }

    public function setDateRequired(DateTimeImmutable $date_required): void
    {
        $this->date_required = $date_required;
    }

    public function getDateRequired(): DateTimeImmutable
    {
        return $this->date_required;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
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
}
