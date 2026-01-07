<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderCustom;

use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\Entity\SalesOrderCustom;
use App\Invoice\SalesOrder\SalesOrderRepository;

final readonly class SalesOrderCustomService
{
    public function __construct(
        private SalesOrderCustomRepository $repository,
        private CustomFieldRepository $customFieldRepository,
        private SalesOrderRepository $salesOrderRepository,
    ) {
    }

    private function persist(
        SalesOrderCustom $model,
        array $array
    ): void {
        $sales_order =
            $this->salesOrderRepository->repoSalesOrderUnLoadedquery(
                (string) $array['sales_order_id']
            );
        if ($sales_order) {
            $model->setSalesOrder($sales_order);
            $model->setSales_order_id((int) $sales_order->getId());
        }
        $custom_field =
            $this->customFieldRepository->repoCustomFieldquery(
                (string) $array['custom_field_id']
            );
        if ($custom_field) {
            $model->setCustomField($custom_field);
            $model->setCustom_field_id(
                (int) $custom_field->getId()
            );
        }
    }

    /**
     * @param SalesOrderCustom $model
     * @param array $array
     */
    public function saveSoCustom(
        SalesOrderCustom $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['value'])
            ? $model->setValue((string) $array['value'])
            : '';
        $this->repository->save($model);
    }

    /**
     * @param array|SalesOrderCustom|null $model
     */
    public function deleteSalesOrderCustom(
        array|SalesOrderCustom|null $model
    ): void {
        $this->repository->delete($model);
    }
}
