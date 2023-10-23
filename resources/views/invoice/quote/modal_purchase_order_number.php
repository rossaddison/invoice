<?php

declare(strict_types=1);

/**
 * @var \Yiisoft\View\WebView $this
 */

// id="purchase-order-number" triggered by <a href="#purchase-order-number" class="btn btn-success" data-toggle="modal"  style="text-decoration:none"> with 
// quote/url_key controller/function and ...\resources\views\invoice\quote\url_key 

?>
<div id="purchase-order-number" class="modal modal-lg" role="dialog" aria-labelledby="modal_purchase_order_number" aria-hidden="true">
    <form class="modal-content">
      <div class="modal-body">  
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
        </div>        
        <div class="modal-header">
            <h5 class="col-12 modal-title text-center"><?= $translator->translate('invoice.salesorder') ?></h5>
            <br>
        </div>
        <div>
            <input type="text" name="url_key" id="url_key" class="form-control"
                   value="<?= $url_key; ?>" hidden>
        </div>
        <div>
            <label for="quote_with_purchase_order_number"><?= $translator->translate('invoice.quote.with.purchase.order.number') ?></label>
            <input type="text" name="quote_with_purchase_order_number" id="quote_with_purchase_order_number" class="form-control"
                   value="" autocomplete="off">
        </div> 
        <div>
            <label for="quote_with_purchase_order_person"><?= $translator->translate('invoice.quote.with.purchase.order.person') ?></label>
            <input type="text" name="quote_with_purchase_order_person" id="quote_with_purchase_order_person" class="form-control"
                   value="" autocomplete="off">
        </div>   
        <div class="modal-header">
            <div class="btn-group">
                <button class="quote_with_purchase_order_number_confirm btn btn-success" id="quote_with_purchase_order_number_confirm" type="button">
                    <i class="fa fa-check"></i>
                    <?= $s->trans('submit'); ?>
                </button>
            </div>
        </div>
      </div>    
    </form>
</div>

