<?php

declare(strict_types=1);

/**
 * Related logic: see id="modal-copy-inv-multiple" triggered by
 *  <a href="#modal-copy-inv-multiple" data-bs-toggle="modal"
 *   style="text-decoration:none">
 * Related logic: see InvController index function
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 */

?>
    
<div id="modal-copy-inv-multiple"
     class="modal"
     tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title">
                    <?= $translator->translate('copy.invoice'); ?>
               </h5>
               <button type="button"
                       class="btn-close"
                       data-bs-dismiss="modal"
                       aria-label="Close"></button>
             </div>
             <div class="modal-body">
                <form >
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <div class="form-group">
                        <label for="modal_created_date">
                            <?= $translator->translate('date.created'); ?>
                        </label>
<!-- https://html.spec.whatwg.org/multipage/input.html#date-state-(type=date) -->
<!-- https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date -->
<!-- type = "date" : Note the format of the date displayed e.g 31/12/2025 will
     correspond to your computer date -->
<!-- type = "date" : However the format of the date parsed will be in
     YYYY-MM-DD format -->
                            <input name="modal_created_date"
                                   id="modal_created_date"
                                   class="form-control"
                                   type="date"
                                   autocomplete="off">
                       
                    </div>
                </form>    
            </div>
            <div class="modal-footer">
                 <button type="button"
                         class="btn btn-secondary"
                         data-bs-dismiss="modal">
                            <?= $translator->translate('cancel'); ?>
                 </button>
                 <button type="button"
                         class="modal_copy_inv_multiple_confirm btn btn-success"
                         id="modal_copy_inv_multiple_confirm">
                     <i class="fa fa-check"></i>
                     <?= $translator->translate('submit'); ?>
                 </button>
            </div>
        </div>
    </div>
</div>
