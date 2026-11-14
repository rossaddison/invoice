<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CategorySecondary;

use App\Infrastructure\Persistence\{CategoryPrimary\CategoryPrimary as CP,
    Trait\RequireId};
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;

#[Entity(repository: CSR::class)]
class CategorySecondary
{
    use RequireId;

    #[BelongsTo(target: CP::class, nullable: true, fkAction: 'NO ACTION')]
    private ?CP $category_primary = null;

    public function __construct(
        #[Column(type: 'primary')]
        public ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $category_primary_id = null,
        #[Column(type: 'text', nullable: true)]
        private ?string $name = '',
        /**
         * Where a HomeCare run starts: a UK postcode, canonical ("AB1 2CD"), which the run's "Start"
         * button opens in Google Maps. Null for a category that is not a run, or a run not set up yet.
         */
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $start_postcode = null,
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'CategorySecondary');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqCategoryPrimaryId(): int
    {
        return $this->requireId($this->category_primary_id, 'CategoryPrimary');
    }

    public function setCategoryPrimaryId(int $category_primary_id): void
    {
        $this->category_primary_id = $category_primary_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getStartPostcode(): ?string
    {
        return $this->start_postcode;
    }

    public function setStartPostcode(?string $startPostcode): void
    {
        $this->start_postcode = $startPostcode;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    // get relation CategoryPrimary
    public function getCategoryPrimary(): ?CP
    {
        return $this->category_primary;
    }

    public function setCategoryPrimary(?CP $categoryPrimary): void
    {
        $this->category_primary = $categoryPrimary;
    }
}
