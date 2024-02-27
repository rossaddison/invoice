<div id="modal-choose-items" class="modal modal-lg" role="dialog" aria-labelledby="modal_choose_items" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="filter_family_inv"><?= $translator->translate('i.any_family'); ?></label>
                <div class="form-group">
                    <select name="filter_family_inv" id="filter_family_inv" class="form-control">
                        <option value="0"><?= $translator->translate('i.any_family'); ?></option>
                        <?php foreach ($families as $family) { ?>
                            <option value="<?= $family->getFamily_id(); ?>"
                                <?php if (isset($filter_family) && $family->getFamily_id() == $filter_family) {
                                    echo ' selected="selected"';
                                } ?>>
                                <?= $family->getFamily_name(); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group panel panel-primary">
                    <label for="filter_product_inv"><?= $translator->translate('i.product_name'); ?></label>
                    <input type="text" class="form-control" name="filter_product_inv" id="filter_product_inv"
                           placeholder="<?= $translator->translate('i.product_name'); ?>"
                           value="<?= $filter_product ?>">                
                    <button type="button" id="filter-button-inv" class="btn btn-info"><?= $translator->translate('i.search_product'); ?></button>
                    <button type="button" id="product-reset-button-inv" class="btn btn-danger"><?= $translator->translate('i.reset'); ?></button>
                </div>
            </div>

            <br/>
            
            <div class="modal-header"> 
                    <!-- see src\Invoice\Asset\rebuild-1.13\js\modal-product-lookups.js line 64 -->
                    <!-- Note: The above js will pass selected products to invoice/product/selection_quote function -->
                    <button class="select-items-confirm-inv btn btn-success alignment:center" type="button" disabled>
                        <i class="fa fa-check"></i>
                        <?= $translator->translate('i.submit'); ?>
                    </button>            
            </div>
            <div id="product-lookup-table">
                <?php  
                    $response = $head->renderPartial('invoice/product/_partial_product_table_modal', [
                        'products' => $products,
                        'numberhelper' => $numberHelper,
                    ]);
                    echo (string)$response->getBody();
                ?>     
            </div>
        </div>
    </form>
    <div id="default_item_tax_rate" value="<?= $default_item_tax_rate; ?>"></div>
</div>