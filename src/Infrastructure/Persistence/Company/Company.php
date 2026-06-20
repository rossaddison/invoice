<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Company;

use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use App\Infrastructure\Persistence\Company\Trait\CompanyTrait1;
use App\Infrastructure\Persistence\Company\Trait\CompanyTrait2;
use App\Infrastructure\Persistence\Company\Trait\CompanyTrait3;

#[Entity(repository: \App\Invoice\Company\CompanyRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class Company
{
    use RequireId;
    use CompanyTrait1;
    use CompanyTrait2;
    use CompanyTrait3;

    #[Column(type: 'datetime')]
    private readonly DateTimeImmutable $date_created;

    #[Column(type: 'datetime')]
    private readonly DateTimeImmutable $date_modified;

    /**
     * @var ArrayCollection<array-key, CompanyPrivate>
     */
    #[HasMany(target: CompanyPrivate::class)]
    private readonly ArrayCollection $companyPrivates;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'tinyInteger(1)', nullable: false, default: 0)]
        private ?int $current = 0,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $address_1 = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $address_2 = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $city = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $state = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $zip = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $country = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $phone = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $fax = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $email = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $seo_description = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $web = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $slack = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $facebook = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $twitter = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $linkedin = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $whatsapp = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $arbitrationBody = '',
        #[Column(type: 'text', nullable: true)]
        private ?string $arbitrationJurisdiction = '',
    ) {
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable();
        $this->companyPrivates = new ArrayCollection();
    }
}
