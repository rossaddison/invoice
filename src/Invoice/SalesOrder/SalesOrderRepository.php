<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Invoice\Entity\SalesOrder;
use App\Invoice\Group\GroupRepository as GR;
use Cycle\ORM\Select;
use Throwable;
use Cycle\Database\Injection\Parameter;
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
                    ->load(['client','group','user'])
                    ->where(['status_id' => $status_id]);
            return $this->prepareDataReader($query);
        }
        return $this->findAllPreloaded();
    }

    /**
     * Get salesorders  without filter
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

    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        // Provide the latest salesorder at the top of the list and order additionally according to status
        return Sort::only(['id', 'status'])->withOrder(['id' => 'desc', 'status' => 'asc']);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SalesOrder|null $salesorder
     * @throws Throwable
     */
    public function save(array|SalesOrder|null $salesorder): void
    {
        $this->entityWriter->write([$salesorder]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
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
     * @param string $id
     * @psalm-return TEntity|null
     * @return SalesOrder|null
     */
    public function repoSalesOrderUnLoadedquery(string $id): SalesOrder|null
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
    public function repoSalesOrderLoadedquery(string $id): SalesOrder|null
    {
        $query = $this->select()
                      ->load(['client','group','user'])
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string|null $salesorder_id
     * @param int $status_id
     * @return SalesOrder|null
     */
    public function repoSalesOrderStatusquery(string|null $salesorder_id, int $status_id): SalesOrder|null
    {
        $query = $this->select()->where(['id' => $salesorder_id])
                                ->where(['status_id' => $status_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @psalm-param 1 $status_id
     *
     * @param string|null $salesorder_id
     */
    public function repoSalesOrderStatuscount(string|null $salesorder_id, int $status_id): int
    {
        return $this->select()->where(['id' => $salesorder_id])
                                ->where(['status_id' => $status_id])
                                ->count();
    }

    /**
     * @param string $url_key
     * @return SalesOrder|null
     */
    public function repoUrl_key_guest_loaded(string $url_key): SalesOrder|null
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
     * @param string $salesorder_id
     * @param array $user_client
     * @return int
     */
    public function repoClient_guest_count(string $salesorder_id, array $user_client = []): int
    {
        return $this->select()
                      ->where(['id' => $salesorder_id])
                      ->andWhere(['client_id' => ['in' => new Parameter($user_client)]])
                      ->count();
    }

    /**
     * @param int $status_id
     * @param array $user_client
     * @return EntityReader
     */
    public function repoGuestStatuses(int $status_id, array $user_client = []): EntityReader
    {
        // Get specific statuses
        if ($status_id > 0) {
            $query = $this->select()
                          ->where(['status_id' => $status_id])
                          ->andWhere(['client_id' => ['in' => new Parameter($user_client)]]);
            return $this->prepareDataReader($query);
        }   // Get all the salesorders according to status
        $query = $this->select()
                     // Terms Agreement Required = 2, Client Confirmed Terms = 3, Assembled/Packaged/Prepared = 4, Goods/Service delivered = 5,
                     // Invoice Generate = 7, Invoice Generated = 8, Rejected = 9, Canceled = 10
                     ->where(['client_id' => ['in' => new Parameter($user_client)]])
                     ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10])]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @see Invoice\Entity\SalesOrder getStatus_id in_array
     * @param Translator $translator
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
                // Terms Agreement required
                'label' => $translator->translate('salesorder.sent.to.customer'),
                'class' => 'sent',
                'href' => 2,
            ],
            '3' => [
                // Client Confirmed Terms
                'label' => $translator->translate('salesorder.client.confirmed.terms'),
                'class' => 'viewed',
                'href' => 3,
            ],
            '4' => [
                // Assembled/Packaged/Prepared
                'label' => $translator->translate('salesorder.assembled.packaged.prepared'),
                'class' => 'assembled',
                'href' => 4,
            ],
            '5' => [
                // Goods/Services Delivered
                'label' => $translator->translate('salesorder.goods.services.delivered'),
                'class' => 'approved',
                'href' => 5,
            ],
            '6' => [
                // Customer Confirmed Delivery
                'label' => $translator->translate('salesorder.goods.services.confirmed'),
                // '@see App(src)/Invoice/Asset/invoice/css/yii3i.css
                'class' => 'confirmed',
                'href' => 6,
            ],
            '7' => [
                'label' => $translator->translate('salesorder.invoice.generate'),
                // '@see App(src)/Invoice/Asset/invoice/css/yii3i.css
                'class' => 'generate',
                'href' => 7,
            ],
            '8' => [
                'label' => $translator->translate('salesorder.invoice.generated'),
                // '@see App(src)/Invoice/Asset/invoice/css/yii3i.css
                'class' => 'generated',
                'href' => 8,
            ],
            '9' => [
                'label' => $translator->translate('rejected'),
                'class' => 'rejected',
                'href' => 9,
            ],
            '10' => [
                'label' => $translator->translate('canceled'),
                'class' => 'canceled',
                'href' => 10,
            ],
        ];
    }

    /**
     * @param string $key
     * @return string
     */
    public function getSpecificStatusArrayLabel(string $key): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['label']
         */
        return $statuses_array[$key]['label'];
    }

    /**
     * @param int $status
     * @return string
     */
    public function getSpecificStatusArrayClass(int $status): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$status]
         * @var string $statuses_array[$status]['class']
         */
        return $statuses_array[$status]['class'];
    }

    /**
     * @param string $group_id
     * @return mixed
     */
    public function get_salesorder_number(string $group_id, GR $gR): mixed
    {
        return $gR->generate_number((int) $group_id);
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
    public function by_client_salesorder_status(int $client_id, int $status_id): EntityReader
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
    public function by_client_salesorder_status_count(int $client_id, int $status_id): int
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
    public function approve_or_reject_salesorder_by_key(string $url_key): Select
    {
        return $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3,4,5, 6])]])
                      ->where(['url_key' => $url_key]);
    }

    /**
     * @param string $id
     * @return Select
     */
    public function approve_or_reject_salesorder_by_id(string $id): Select
    {
        return $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2])]])
                      ->where(['id' => $id]);
    }

    /**
     * @param string|null $salesorder_id
     */
    public function repoCount(string|null $salesorder_id): int
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
