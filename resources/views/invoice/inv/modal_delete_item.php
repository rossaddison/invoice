<?php

declare(strict_types=1);

/**
 * Related logic: see id = "delete-items" triggered by #delete-items on inv\view.php
 * Related logic: see InvController function view search modal_delete_items
 * Related logic: see invitem\_partial_item_table_modal.php.
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string                                 $partial_item_table_modal
 */
?>

<div id="delete-items" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?php echo $translator->translate('delete').' '.$translator->translate('item'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">                
                <?php echo $partial_item_table_modal; ?>
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <!--inv.js delete-items-confirm-inv function  -->
                    <button class="delete-items-confirm-inv btn btn-success" id="delete-items-confirm-inv" type="button">
                                <i class="fa fa-check"></i><?php echo $translator->translate('yes'); ?>
                    </button>                
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> <?php echo $translator->translate('cancel'); ?>
                    </button>
                </div>
            </div>            
        </div>
    </div>
</div>

