<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InvItem;

use App\Infrastructure\Persistence\{
    Inv\Inv, Product\Product, Task\Task, TaxRate\TaxRate, Trait\RequireId
};
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;
use App\Infrastructure\Persistence\InvItem\Trait\InvItemTrait1;
use App\Infrastructure\Persistence\InvItem\Trait\InvItemTrait2;
use App\Infrastructure\Persistence\InvItem\Trait\InvItemTrait3;
use App\Infrastructure\Persistence\InvItem\Trait\InvItemTrait4;

#[Entity(repository: \App\Invoice\InvItem\InvItemRepository::class)]
class InvItem
{
    use RequireId;
    use InvItemTrait1;
    use InvItemTrait2;
    use InvItemTrait3;
    use InvItemTrait4;
    
    #[Column(type: 'date', nullable: false)]
    private mixed $date_added;

    #[Column(type: 'datetime', nullable: false)]
    private DateTimeImmutable $date;

    #[BelongsTo(target: TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;

    #[BelongsTo(target: Product::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Product $product = null;

    #[BelongsTo(target: Task::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Task $task = null;

    #[BelongsTo(target: Inv::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Inv $inv = null;

    public function __construct(
        #[Column(type: 'primary')]
        public ?int $id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'longText', nullable: true)]
        private ?string $description = '',
        #[Column(type: 'decimal(10,2)', nullable: false, default: 1)]
        private ?float $quantity = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $price = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $discount_amount = null,
        #[Column(type: 'integer(2)', nullable: true, default: 0)]
        private ?int $order = null,
        #[Column(type: 'boolean', nullable: false)]
        private ?bool $is_recurring = false,
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $product_unit = '',
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $inv_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $so_item_id = null,
        #[Column(type: 'integer(11)', nullable: false, default: 0)]
        private ?int $tax_rate_id = null,
        #[Column(type: 'integer(11)', nullable: true, default: null)]
        private ?int $product_id = null,
        #[Column(type: 'integer(11)', nullable: true, default: null)]
        private ?int $task_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $product_unit_id = null,
        #[Column(type: 'integer(2)', nullable: true, default: 0)]
        private ?int $belongs_to_vat_invoice = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $delivery_id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $peppol_po_itemid = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $peppol_po_lineid = '',
        #[Column(type: 'longText', nullable: true)]
        private ?string $note = null,
    ) {
        $this->date_added = new DateTimeImmutable();
        $this->date = new DateTimeImmutable();
    }
}
