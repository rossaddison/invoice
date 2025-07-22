<?php

declare(strict_types=1);

namespace App\Invoice\Merchant;

use App\Invoice\Entity\Merchant;
// Cycle
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
// Yiisoft
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of Merchant
 *
 * @extends Select\Repository<TEntity>
 */
final class MerchantRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get merchants  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

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
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function save(array|Merchant|null $merchant): void
    {
        $this->entityWriter->write([$merchant]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     *
     * @throws \Throwable
     */
    public function delete(array|Merchant|null $merchant): void
    {
        $this->entityWriter->delete([$merchant]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoMerchantquery(string $id): ?Merchant
    {
        $query = $this->select()->load('inv')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * Retrieve all the merchants that relate to this invoice id.
     *
     * @psalm-return EntityReader
     */
    public function repoMerchantInvNumberquery(string $invNumber): EntityReader
    {
        $query = $this->select()
            ->where(['inv.number' => $invNumber]);

        return $this->prepareDataReader($query);
    }

    /**
     * Retrieve all the merchants that relate to this invoice id.
     *
     * @psalm-return EntityReader
     */
    public function repoMerchantPaymentProviderquery(string $paymentProvider): EntityReader
    {
        $query = $this->select()
            ->where(['driver' => $paymentProvider]);

        return $this->prepareDataReader($query);
    }

    public function repoMerchantInvNumberWithPaymentProvider(string $invNumber, string $invPaymentProvider): EntityReader
    {
        $query = $this->select()
            ->where(['inv.number' => $invNumber])
            ->andWhere(['driver' => $invPaymentProvider]);

        return $this->prepareDataReader($query);
    }

    // Find all merchant responses associated with a user's clients ie. their client list / client_id_array

    /**
     * Get payments  with filter.
     *
     * @psalm-return EntityReader
     */
    public function findOneUserManyClientsMerchantResponses(array $client_id_array): EntityReader
    {
        $query = $this->select()
            ->load('inv')
            ->where(['inv.client_id' => ['in' => new Parameter($client_id_array)]]);

        return $this->prepareDataReader($query);
    }
}
