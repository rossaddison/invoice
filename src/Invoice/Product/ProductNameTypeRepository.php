<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Infrastructure\Persistence\Product\Product;
use Cycle\ORM\Select;

/**
 * Exact-match Product lookup by product_name + product_type, split out of ProductRepository purely to
 * keep that class under SonarQube's php:S1448 method-count ceiling (20 methods) — the same reasoning
 * already documented for ClientDwellingRepository splitting out of ClientRepository. Not Product's
 * Cycle-ORM-designated repository (that's still ProductRepository, per Product's own
 * #[Entity(repository: ...)] attribute) — wired explicitly in config/common/di/cycle.php, following the
 * same explicit-Select pattern already used for CycleOrmAs4MessageRepository.
 *
 * @template TEntity of Product
 * @extends Select\Repository<TEntity>
 */
final class ProductNameTypeRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select)
    {
        parent::__construct($select);
    }

    /**
     * Exact match on product_name + product_type, unscoped by family — used to find-or-create the single
     * shared Service-type Product a HomeCare invoice line references (see
     * {@see \App\Invoice\Product\ProductService::findOrCreateHomeCareServiceProduct()}), where family_id
     * is a structural FK requirement only (Product.family's BelongsTo is nullable: false) and carries no
     * semantic meaning for this particular Product — unlike ProductRepository::repoProductWithFamilyIdQuery(),
     * which is deliberately family-scoped for the (unrelated) commalist bulk-generation dedup check.
     *
     * @return Product|null
     *
     * @psalm-return TEntity|null
     */
    public function repoProductByNameAndTypeQuery(string $product_name, string $product_type): ?Product
    {
        $query = $this
            ->select()
            ->where(['product_name' => $product_name])
            ->andWhere(['product_type' => $product_type]);
        return $query->fetchOne() ?: null;
    }
}
