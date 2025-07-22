<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Entity\Inv                            $inv
 * @var App\Invoice\Helpers\CustomValuesHelper            $cvH
 * @var App\Invoice\Inv\InvForm                           $form
 * @var App\Invoice\InvCustom\InvCustomForm               $invCustomForm
 * @var App\Invoice\PostalAddress\PostalAddressRepository $paR
 * @var App\Invoice\Setting\SettingRepository             $s
 * @var App\Widget\Button                                 $button
 * @var Yiisoft\Translator\TranslatorInterface            $translator
 * @var Yiisoft\Router\UrlGeneratorInterface              $urlGenerator
 * @var array                                             $customFields
 * @var array                                             $customValues
 * @var array                                             $editInputAttributesPaymentMethod
 * @var array                                             $editInputAttributesUrlKey
 * @var array                                             $invCustomValues
 * @var array                                             $invs
 * @var array                                             $users
 * @var int                                               $contractCount
 * @var int                                               $defaultGroupId
 * @var int                                               $delCount
 * @var int                                               $deliveryCount
 * @var int                                               $postalAddressCount
 * @var string                                            $alert
 * @var string                                            $csrf
 * @var string                                            $actionName
 * @var string                                            $noteOnTaxPoint
 * @var string                                            $title
 *
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['client']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['contract']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['delivery']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['deliveryLocation']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['invoiceStatus']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['group']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['paymentMethod']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['paymentTerm']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['postalAddress']
 */
$vat = '1' == $s->getSetting('enable_vat_registration') ? true : false;
if ($vat) {
    echo $noteOnTaxPoint;
}

?>
<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
    <?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?><?php echo $translator->translate('edit'); ?><?php echo Html::closeTag('h1'); ?>
        <?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('InvForm')
    ->open(); ?>
                <?php echo Html::openTag('div', ['class' => 'container']); ?>
                    <?php echo Html::openTag('div', ['class' => 'row']); ?>
                        <?php echo Html::openTag('div', ['class' => 'col card mb-3']); ?>
                            <?php echo Html::openTag('div', ['class' => 'card-header']); ?>
                                <?php echo Html::openTag('div'); ?>
                                     <?php echo Field::hidden($form, 'number')
    ->hideLabel(false)
    ->label($translator->translate('invoice'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getNumber()));
