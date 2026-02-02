<?php

declare(strict_types=1);

/**
 * Related logic: see id="inv-to-html" triggered by <a href="#inv-to-html" data-bs-toggle="modal"  style="text-decoration:none"> views/inv/view.php
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 */

// id="inv-to-html" triggered by <a href="#inv-to-html" data-bs-toggle="modal"  style="text-decoration:none"> on views/inv/view.php
?>
   
<div id="inv-to-html" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <div class="control-label">
                        <?= $translator->translate('custom.fields'); ?>?                
                    </div>
                    <input type="hidden" name="inv_id" id="inv_id" value="<?php echo $inv->getId(); ?>">
                </form>    
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <button type="button" data-bs-toggle = "tooltip" title="html">
                    <div>
                        <i class="fa fa-code"></i>
                        <?php if ((!empty($s->getSetting('pdf_html_inv'))) && ($s->getSetting('pdf_html_inv') === '1')) { ?>
                            <i class="fa fa-check"></i>
                        <?php } else {?>
                            <i class="fa fa-times"></i>
                        <?php } ?>
                    </div>
                    </button>
                    <button class="inv_to_html_confirm_with_custom_fields btn btn-success" id="inv_to_html_confirm_with_custom_fields" type="button">
                        <i class="fa fa-check"></i> <?= $translator->translate('yes'); ?>
                    </button>
                    <button class="inv_to_html_confirm_without_custom_fields btn btn-info" id="inv_to_html_confirm_without_custom_fields" type="button">
                        <i class="fa fa-times"></i> <?= $translator->translate('no'); ?>
                    </button>                
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> <?= $translator->translate('back'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>