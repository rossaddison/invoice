<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Quote\Trait\QuoteClientTrait;
use App\Invoice\Quote\Trait\QuoteFilterTrait;
use App\Invoice\Quote\Trait\QuoteGuestTrait;
use App\Invoice\Quote\Trait\QuoteStatusSelectTrait;
use App\Invoice\Quote\Trait\QuoteStatusTrait;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Quote
 * @extends Select\Repository<TEntity>
 */
final class QuoteRepository extends Select\Repository
{
    use QuoteClientTrait;
    use QuoteFilterTrait;
    use QuoteGuestTrait;
    use QuoteStatusSelectTrait;
    use QuoteStatusTrait;

    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select,
            private readonly EntityWriter $entityWriter,
            private readonly Translator $translator)
    {
        parent::__construct($select);
    }

    /**
     * Get Quotes with filter
     *
     * @psalm-return EntityReader
     */
    public function findAllWithStatus(int $status_id): EntityReader
    {
        if ($status_id > 0) {
            $query = $this->select()
                    ->load(['client','group','user'])
                    ->where(['status_id' => $status_id]);
            return $this->prepareDataReader($query);
        }
        return $this->findAllPreloaded();
    }

    /**
     * @param int $delivery_location_id
     * @return EntityReader
     */
    public function findAllWithDeliveryLocation(int $delivery_location_id): EntityReader
    {
        $query = $this->select()
                      ->where(['delivery_location_id' => $delivery_location_id]);
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
                ->count();
    }

    /**
     * Get quotes  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                ->load(['client','group','user']);
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
        // Provide the latest quote at the top of the list and order additionally according to status
        return Sort::only(['id', 'status'])->withOrder(['id' => 'desc', 'status' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Quote|null $quote
     * @throws Throwable
     */
    public function save(array|Quote|null $quote): void
    {
        $this->entityWriter->write([$quote]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|Quote|null $quote
     * @throws Throwable
     */
    public function delete(array|Quote|null $quote): void
    {
        $this->entityWriter->delete([$quote]);
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
     * @return Quote|null
     *
     * @psalm-return TEntity|null
     */
    public function repoQuoteUnLoadedquery(int $id): ?Quote
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @return Quote|null
     *
     * @psalm-return TEntity|null
     */
    public function repoQuoteLoadedquery(int $id): ?Quote
    {
        $query = $this->select()
                      ->load(['client','group','user'])
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param int|null $quote_id
     * @param int $status_id
     * @return Quote|null
     */
    public function repoQuoteStatusquery(?int $quote_id, int $status_id): ?Quote
    {
        $query = $this->select()->where(['id' => $quote_id])
                                ->where(['status_id' => $status_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @psalm-param 1 $status_id
     *
     * @param int|null $quote_id
     */
    public function repoQuoteStatuscount(?int $quote_id, int $status_id): int
    {
        return $this->select()->where(['id' => $quote_id])
                                ->where(['status_id' => $status_id])
                                ->count();
    }

    /**
     * @param int $group_id
     * @return mixed
     */
    public function getQuoteNumber(int $group_id, GR $gR): mixed
    {
        return $gR->generateNumber($group_id);
    }

    /**
     * @param int|null $quote_id
     */
    public function repoCount(?int $quote_id): int
    {
        return $this->select()
                      ->where(['id' => $quote_id])
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
