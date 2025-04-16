<?php

declare(strict_types=1);

/**
 * @see id="inv-to-modal-pdf" triggered by <a href="#inv-to-modal-pdf" data-bs-toggle="modal"  style="text-decoration:none"> views/inv/view.php
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 */

?>
   
<div id="inv-to-modal-pdf" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?= $translator->translate('invoice.invoice.pdf.modal'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <div class="control-label">
                        <?= $translator->translate('i.custom_fields'); ?>?                
                    </div>
                    <input type="hidden" name="inv_id" id="inv_id" value="<?php $inv->getId(); ?>">
                </form>
            </div>
            <div class="modal-footer">
              <div class="btn-group">
                    <button class="inv_to_modal_pdf_confirm_with_custom_fields btn btn-success" id="inv_to_modal_pdf_confirm_with_custom_fields" type="button">
                        <i class="fa fa-check"></i> <?= $translator->translate('i.yes'); ?>
                    </button>
                    <button class="inv_to_modal_pdf_confirm_without_custom_fields btn btn-info" id="inv_to_modal_pdf_confirm_without_custom_fields" type="button">
                        <i class="fa fa-times"></i> <?= $translator->translate('i.no'); ?>
                    </button>                
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> <?= $translator->translate('i.back'); ?>
                    </button>
                </div>  
            </div>
        </div>
    </div>
</div>

