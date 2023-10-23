<?php

declare(strict_types=1);

// id="so-to-pdf" triggered by <a href="#so-to-pdf" data-toggle="modal"  style="text-decoration:none"> on views/salesorder/view.php 
?>
<div id="so-to-pdf" class="modal modal-lg" role="dialog" aria-labelledby="modal_salesorder_to_pdf" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-body">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
            </div>       
            <div class="modal-header">
                <h5 class="col-12 modal-title text-center"><?php echo $s->trans('download_pdf'); ?></h5>
                <br>
            </div>            
            <input type="hidden" name="salesorder_id" id="salesorder_id" value="<?php echo $so->getId(); ?>">
            <div  class="p-2">
            <label for="custom_fields_include" class="control-label">
                <?= $s->trans('custom_fields'); ?>                
            </label>   
            </div>    
        </div>
        <div class="modal-footer">
            <div class="btn-group">
                <button class="salesorder_to_pdf_confirm_with_custom_fields btn btn-success" id="salesorder_to_pdf_confirm_with_custom_fields" type="button">
                    <i class="fa fa-check"></i> <?= $s->trans('yes'); ?>
                </button>
                <button class="salesorder_to_pdf_confirm_without_custom_fields btn btn-info" id="salesorder_to_pdf_confirm_without_custom_fields" type="button">
                    <i class="fa fa-times"></i> <?= $s->trans('no'); ?>
                </button>                
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?= $s->trans('back'); ?>
                </button>
            </div>
        </div>
    </form>
</div>