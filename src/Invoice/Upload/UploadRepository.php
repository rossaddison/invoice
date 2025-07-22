<?php

declare(strict_types=1);

namespace App\Invoice\Upload;

use App\Invoice\Entity\Upload;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of Upload
 *
 * @extends Select\Repository<TEntity>
 */
final class UploadRepository extends Select\Repository
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
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'txt'  => 'text/plain',
        'xml'  => 'application/xml',
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
     * Get uploads  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('client');

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
    public function save(array|Upload|null $upload): void
    {
        $this->entityWriter->write([$upload]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|Upload|null $upload): void
    {
        $this->entityWriter->delete([$upload]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    public function repoUploadquery(string $id): ?Upload
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * Get uploads.
     *
     * @psalm-return EntityReader
     */
    public function repoUploadUrlClientquery(string $url_key, int $client_id): EntityReader
    {
        $query = $this->select()
            ->where(['url_key' => $url_key])
            ->andWhere(['client_id' => $client_id]);

        return $this->prepareDataReader($query);
    }

    public function repoCount(string $url_key, int $client_id): int
    {
        $query = $this->select()
            ->where(['url_key' => $url_key])
            ->andWhere(['client_id' => $client_id]);

        return $query->count();
    }
}
