<?php

declare(strict_types=1);

/**
 * @see id="inv-to-html" triggered by <a href="#inv-to-html" data-toggle="modal"  style="text-decoration:none"> views/inv/view.php 
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator 
 */

// id="inv-to-html" triggered by <a href="#inv-to-html" data-toggle="modal"  style="text-decoration:none"> on views/inv/view.php 
?>
<div id="inv-to-html" class="modal modal-lg" role="dialog" aria-labelledby="modal_inv_to_html" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-body">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
            </div>       
            <div class="modal-header">
                <h5 class="col-12 modal-title text-center"><i class="fa fa-code"></i></h5>
                <br>
            </div>            
            <input type="hidden" name="inv_id" id="inv_id" value="<?php echo $inv->getId(); ?>">
            <div  class="p-2">
            <div class="control-label">
                <?= $translator->translate('i.custom_fields'); ?>?                
            </div>   
            </div>    
        </div>
        <div class="modal-footer">
            <div class="btn-group">
                <button type="button" data-bs-toggle = "tooltip" title="html">
                <div>
                    <i class="fa fa-code"></i>
                    <?php if ((!empty($s->get_setting('pdf_html_inv'))) && ($s->get_setting('pdf_html_inv') === '1')) { ?>
                        <i class="fa fa-check"></i>
                    <?php } else {?>
                        <i class="fa fa-times"></i>
                    <?php } ?>
                </div>
                </button>
                <button class="inv_to_html_confirm_with_custom_fields btn btn-success" id="inv_to_html_confirm_with_custom_fields" type="button">
                    <i class="fa fa-check"></i> <?= $translator->translate('i.yes'); ?>
                </button>
                <button class="inv_to_html_confirm_without_custom_fields btn btn-info" id="inv_to_html_confirm_without_custom_fields" type="button">
                    <i class="fa fa-times"></i> <?= $translator->translate('i.no'); ?>
                </button>                
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?= $translator->translate('i.back'); ?>
                </button>
            </div>
        </div>
    </form>
</div>