<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

// Entities
use App\User\User;
use App\Invoice\Entity\SalesOrder;
use App\Invoice\Entity\SalesOrderCustom;
use App\Invoice\Entity\SalesOrderItem;
use App\Invoice\Entity\SalesOrderTaxRate;
// Repositories
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository as SoAR;
use App\Invoice\SalesOrderCustom\SalesOrderCustomRepository as SoCR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository as SoTRR;
// Services
use App\Invoice\SalesOrderAmount\SalesOrderAmountService as SoAS;
use App\Invoice\SalesOrderCustom\SalesOrderCustomService as SoCS;
use App\Invoice\SalesOrderItem\SalesOrderItemService as SoIS;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateService as SoTRS;
// Ancillary
use Yiisoft\Security\Random;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final readonly class SalesOrderService
{
    public function __construct(private SalesOrderRepository $repository, private SessionInterface $session) {}

    /**
     * @param User $user
     * @param SalesOrder $model
     * @param array $array
     */
    public function addSo(User $user, SalesOrder $model, array $array): void
    {
        isset($array['quote_id']) ? $model->setQuote_id((int) $array['quote_id']) : '';
        isset($array['inv_id']) ? $model->setInv_id((int) $array['inv_id']) : '';
        isset($array['group_id']) ? $model->setGroup_id((int) $array['group_id']) : '';
        isset($array['client_id']) ? $model->setClient_id((int) $array['client_id']) : '';
        isset($array['client_po_number']) ? $model->setClient_po_number((string) $array['client_po_number']) : '';
        isset($array['client_po_person']) ? $model->setClient_po_person((string) $array['client_po_person']) : '';
        isset($array['status_id']) ? $model->setStatus_id((int) $array['status_id']) : '';
        isset($array['discount_amount']) ? $model->setDiscount_amount((float) $array['discount_amount']) : '';
        isset($array['discount_percent']) ? $model->setDiscount_percent((float) $array['discount_percent']) : '';
        isset($array['url_key']) ? $model->setUrl_key((string) $array['url_key']) : Random::string(32);
        isset($array['password']) ? $model->setPassword((string) $array['password']) : '';
        isset($array['notes']) ? $model->setNotes((string) $array['notes']) : '';
        isset($array['payment_term']) ? $model->setPaymentTerm((string) $array['payment_term']) : '';
        $model->setNumber((string) $array['number']);
        if ($model->isNewRecord()) {
            $model->setStatus_id(1);
            $model->setUser_id((int) $user->getId());
            $model->setDate_created(new \DateTimeImmutable('now'));
            $model->setDiscount_amount(0.00);
        }
        $this->repository->save($model);
    }

    /**
     * @param SalesOrder $model
     * @param array $array
     * @return SalesOrder
     */
    public function saveSo(SalesOrder $model, array $array): SalesOrder
    {
        $model->setQuote_id((int) $array['quote_id']);
        $model->setInv_id((int) $array['inv_id']);
        isset($array['client_id']) ? $model->setClient_id((int) $array['client_id']) : '';
        isset($array['group_id']) ? $model->setGroup_id((int) $array['group_id']) : '';
        isset($array['client_po_number']) ? $model->setClient_po_number((string) $array['client_po_number']) : '';
        isset($array['client_po_person']) ? $model->setClient_po_person((string) $array['client_po_person']) : '';
        isset($array['status_id']) ? $model->setStatus_id((int) $array['status_id']) : '';
        isset($array['discount_percent']) ? $model->setDiscount_percent((float) $array['discount_percent']) : '';
        isset($array['discount_amount']) ? $model->setDiscount_amount((float) $array['discount_amount']) : '';
        isset($array['url_key']) ? $model->setUrl_key((string) $array['url_key']) : '';
        isset($array['password']) ? $model->setPassword((string) $array['password']) : '';
        isset($array['notes']) ? $model->setNotes((string) $array['notes']) : '';
        isset($array['payment_term']) ? $model->setPaymentTerm((string) $array['payment_term']) : '';
        $this->repository->save($model);
        return $model;
    }

    /**
     * @param SalesOrder $model
     * @param SoCR $socR
     * @param SoCS $socS
     * @param SoIR $soiR
     * @param SoIS $soiS
     * @param SoTRR $sotrR
     * @param SoTRS $sotrS
     * @param SoAR $soaR
     * @param SoAS $soaS
     */
    public function deleteSo(SalesOrder $model, SoCR $socR, SoCS $socS, SoIR $soiR, SoIS $soiS, SoTRR $sotrR, SoTRS $sotrS, SoAR $soaR, SoAS $soaS): void
    {
        $so_id = $model->getId();
        // SalesOrders with no items: If there are no quote items there will be no quote amount record
        // so check if there is a quote amount otherwise null error will occur.
        if (null !== $so_id) {
            $count = $soaR->repoSalesOrderAmountCount($so_id);
            if ($count > 0) {
                $so_amount = $soaR->repoSalesOrderquery($so_id);
                if ($so_amount) {
                    $soaS->deleteSalesOrderAmount($so_amount);
                }
            }

            /** @var SalesOrderItem $item */
            foreach ($soiR->repoSalesOrderItemIdquery($so_id) as $item) {
                $soiS->deleteSalesOrderItem($item);
            }

            /** @var SalesOrderTaxRate $so_tax_rate */
            foreach ($sotrR->repoSalesOrderquery($so_id) as $so_tax_rate) {
                $sotrS->deleteSalesOrderTaxRate($so_tax_rate);
            }

            /** @var SalesOrderCustom $so_custom */
            foreach ($socR->repoFields($so_id) as $so_custom) {
                $socS->deleteSalesOrderCustom($so_custom);
            }
            $this->repository->delete($model);
        }
    }

    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash(string $level, string $message): Flash
    {
        $flash = new Flash($this->session);
        $flash->set($level, $message);
        return $flash;
    }
}
