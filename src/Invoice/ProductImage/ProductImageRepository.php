<?php

declare(strict_types=1);

namespace App\Invoice\ProductImage;

use App\Invoice\Entity\ProductImage;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of ProductImage
 *
 * @extends Select\Repository<TEntity>
 */
final class ProductImageRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    public string $ctype_default = 'application/octet-stream';

    public array $content_types = [
        'gif'  => 'image/gif',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'bmp'  => 'image/bmp',
        'tiff' => 'image/tiff',
    ];

    public function getContentTypes(): array
    {
        return $this->content_types;
    }

    public function getContentTypeDefaultOctetStream(): string
    {
        return $this->ctype_default;
    }

    /**
     * Get productimages  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('product');

        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }

    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function save(array|ProductImage|null $productimage): void
    {
        $this->entityWriter->write([$productimage]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|ProductImage|null $productimage): void
    {
        $this->entityWriter->delete([$productimage]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoProductImagequery(string $id): ?ProductImage
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * Get productimages.
     *
     * @psalm-return EntityReader
     */
    public function repoProductImageProductquery(int $product_id): EntityReader
    {
        $query = $this->select()
            ->andWhere(['product_id' => $product_id]);

        return $this->prepareDataReader($query);
    }

    public function repoCount(int $product_id): int
    {
        $query = $this->select()
            ->andWhere(['product_id' => $product_id]);

        return $query->count();
    }
}
