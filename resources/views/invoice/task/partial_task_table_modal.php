<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
?>

<div class="table-responsive">
    <table id="tasks_table" class="table table-hover table-bordered table-striped no-margin">
        <tr>
            <th>&nbsp;</th>
            <th><?= $translator->translate('i.project_name'); ?></th>
            <th><?= $translator->translate('i.task_name'); ?></th>
            <th><?= $translator->translate('i.task_finish_date'); ?></th>
            <th><?= $translator->translate('i.task_description'); ?></th>
            <th class="text-right">
                <?= $translator->translate('i.task_price'); ?></th>
        </tr>

        <?php foreach ($tasks as $task) { ?>
            <tr class="task-row">
                <td class="text-left">
                    <input type="checkbox" class="modal-task-id" name="task_ids[]"
                           id="task-id-<?= $task->getId() ?>" value="<?= $task->getId(); ?>">
                </td>
                <td nowrap class="text-left">
                    <b><?= ($prjct->count($task->getProject_id()) > 0 ? $prjct->repoProjectquery($task->getProject_id())->getName() : '') ?></b>
                </td>
                <td>
                    <b><?= Html::encode($task->getName()); ?></b>
                </td>
                <td>
                    <b><?= $datehelper->date_from_mysql($task->getFinish_date()); ?></b>
                </td>
                <td>
                    <?= nl2br(Html::encode($task->getDescription())); ?>
                </td>
                <td class="amount">
                    <?= $numberhelper->format_currency($task->getPrice()); ?>
                </td>
            </tr>
        <?php } ?>

    </table>
</div>