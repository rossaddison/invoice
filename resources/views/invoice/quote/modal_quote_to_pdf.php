<?php

declare(strict_types=1);

/**
 * @see id="quote-to-pdf" triggered by <a href="#quote-to-pdf" data-toggle="modal"  style="text-decoration:none">  
 * @see views/quote/view.php
 * @var App\Invoice\Entity\Quote $quote
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

?>
<div id="quote-to-pdf" class="modal modal-lg" role="dialog" aria-labelledby="modal_quote_to_pdf" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-body">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
            </div>       
            <div class="modal-header">
                <h5 class="col-12 modal-title text-center"><?php echo $translator->translate('i.download_pdf'); ?></h5>
                <br>
            </div>            
            <input type="hidden" name="quote_id" id="quote_id" value="<?php echo $quote->getId(); ?>">
            <div  class="p-2">
            <label for="custom_fields_include" class="control-label">
                <?= $translator->translate('i.custom_fields'); ?>?                
            </label>   
            </div>    
        </div>
        <div class="modal-footer">
            <div class="btn-group">
                <button class="quote_to_pdf_confirm_with_custom_fields btn btn-success" id="quote_to_pdf_confirm_with_custom_fields" type="button">
                    <i class="fa fa-check"></i> <?= $translator->translate('i.yes'); ?>
                </button>
                <button class="quote_to_pdf_confirm_without_custom_fields btn btn-info" id="quote_to_pdf_confirm_without_custom_fields" type="button">
                    <i class="fa fa-times"></i> <?= $translator->translate('i.no'); ?>
                </button>                
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?= $translator->translate('i.back'); ?>
                </button>
            </div>
        </div>
    </form>
</div>