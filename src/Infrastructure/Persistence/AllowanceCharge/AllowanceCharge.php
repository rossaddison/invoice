<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\AllowanceCharge;

use App\Infrastructure\Persistence\{TaxRate\TaxRate, Trait\RequireId};
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use App\Infrastructure\Persistence\AllowanceCharge\Trait\AllowanceChargeTrait1;
use App\Infrastructure\Persistence\AllowanceCharge\Trait\AllowanceChargeTrait2;

#[Entity(repository: AllowanceChargeRepository::class)]

class AllowanceCharge
{
    use RequireId;
    use AllowanceChargeTrait1;
    use AllowanceChargeTrait2;
    
    #[BelongsTo(target: TaxRate::class, nullable: false, fkAction: 'NO ACTION')]
    private ?TaxRate $tax_rate = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'bool', typecast: 'bool', default: false, nullable: false)]
        /**
         * Identifier true => charge, false => allowance
         */
        private bool $identifier = false,
        #[Column(type: 'tinyInteger(1)',
                nullable: false, default: 0)]
        /* 0 = Overall, 1 = InvoiceLine */
        private int $level = 0,
        #[Column(type: 'string(3)', nullable: false)]
        private string $reason_code = '',
        #[Column(type: 'longText', nullable: false)]
        private string $reason = '',
        #[Column(type: 'integer(11)', nullable: false)]
        /**
         * $multiplier_factor_numeric x $base_amount / 100 = $amount
         * Fixed $amount i.e. no calculation involved
         * ... use a 0 or 1 for $multiplier_factor_numeric
         * $multiplier_factor_numeric > 1 => $base_amount must be > 0
         */
        private int $multiplier_factor_numeric = 0,
        #[Column(type: 'integer(11)', nullable: false)]
        private int $amount = 0,
        #[Column(type: 'integer(11)', nullable: false)]
        private int $base_amount = 0,
        #[Column(type: 'integer(11)', nullable: false)]
        private int $tax_rate_id = 0)
    {
    }
}
