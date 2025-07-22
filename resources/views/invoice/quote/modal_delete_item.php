<?php

declare(strict_types=1);

/**
 * Related logic: see delete-items triggered by #delete-items on quote\view.php
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $partial_item_table_modal
 */

?>

<div id="delete-items" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?= $translator->translate('delete') . " " . $translator->translate('item'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">                
                <?= $partial_item_table_modal; ?>
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <!--quote.js delete-items-confirm-quote function  -->
                    <button class="delete-items-confirm-quote btn btn-success" id="delete-items-confirm-quote" type="button">
                        <i class="fa fa-check"></i><?= $translator->translate('yes'); ?>
                    </button>                
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> <?= $translator->translate('cancel'); ?>
                    </button>
                </div>
            </div>            
        </div>
    </div>
</div>
