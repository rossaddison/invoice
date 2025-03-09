<?php

declare(strict_types=1);

/**
 * @see id="quote-to-pdf" triggered by <a href="#quote-to-pdf" data-bs-toggle="modal"  style="text-decoration:none">
 * @see views/quote/view.php
 * @var App\Invoice\Entity\Quote $quote
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 */

?>

<div id="quote-to-pdf" class="modal" tabindex="-1">
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
                    <input type="hidden" name="quote_id" id="quote_id" value="<?php $quote->getId(); ?>">
                </form>    
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <button class="quote_to_pdf_confirm_with_custom_fields btn btn-success" id="quote_to_pdf_confirm_with_custom_fields" type="button">
                        <i class="fa fa-check"></i> <?= $translator->translate('i.yes'); ?>
                    </button>
                    <button class="quote_to_pdf_confirm_without_custom_fields btn btn-info" id="quote_to_pdf_confirm_without_custom_fields" type="button">
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