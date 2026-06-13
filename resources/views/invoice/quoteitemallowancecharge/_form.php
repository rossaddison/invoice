<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $allowance_charges
 * @var string $alert
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataAllowanceCharge
 */

$optionsDataAllowanceCharge = [];
$optionsAttributesAllowanceCharge = [];
$acTemplateData = [];
/**
 * @var App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge $allowance_charge
 */
foreach ($allowance_charges as $allowance_charge) {
    $id = $allowance_charge->reqId();
    $isCharge = $allowance_charge->getIdentifier();
    $type = $isCharge
        ? $translator->translate('allowance.or.charge.charge')
        : $translator->translate('allowance.or.charge.allowance');
    $parts = array_filter(
        [
            $allowance_charge->getReasonCode(),
            $allowance_charge->getReason(),
            $allowance_charge->getTaxRate()?->getTaxRateName() ?? '',
        ],
        static fn(string $v): bool => $v !== ''
    );
    $optionsDataAllowanceCharge[$id] = $type . ' — ' . implode(' — ', $parts);
    $optionsAttributesAllowanceCharge[$id] = ['style' => $isCharge ? 'color:#dc3545' : 'color:#198754'];
    $acTemplateData[$id] = [
        'mfn'  => $allowance_charge->getMultiplierFactorNumeric(),
        'base' => $allowance_charge->getBaseAmount(),
    ];
}

$currencySymbol = $s->getSetting('currency_symbol');
$formControlLg  = ['class' => 'form-control form-control-lg'];
$ac = 'allowance.or.charge.';
?>

<?= new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('QuoteItemAllowanceChargeForm')
    ->open(); ?>

<?= Html::openTag('div', ['class' => 'row']); ?>
    <?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 mx-auto']); ?>
        <?= Html::openTag('div', ['class' => 'card border border-secondary']); ?>
            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                <?= Html::openTag('h4'); ?>
                    <?= Html::encode($title); ?>
                <?= Html::closeTag('h4'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'card-body']); ?>

                <?= Html::a(
                    $translator->translate('allowance.or.charge.index'),
                    $urlGenerator->generate('allowancecharge/index'),
                    ['class' => 'small text-muted d-block mb-1']
                ); ?>
                <?= Field::select($form, 'allowance_charge_id')
                    ->label($translator->translate('allowance.or.charge.item.quote'))
                    ->addInputAttributes([
                        'class'            => $formControlLg['class'],
                        'id'               => 'allowance_charge_id',
                        'data-ac-templates' => json_encode($acTemplateData, JSON_THROW_ON_ERROR),
                    ])
                    ->optionsData($optionsDataAllowanceCharge, true, $optionsAttributesAllowanceCharge)
                    ->value($form->getAllowanceChargeId())
                    ->prompt($translator->translate('none'))
                    ->hint($translator->translate('hint.this.field.is.required')); ?>

                <?= Html::openTag('div', ['id' => 'row-base-amount', 'style' => 'display:none']); ?>
                    <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                        <?= Html::label(
                            $translator->translate($ac . 'base.amount') . ' (' . $currencySymbol . ')',
                            'base_amount_calc'
                        )->class('form-label'); ?>
                        <?= Html::input('number', null, null, [
                            'id'          => 'base_amount_calc',
                            'class'       => $formControlLg['class'],
                            'min'         => '0',
                            'step'        => '0.01',
                            'placeholder' => $translator->translate($ac . 'base.amount'),
                        ]); ?>
                        <?= Html::tag('small', '', [
                            'id'    => 'amount-formula',
                            'class' => 'text-muted',
                        ]); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>

                <?= Field::text($form, 'amount')
                    ->label($translator->translate('amount.quote.item') . ' (' . $currencySymbol . ')')
                    ->addInputAttributes([
                        'class' => $formControlLg['class'],
                        'id'    => 'amount',
                        'step'  => '0.01',
                    ])
                    ->value($s->formatAmount($form->getAmount() ?? 0.00))
                    ->hint($translator->translate('hint.this.field.is.required')); ?>

            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'card-footer']); ?>
                <?= $button::backSave(); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= new Form()->close(); ?>
