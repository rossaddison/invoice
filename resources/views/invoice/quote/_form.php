<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Entity\Quote                $quote
 * @var App\Invoice\Helpers\CustomValuesHelper  $cvH
 * @var App\Invoice\Quote\QuoteForm             $form
 * @var App\Invoice\QuoteCustom\QuoteCustomForm $quoteCustomForm
 * @var App\Invoice\Setting\SettingRepository   $s
 * @var App\Widget\Button                       $button
 * @var Yiisoft\Translator\TranslatorInterface  $translator
 * @var Yiisoft\Router\UrlGeneratorInterface    $urlGenerator
 * @var array                                   $customFields
 * @var array                                   $optionsData
 * @var array                                   $quoteCustomValues
 * @var array                                   $customValues
 * @var string                                  $alert
 * @var string                                  $csrf
 * @var string                                  $actionName
 * @var string                                  $returnUrlAction
 * @var string                                  $title
 * @var int                                     $delCount
 *
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['client']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['contract']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['group']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['deliveryLocation']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['quoteStatus']
 */
$vat = '1' === $s->getSetting('enable_vat_registration') ? true : false;

/*
 * Related logic: see alert if there are no delivery locations associated with this quote
 */
echo $alert;

?>
<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
    <?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?><?php echo $translator->translate('edit'); ?><?php echo Html::closeTag('h1'); ?>
        <?php echo Form::tag()->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('QuoteForm')
    ->open(); ?>
                <?php echo Html::openTag('div', ['class' => 'container']); ?>
                    <?php echo Html::openTag('div', ['class' => 'row']); ?>
                        <?php echo Html::openTag('div', ['class' => 'col card mb-3']); ?>
                            <?php echo Html::openTag('div', ['class' => 'card-header']); ?>
                                <?php echo Html::openTag('div'); ?>
                                     <?php echo Field::hidden($form, 'number')
    ->hideLabel(false)
    ->label($translator->translate('quote'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'readonly' => 'readonly',
    ])
    ->value(Html::encode($form->getNumber()));
?>
                                <?php echo Html::closeTag('div'); ?>
                                <?php echo Html::openTag('div'); ?>
                                    <?php echo Field::select($form, 'client_id')
    ->label($translator->translate('user.account.clients'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value($form->getClient_id())
    ->prompt($translator->translate('none'))
    ->optionsData($optionsData['client'])
    ->hint($translator->translate('hint.this.field.is.required'));
?>
                                <?php echo Html::closeTag('div'); ?>            
                                <?php echo Html::openTag('div'); ?>
                                    <?php echo Field::select($form, 'group_id')
                                    ->label($translator->translate('quote.group'))
                                    ->addInputAttributes(['class' => 'form-control'])
                                    ->value($form->getGroup_id() ?? 2)
                                    ->prompt($translator->translate('none'))
                                    ->optionsData($optionsData['group'])
                                    ->hint($translator->translate('hint.this.field.is.required'));
?>
                                <?php echo Html::closeTag('div'); ?>   
                            
                            <?php if ($delCount > 0) { ?>
                                <?php echo Html::openTag('div'); ?>
                                    <?php echo Field::select($form, 'delivery_location_id')
                                ->label($translator->translate('delivery.location'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value($form->getDelivery_location_id())
                                ->prompt($translator->translate('none'))
                                ->optionsData($optionsData['deliveryLocation'])
                                ->hint($translator->translate('hint.this.field.is.not.required'));
                                ?>
                                <?php echo Html::closeTag('div'); ?>           
                                <?php if (null !== $form->getDelivery_location_id() && !empty($form->getDelivery_location_id())) { ?>
                                <span class="input-group-text">
                                    <!-- Remember second set of square brackets in urlGenerator are query Parameters NOT currentRoute arguments -->
                                    <a href="<?php echo $urlGenerator->generate(
                                        'del/edit',
                                        // Argument Parameters
                                        [
                                            'id' => $form->getDelivery_location_id(),
                                        ],
                                        // Query Parameters
                                        [
                                            'origin'    => 'quote',
                                            'origin_id' => $quote->getId(),
                                            'action'    => 'edit',
                                        ],
                                    ); ?>"><i class="fa fa-pencil fa-fw"></i><?php echo $translator->translate('delivery.location'); ?></a>
                                </span>  
                                <?php } ?>
                                <?php
                            } else {
                                echo Html::a(
                                    $translator->translate('delivery.location.add'),
                                    $urlGenerator->generate(
                                        'del/add',
                                        // Argument Parameters
                                        [
                                            'client_id' => $quote->getClient_id(),
                                        ],
                                        // Query Parameters
                                        [
                                            'origin'    => 'quote',
                                            'origin_id' => $quote->getId(),
                                            'action'    => $returnUrlAction,
                                        ],
                                    ),
                                    [
                                        'class' => 'btn btn-danger btn-lg mt-3',
                                    ],
                                );
                            }
?>
                        <?php echo Html::openTag('br'); ?>    
                        <?php echo Html::openTag('br'); ?>    
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::date($form, 'date_created')
                            ->label($translator->translate('date.issued'))
                            ->value($form->getDate_created() instanceof DateTimeImmutable ? ($form->getDate_created())->format('Y-m-d') : '')
                            ->hint($translator->translate('hint.this.field.is.required'));
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
    ->optionsData($optionsData['quoteStatus'])
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::openTag('div'); ?>
                        <?php // If the quote is in draft status; do not show the url_key
if (1 == $form->getStatus_id()) { ?>
                            <?php echo Field::hidden($form, 'url_key')
    ->hideLabel(true);
    ?>
                        <?php } ?>
                        <?php if ($form->getStatus_id() > 1) { ?>
                            <?php echo Field::text($form, 'url_key')
        ->hideLabel(false)
        ->label($form->getStatus_id() > 1 ? $translator->translate('guest.url') : '');
                            ?>
                        <?php } ?>
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
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::textarea($form, 'notes')
                            ->label($translator->translate('notes'))
                            ->value(Html::encode($form->getNotes() ?? ''))
                            ->hint($translator->translate('hint.this.field.is.not.required'));
                        ?>
                        <?php echo Html::closeTag('div'); ?>    
                    <?php } ?>
                        <?php echo Html::openTag('div'); ?>
                            <?php echo Field::hidden($form, 'inv_id')
                        ->hideLabel(); ?>
                        <?php echo Html::closeTag('div'); ?>
                        <?php echo Html::closeTag('div'); ?>
                    <?php
                        /**
                         * @var App\Invoice\Entity\CustomField $customField
                         */
                        foreach ($customFields as $customField) { ?>
                        <?php $cvH->print_field_for_form($customField, $quoteCustomForm, $translator, $quoteCustomValues, $customValues); ?>
                    <?php } ?>
                    <?php echo Html::closeTag('div'); ?>
                <?php echo Html::closeTag('div'); ?>    
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo $button::backSave(); ?>
    <?php echo Html::closeTag('div'); ?>
    
<?php echo Html::closeTag('div'); ?>

<?php echo Html::closeTag('form'); ?>

<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>  