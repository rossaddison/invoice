<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Form\Field;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}
?>

<h1><?= Html::encode($title) ?></h1>

<form id="productForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data" >

    <input type="hidden" name="_csrf" value="<?= $csrf ?>">

    <div id="headerbar">
        <h1 class="headerbar-title"><?= $s->trans('products_form'); ?></h1>
        <?php
            $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]);
            echo (string)$response->getBody();
        ?>
        <div class="mb-3 form-group btn-group-sm"></div>
    </div>

    <div id="content">

        <div class="row">
            <div class="mb-3 form-group btn-group-sm">

                <div class="panel panel-default">
                    <div class="panel-heading">

                        <?php if (!empty($body['id'])) : ?>
                            #<?php echo Html::encode($body['id'] ?? ''); ?>&nbsp;
                            <?php echo Html::encode($body['product_name'] ?? ''); ?>
                        <?php else : ?>
                            <?= $s->trans('new_product'); ?>
                        <?php endif; ?>

                    </div>
                    
                    <div class="panel-body">                       
                        <div class="form-group">
                            <label for="family_id">
                                <?= $s->trans('family'); ?>
                            </label>
                            <select name="family_id" id="family_id" class="form-control" required>
                                <option value=""><?= $s->trans('select_family'); ?></option>
                                <?php foreach ($families as $family) { ?>
                                    <option value="<?= $family->getFamily_id(); ?>"
                                        <?php $s->check_select(($body['family_id'] ?? $family->getFamily_id()), $family->getFamily_id()); ?>
                                    ><?= $family->getFamily_name(); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        
                         <div class="form-group">
                            <label for="unit_id">
                                <?= $s->trans('product_unit'); ?>
                            </label>
                            <select name="unit_id" id="unit_id" class="form-control" required>
                                <option value=""><?= $s->trans('select_unit'); ?></option>
                                <?php foreach ($units as $unit) { ?>
                                    <option value="<?= $unit->getUnit_id(); ?>"
                                        <?php $s->check_select(($body['unit_id'] ?? $unit->getUnit_id()), $unit->getUnit_id()); ?>
                                    ><?= $unit->getUnit_name() . '/' . $unit->getUnit_name_plrl(); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        
                         <div class="form-group">
                            <label for="unit_peppol_id">
                                <?= $translator->translate('invoice.product.peppol.unit'); ?>
                            </label>
                            <span>
                                <a href="<?= $urlGenerator->generate('unitpeppol/index'); ?>"><i class="fa fa-pencil fa-fw"></i></a>
                            </span> 
                            <select name="unit_peppol_id" id="unit_peppol_id" class="form-control">
                                <option value=""><?= $s->trans('select_unit'); ?></option>
                                <?php foreach ($unit_peppols as $unit_peppol) { ?>
                                    <option value="<?= $unit_peppol->getId(); ?>"
                                        <?php $s->check_select(($body['unit_peppol_id'] ?? $unit_peppol->getId()), $unit_peppol->getId()); ?>
                                    ><?= $unit_peppol->getCode() . ' --- '. $unit_peppol->getName(). ' --- ' .$unit_peppol->getDescription(); ?></option>
                                <?php } ?>
                            </select>
                                   
                        </div>

                        <div class="form-group">
                            <label for="tax_rate_id" required>
                                <?= $s->trans('tax_rate'); ?>  (Tip: Create a zero tax rate <a href="<?= $urlGenerator->generate('taxrate/add');?>">here</a>)
                            </label>
                            <select name="tax_rate_id" id="tax_rate_id" class="form-control" required>
                                <option value=""> <?= $s->trans('tax_rate'); ?></option>
                                <?php foreach ($tax_rates as $tax_rate) { ?>
                                    <option value="<?= $tax_rate->getTax_rate_id(); ?>"
                                        <?php $s->check_select(($body['tax_rate_id'] ?? $tax_rate->getTax_rate_id()), $tax_rate->getTax_rate_id()); ?>>
                                        <?= $tax_rate->getTax_rate_name()
                                            . ' (' . ($s->format_amount($tax_rate->getTax_rate_percent()) ?: '0.00') . '%)'; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="product_sku">
                                <?= $s->trans('product_sku'); ?>
                            </label>

                            <input type="text" name="product_sku" id="product_sku" class="form-control"
                                   value="<?= Html::encode($body['product_sku'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_sii_schemeid">
                                <?= $translator->translate('invoice.product.sii.schemeid'); ?>
                            </label>
                            <select name="product_sii_schemeid" id="product_sii_schemeid"
                                class="form-control">
                                <option value=""><?= $s->trans('none'); ?></option>
                                <?php foreach ($standard_item_identification_schemeids as $key => $value) { ?>
                                    <option value="<?= $value['Id']; ?>" 
                                        <?php
                                            $s->check_select($body['product_sii_schemeid'] ?? '', $value['Id']); ?>>
                                        <?= $value['Id'].' ---- '.$value['Name'].' ---- '.$value['Description']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_sii_id">
                                <?= $translator->translate('invoice.product.sii.id'); ?>
                            </label>
                            <input type="text" name="product_sii_id" id="product_sii_id" class="form-control"
                                   value="<?= Html::encode($body['product_sii_id'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_icc_listid">
                                <?= $translator->translate('invoice.product.icc.listid'); ?>
                            </label>
                            <select name="product_icc_listid" id="product_icc_listid"
                                class="form-control">
                                <option value=""><?= $s->trans('none'); ?></option>
                                <?php foreach ($item_classification_code_listids as $key => $value) { ?>
                                    <option value="<?= $value['Id']; ?>" 
                                        <?php
                                            $s->check_select($body['product_icc_listid'] ?? '', $value['Id']); ?>>
                                        <?= $value['Id'].' ---- '.$value['Name'].' ---- '.$value['Description']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_icc_listversionid">
                                <?= $translator->translate('invoice.product.icc.listversionid'); ?>
                            </label>
                            <input type="text" name="product_icc_listversionid" id="product_icc_listversionid" class="form-control"
                                   value="<?= Html::encode($body['product_icc_listversionid'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_icc_id">
                                <?= $translator->translate('invoice.product.icc.id'); ?>
                            </label>
                            <input type="text" name="product_icc_id" id="product_icc_id" class="form-control"
                                   value="<?= Html::encode($body['product_icc_id'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_country_of_origin_code" <?= $s->where('default_country'); ?>>
                                <?= $translator->translate('invoice.product.country.of.origin.code'); ?>
                            </label>
                            <select name="product_country_of_origin_code" id="product_country_of_origin_code"
                                class="form-control">                               
                                <option value=""><?= $s->trans('none'); ?></option>
                                <?php foreach ($countries as $cldr => $country) { ?>
                                    <option value="<?= $cldr; ?>" 
                                        <?php
                                            $s->check_select($body['product_country_of_origin_code'] ?? '', $cldr); ?>>
                                        <?= $cldr.' ---- '.$country ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="product_name">
                                <?= $s->trans('product_name'); ?>
                            </label>
                            <input type="text" name="product_name" id="product_name" class="form-control" 
                                   value="<?= Html::encode($body['product_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="product_description">
                                <?= $s->trans('product_description'); ?>
                            </label>

                            <textarea name="product_description" id="product_description" class="form-control"
                                      rows="3"><?= Html::encode($body['product_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_additional_item_property_name">
                                <?= $translator->translate('invoice.product.additional.item.property.name'); ?>
                            </label>
                            <input type="text" name="product_additional_item_property_name" id="product_additional_item_property_name" class="form-control" 
                                   value="<?= Html::encode($body['product_additional_item_property_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_additional_item_property_value">
                                <?= $translator->translate('invoice.product.additional.item.property.value'); ?>
                            </label>
                            <input type="text" name="product_additional_item_property_value" id="product_additional_item_property_value" class="form-control" 
                                   value="<?= Html::encode($body['product_additional_item_property_value'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="product_price">
                                <?= $s->trans('product_price'); ?>
                            </label>

                            <div class="input-group has-feedback">
                                <input type="text" name="product_price" id="product_price" class="form-control"
                                       value="<?= $s->format_amount((float)($body['product_price'] ?? 0.00)); ?>">
                                <span class="input-group-text"><?= $s->get_setting('currency_symbol'); ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="product_price_base_quantity">
                                <?= $translator->translate('invoice.product.price.base.quantity'); ?>
                            </label>

                            <div class="input-group has-feedback">
                                <input type="text" name="product_price_base_quantity" id="product_price_base_quantity" class="form-control"
                                       value="<?= (float)($body['product_price_base_quantity'] ?? 1.00); ?>">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xs-12 col-md-6">

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?= $s->trans('extra_information'); ?>
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label for="provider_name">
                                <?= $s->trans('provider_name'); ?>
                            </label>

                            <input type="text" name="provider_name" id="provider_name" class="form-control"
                                   value="<?= Html::encode($body['provider_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="purchase_price">
                                <?= $s->trans('purchase_price'); ?>
                            </label>

                            <div class="input-group has-feedback">
                                <input type="text" name="purchase_price" id="purchase_price" class="form-control"
                                       value="<?= $s->format_amount((float)($body['purchase_price'] ?? 0.00)); ?>">
                                <span class="input-group-text"><?= $s->get_setting('currency_symbol'); ?></span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?= $s->trans('invoice_sumex'); ?>
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label for="product_tariff">
                                <?= $s->trans('product_tariff'); ?>
                            </label>

                            <input type="text" name="product_tariff" id="product_tariff" class="form-control"
                                   value="<?= Html::encode($body['product_tariff'] ?? ''); ?>">
                        </div>

                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?= $translator->translate('invoice.product.custom.fields'); ?>
                    </div>
                    <div class="panel-body">
                      <?php foreach ($custom_fields as $custom_field): ?>
                          <?=
                          $cvH->print_field_for_form($product_custom_values,
                            $custom_field,
                            // Custom values to fill drop down list if a dropdown box has been created
                            $custom_values,
                            // Class for div surrounding input
                            '',
                            // Class surrounding above div
                            'form-group',
                            // Label class similar to above
                            'control-label');
                          ?>
                      <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>