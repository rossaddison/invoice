<?php

declare(strict_types=1);

/**
 * Related logic: see id="purchase-order-number" triggered by <a href="#purchase-order-number" class="btn btn-success" data-bs-toggle="modal"  style="text-decoration:none">
 * Related logic: see Quote/url_key controller/function and ...\resources\views\invoice\quote\url_key.
 *
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string                                 $csrf
 * @var string                                 $urlKey
 */
?>

<div id="purchase-order-number" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title"><?php echo $translator->translate('salesorder'); ?></h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">
                    <div>
                        <input type="text" name="url_key" id="url_key" class="form-control"
                               value="<?php echo $urlKey; ?>" hidden>
                    </div>
                    <div>
                        <label for="quote_with_purchase_order_number"><?php echo $translator->translate('quote.with.purchase.order.number'); ?></label>
                        <input type="text" name="quote_with_purchase_order_number" id="quote_with_purchase_order_number" class="form-control"
                               value="" autocomplete="off">
                    </div> 
                    <div>
                        <label for="quote_with_purchase_order_person"><?php echo $translator->translate('quote.with.purchase.order.person'); ?></label>
                        <input type="text" name="quote_with_purchase_order_person" id="quote_with_purchase_order_person" class="form-control"
                               value="" autocomplete="off">
                    </div>   
                </form>    
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <button class="quote_with_purchase_order_number_confirm btn btn-success" id="quote_with_purchase_order_number_confirm" type="button">
                        <i class="fa fa-check"></i>
                        <?php echo $translator->translate('submit'); ?>
                    </button>
                    <button class="btn btn-danger" type="button" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> <?php echo $translator->translate('cancel'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

