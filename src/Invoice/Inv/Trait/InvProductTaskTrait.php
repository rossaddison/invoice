<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use Yiisoft\Data\Cycle\Reader\EntityReader;

trait InvProductTaskTrait
{
    /**
     * @param int|null $product_id
     * @return int
     */
    public function repoCountByProduct(?int $product_id): int
    {
        return $this->select()
                      ->distinct()
                      ->with('items')
                      ->where('items.product_id', $product_id)
                      ->where('deleted_at', null)
                      ->count();
    }

    /**
     * @param int $product_id
     * @param string $from_date
     * @param string $to_date
     * @return EntityReader
     */
    public function repoProductWithInvItemsFromToDate(
            int $product_id, string $from_date, string $to_date): EntityReader
    {
        $query = $this->select()
                      ->distinct()
                      ->with('items')
                      ->where('items.product_id', $product_id)
                      ->andWhere('date_created', '>=', $from_date)
                      ->andWhere('date_created', '<=', $to_date)
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $product_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemSubtotalFromToUsingProduct(
            int $product_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices =
            $this->repoProductWithInvItemsFromToDate($product_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getProductId() == $product_id) {
                    $inv_item_amount = $iiaR->repoInvItemAmountquery($item->reqId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getSubtotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }

    /**
     * @param int $product_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemTaxTotalFromToUsingProduct(
        int $product_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoProductWithInvItemsFromToDate(
                                                        $product_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getProductId() == $product_id) {
                    $inv_item_amount =
                            $iiaR->repoInvItemAmountquery($item->reqId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getTaxTotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }

    /**
     * item_subtotal + item_tax_total = item_total
     *
     * @param int $product_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemTotalFromToUsingProduct(
                int $product_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices =
                $this->repoProductWithInvItemsFromToDate($product_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getProductId() == $product_id) {
                    $inv_item_amount =
                        $iiaR->repoInvItemAmountquery($item->reqId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getTotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }

    /**
     * @param int|null $task_id
     * @return int
     */
    public function repoCountByTask(?int $task_id): int
    {
        return $this->select()
                      ->distinct()
                      ->with('items')
                      ->where('items.task_id', $task_id)
                      ->where('deleted_at', null)
                      ->count();
    }

    /**
     * @param int $task_id
     * @param string $from_date
     * @param string $to_date
     * @return EntityReader
     */
    public function repoTaskWithInvItemsFromToDate(
                int $task_id, string $from_date, string $to_date): EntityReader
    {
        $query = $this->select()
                      ->distinct()
                      ->with('items')
                      ->where('items.task_id', $task_id)
                      ->andWhere('date_created', '>=', $from_date)
                      ->andWhere('date_created', '<=', $to_date)
                      ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $task_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemSubtotalFromToUsingTask(
                    int $task_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoTaskWithInvItemsFromToDate($task_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getTaskId() == (string) $task_id) {
                    $inv_item_amount = $iiaR->repoInvItemAmountquery($item->reqId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getSubtotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }

    /**
     * @param int $task_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemTaxTotalFromToUsingTask(
                    int $task_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoTaskWithInvItemsFromToDate($task_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getTaskId() == (string) $task_id) {
                    $inv_item_amount = $iiaR->repoInvItemAmountquery($item->reqId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getTaxTotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }

    /**
     * item_subtotal + item_tax_total = item_total
     *
     * @param int $task_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemTotalFromToUsingTask(
                    int $task_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoTaskWithInvItemsFromToDate($task_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getTaskId() == (string) $task_id) {
                    $inv_item_amount = $iiaR->repoInvItemAmountquery($item->reqId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getTotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }
}
