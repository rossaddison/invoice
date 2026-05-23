<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Project\ProjectRepository $projectR
 * @var App\Invoice\Task\TaskRepository $taskR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var DateTimeImmutable|string|null $fDate
 * @var array $tasks
 */
?>

<div class="table-responsive">
    <table id="tasks_table" class="table table-hover table-bordered table-striped m-0">
        <tr>
            <th>&nbsp;</th>
            <th><?= $translator->translate('project.name'); ?></th>
            <th><?= $translator->translate('task.name'); ?></th>
            <th><?= $translator->translate('task.finish.date'); ?></th>
            <th><?= $translator->translate('task.description'); ?></th>
            <th class="text-end">
                <?= $translator->translate('task.price'); ?></th>
        </tr>

        <?php
            /**
             * @var App\Infrastructure\Persistence\Task\Task $task
             */
            foreach ($tasks as $task) { ?>
            <tr class="task-row">
                <td class="text-start">
                    <input type="checkbox"
                           class="modal-task-id"
                           name="task_ids[]"
                           id="task-id-<?= $task->reqId() ?>"
                           value="<?= $task->reqId(); ?>">
                </td>
                <td nowrap class="text-start">
                    <b><?php echo ($p = $task->getProject()) !== null
                        && $projectR->count($p->reqId()) > 0 ?
                        $projectR->repoProjectquery(
                            $p->reqId())?->getName() ?? '' : '' ?>
                    </b>
                </td>
                <td>
                    <b><?php echo Html::encode($task->getName()); ?></b>
                </td>
                <td>
                    <?php
                        $finishDate = $task->getFinishDate();
                if ($finishDate instanceof \DateTimeImmutable) {
                    $fDate = $finishDate->format('Y-m-d');
                }
                if (is_string($finishDate)) {
                    $fDate = $finishDate;
                }
                if (null == $finishDate) {
                    $fDate = $finishDate;
                }
                ?>
                    <b><?= Html::encode($fDate); ?></b>
                </td>
                <td>
                    <?= nl2br(Html::encode($task->getDescription())); ?>
                </td>
                <td class="text-end">
                    <?= $numberHelper->formatCurrency($task->getPrice()); ?>
                </td>
            </tr>
        <?php } ?>

    </table>
</div>