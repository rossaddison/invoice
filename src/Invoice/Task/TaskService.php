<?php

declare(strict_types=1);

namespace App\Invoice\Task;

use App\Invoice\Entity\Task;
use App\Invoice\Project\ProjectRepository as PR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;

final readonly class TaskService
{
    public function __construct(
        private TaskRepository $repository,
        private PR $pR,
        private TRR $trR,
    ) {
    }

    private function persist(Task $model, array $array): void
    {
        if (isset($array['project_id'])) {
            $project = $this->pR->repoProjectquery(
                (string) $array['project_id']
            );
            if ($project) {
                $model->setProject($project);
                $model->setProject_id((int) $project->getId());
            }
        }
        if (isset($array['tax_rate_id'])) {
            $tax_rate = $this->trR->repoTaxRatequery(
                (string) $array['tax_rate_id']
            );
            if ($tax_rate) {
                $model->setTaxRate($tax_rate);
                $model->setTax_rate_id(
                    (int) $tax_rate->getTaxRateId()
                );
            }
        }
    }

    /**
     * @param Task $model
     * @param array $array
     */
    public function saveTask(Task $model, array $array): void
    {
        $this->persist($model, $array);
        isset($array['name'])
            ? $model->setName((string) $array['name'])
            : '';
        isset($array['description'])
            ? $model->setDescription((string) $array['description'])
            : '';
        isset($array['price'])
            ? $model->setPrice((float) $array['price'])
            : $model->setPrice(0.00);
        isset($array['status'])
            ? $model->setStatus((int) $array['status'])
            : '';
        $datetime = new \DateTime();
        /**
         * @var string $array['finish_date']
         */
        $finish_date = $array['finish_date'] ?? '';
        $model->setFinish_date(
            $datetime::createFromFormat('Y-m-d', $finish_date)
        );
        $this->repository->save($model);
    }

    /**
     * @param Task $model
     */
    public function deleteTask(Task $model): void
    {
        $this->repository->delete($model);
    }
}
