<?php

declare(strict_types=1);

/**
 * @see id="inv-to-pdf" triggered by <a href="#inv-to-pdf" data-bs-toggle="modal"  style="text-decoration:none"> views/inv/view.php 
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf 
 */

?>
   
<div id="inv-to-pdf" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?= $translator->translate('i.download_pdf'); ?></h5>
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
                    <!-- display Settings...View...Invoices...Pdf Settings...G(ie. stream)...Folder(ie.archive)...</>(ie Html)... -->
                    <button type="button" data-bs-toggle = "tooltip" title="stream/archive/html">
                    <div>
                        <i class="fa fa-google"></i>
                        <?php if ((!empty($s->get_setting('pdf_stream_inv'))) && ($s->get_setting('pdf_stream_inv') === '1')) { ?>
                            <i class="fa fa-check"></i>
                        <?php } else {?>
                            <i class="fa fa-times"></i>
                        <?php } ?>    
                        <i class="fa fa-folder"></i>
                        <?php if ((!empty($s->get_setting('pdf_archive_inv'))) && ($s->get_setting('pdf_archive_inv') === '1')) { ?>
                            <i class="fa fa-check"></i>
                        <?php } else {?>
                            <i class="fa fa-times"></i>
                        <?php } ?>
                    </div>
                    </button>
                    <button class="inv_to_pdf_confirm_with_custom_fields btn btn-success" id="inv_to_pdf_confirm_with_custom_fields" type="button">
                        <i class="fa fa-check"></i> <?= $translator->translate('i.yes'); ?>
                    </button>
                    <button class="inv_to_pdf_confirm_without_custom_fields btn btn-info" id="inv_to_pdf_confirm_without_custom_fields" type="button">
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

