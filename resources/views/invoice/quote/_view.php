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

?>
<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>
    <?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?><?= $translator->translate('i.edit'); ?><?= Html::closeTag('h1'); ?>
        <?= Form::tag()->post($urlGenerator->generate(...$action))
                       ->enctypeMultipartFormData()
                       ->csrf($csrf)
                       ->id('QuoteForm')
                       ->open()?>
                <?= Html::openTag('div', ['class' => 'container']); ?>
                    <?= Html::openTag('div', ['class' => 'row']); ?>
                        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
                            <?= Html::openTag('div',['class' => 'card-header']); ?>
                                <?= Html::openTag('div'); ?>
                                     <?= Field::hidden($form,'number')
                                         ->hideLabel(true);
                                     ?>
                                <?= Html::closeTag('div'); ?>
                                <?= Html::openTag('div'); ?>
                                    <?= Field::select($form, 'client_id')
                                        ->label($translator->translate('i.client'))
                                        ->addInputAttributes(['class' => 'form-control'])
                                        ->value($form->getClient_id())
                                        ->prompt($translator->translate('i.none'))
                                        ->optionsData($optionsData['client'])
                                        ->disabled(true)
                                    ?>
                                <?= Html::closeTag('div'); ?>            
                                <?= Html::openTag('div'); ?>
                                    <?= Field::select($form, 'group_id')
                                        ->label($translator->translate('i.quote_group'))
                                        ->addInputAttributes(['class' => 'form-control'])
                                        ->value($form->getGroup_id() ?? $defaultGroupId)
                                        ->prompt($translator->translate('i.none'))
                                        ->optionsData($optionsData['group'])
                                        ->disabled(true); 
                                    ?>
                                <?= Html::closeTag('div'); ?>   
                            
                            <?php if ($del_count > 0) { ?>
                                <?= Html::openTag('div'); ?>
                                    <?= Field::select($form, 'delivery_location_id')
                                        ->label($translator->translate('invoice.invoice.delivery.location'))
                                        ->addInputAttributes(['class' => 'form-control'])
                                        ->value($form->getDelivery_location_id())
                                        ->prompt($translator->translate('i.none'))
                                        ->optionsData($optionsData['deliveryLocation'])
                                        ->disabled(true); 
                                    ?>
                                <?= Html::closeTag('div'); ?>           
                                <?php if (null!==$del->getId()) { ?>
                                <span class="input-group-text">
                                    <a href="<?= $urlGenerator->generate('del/edit', ['id'=> $del->getId()]); ?>"><i class="fa fa-pencil fa-fw"></i></a>
                                </span>  
                                <?php } ?>
                                <?php
                            } else {
                                echo Html::a($translator->translate('invoice.invoice.delivery.location.add'), $urlGenerator->generate('del/add', ['client_id' => $inv->getClient_id()]), ['class' => 'btn btn-danger btn-lg mt-3']);
                            }
                            ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::date($form,'date_created')
                                ->label($translator->translate('invoice.invoice.date.issued'))
                                ->value($form->getDate_created() ? ($form->getDate_created())->format('Y-m-d') : '')
                                ->disabled(true); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::password($form,'password')
                                ->label($translator->translate('i.password'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value(Html::encode($form->getPassword()))
                                ->placeholder($translator->translate('i.password'))
                                ->disabled(true); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::select($form, 'status_id')
                                ->label($translator->translate('i.status'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value($form->getStatus_id())
                                ->prompt($translator->translate('i.none'))
                                ->optionsData($optionsData['quoteStatus'])
                                ->disabled(true); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                        <?php // If the quote is in draft status; do not show the url_key
                            if ($form->getStatus_id() == 1) { ?>
                            <?= Field::hidden($form,'url_key')
                                ->hideLabel(true);
                            ?>
                        <?php } ?>
                        <?php if ($form->getStatus_id() > 1) { ?>
                            <?= Field::text($form,'url_key')
                                ->hideLabel(false)
                                ->label(($form->getStatus_id() ?? 1) > 1 ? $translator->translate('i.guest_url') : '') 
                                ->addInputAttributes($editInputAttributesUrlKey)
                                ->disabled(true);
                            ?>
                        <?php } ?>
                        <?= Html::closeTag('div'); ?>
                    <?php   if ($vat === false) { ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::text($form,'discount_amount')
                                ->hideLabel(false)
                                ->label($translator->translate('i.discount_amount').' '. $s->get_setting('currency_symbol'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value($s->format_amount((float)($form->getDiscount_amount() ?? 0.00)))
                                ->placeholder($translator->translate('i.discount_amount'))
                                ->disabled(true); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::text($form,'discount_percent')
                                ->label($translator->translate('i.discount_percent'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value(Html::encode($s->format_amount((float)($form->getDiscount_percent() ?? 0.00))))
                                ->placeholder($translator->translate('i.discount_percent'))
                                ->disabled(true); 
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
                                ->disabled(true); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::textarea($form,'note')
                                ->label($translator->translate('invoice.invoice.note'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value(Html::encode($form->getNote()))
                                ->placeholder($translator->translate('i.note'))
                                ->disabled(true); 
                            ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::hidden($form, 'inv_id'); ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::hidden($form, 'id'); ?>
                        <?= Html::closeTag('div'); ?>
                    <?php foreach ($custom_fields as $custom_field): ?>
                        <?php echo $cvH->print_field_for_form($custom_field, $quoteCustomForm, $translator, $quote_custom_values, $custom_values); ?>
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