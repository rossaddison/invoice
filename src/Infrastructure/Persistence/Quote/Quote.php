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
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\HasOne;
use Cycle\ORM\Entity\Behavior;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;
use App\Infrastructure\Persistence\Quote\Trait\QuoteTrait1;
use App\Infrastructure\Persistence\Quote\Trait\QuoteTrait2;
use App\Infrastructure\Persistence\Quote\Trait\QuoteTrait3;

#[Entity(repository: QuoteRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
// Priority 1 — sort targets and heavy filters
#[Index(columns: ['status_id'])]
#[Index(columns: ['client_id'])]
#[Index(columns: ['date_created'])]
#[Index(columns: ['date_expires'])]
#[Index(columns: ['number'], unique: true)]
#[Index(columns: ['url_key'], unique: true)]
// Priority 2 — FK joins
#[Index(columns: ['user_id'])]
#[Index(columns: ['group_id'])]
// Priority 3 — nullable FK lookups
#[Index(columns: ['so_id'])]
#[Index(columns: ['inv_id'])]
#[Index(columns: ['delivery_location_id'])]
#[Index(columns: ['contract_id'])]
class Quote
{
    use RequireId;
    use QuoteTrait1;
    use QuoteTrait2;
    use QuoteTrait3;
    
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
}
