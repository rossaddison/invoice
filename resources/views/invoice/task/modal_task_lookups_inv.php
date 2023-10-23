<?php
declare(strict_types=1);
?>

<div id="modal-choose-tasks" class="modal col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2"
     role="dialog" aria-labelledby="modal-choose-tasks" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
        </div>
        <div class="modal-body">
            <?= $partial_task_table_modal; ?>
        </div>

        <div class="modal-footer">
            <div class="btn-group">
                <button id="task-modal-submit" class="select-items-confirm-task btn btn-success" type="button">
                    <i class="fa fa-check"></i>
                    <?= $s->trans('submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i>
                    <?= $s->trans('cancel'); ?>
                </button>
            </div>
        </div>

    </form>

</div>