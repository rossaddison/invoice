<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\Trait\InvClientTrait;
use App\Invoice\Inv\Trait\InvFilterTrait;
use App\Invoice\Inv\Trait\InvGuestTrait;
use App\Invoice\Inv\Trait\InvProductTaskTrait;
use App\Invoice\Inv\Trait\InvStatusTrait;
use Cycle\ORM\Select;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Inv
 * @extends Select\Repository<TEntity>
 */
final class InvRepository extends Select\Repository
{
    use InvClientTrait;
    use InvFilterTrait;
    use InvGuestTrait;
    use InvProductTaskTrait;
    use InvStatusTrait;

    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     * @param Translator $translator
     */
    public function __construct(
        Select $select,
        private readonly EntityWriter $entityWriter,
        private readonly Translator $translator
    )
    {
        parent::__construct($select->load('client')->where(
                ['client.client_active' => 1]));
    }

    /**
     * @param int $status_id
     * @return EntityReader
     */
    public function findAllWithStatus(int $status_id): EntityReader
    {
        if ($status_id > 0) {
            $query = $this->select()
                    ->load(['client','group','user'])
                    ->where(['status_id' => $status_id])
                    ->where('deleted_at', null);
            return $this->prepareDataReader($query);
        }
        return $this->findAllPreloaded();
    }

    /**
     * @param int $client_id
     * @return EntityReader
     */
    public function findAllWithClient(int $client_id): EntityReader
    {
        $query = $this->select()
                ->where(['client_id' => $client_id])
                ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $contract_id
     * @return EntityReader
     */
    public function findAllWithContract(int $contract_id): EntityReader
    {
        $query = $this->select()
                      ->where(['contract_id' => $contract_id])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $delivery_location_id
     * @return EntityReader
     */
    public function findAllWithDeliveryLocation(int $delivery_location_id):
        EntityReader
    {
        $query = $this->select()
                      ->where(['delivery_location_id' => $delivery_location_id])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
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
                ->where('deleted_at', null)
                ->count();
    }

    /**
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                ->load(['client','group','user'])
                ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()->where('deleted_at', null)))
            ->withSort($this->getSort());
    }

    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id', 'status'])->withOrder([
            'id' => 'desc', 'status' => 'asc']);
    }

    public function save(array|Inv|null $inv): void
    {
        $this->entityWriter->write([$inv]);
    }

    public function delete(array|Inv|null $inv): void
    {
        $this->entityWriter->delete([$inv]);
    }

    public function findTrashed(): EntityReader
    {
        $query = $this->select()
            ->scope(null)
            ->where('deleted_at', '!=', null);
        return $this->prepareDataReader($query);
    }

    public function findTrashedById(int $id): ?Inv
    {
        /** @var Inv|null */
        return $this->select()
            ->scope(null)
            ->where(['id' => $id])
            ->where('deleted_at', '!=', null)
            ->fetchOne();
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
     * @return int
     */
    public function repoCount(int $id): int
    {
        return $this->select()
                ->where(['id' => $id])
                ->where('deleted_at', null)
                ->count();
    }

    /**
     * @return int
     */
    public function repoCountAll(): int
    {
        return $this->select()
                    ->where('deleted_at', null)
                    ->count();
    }

    /**
     * @param int $invoice_id
     * @param int $status_id
     * @return Inv|null
     */
    public function repoInvStatusquery(int $invoice_id, int $status_id): ?Inv
    {
        $query = $this->select()
                      ->where(['id' => $invoice_id])
                      ->where(['status_id' => $status_id])
                      ->where('deleted_at', null);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param int $id
     * @return Inv|null
     */
    public function repoInvUnLoadedquery(int $id): ?Inv
    {
        $query = $this->select()
                      ->where(['id' => $id])
                      ->where('deleted_at', null);
        return  $query->fetchOne() ?: null;
    }

    public function repoInvLoadInvAmountquery(int $id): ?Inv
    {
        $query = $this->select()
                      ->load('invAmount')
                      ->where(['id' => $id])
                      ->where('deleted_at', null);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param int $id
     * @return Inv|null
     */
    public function repoInvLoadedquery(int $id): ?Inv
    {
        $query = $this->select()
                      ->load(['client','group','user'])
                      ->where(['id' => $id])
                      ->where('deleted_at', null);
        return  $query->fetchOne() ?: null;
    }
}
