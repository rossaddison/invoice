<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * Related logic: see App\Invoice\SalesOrder\SalesOrderController function add()
 * @var App\Invoice\CustomField\CustomFieldRepository $cfR
 * @var App\Invoice\CustomValue\CustomValueRepository $cvR
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\SalesOrder\SalesOrderForm $form
 * @var App\Invoice\SalesOrderCustom\SalesOrderCustomForm $salesOrderCustomForm
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $dels
 * @var array $errors
 * @var array $so_custom_values
 * @var array $custom_values
 * @var int $delCount
 * @var string $actionName
 * @var string $csrf
 * @var string $defaultGroupId
 * @var string $invNumber
 * @var string $terms_and_conditions_file
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string, Stringable|null|scalar> $actionArgumentsDelAdd
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array<array-key, array<array-key, string>|string>> $optionsData
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['client']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['deliveryLocation']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['group']
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsData['salesOrderStatus']
 */
$vat = $s->getSetting('enable_vat_registration') === '1' ? true : false;

?>
<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
    <?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?><?= $title; ?><?= Html::closeTag('h1'); ?>
        <?= Form::tag()->post($urlGenerator->generate($actionName, $actionArguments))
                       ->enctypeMultipartFormData()
                       ->csrf($csrf)
                       ->id('SalesOrderForm')
                       ->open()?>
                <?= Html::openTag('div', ['class' => 'container']); ?>
                    <?= Html::openTag('div', ['class' => 'row']); ?>
                        <?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
                            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                                <?= Html::openTag('div'); ?>
                                    <?= Field::errorSummary($form)
                                        ->errors($errors)
                                        ->header($translator->translate('error.summary'))
                                        ->onlyCommonErrors()