?>
                                <?php echo Html::closeTag('div'); ?>
                                <?php echo Html::openTag('div'); ?>
                                    <?php echo Field::select($form, 'client_id')
    ->label($translator->translate('client'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value($form->getClient_id())
    ->prompt($translator->translate('none'))
    ->optionsData($optionsData['client'])
    ->hint($translator->translate('hint.this.field.is.required'));

?>
                                <?php echo Html::closeTag('div'); ?>            
                                <?php echo Html::openTag('div'); ?>
                                    <?php echo Field::select($form, 'group_id')
                                    ->label($translator->translate('group'))
                                    ->addInputAttributes(['class' => 'form-control'])
                                    ->value($form->getGroup_id() ?? $defaultGroupId)
                                    ->prompt($translator->translate('none'))
                                    ->optionsData($optionsData['group'])
                                    ->hint($translator->translate('hint.this.field.is.required'));
?>
                                <?php echo Html::closeTag('div'); ?>   

                            <?php if ($deliveryCount > 0) { ?>
                                <?php echo Html::openTag('div'); ?>
                                    <?php echo Field::select($form, 'delivery_id')
                                ->label($translator->translate('delivery'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value($form->getDelivery_id())
                                ->prompt($translator->translate('none'))
                                ->optionsData($optionsData['delivery'])
                                ->hint($translator->translate('hint.this.field.is.required'));
                                ?>
                                <?php echo Html::closeTag('div'); ?>            
                                <?php if (!empty($inv->getDelivery_id())) { ?>
                                <span class="input-group-text">
                                    <a href="<?php echo $urlGenerator->generate('delivery/edit', ['id' => $inv->getDelivery_id()]); ?>"><i class="fa fa-pencil fa-fw"></i>
                                        <?php echo $translator->translate('delivery'); ?>
                                    </a>
                                </span>  
                                <?php } ?>
                                <span class="input-group-text">
                                    <a href="<?php echo $s->href('stand_in_code'); ?>" <?php echo $s->where('stand_in_code'); ?>><i class="fa fa-question fa-fw"></i></a>
                                </span>
                                <?php
                            } else {
                                echo Html::a($translator->translate('delivery.add'), $urlGenerator->generate('delivery/add', ['inv_id' => $inv->getId()]), ['class' => 'btn btn-danger btn-lg mt-3']);
                            }
?>
                            <?php echo Html::br(); ?>
                            <?php echo Html::br(); ?>
                            <?php if ($delCount > 0) { ?>
                                <?php echo Html::openTag('div'); ?>
                                    <?php echo Field::select($form, 'delivery_location_id')
    ->label($translator->translate('delivery.location'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value($form->getDelivery_location_id())
    ->prompt($translator->translate('none'))
    ->disabled(false)
    ->optionsData($optionsData['deliveryLocation'])
    ->hint($translator->translate('hint.this.field.is.not.required'));
                                ?>
                                <?php echo Html::closeTag('div'); ?> 
                                <?php if (null !== $form->getDelivery_location_id() && '0' != $form->getDelivery_location_id()) { ?>
                                    
                                    <span class="input-group-text">
                                    <a href="<?php echo $urlGenerator->generate(
                                        'del/edit',
                                        [
                                            'id' => $form->getDelivery_location_id(),
                                        ],
                                        [
                                            /*
                                             * Query parameters used to build a return url back to this form
                                             * in DeliveryController edit function
                                             * once the delivery location has been edited
                                             * @see vendor\yiisoft\router\UrlGeneratorInterface;
                                             */
                                            'origin'    => 'inv',
                                            'origin_id' => $form->getId(),
                                            'action'    => 'edit',
                                        ],
                                    ); ?>"><i class="fa fa-pencil fa-fw"></i><?php echo $translator->translate('delivery.location'); ?>
                                    </a>
                                </span>  
                                <?php } ?>
                                <?php
                            } else {
                                echo Html::a(
                                    $translator->translate('delivery.location.add'),
                                    $urlGenerator->generate(
                                        'del/add',
                                        [
                                            'client_id' => $inv->getClient_id(),
                                        ],
                                        [
                                            'origin'    => 'inv',
                                            'origin_id' => $inv->getId(),
                                            'action'    => 'edit',
                                        ],
                                    ),
                                    ['class' => 'btn btn-danger btn-lg mt-3'],
                                );
                            }
?>
                        <?php if ($contractCount > 0) { ?>
                            <?php echo Html::openTag('div'); ?>
                                <?php echo Field::select($form, 'contract_id')
    ->label(null === $form->getContract_id()
            ? $translator->translate('contract.none')
            : $translator->translate('contract'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value($form->getContract_id())
    ->prompt($translator->translate('none'))
    ->optionsData($optionsData['contract'])
    ->hint($translator->translate('hint.this.field.is.not.required'));
                            ?>
                            <?php echo Html::closeTag('div'); ?>         
                            <?php
                        } ?>
                        <?php echo Html::a($translator->translate('contract.add'),
                            $urlGenerator->generate(
                                'contract/add',
                                ['client_id' => $inv->getClient_id()],
                            ),
                            ['class' => 'btn btn-info btn-lg mt-3']); ?>                        
                        <?php if ($postalAddressCount > 0) { ?>
                                <?php echo Html::openTag('div'); ?>
                                    <?php echo Field::select($form, 'postal_address_id')
                            ->label($translator->translate('client.postaladdress.available'))
                            ->addInputAttributes(['class' => 'form-control'])
                            ->value($form->getPostal_address_id())
                            ->prompt($translator->translate('none'))
                            ->optionsData($optionsData['postalAddress'])
                            ->disabled(false)
                            ->hint($translator->translate('hint.this.field.is.not.required'));
                            ?>
                                <?php echo Html::closeTag('div'); ?> 
                                <?php if (null !== $form->getPostal_address_id() && '0' != $form->getPostal_address_id()) { ?>
                                    <span class="input-group-text">
                                    <a href="<?php echo $urlGenerator->generate(
                                        'postaladdress/edit',
                                        [
                                            'id' => $form->getPostal_address_id(),
                                        ],
                                        [
                                            /*
                                             * Query parameters used to build a return url back to this form
                                             * in PostalAddressController edit function
                                             * once the postal address location has been edited
                                             * @see vendor\yiisoft\router\UrlGeneratorInterface;
                                             */
                                            'origin'    => 'inv',
                                            'origin_id' => $form->getId(),
                                            'action'    => 'edit',
                                        ],
                                    ); ?>"><i class="fa fa-pencil fa-fw"></i><?php echo $translator->translate('client.postaladdress'); ?>
                                    </a>
                                </span>  
                                <?php } ?>
                                <?php
                        } else {
                            echo Html::a(
                                $translator->translate('client.postaladdress.add'),
                                $urlGenerator->generate(
                                    'postaladdress/add',
                                    [
                                        'client_id' => $inv->getClient_id(),
                                    ],
                                    [
                                        'origin'    => 'inv',
                                        'origin_id' => $inv->getId(),
                                        'action'    => 'edit',
                                    ],
                                ),
                                ['class' => 'btn btn-danger btn-lg mt-3'],
                            );
                        }
?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::hidden($form, 'creditinvoice_parent_id')
    ->hideLabel(true)
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getCreditinvoice_parent_id()) ?: 0);
?>
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::Tag('br'); ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::date($form, 'date_created')
    ->label($translator->translate('date.issued'))
    ->value(!is_string($form->getDate_created()) ? $form->getDate_created()?->format('Y-m-d') : '')
    ->hint($translator->translate('hint.this.field.is.required'));
?>
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::date($form, 'date_supplied')
    ->label($translator->translate('date.supplied'))
    ->value(!is_string($form->getDate_supplied()) ? $form->getDate_supplied()?->format('Y-m-d') : '')
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>    
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::date($form, 'date_tax_point')
                            ->label($translator->translate('tax.point'))
                            ->value(!is_string($form->getDate_tax_point()) ? $form->getDate_tax_point()?->format('Y-m-d') : '')
                            ->hint($translator->translate('hint.this.field.is.not.required'));
?>    
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::password($form, 'password')
                            ->label($translator->translate('password'))
                            ->addInputAttributes(['class' => 'form-control'])
                            ->value(Html::encode($form->getPassword()))
                            ->placeholder($translator->translate('password'))
                            ->hint($translator->translate('hint.this.field.is.not.required'));
?>
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::select($form, 'status_id')
    ->label($translator->translate('status'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value($form->getStatus_id())
    ->prompt($translator->translate('none'))
    ->optionsData($optionsData['invoiceStatus'])
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::select($form, 'payment_method')
    ->hideLabel(false)
    ->label($translator->translate('payment.method'))
    ->value($form->getPayment_method())
    ->prompt($translator->translate('none'))
    ->optionsData($optionsData['paymentMethod'])
    ->addInputAttributes($editInputAttributesPaymentMethod);
?>
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::text($form, 'url_key')
    ->hideLabel(false)
    ->label(($form->getStatus_id() ?? 1) > 1 ? $translator->translate('guest.url') : '')
    ->addInputAttributes($editInputAttributesUrlKey);
?>
                        <?php echo Html::closeTag('div'); ?>
                    <?php if (false === $vat) { ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::text($form, 'discount_amount')
    ->hideLabel(false)
    ->disabled($form->getDiscount_percent() > 0.00 && 0.00 == $form->getDiscount_amount() ? true : false)
    ->label($translator->translate('discount.amount').' '.$s->getSetting('currency_symbol'))
    ->addInputAttributes(['class' => 'form-control', 'id' => 'inv_discount_amount'])
    ->value(Html::encode($s->format_amount($form->getDiscount_amount() ?? 0.00)))
    ->placeholder($translator->translate('discount.amount'));
                        ?>
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::text($form, 'discount_percent')
                            ->label($translator->translate('discount.percent'))
                            ->disabled(($form->getDiscount_amount() > 0.00 && 0.00 == $form->getDiscount_percent()) ? true : false)
                            ->addInputAttributes(['class' => 'form-control', 'id' => 'inv_discount_percent'])
                            ->value(Html::encode($s->format_amount($form->getDiscount_percent() ?? 0.00)))
                            ->placeholder($translator->translate('discount.percent'));
                        ?>
                        <?php echo Html::closeTag('div'); ?>
                    <?php } ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::select($form, 'terms')
                            ->label($translator->translate('terms'))
                            ->addInputAttributes(['class' => 'form-control'])
                            ->value(Html::encode($form->getTerms()))
                            ->prompt($translator->translate('none'))
                            ->optionsData($optionsData['paymentTerm'])
                            ->hint($translator->translate('hint.this.field.is.not.required'));
?>
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::textarea($form, 'note')
    ->label($translator->translate('note'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getNote()))
    ->placeholder($translator->translate('note'))
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::text($form, 'document_description')
    ->label($translator->translate('description.document'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getDocumentDescription()))
    ->placeholder($translator->translate('description.document'))
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
                        <?php echo Html::closeTag('div'); ?>
                    <?php
                        /**
                         * @var App\Invoice\Entity\CustomField $customField
                         */
                        foreach ($customFields as $customField) { ?>
                        <?php $cvH->print_field_for_form($customField, $invCustomForm, $translator, $invCustomValues, $customValues); ?>
                    <?php } ?>
                    <?php echo Html::closeTag('div'); ?>
                <?php echo Html::closeTag('div'); ?>    
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
    
    <?php echo $button::backSave(); ?>
    
<?php echo Html::closeTag('div'); ?>

<?php echo Html::closeTag('form'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?> 
