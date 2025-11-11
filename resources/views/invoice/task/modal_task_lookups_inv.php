<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\Button;

/**
 * @var string $partial_task_table_modal
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 */

?>

<div id="modal-choose-tasks-inv" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5><?= $translator->translate('tasks') . ' ' . $translator->translate('complete'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <div class="modal-body">                       
                        <div id="task-lookup-table">
                            <?php
                               echo $partial_task_table_modal
?>     
                        </div>
                    </div>
                    <div class="modal-footer">
                        <?php
echo Button::tag()
->id('task-modal-submit')
->addClass('select-items-confirm-task-inv btn-success')
->content($translator->translate('submit'))
->disabled(true)
->render();
?>        
                        <?php
    echo Button::tag()
    ->addClass('btn btn-danger')
    ->content($translator->translate('close'))
    ->addAttributes(['data-bs-dismiss' => 'modal'])
    ->render();
?>    
                    </div>
                </form>    
            </div>
        </div>
    </div>
</div>