?>
                                <?= Html::closeTag('div'); ?>
                                <?= Html::openTag('div'); ?>
                                	<?= Field::hidden($form, 'quote_id'); ?>
                                <?= Html::closeTag('div'); ?>
                                <?= Html::openTag('div'); ?>
                                     <?= Field::hidden($form, 'number')
                                        ->hideLabel(false)
                                        ->label($translator->translate('salesorder'))
                                        ->addInputAttributes([
                                            'class' => 'form-control',
                                            'readonly' => 'readonly',
                                        ])
                                        ->value(Html::encode($form->getNumber()))
                                   ?>
                                <?= Html::closeTag('div'); ?>
                                <?= Html::openTag('div'); ?>
                                    <?= Field::select($form, 'client_id')
                                        ->label($translator->translate('user.account.clients'))
                                        ->addInputAttributes(['class' => 'form-control'])
                                        ->value($form->getClient_id())
                                        ->prompt($translator->translate('none'))
                                        ->optionsData($optionsData['client'])
                                        ->hint($translator->translate('hint.this.field.is.required'));
                                     ?>
                                <?= Html::closeTag('div'); ?>            
                                <?= Html::openTag('div'); ?>
                                    <?= Field::select($form, 'group_id')
    ->label($translator->translate('salesorder.default.group'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value($form->getGroup_id() ?? $defaultGroupId)
    ->prompt($translator->translate('none'))
    ->optionsData($optionsData['group'])
    ->hint($translator->translate('hint.this.field.is.required'));
?>
                                <?= Html::closeTag('div'); ?>   
                            
                            <?php
                            // If there is no delivery location for this client, create the delivery location now for later use by invoice construction
                            // and to avoid undeliverable locations
                            if ($delCount == 0) {
                                echo Html::a(
                                    $translator->translate('delivery.location.add'),
                                    $urlGenerator->generate('del/add', $actionArgumentsDelAdd, ['class' => 'btn btn-danger btn-lg mt-3']),
                                );
                            } else { ?>
                               <div class="form-group">
                                            <label for="delivery_location_id"><?= $translator->translate('delivery.location'); ?>: </label>
                                            <select name="delivery_location_id" id="delivery_location_id"
                                                    class="form-control" disabled>
                                                <?php
                                                    /**
                                                     * @var App\Invoice\Entity\DeliveryLocation $del
                                                     */
                                                    foreach ($dels as $del) { ?>
                                                    <option value="<?php echo $del->getId(); ?>"
                                                        <?php $s->check_select(Html::encode($del->getId() ?? $del->getId()), $del->getId()); ?>>
                                                        <?php $delAddress1 =  $del->getAddress_1();
                                                        $delAddress2 = $del->getAddress_2();
                                                        $delCity = $del->getCity();
                                                        $delZip = $del->getZip();
                                                        echo (null !== $delAddress1 ? $delAddress1 : '') . ', '
                                                             . (null !== $delAddress2 ? $delAddress2 : '') . ', '
                                                             . (null !== $delCity ? $delCity : '') . ', '
                                                             . (null !== $delZip ? $delZip : ''); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>    
                                </div> 
                        <?php } ?>
                        <?= Html::openTag('br'); ?>    
                        <?= Html::openTag('br'); ?>    
                        <?= Html::openTag('div'); ?>
                            <?= Field::date($form, 'date_created')
                                ->label($translator->translate('date.issued'))
                                ->value(
                                    Html::encode($form->getDate_created() instanceof \DateTimeImmutable
                                    ? $form->getDate_created()->format('Y-m-d') : (is_string(
                                        $form->getDate_created(),
                                    )
                                    ? $form->getDate_created() : '')),
                                )
                                ->hint($translator->translate('hint.this.field.is.required'));
?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::password($form, 'password')
    ->label($translator->translate('password'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value(Html::encode($form->getPassword()))
    ->placeholder($translator->translate('password'))
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::select($form, 'status_id')
    ->label($translator->translate('status'))
    ->addInputAttributes(['class' => 'form-control'])
    ->value($form->getStatus_id())
    ->prompt($translator->translate('none'))
    ->optionsData($optionsData['salesOrderStatus'])
    ->hint($translator->translate('hint.this.field.is.not.required'));
?>
                        <?= Html::closeTag('div'); ?>
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
                                ->label($translator->translate('guest.url'));
                            ?>
                        <?php } ?>
                        <?= Html::closeTag('div'); ?>
                    <?php   if ($vat === false) { ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::text($form, 'discount_amount')
                                ->hideLabel(false)
                                ->label($translator->translate('discount') . ' ' . $s->getSetting('currency_symbol'))
                                ->addInputAttributes(['class' => 'form-control'])
                                ->value($s->format_amount(($form->getDiscount_amount() ?? 0.00)))
                                ->placeholder($translator->translate('discount'));
                        ?>
                        <?= Html::closeTag('div'); ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::text($form, 'discount_percent')
                            ->label($translator->translate('discount.percentage'))
                            ->addInputAttributes(['class' => 'form-control'])
                            ->value(Html::encode($s->format_amount(($form->getDiscount_percent() ?? 0.00))))
                            ->placeholder($translator->translate('discount.percentage'));
                        ?>
                        <?= Html::closeTag('div'); ?>
                    <?php } ?>
                        <?= Html::openTag('div'); ?>
                            <?= Field::hidden($form, 'inv_id')
                            ->hideLabel(); ?>
                        <?= Html::closeTag('div'); ?>
                    <?php
                        /**
                         * @var App\Invoice\Entity\CustomField $customField
                         */
                        foreach ($cfR->repoTablequery('sales_order_custom') as $customField) {
                            $custom_values = $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('salesorder_custom'));
                            $cvH->print_field_for_form($customField, $salesOrderCustomForm, $translator, $urlGenerator, $so_custom_values, $custom_values);
                        }
?>
                        <?= Html::openTag('div'); ?>
                            <div class="row">
                                 <label for="terms_and_conditions_file" class="control-label"><?= $translator->translate('term') ?></label>
                                 <textarea id="terms_and_conditions_file" class="form-control" rows="20" cols="20"><?= $terms_and_conditions_file; ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-xs-12 col-sm-2">  
                                    <label for="inv_number" class="control-label"><?= $translator->translate('salesorder.invoice.number'); ?></label>
                                    <input type="text" name="inv_number" id="inv_number" class="form-control" required disabled value="<?= $invNumber ?: $translator->translate('not.set'); ?>">
                                </div>
                            </div>
                        <?= Html::closeTag('div'); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
                <?= $button::backSave(); ?>    
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    
<?= Html::closeTag('div'); ?>

<?= Html::closeTag('form'); ?>

<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>