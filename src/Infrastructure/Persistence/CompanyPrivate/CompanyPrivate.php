<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CompanyPrivate;

use App\Infrastructure\Persistence\Company\Company;
use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\ORM\Entity\Behavior;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTime;
use DateTimeImmutable;
use App\Infrastructure\Persistence\CompanyPrivate\Trait\CompanyPrivateTrait1;
use App\Infrastructure\Persistence\CompanyPrivate\Trait\CompanyPrivateTrait2;
use App\Infrastructure\Persistence\CompanyPrivate\Trait\CompanyPrivateTrait3;

#[Entity(repository: \App\Invoice\CompanyPrivate\CompanyPrivateRepository::class)]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class CompanyPrivate
{
    use RequireId;
    use CompanyPrivateTrait1;
    use CompanyPrivateTrait2;
    use CompanyPrivateTrait3;

    #[BelongsTo(target: Company::class, nullable: false, fkAction: 'NO ACTION')]
    private ?Company $company = null;

    #[Column(type: 'datetime')]
    private readonly DateTimeImmutable $date_created;

    #[Column(type: 'datetime')]
    private readonly DateTimeImmutable $date_modified;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $company_id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $vat_id = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $tax_code = '',
        #[Column(type: 'string(34)', nullable: true)]
        private ?string $iban = '',
        #[Column(type: 'string(8)', nullable: true)]
        private ?string $bacs_sort_code = '',
        #[Column(type: 'string(8)', nullable: true)]
        private ?string $bacs_account_number = '',
        #[Column(type: 'string(14)', nullable: true)]
        private ?string $gln = '',
        #[Column(type: 'string(7)', nullable: true)]
        private ?string $rcc = '',
        #[Column(type: 'string(150)', nullable: true)]
        private ?string $logo_filename = '',
        #[Column(type: 'int', nullable: false, default: 80)]
        private ?int $logo_width = null,
        #[Column(type: 'int', nullable: false, default: 40)]
        private ?int $logo_height = null,
        #[Column(type: 'int', nullable: false, default: 10)]
        private ?int $logo_margin = null,
        #[Column(type: 'date', nullable: true)]
        private mixed $start_date = null,
        #[Column(type: 'date', nullable: true)]
        private mixed $end_date = null,
    ) {
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable();
    }
}
