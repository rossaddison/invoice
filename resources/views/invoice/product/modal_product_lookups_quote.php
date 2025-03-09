<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\Button;

/**
 * @see ...\src\Invoice\Quote\QuoteController function view $parameters['modal_choose_items']
 * @see ...\resources\views\invoice\product\_partial_product_table_modal.php
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $families
 * @var array $products
 * @var string $csrf
 * @var string $filter_product
 * @var string $default_item_tax_rate
 * @var string $partial_product_table_modal
 */
?>

<div id="modal-choose-items" class="modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="filter_family_inv"><?= $translator->translate('i.any_family'); ?></label>
                            <div class="form-group">
                                <select name="filter_family_quote" id="filter_family_quote" class="form-control">
                                    <option value="0"><?= $translator->translate('i.any_family'); ?></option>
                                    <?php
                                        /**
                                         * @var App\Invoice\Entity\Family $family
                                         */
                                        foreach ($families as $family) { ?>
                                        <option value="<?= $family->getFamily_id(); ?>"
                                            <?php if (isset($filter_family) && $family->getFamily_id() == $filter_family) {
                                                echo ' selected="selected"';
                                            } ?>>
                                            <?= $family->getFamily_name() ?? ''; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group panel panel-primary">
                                <label for="filter_product_quote"><?= $translator->translate('i.product_name'); ?></label>
                                <input type="text" class="form-control" name="filter_product_quote" id="filter_product_quote"
                                       placeholder="<?= $translator->translate('i.product_name'); ?>"
                                       value="<?= $filter_product ?>">                
                                <button type="button" id="filter-button-quote" class="btn btn-info"><?= $translator->translate('i.search_product'); ?></button>
                                <button type="button" id="product-reset-button-quote" class="btn btn-danger"><?= $translator->translate('i.reset'); ?></button>
                            </div>
                        </div>

                        <br/>

                        <div class="modal-header"> 
                                <!-- see src\Invoice\Asset\rebuild-1.13\js\modal-product-lookups.js line 64 -->
                                <!-- Note: The above js will pass selected products to invoice/product/selection_quote function -->
                                <button class="select-items-confirm-quote btn btn-success alignment:center" type="button" disabled>
                                    <i class="fa fa-check"></i>
                                    <?= $translator->translate('i.submit'); ?>
                                </button>            
                        </div>
                        <div id="product-lookup-table">
                            <?php
                               echo $partial_product_table_modal
?>     
                        </div>
                    </div>
                    <div class="modal-footer">
                    <?php
                        echo Button::tag()
                        ->addClass('btn btn-danger')
                        ->content($translator->translate('i.close'))
                        ->addAttributes(['data-bs-dismiss' => 'modal'])
                        ->render();
?>    
                    </div>
                </form>    
            </div>
            <div id="default_item_tax_rate" value="<?= $default_item_tax_rate; ?>"></div>
        </div>
    </div>
</div>