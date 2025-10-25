<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Quote\QuoteForm $form
 * @var App\Invoice\QuoteCustom\QuoteCustomForm $quoteCustomForm
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var App\Widget\FormFields $formFields
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $customFields
 * @var array $optionsData
 * @var array $quoteCustomValues
 * @var array $customValues
 * @var string $alert
 * @var string $csrf
 * @var string $actionName
 * @var string $returnUrlAction
 * @var string $title
 * @var int $delCount
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['client']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['contract']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['group']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['deliveryLocation']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['quoteStatus']
 *
 */

$vat = $s->getSetting('enable_vat_registration') === '1' ? true : false;

/**
 * Related logic: see alert if there are no delivery locations associated with this quote
 */
echo $alert;

?>
<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
    <?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?><?= $translator->translate('edit'); ?><?= Html::closeTag('h1'); ?>
        <?= Form::tag()->post($urlGenerator->generate($actionName, $actionArguments))
                       ->enctypeMultipartFormData()
                       ->csrf($csrf)
                       ->id('QuoteForm')
                       ->open()?>
                <?= Html::openTag('div', ['class' => 'container']); ?>
                    <?= Html::openTag('div', ['class' => 'row']); ?>
                        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
                            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                                <?= Html::openTag('div'); ?>
                                     <?= Field::hidden($form, 'number')
                                         ->hideLabel(false)
                                         ->label($translator->translate('quote'))
                                         ->addInputAttributes([
                                             'class' => 'form-control',
                                             'readonly' => 'readonly',
                                         ])
                                         ->value(Html::encode($form->getNumber()))
?>
                                <?= Html::closeTag('div'); ?>
                                <?= $formFields->clientSelect($form, $optionsData, 'user.account.clients'); ?>
                                <?= $formFields->groupSelect($form, $optionsData); ?>   
                            
                            <?php if ($delCount > 0) { ?>
                                <?= Html::openTag('div'); ?>
                                    <?= Field::select($form, 'delivery_location_id')
    ->label($translator->translate('delivery.location'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value($form->getDelivery_location_id())
    ->prompt($translator->translate('none'))
    ->optionsData($optionsData['deliveryLocation'])
    ->hint($translator->translate('hint.this.field.is.not.required'));
                                ?>
                                <?= Html::closeTag('div'); ?>           
                                <?php if (null !== $form->getDelivery_location_id() && !empty($form->getDelivery_location_id())) { ?>
                                <span class="input-group-text">
                                    <!-- Remember second set of square brackets in urlGenerator are query Parameters NOT currentRoute arguments -->
                                    <a href="<?= $urlGenerator->generate(
                                        'del/edit',
                                        // Argument Parameters
                                        [
                                            'id' => $form->getDelivery_location_id(),
                                        ],
                                        // Query Parameters
                                        [
                                            'origin' => 'quote',
                                            'origin_id' => $quote->getId(),
                                            'action' => 'edit',
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
                                            'origin' => 'quote',
                                            'origin_id' => $quote->getId(),
                                            'action' => $returnUrlAction,
                                        ],
                                    ),
                                    [
                                        'class' => 'btn btn-danger btn-lg mt-3',
                                    ],
                                );
                            }
?>
                        <?= Html::openTag('br'); ?>    
                        <?= Html::openTag('br'); ?>    
                        <?= $formFields->dateCreatedField($form, 'date.issued'); ?>
                        <?= $formFields->passwordField($form); ?>
                        <?= $formFields->statusSelect($form, $optionsData, 'quoteStatus'); ?>
                        <?= Html::openTag('div'); ?>
                        <?php // If the quote is in draft status; do not show the url_key
if ($form->getStatus_id() == 1) { ?>
                            <?= Field::hidden($form, 'url_key')
    ->hideLabel(true);
    ?>
                        <?php } ?>
                        <?php if ($form->getStatus_id() > 1) { ?>
                            <?= Field::text($form, 'url_key')
        ->hideLabel(false)
        ->label(($form->getStatus_id()) > 1 ? $translator->translate('guest.url') : '');
                            ?>
                        <?php } ?>
                        <?= Html::closeTag('div'); ?>
                    <?php   if ($vat === false) { ?>
                        <?= $formFields->discountFields($form); ?>
                        <?= $formFields->notesField($form); ?>    
                    <?php } ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::hidden($form, 'inv_id')
                            ->hideLabel(); ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::closeTag('div'); ?>
                    <?php
                        /**
                         * @var App\Invoice\Entity\CustomField $customField
                         */
                        foreach ($customFields as $customField): ?>
                        <?php $cvH->print_field_for_form($customField, $quoteCustomForm, $translator, $quoteCustomValues, $customValues); ?>
                    <?php endforeach; ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>    
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        <?= $button::backSave(); ?>
    <?= Html::closeTag('div'); ?>
    
<?= Html::closeTag('div'); ?>

<?= Html::closeTag('form'); ?>

<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>  