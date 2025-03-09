<?php
declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Payment\PaymentForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $customValues
 * @var array $paymentMethods
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @var string $viewCustomFields
 * @psalm-var array<string, Stringable|string|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataPaymentMethod
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('PaymentForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?= Html::encode($translator->translate('i.payment_form')) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyCommonErrors()
?>
                <?php
    $optionsDataPaymentMethod = [];
/**
 * @var App\Invoice\Entity\PaymentMethod $paymentMethod
 */
foreach ($paymentMethods as $paymentMethod) {
    $paymentMethodId = $paymentMethod->getId();
    $paymentMethodName = $paymentMethod->getName();
    if ((strlen($paymentMethodId) > 0)
        && (strlen(($paymentMethodName ?? '')) > 0) && (null !== $paymentMethodName)) {
        $optionsDataPaymentMethod[$paymentMethodId] = $paymentMethodName;
    }
}
echo Field::select($form, 'payment_method_id')
->label($translator->translate('i.payment_method'))
->optionsData($optionsDataPaymentMethod)
->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled'
])
?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?=
        Field::text($form, 'inv')
        ->label($translator->translate('invoice.invoice'))
        ->addInputAttributes([
            'readonly' => 'readonly',
            'disabled' => 'disabled'
        ])
        ->value(Html::encode($form->getInv()?->getNumber() ?? $translator->translate('invoice.invoice.number.no')))
?>
                <?= Html::closeTag('div'); ?>    
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::date($form, 'payment_date')
    ->label($translator->translate('i.date'))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled'
    ])
    ->value(Html::encode($form->getPayment_date() instanceof DateTimeImmutable ? $form->getPayment_date()->format('Y-m-d') : ''))
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::textarea($form, 'note')
    ->label($translator->translate('i.note'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('i.note'),
        'value' => Html::encode($form->getNote() ?? ''),
        'class' => 'form-control',
        'id' => 'note',
        'readonly' => 'readonly',
        'disabled' => 'disabled'
])
?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'amount')
    ->label($translator->translate('i.amount'))
    ->placeholder($translator->translate('i.amount'))
    ->value(Html::encode($form->getAmount() ?? ''))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled'
    ])
?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= $viewCustomFields; ?>
            <?= Html::closeTag('div'); ?>    
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>   