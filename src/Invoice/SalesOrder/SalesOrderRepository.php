<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\SalesOrder\Trait\SalesOrderClientTrait;
use App\Invoice\SalesOrder\Trait\SalesOrderGuestTrait;
use App\Invoice\SalesOrder\Trait\SalesOrderStatusTrait;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of SalesOrder
 * @extends Select\Repository<TEntity>
 */
final class SalesOrderRepository extends Select\Repository
{
    use SalesOrderStatusTrait;
    use SalesOrderGuestTrait;
    use SalesOrderClientTrait;

    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     * @param Translator $translator
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter, private readonly Translator $translator)
    {
        parent::__construct($select);
    }

    /**
     * Get SalesOrders with filter
     *
     * @psalm-return EntityReader
     */
    public function findAllWithStatus(int $status_id): EntityReader
    {
        if ($status_id > 0) {
            $query = $this->select()
                    ->load(['client','group','user','quote'])
                    ->where(['status_id' => $status_id]);
            return $this->prepareDataReader($query);
        }
        return $this->findAllPreloaded();
    }

    /**
     * @param int $user_id
     * @param int $client_id
     * @return int
     */
    public function countAllWithUserClient(int $user_id, int $client_id): int
    {
        return $this->select()
                ->load(['user', 'client'])
                ->where(['user.id' => $user_id])
                ->andWhere(['client.id' => $client_id])
                ->count();
    }

    /**
     * Get salesorders  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load(['client','group','user','quote']);
        return $this->prepareDataReader($query);
    }

    public function filterClient(string $fullName): EntityReader
    {
        $nameParts = explode(' ', $fullName);
        $firstName = $nameParts[0];
        $secondName = $nameParts[1] ?? '';
        $query = $this->select()
                       ->load(['client','group','user','quote'])
                       ->where(['client.client_name' => $firstName])
                       ->where(['client.client_surname' => $secondName]);
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

    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        // Provide the latest salesorder at the top of the list and order additionally according to status
        return Sort::only(['id', 'status'])->withOrder(['id' => 'desc', 'status' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrder|null $salesorder
     * @throws Throwable
     */
    public function save(array|SalesOrder|null $salesorder): void
    {
        $this->entityWriter->write([$salesorder]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrder|null $salesorder
     * @throws Throwable
     */
    public function delete(array|SalesOrder|null $salesorder): void
    {
        $this->entityWriter->delete([$salesorder]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'desc']),
        );
    }

    /**
     * @param int $id
     * @psalm-return TEntity|null
     * @return SalesOrder|null
     */
    public function repoSalesOrderUnLoadedquery(int $id): ?SalesOrder
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @return SalesOrder|null
     *
     * @psalm-return TEntity|null
     */
    public function repoSalesOrderLoadedquery(int $id): ?SalesOrder
    {
        $query = $this->select()
                      ->load(['client','group','user'])
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param int $salesorder_id
     * @param int $status_id
     * @return SalesOrder|null
     */
    public function repoSalesOrderStatusquery(int $salesorder_id,
            int $status_id): ?SalesOrder
    {
        $query = $this->select()->where(['id' => $salesorder_id])
                                ->where(['status_id' => $status_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @psalm-param 1 $status_id
     *
     * @param int $salesorder_id
     */
    public function repoSalesOrderStatuscount(int $salesorder_id,
            int $status_id): int
    {
        return $this->select()->where(['id' => $salesorder_id])
                                ->where(['status_id' => $status_id])
                                ->count();
    }

    /**
     * @param int $group_id
     * @return mixed
     */
    public function getSalesorderNumber(int $group_id, GR $gR): mixed
    {
        return $gR->generateNumber($group_id);
    }

    /**
     * @param int $salesorder_id
     */
    public function repoCount(int $salesorder_id): int
    {
        return $this->select()
                      ->where(['id' => $salesorder_id])
                      ->count();
    }

    /**
     * @return int
     */
    public function repoCountAll(): int
    {
        return $this->select()
                    ->count();
    }

}
