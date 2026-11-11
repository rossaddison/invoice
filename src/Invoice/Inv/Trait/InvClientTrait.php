<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use Yiisoft\Data\Cycle\Reader\EntityReader;

trait InvClientTrait
{
    /**
     * @param int $client_id
     * @return EntityReader
     */
    public function byClient(int $client_id): EntityReader
    {
        return $this->findAllWithClient($client_id);
    }

    /**
     * @param int $client_id
     * @param int $status_id
     * @return EntityReader
     */
    public function byClientInvStatus(int $client_id, int $status_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id])
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $client_id
     * @param int $status_id
     * @return int
     */
    public function byClientInvStatusCount(int $client_id, int $status_id): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id])
                      ->where('deleted_at', null)
                      ->count();
    }

    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function withTotal(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount =
                    ($iaR->repoInvAmountCount($invoice->reqId()) > 0
                    ? $iaR->repoInvquery($invoice->reqId()) : null);
            $sum += (null !== $invoice_amount ? $invoice_amount->getTotal()
                    ?? 0.00 : 0.00);
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function withItemSubtotal(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery($invoice->reqId());
            if (null !== $invoice_amount) {
                $sum += $invoice_amount->getItemSubtotal() ?: 0.00;
            }
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function withTotalFromTo(
        int $client_id,
        string $from,
        string $to,
        IAR $iaR
    ): float {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery($invoice->reqId());
            if (null !== $invoice_amount) {
                $sum += $invoice_amount->getTotal() ?? 0.00;
            }
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function withItemSubtotalFromTo(
        int $client_id,
        string $from,
        string $to,
        IAR $iaR
    ): float {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery($invoice->reqId());
            if (null !== $invoice_amount) {
                $sum += $invoice_amount->getItemSubtotal();
            }
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function withItemTaxTotalFromTo(
        int $client_id,
        string $from,
        string $to,
        IAR $iaR
    ): float {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery($invoice->reqId());
            if (null !== $invoice_amount) {
                $sum += $invoice_amount->getItemTaxTotal();
            }
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function withTaxTotalFromTo(
        int $client_id,
        string $from,
        string $to,
        IAR $iaR
    ): float {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount =
                    ($iaR->repoInvAmountCount($invoice->reqId()) > 0 ?
                    $iaR->repoInvquery($invoice->reqId()) : null);
            $sum += (null !== $invoice_amount ? $invoice_amount->getTaxTotal()
                                                                ?? 0.00 : 0.00);
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function withPaidFromTo(
        int $client_id,
        string $from,
        string $to,
        IAR $iaR
    ): float {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount =
                    ($iaR->repoInvAmountCount($invoice->reqId()) > 0 ?
                    $iaR->repoInvquery($invoice->reqId()) : null);
            $sum += (null !== $invoice_amount ? $invoice_amount->getPaid() ??
                    0.00 : 0.00);
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function withTotalPaid(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = (
                $iaR->repoInvAmountCount($invoice->reqId()) > 0 ?
                    $iaR->repoInvquery($invoice->reqId()) : null
            );
            $sum += (null !== $invoice_amount ? $invoice_amount->getPaid() ??
                    0.00 : 0.00);
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return int
     */
    public function withTotalBalanceInvoices(int $client_id, IAR $iaR): int
    {
        $invoices = $this->findAllWithClient($client_id);
        $num_invoices = 0;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount =
                    ($iaR->repoInvAmountCount($invoice->reqId()) > 0 ?
                    $iaR->repoInvquery($invoice->reqId()) : null);
            $num_invoices += (null !== $invoice_amount
                    && null !== $invoice_amount->getBalance() ? 1 : 0);
        }
        return $num_invoices;
    }

    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function withTotalBalance(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = (
                $iaR->repoInvAmountCount($invoice->reqId()) > 0 ?
                    $iaR->repoInvquery($invoice->reqId()) : null
            );
            $sum += (null !== $invoice_amount ? $invoice_amount->getBalance()
                                                                ?? 0.00 : 0.00);
        }
        return $sum;
    }

    /**
     * @param int|null $client_id
     * @return int
     */
    public function repoCountByClient(?int $client_id): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->where('deleted_at', null)
                      ->count();
    }

    /**
     * @param int $client_id
     * @param string $from_date
     * @param string $to_date
     * @return int
     */
    public function repoCountClientLoadedFromToDate(
        int $client_id,
        string $from_date,
        string $to_date
    ): int {
        return $this->select()
                      ->load('client')
                      ->where(['client_id' => $client_id])
                      ->andWhere('date_created', '>=', $from_date)
                      ->andWhere('date_created', '<=', $to_date)
                      ->where('deleted_at', null)
                      ->count();
    }

    /**
     * @param int $client_id
     * @param string $from_date
     * @param string $to_date
     * @return EntityReader
     */
    public function repoClientLoadedFromToDate(
        int $client_id,
        string $from_date,
        string $to_date
    ): EntityReader {
        $query = $this->select()
                      ->load('client')
                      ->where(['client_id' => $client_id])
                      ->andWhere('date_created', '>=', $from_date)
                      ->andWhere('date_created', '<=', $to_date)
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int|null $client_id
     * @return EntityReader
     */
    public function repoClient(?int $client_id): EntityReader
    {
        return $this->findAllWithClient((int) $client_id);
    }

    /**
     * Whether the client has at least one paid (status 4) invoice: an established customer.
     */
    public function repoClientHasPaidInvoicequery(int $clientId): bool
    {
        return $this->select()
            ->where(['client_id' => $clientId, 'status_id' => 4])
            ->andWhere('deleted_at', null)
            ->count() > 0;
    }

    /**
     * The client's most recent DRAFT invoice dated today or earlier that still has a side
     * waiting to be done (allocation status 'allocated'): the planned visit a customer's QR
     * scan can confirm. Newest first, so only the latest is ever returned; a future-dated
     * draft (a later run copied ahead) is never touched.
     */
    public function repoClientPendingAllocatedDraftquery(int $clientId, \DateTimeImmutable $today): ?Inv
    {
        $tomorrow = $today->modify('+1 day')->format('Y-m-d 00:00:00');
        $hasASideWaiting = new \Cycle\Database\Injection\Fragment('(SELECT inv_id FROM inv_worker_allocation WHERE status = ?)', 'allocated');
        return $this->select()
            ->where(['client_id' => $clientId, 'status_id' => 1])
            ->andWhere('date_created', '<', $tomorrow)
            ->andWhere('deleted_at', null)
            ->andWhere(['id' => ['in' => $hasASideWaiting]])
            ->orderBy('date_created', 'DESC')
            ->orderBy('id', 'DESC')
            ->fetchOne() ?: null;
    }
}
