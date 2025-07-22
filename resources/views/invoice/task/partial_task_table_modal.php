<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Project\ProjectRepository $prjctR
 * @var App\Invoice\Task\TaskRepository $taskR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var DateTimeImmutable|string|null $fDate
 */
?>

<div class="table-responsive">
    <table id="tasks_table" class="table table-hover table-bordered table-striped no-margin">
        <tr>
            <th>&nbsp;</th>
            <th><?= $translator->translate('project.name'); ?></th>
            <th><?= $translator->translate('task.name'); ?></th>
            <th><?= $translator->translate('task.finish.date'); ?></th>
            <th><?= $translator->translate('task.description'); ?></th>
            <th class="text-right">
                <?= $translator->translate('task.price'); ?></th>
        </tr>

        <?php
            /**
             * @var App\Invoice\Entity\Task $task
             */
            foreach ($taskR->repoTaskStatusquery(3) as $task) { ?>
            <tr class="task-row">
                <td class="text-left">
                    <input type="checkbox" class="modal-task-id" name="task_ids[]"
                           id="task-id-<?= $task->getId() ?>" value="<?= $task->getId(); ?>">
                </td>
                <td nowrap class="text-left">
                    <b><?= ($prjctR->count($task->getProject_id()) > 0 ? $prjctR->repoProjectquery($task->getProject_id())?->getName() : '') ?></b>
                </td>
                <td>
                    <b><?= Html::encode($task->getName()); ?></b>
                </td>
                <td>
                    <?php
                        $finishDate = $task->getFinish_date();
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
                <td class="amount">
                    <?= $numberHelper->format_currency($task->getPrice()); ?>
                </td>
            </tr>
        <?php } ?>

    </table>
</div>