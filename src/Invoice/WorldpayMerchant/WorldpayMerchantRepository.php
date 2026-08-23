<?php

declare(strict_types=1);

namespace App\Invoice\WorldpayMerchant;

use App\Infrastructure\Persistence\WorldpayMerchant\WorldpayMerchant;
// Cycle
use Cycle\ORM\Select;
// Yiisoft
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Throwable;

/**
 * @template TEntity of WorldpayMerchant
 * @extends Select\Repository<TEntity>
 */
final class WorldpayMerchantRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|WorldpayMerchant|null $worldpayMerchant
     * @throws Throwable
     */
    public function save(array|WorldpayMerchant|null $worldpayMerchant): void
    {
        $this->entityWriter->write([$worldpayMerchant]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|WorldpayMerchant|null $worldpayMerchant
     * @throws Throwable
     */
    public function delete(array|WorldpayMerchant|null $worldpayMerchant): void
    {
        $this->entityWriter->delete([$worldpayMerchant]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * The most recent successful Worldpay payment audit record for this
     * invoice, used to look up the self_href a verifyPayment()/refund()
     * call must be issued against — mirrors
     * SquareMerchantRepository::repoSquareMerchantLatestSuccessfulByInvId().
     */
    public function repoWorldpayMerchantLatestSuccessfulByInvId(int $invId): ?WorldpayMerchant
    {
        $query = $this->select()
                      ->where(['inv_id' => $invId])
                      ->andWhere(['successful' => true])
                      ->orderBy('id', 'DESC');
        return $query->fetchOne() ?: null;
    }

    /**
     * The most recent Worldpay payment audit record for this invoice
     * regardless of `successful` — used by the 3DS AJAX/return
     * endpoints to find the row still awaiting a `pending_action_href`,
     * which by definition isn't `successful` yet. See
     * repoWorldpayMerchantLatestSuccessfulByInvId() for the
     * refund-eligible equivalent.
     */
    public function repoWorldpayMerchantLatestByInvId(int $invId): ?WorldpayMerchant
    {
        $query = $this->select()
                      ->where(['inv_id' => $invId])
                      ->orderBy('id', 'DESC');
        return $query->fetchOne() ?: null;
    }

    /**
     * Resolves an incoming webhook's `eventDetails.transactionReference`
     * back to the WorldpayMerchant row created for that payment — see
     * WorldpayWebhookHandler.
     */
    public function repoWorldpayMerchantByTransactionReference(string $transactionReference): ?WorldpayMerchant
    {
        $query = $this->select()
                      ->where(['transaction_reference' => $transactionReference])
                      ->orderBy('id', 'DESC');
        return $query->fetchOne() ?: null;
    }
}
