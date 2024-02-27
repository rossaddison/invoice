<?php
declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
$vat = $s->get_setting('enable_vat_registration') === '1' ? true : false;
echo $note_on_tax_point;

?>
<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>
    <?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?><?= $translator->translate('i.edit'); ?><?= Html::closeTag('h1'); ?>
        <?= Form::tag()->post($urlGenerator->generate(...$action))->enctypeMultipartFormData()->csrf($csrf)->id('InvForm')->open()?>
                <?= Html::openTag('div', ['class' => 'container']); ?>
                    <?= Html::openTag('div', ['class' => 'row']); ?>
                        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
                            <?= Html::openTag('div',['class' => 'card-header']); ?>
                                <?= Html::openTag('div'); ?>
                                     <?= Field::hidden($form,'number')
                                         ->hideLabel(false)
                                         ->label($translator->translate('i.invoice'))
                                         ->addInputAttributes([
                                             'class' => 'form-control',
                                             'readonly' => 'readonly',
                                           ])
                                         ->value(Html::encode($form->getNumber()))
                                     ?>
                                <?= Html::closeTag('div'); ?>
                                <?= Html::openTag('div'); ?>
                                    <?= Field::select($form, 'client_id')
                                        ->label($translator->translate('i.client'))
                                        ->addInputAttributes(['class' => 'form-control'])
                                        ->value($form->getClient_id())
                                        ->prompt($translator->translate('i.none'))
                                        ->optionsData($optionsData['client'])
                                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 

                                    ?>
                                <?= Html::closeTag('div'); ?>            
                                <?= Html::openTag('div'); ?>
                                    <?= Field::select($form, 'group_id')
                                        ->label($translator->translate('i.invoice_group'))
                                        ->addInputAttributes(['class' => 'form-control'])
                                        ->value($form->getGroup_id() ?? $defaultGroupId)
                                        ->prompt($translator->translate('i.none'))
                                        ->optionsData($optionsData['group'])
                                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                                    ?>
                                <?= Html::closeTag('div'); ?>   

                            <?php if ($delivery_count > 0) { ?>
                                <?= Html::openTag('div'); ?>
                                    <?= Field::select($form, 'delivery_id')
                                        ->label($translator->translate('invoice.invoice.delivery'))
                                        ->addInputAttributes(['class' => 'form-control'])
                                        ->value($form->getDelivery_id())
                                        ->prompt($translator->translate('i.none'))
                                        ->optionsData($optionsData['delivery'])
                                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                                    ?>
                                <?= Html::closeTag('div'); ?>            
                                <?php if (null!==$delivery->getId()) { ?>
                                <span class="input-group-text">
                                    <a href="<?= $urlGenerator->generate('delivery/edit', ['id'=> $delivery->getId()]); ?>"><i class="fa fa-pencil fa-fw"></i></a>
                                </span>  
                                <?php } ?>
                                <span class="input-group-text">
                                    <a href="<?= $s->href('stand_in_code'); ?>" <?= $s->where('stand_in_code'); ?>><i class="fa fa-question fa-fw"></i></a>
                                </span>
                                <?php
                            } else {
                                echo Html::a($translator->translate('invoice.invoice.delivery.add'), $urlGenerator->generate('delivery/add', ['inv_id' => $inv->getId()]), ['class' => 'btn btn-danger btn-lg mt-3']);
                            }
                            ?>
                            <?= html::br(); ?>
                            <?= html::br(); ?>
                            <?php if ($del_count > 0) { ?>
                                <?= Html::openTag('div'); ?>
                                    <?= Field::select($form, 'delivery_location_id')
                                        ->label($translator->translate('invoice.invoice.delivery.location'))
                                        ->addInputAttributes(['class' => 'form-control'])
                                        ->value($form->getDelivery_location_id())
                                        ->prompt($translator->translate('i.none'))
                                        ->optionsData($optionsData['deliveryLocation'])
                                        ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                                    ?>
                                <?= Html::closeTag('div'); ?> 
                                <?php if (null!==$form->getDelivery_location_id() && $form->getDelivery_location_id() <> '0') { ?>
                                    
                                    <span class="input-group-text">
                                    <a href="<?= $urlGenerator->generate('del/edit', [
                                                'id' => $form->getDelivery_location_id()
                                            ], 
                                            [
                                                /**
                                                 * Query parameters used to build a return url back to this form 
                                                 * in DeliveryController edit function
                                                 * once the delivery location has been edited
                                                 * @see vendor\yiisoft\router\UrlGeneratorInterface;
                                                 */
                                                'origin' => 'inv', 
                                                'origin_id' => $form->getId(), 
                                                'action' => 'edit'
                                            ]); ?>"><i class="fa fa-pencil fa-fw"></i><?= $translator->translate('invoice.delivery.location') ?>
                                    </a>
                                </span>  
                                <?php } ?>
                                <?php
                            } else {
                                echo Html::a($translator->translate('invoice.invoice.delivery.location.add'), 
                                        $urlGenerator->generate('del/add', 
                                        [
                                            'client_id' => $inv->getClient_id()
                                        ],
                                        [
                                            
                                        ]), ['class' => 'btn btn-danger btn-lg mt-3']);
                            }
                            ?>
                        <?php if ($contract_count > 0) { ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::select($form, 'contract_id')
                                    ->label($form->getContract_id() === null 
                                            ? $translator->translate('invoice.invoice.contract.none') 
                                            : $translator->translate('invoice.invoice.contract'))
                                    ->addInputAttributes(['class' => 'form-control'])
                                    ->value($form->getContract_id())
                                    ->prompt($translator->translate('i.none'))
                                    ->optionsData($optionsData['contract'])
                                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                                ?>
                            <?= Html::closeTag('div'); ?>         
                            <?php
                        } else {
                            echo Html::a($translator->translate('invoice.invoice.contract.add'), $urlGenerator->generate('contract/add', ['client_id' => $inv->getClient_id()]), ['class' => 'btn btn-info btn-lg mt-3']);
                        }
                        ?>
                        <?php if ($postal_address_count > 0) { ?>
                            <?= Html::openTag('div'); ?>
                                <?= Field::select($form, 'postaladdress_id')
                                    ->label($translator->translate('invoice.client.postaladdress.available'))
                                    ->addInputAttributes(['class' => 'form-control'])
                                    ->value($form->getPostal_address_id())
                                    ->prompt($translator->translate('i.none'))
                                    ->optionsData($optionsData['postalAddress'])
                                    ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                                ?>
                            <?= Html::closeTag('div'); ?>                         
                            <?php
                        } else {
                            echo Html::a($translator->translate('invoice.client.postaladdress.add'), $urlGenerator->generate('postaladdress/add', ['client_id' => $inv->getClient_id()]), ['class' => 'btn btn-warning btn-lg mt-3']);
                        }
                        ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::hidden($form,'creditinvoice_parent_id')
                                ->hideLabel(true)
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value(Html::encode($form->getCreditinvoice_parent_id()) ?? 0)
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::Tag('br'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::date($form,'date_created')
                                ->label($translator->translate('invoice.invoice.date.issued'))
                                ->value($form->getDate_created() ? ($form->getDate_created())->format('Y-m-d') : '')
                                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::date($form,'date_supplied')
                                ->label($translator->translate('invoice.invoice.date.supplied'))
                                ->value($form->getDate_supplied() ? ($form->getDate_supplied())->format('Y-m-d') : '')
                                ->hint($translator->translate('invoice.hint.this.field.is.not.required'));
                            ?>    
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::date($form,'date_tax_point')
                                ->label($translator->translate('invoice.invoice.tax.point'))
                                ->value($form->getDate_tax_point() ? ($form->getDate_tax_point())->format('Y-m-d') : '')
                                ->hint($translator->translate('invoice.hint.this.field.is.not.required'));
                            ?>    
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::password($form,'password')
                                ->label($translator->translate('i.password'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value(Html::encode($form->getPassword()))
                                ->placeholder($translator->translate('i.password'))
                                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::select($form, 'status_id')
                                ->label($translator->translate('i.status'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value($form->getStatus_id())
                                ->prompt($translator->translate('i.none'))
                                ->optionsData($optionsData['invoiceStatus'])
                                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::select($form,'payment_method')
                                ->hideLabel(false)
                                ->label($translator->translate('i.payment_method'))
                                ->prompt($translator->translate('i.none'))
                                ->optionsData($optionsData['paymentMethod'])
                                ->addInputAttributes($editInputAttributesPaymentMethod)
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::text($form,'url_key')
                                ->hideLabel(false)
                                ->label(($form->getStatus_id() ?? 1) > 1 ? $translator->translate('i.guest_url') : '')
                                ->addInputAttributes($editInputAttributesUrlKey);
                            ?>
                        <?= Html::closeTag('div'); ?>
                    <?php   if ($vat === false) { ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::text($form,'discount_amount')
                                ->hideLabel(false)
                                ->label($translator->translate('i.discount_amount').' '. $s->get_setting('currency_symbol'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value($s->format_amount((float)($form->getDiscount_amount() ?? 0.00)))
                                ->placeholder($translator->translate('i.discount_amount')); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::text($form,'discount_percent')
                                ->label($translator->translate('i.discount_percent'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value(Html::encode($s->format_amount((float)($form->getDiscount_percent() ?? 0.00))))
                                ->placeholder($translator->translate('i.discount_percent')); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                    <?php } ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::select($form, 'terms')
                                ->label($translator->translate('i.terms'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value($form->getTerms())
                                ->prompt($translator->translate('i.none'))
                                ->optionsData($optionsData['paymentTerm'])
                                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::textarea($form,'note')
                                ->label($translator->translate('invoice.invoice.note'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value(Html::encode($form->getNote()))
                                ->placeholder($translator->translate('i.note'))
                                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::text($form,'document_description')
                                ->label($translator->translate('invoice.invoice.description.document'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value(Html::encode($form->getDocumentDescription()))
                                ->placeholder($translator->translate('invoice.invoice.description.document')) 
                                ->hint($translator->translate('invoice.hint.this.field.is.not.required')) 
                            ?>
                        <?= Html::closeTag('div'); ?>
                    <?php foreach ($custom_fields as $custom_field): ?>
                        <?php echo $cvH->print_field_for_form($custom_field, $invCustomForm, $translator, $inv_custom_values, $custom_values); ?>
                    <?php endforeach; ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>    
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    
    <?= $button::back_save($translator); ?>
    
<?= Html::closeTag('div'); ?>

<?= Html::closeTag('form'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>  
