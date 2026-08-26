<?php

declare(strict_types=1);

namespace App\Invoice\InvItem\Trait;

use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\Task\Task;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Task\TaskRepository as taskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * The task-only half of InvItemService's add/save-item pair — split out
 * purely to stay under SonarQube's php:S1448 method-count ceiling (max 20),
 * the same technique ClientRepositoryFilterTrait already uses. Relies on
 * $this->repository, $this->taxratePercentage(), and $this->saveInvItemAmount()
 * all being visible here exactly as they would be on a normal method, since
 * trait methods run in the composing class's own scope.
 */
trait InvItemTaskTrait
{
    /**
     * Related logic: see InvController function invToInvItems
     * @param InvItem $model
     * @param array $array
     * @param string $inv_id
     * @param taskR $taskR
     * @param TRR $trr
     * @param IIAS $iias
     * @param IIAR $iiar
     * @return int|null
     */
    public function addInvItemTask(InvItem $model, array $array, string $inv_id,
                    taskR $taskR, TRR $trr, IIAS $iias, IIAR $iiar): ?int
    {
        // This function is used in task/selection_inv when adding a new task
        // from the modal. Related logic https://github.com/cycle/orm/issues/348
        $tax_rate_id = ((isset($array['tax_rate_id'])) ?
            (int) $array['tax_rate_id'] : '');
        $model->setTaxRateId((int) $tax_rate_id);
        $task_id = ((isset($array['task_id'])) ? (int) $array['task_id'] : '');
        // Product id and task id are mutually exclusive
        $model->setTaskId((int) $task_id);

        $model->setInvId((int) $inv_id);

        /** @var Task $task */
        $task = $taskR->repoTaskquery((int) $array['task_id']);
        $model->setName($task->getName() ?? '');

        // If the user has changed the description on the form => override
        // default task description
        if (isset($array['description'])) {
            $description = (string) $array['description'];
        } else {
            $description = $task->getDescription();
        }
        $model->setDescription($description ?: '');
        $note = ((isset($array['note'])) ? (string) $array['note'] : '');
        $model->setNote($note ?: '');

        $model->setQuantity((float) $array['quantity'] ?: 1.00);
        $model->setProductUnit('');
        $model->setPrice((float) $array['price'] ?: 0.00);
        $model->setDiscountAmount((float) $array['discount_amount'] ?: 0.00);
        $model->setOrder((int) $array['order'] ?: 0);

        $datetimeimmutable = new \DateTimeImmutable('now');
        $model->setDate($datetimeimmutable);
        $tax_rate_percentage =
                            $this->taxratePercentage((int) $tax_rate_id, $trr);
        if ($task_id > 0) {
            $this->repository->save($model);
            if (isset($array['quantity'], $array['price'],
                    $array['discount_amount'])
                        && null !== $tax_rate_percentage) {
                $this->saveInvItemAmount($model->reqId(),
                        (float) $array['quantity'],
                        (float) $array['price'],
                        (float) $array['discount_amount'],
                        $tax_rate_percentage,
                        $iias,
                        $iiar);
            }
        }
        return $model->reqId();
    }

    /**
     * @param InvItem $model
     * @param array $array
     * @param string $inv_id
     * @param taskR $taskR
     * @return int
     */
    public function saveInvItemTask(InvItem $model, array $array,
                                    string $inv_id, taskR $taskR): int
    {
        if (isset($array['tax_rate_id'])) {
            $currentTaxRate = $model->getTaxRate();
            $model->setTaxRate(
                $currentTaxRate?->reqId() == (int) $array['tax_rate_id'] ? $currentTaxRate : null
            );
        }
        $tax_rate_id = (int) ($array['tax_rate_id'] ?? 0);
        $model->setTaxRateId($tax_rate_id);
        if (isset($array['task_id'])) {
            $currentTask = $model->getTask();
            $model->setTask(
                $currentTask?->reqId() == (int) $array['task_id'] ? $currentTask : null
            );
        }
        $model->setTaskId((int) ($array['task_id'] ?? 0));
        $model->setInvId((int) $inv_id);
        /** @var Task $task */
        $task = $taskR->repoTaskquery((int) ($array['task_id'] ?? 0));
        if (isset($array['name'])) {
            $model->setName($task->getName() ?? '');
        }
        $description = isset($array['description'])
            ? (string) $array['description']
            : $task->getDescription();
        $model->setDescription($description ?: '');
        $model->setNote(isset($array['note']) ? (string) $array['note'] : '');
        $model->setQuantity((float) $array['quantity'] ?: 1.00);
        $model->setPrice((float) $array['price'] ?: 0.00);
        $model->setDiscountAmount((float) $array['discount_amount'] ?: 0.00);
        $model->setOrder((int) $array['order'] ?: 0);
        $model->setProductUnit('');
        $model->setDate(new \DateTimeImmutable('now'));
        if ((int) ($array['task_id'] ?? 0) > 0) {
            $this->repository->save($model);
        }
        return $tax_rate_id;
    }

    private function applyTaskItemBlock(
        InvItem $model,
        array $array,
        int $task_id,
        Task $task,
        taskR $taskR,
        Translator $translator,
    ): void {
        $model->setTaskId($task_id);
        $name = null;
        if (isset($array['task_id']) && $taskR->repoCount($task_id) > 0) {
            $name = $task->getName();
        }
        null !== $name ? $model->setName($name) : $model->setName('');
        $description = isset($array['description'])
            ? (string) $array['description']
            : $task->getDescription();
        if (strlen($description) > 0) {
            $model->setDescription($description);
        } else {
            $model->setDescription($translator->translate('not.available'));
        }
    }
}
