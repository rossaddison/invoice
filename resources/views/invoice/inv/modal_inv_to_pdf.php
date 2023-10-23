<?php
declare(strict_types=1);

// id="inv-to-pdf" triggered by <a href="#inv-to-pdf" data-toggle="modal"  style="text-decoration:none"> on views/inv/view.php 
?>
<div id="inv-to-pdf" class="modal modal-lg" role="dialog" aria-labelledby="modal_inv_to_pdf" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-body">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
            </div>       
            <div class="modal-header">
                <h5 class="col-12 modal-title text-center"><?php echo $s->trans('download_pdf'); ?></h5>
                <br>
            </div>            
            <input type="hidden" name="inv_id" id="inv_id" value="<?php echo $inv->getId(); ?>">
            <div  class="p-2">
            <div class="control-label">
                <?= $s->trans('custom_fields'); ?>?                
            </div>   
            </div>    
        </div>
        <div class="modal-footer">
            <div class="btn-group">
                <!-- display Settings...View...Invoices...Pdf Settings...G(ie. stream)...Folder(ie.archive)...</>(ie Html)... -->
                <button type="button" data-bs-toggle = "tooltip" title="stream/archive/html">
                <div>
                    <i class="fa fa-google"></i>
                    <?php if ((null!==$s->get_setting('pdf_stream_inv')) && ($s->get_setting('pdf_stream_inv') === '1')) { ?>
                        <i class="fa fa-check"></i>
                    <?php } else {?>
                        <i class="fa fa-times"></i>
                    <?php } ?>    
                    <i class="fa fa-folder"></i>
                    <?php if ((null!==$s->get_setting('pdf_archive_inv')) && ($s->get_setting('pdf_archive_inv') === '1')) { ?>
                        <i class="fa fa-check"></i>
                    <?php } else {?>
                        <i class="fa fa-times"></i>
                    <?php } ?>
                </div>
                </button>
                <button class="inv_to_pdf_confirm_with_custom_fields btn btn-success" id="inv_to_pdf_confirm_with_custom_fields" type="button">
                    <i class="fa fa-check"></i> <?= $s->trans('yes'); ?>
                </button>
                <button class="inv_to_pdf_confirm_without_custom_fields btn btn-info" id="inv_to_pdf_confirm_without_custom_fields" type="button">
                    <i class="fa fa-times"></i> <?= $s->trans('no'); ?>
                </button>                
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?= $s->trans('back'); ?>
                </button>
            </div>
        </div>
    </form>
</div>