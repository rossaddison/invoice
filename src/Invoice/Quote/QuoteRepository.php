<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\Entity\Quote;
use App\Invoice\Group\GroupRepository as GR;
use Cycle\ORM\Select;
use Throwable;
use Cycle\Database\Injection\Parameter;
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
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter, private readonly Translator $translator)
    {
        parent::__construct($select);
    }

    public function filterQuoteNumber(string $quoteNumber): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['number' => ltrim(rtrim($quoteNumber))]);
        return $this->prepareDataReader($query);
    }

    public function filterQuoteAmountTotal(string $quoteAmountTotal): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('quoteAmount')
                 ->where(['quoteAmount.total' => $quoteAmountTotal]);
        return $this->prepareDataReader($query);
    }

    public function filterQuoteNumberAndQuoteAmountTotal(string $quoteNumber, float $quoteAmountTotal): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('quoteAmount')
                 ->where(['number' => $quoteNumber])
                 ->andWhere(['quoteAmount.total' => $quoteAmountTotal]);
        return $this->prepareDataReader($query);
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
    public function repoQuoteUnLoadedquery(string $id): Quote|null
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
    public function repoQuoteLoadedquery(string $id): Quote|null
    {
        $query = $this->select()
                      ->load(['client','group','user'])
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string|null $quote_id
     * @param int $status_id
     * @return Quote|null
     */
    public function repoQuoteStatusquery(string|null $quote_id, int $status_id): Quote|null
    {
        $query = $this->select()->where(['id' => $quote_id])
                                ->where(['status_id' => $status_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @psalm-param 1 $status_id
     *
     * @param string|null $quote_id
     */
    public function repoQuoteStatuscount(string|null $quote_id, int $status_id): int
    {
        return $this->select()->where(['id' => $quote_id])
                                ->where(['status_id' => $status_id])
                                ->count();
    }

    /**
     * @param string $url_key
     * @return Quote|null
     */
    public function repoUrl_key_guest_loaded(string $url_key): Quote|null
    {
        $query = $this->select()
                       ->load('client')
                       ->where(['url_key' => $url_key]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $url_key
     * @return int
     */
    public function repoUrl_key_guest_count(string $url_key): int
    {
        return $this->select()
                      ->where(['url_key' => $url_key])
                      ->count();
    }

    /**
     * @param string $quote_id
     * @param array $user_client
     * @return int
     */
    public function repoClient_guest_count(string $quote_id, array $user_client = []): int
    {
        return $this->select()
                      ->where(['id' => $quote_id])
                      ->andWhere(['client_id' => ['in' => new Parameter($user_client)]])
                      ->count();
    }

    /**
     * @param int $status_id
     * @param array $user_client
     * @return EntityReader
     */
    public function repoGuest_Clients_Sent_Viewed_Approved_Rejected_Cancelled(int $status_id, array $user_client = []): EntityReader
    {
        // Get specific statuses
        if ($status_id > 0) {
            $query = $this->select()
                    ->where(['status_id' => $status_id])
                    ->andWhere(['client_id' => ['in' => new Parameter($user_client)]]);
            return $this->prepareDataReader($query);
        }   // Get all the quotes that are either sent, viewed, approved, or rejected, or cancelled
        $query = $this->select()
                     // sent = 2, viewed = 3, approved = 4, rejected = 5, cancelled = 6
                     ->where(['client_id' => ['in' => new Parameter($user_client)]])
                     ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6])]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @return array
     */
    public function getStatuses(Translator $translator): array
    {
        return [
            '0' => [
                'label' => $translator->translate('all'),
                'class' => 'all',
                'href' => 0,
            ],
            '1' => [
                'label' => $translator->translate('draft'),
                'class' => 'draft',
                'href' => 1,
            ],
            '2' => [
                'label' => $translator->translate('sent'),
                'class' => 'sent',
                'href' => 2,
            ],
            '3' => [
                'label' => $translator->translate('viewed'),
                'class' => 'viewed',
                'href' => 3,
            ],
            '4' => [
                'label' => $translator->translate('approved'),
                'class' => 'approved',
                'href' => 4,
            ],
            '5' => [
                'label' => $translator->translate('rejected'),
                'class' => 'rejected',
                'href' => 5,
            ],
            '6' => [
                'label' => $translator->translate('canceled'),
                'class' => 'canceled',
                'href' => 6,
            ],
        ];
    }

    public function getSpecificStatusArrayLabel(string $key): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['label']
         */
        return $statuses_array[$key]['label'];
    }

    public function getSpecificStatusArrayClass(string $key): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['class']
         */
        return $statuses_array[$key]['class'];
    }

    /**
     * @param string $group_id
     * @return mixed
     */
    public function get_quote_number(string $group_id, GR $gR): mixed
    {
        return $gR->generate_number((int) $group_id);
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function is_draft(): Select
    {
        return $this->select()->where(['status_id' => 1]);
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function is_sent(): Select
    {
        return $this->select()->where(['status_id' => 2]);
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function is_viewed(): Select
    {
        return $this->select()->where(['status_id' => 3]);
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function is_approved(): Select
    {
        return $this->select()->where(['status_id' => 4]);
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function is_rejected(): Select
    {
        return $this->select()->where(['status_id' => 5]);
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function is_canceled(): Select
    {
        return $this->select()->where(['status_id' => 6]);
    }

    /**
     * Used by guest; includes only sent and viewed
     *
     * @psalm-return Select<TEntity>
     */
    public function is_open(): Select
    {
        return $this->select()->where(['status_id' => ['in' => new Parameter([2,3])]]);
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function guest_visible(): Select
    {
        return $this->select()->where(['status_id' => ['in' => new Parameter([2,3,4,5])]]);
    }

    /**
     * @param int $client_id
     *
     * @psalm-return Select<TEntity>
     */
    public function by_client(int $client_id): Select
    {
        return $this->select()
                      ->where(['client_id' => $client_id]);
    }

    /**
     * @param $client_id
     * @param $status_id
     *
     * @psalm-return EntityReader
     */
    public function by_client_quote_status(int $client_id, int $status_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $client_id
     * @param int $status_id
     * @return int
     */
    public function by_client_quote_status_count(int $client_id, int $status_id): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id])
                      ->count();
    }

    /**
     * @param string $url_key
     *
     * @psalm-return Select<TEntity>
     */
    public function approve_or_reject_quote_by_key(string $url_key): Select
    {
        return $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3,4,5])]])
                      ->where(['url_key' => $url_key]);
    }

    /**
     * @param string $id
     * @return Select
     */
    public function approve_or_reject_quote_by_id(string $id): Select
    {
        return $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3,4,5])]])
                      ->where(['id' => $id]);
    }

    /**
     * @param string|null $quote_id
     */
    public function repoCount(string|null $quote_id): int
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

    /**
     * @param int $client_id
     * @return int
     */
    public function repoCountByClient(int $client_id): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->count();
    }

    /**
     * @param int $client_id
     * @return EntityReader
     */
    public function repoClient(int $client_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
    }
}
