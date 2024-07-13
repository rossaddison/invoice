<?php

  declare(strict_types=1);  
  
  use Yiisoft\Html\Tag\Button;

/**
 * @see ...\src\Invoice\Quote\QuoteController function view $parameters['modal_choose_items']
 * @see ...\resources\views\invoice\product\_partial_product_table_modal.php
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $families
 * @var array $products
 * @var string $filter_product
 * @var string $default_item_tax_rate
 * @var string $partial_product_table_modal
 */
?>

<div id="modal-choose-items" class="modal modal-lg" role="dialog" aria-labelledby="modal_choose_items" aria-hidden="true" style="display: none; z-index: 1050;">
    <form class="modal-content">
        <div class="modal-body">
            <div class="form-group">
                <label><?php echo $translator->translate('i.any_family'); ?></label>
                <div class="form-group">
                    <select name="filter_family_quote" id="filter_family_quote" class="form-control">
                        <option value="0"><?php echo $translator->translate('i.any_family'); ?></option>
                        <?php
                        /**
                         * @var App\Invoice\Entity\Family $family
                         */
                        foreach ($families as $family) { ?>
                            <option value="<?php echo $family->getFamily_id(); ?>"
                                <?php if (isset($filter_family) && $family->getFamily_id() == $filter_family) {
                                    echo ' selected="selected"';
                                } ?>>
                                <?php echo $family->getFamily_name(); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo $translator->translate('i.product_name'); ?></label>
                    <input type="text" class="form-control" name="filter_product_quote" id="filter_product_quote"
                           placeholder="<?php echo $translator->translate('i.product_name'); ?>"
                           value="<?php echo $filter_product ?>">
                </div>
                <button type="button" id="filter-button-quote" class="btn btn-default"><?php echo $translator->translate('i.search_product'); ?></button>
                <button type="button" id="product-reset-button-quote" class="btn btn-default"><?php echo $translator->translate('i.reset'); ?></button>
            </div>
            <div class="modal-header">
                    <!-- see src\Invoice\Asset\rebuild-1.13\js\modal-product-lookups.js line 64 -->
                    <!-- Note: The above js will pass selected products to invoice/product/selection_quote function -->
                    <button class="select-items-confirm-quote btn btn-success alignment:center" type="button" disabled>
                        <i class="fa fa-check"></i>
                        <?= $translator->translate('i.submit'); ?>
                    </button>            
            </div>
            <br/>
            <div id="product-lookup-table">
                <?php  
                    echo $partial_product_table_modal;
                ?>     
            </div>
        </div>
        <div class="modal-footer">
        <?php    
            echo Button::tag()
            ->addClass('btn btn-danger')
            ->content($translator->translate('i.close'))
            ->addAttributes(['data-dismiss' => 'modal'])
            ->render();
        ?>    
        </div>
    </form>
    <div id="default_item_tax_rate" value="<?= $default_item_tax_rate; ?>"></div>
</div>