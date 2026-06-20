<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\QuoteItem;

use App\Infrastructure\Persistence\{
   Quote\Quote, Product\Product, Task\Task, TaxRate\TaxRate, Trait\RequireId
};
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;
use App\Infrastructure\Persistence\QuoteItem\Trait\QuoteItemTrait1;
use App\Infrastructure\Persistence\QuoteItem\Trait\QuoteItemTrait2;
use App\Infrastructure\Persistence\QuoteItem\Trait\QuoteItemTrait3;

#[Entity(repository:QIR::class)]
class QuoteItem
{
    use RequireId;
    use QuoteItemTrait1;
    use QuoteItemTrait2;
    use QuoteItemTrait3;
 
    #[Column(type: 'date', nullable: false)]
    private mixed $date_added;

    #[BelongsTo(target: Quote::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Quote $quote = null;

    #[BelongsTo(target: TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;

    // Task relation - nullable because quote items can be either products or tasks
    #[BelongsTo(target: Product::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Product $product = null;

    // Task relation - nullable because quote items can be either products or tasks
    #[BelongsTo(target: Task::class, nullable: true, fkAction: 'NO ACTION')]
    private ?Task $task = null;

    public function __construct(
        #[Column(type: 'primary')]
        public ?int $id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $description = '',
        #[Column(type: 'decimal(20,2)', nullable: false, default: 1.00)]
        private ?float $quantity = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $price = null,
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0.00)]
        private ?float $discount_amount = null,
        #[Column(type: 'integer(2)', nullable: false, default: 0)]
        private ?int $order = null,
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $product_unit = '',
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $quote_id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $tax_rate_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $product_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $product_unit_id = null,
        #[Column(type: 'integer(11)', nullable: true)]
        private ?int $task_id = null,
    ) {
        $this->date_added = new DateTimeImmutable();
    }


    //relation $tax_rate
}
