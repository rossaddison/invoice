<?php

declare(strict_types=1);

namespace App\Invoice\Task;

use App\Invoice\Entity\Task;

final readonly class TaskService
{
    public function __construct(private TaskRepository $repository)
    {
    }

    /**
     * @param Task $model
     * @param array $array
     */
    public function saveTask(Task $model, array $array): void
    {
        isset($array['project_id']) ? $model->setProject_id((int)$array['project_id']) : '';
        isset($array['name']) ? $model->setName((string)$array['name']) : '';
        isset($array['description']) ? $model->setDescription((string)$array['description']) : '';
        isset($array['price']) ? $model->setPrice((float)$array['price']) : $model->setPrice(0.00);
        isset($array['status']) ? $model->setStatus((int)$array['status']) : '';
        isset($array['tax_rate_id']) ? $model->setTax_rate_id((int)$array['tax_rate_id']) : '';

        $datetime = new \DateTime();
        /**
         * @var string $array['finish_date']
         */
        $finish_date = $array['finish_date'] ?? '';
        $model->setFinish_date($datetime::createFromFormat('Y-m-d', $finish_date));

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
