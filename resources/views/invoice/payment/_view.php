<?php
declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
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

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('PaymentForm')
    ->open(); ?>

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?php echo Html::encode($translator->translate('payment.form')); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
    <?php echo $button::back(); ?>
    <?php echo Html::openTag('div', ['id' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::errorSummary($form)
        ->errors($errors)
        ->header($translator->translate('error.summary'))
        ->onlyCommonErrors();
?>
                <?php
    $optionsDataPaymentMethod = [];
/**
 * @var App\Invoice\Entity\PaymentMethod $paymentMethod
 */
foreach ($paymentMethods as $paymentMethod) {
    $paymentMethodId   = $paymentMethod->getId();
    $paymentMethodName = $paymentMethod->getName();
    if ((strlen($paymentMethodId) > 0)
        && (strlen($paymentMethodName ?? '') > 0) && (null !== $paymentMethodName)) {
        $optionsDataPaymentMethod[$paymentMethodId] = $paymentMethodName;
    }
}
echo Field::select($form, 'payment_method_id')
    ->label($translator->translate('payment.method'))
    ->optionsData($optionsDataPaymentMethod)
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ]);
?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'inv')
    ->label($translator->translate('invoice'))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->value(Html::encode($form->getInv()?->getNumber() ?? $translator->translate('number.no')));
?>
                <?php echo Html::closeTag('div'); ?>    
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::date($form, 'payment_date')
                    ->label($translator->translate('date'))
                    ->addInputAttributes([
                        'readonly' => 'readonly',
                        'disabled' => 'disabled',
                    ])
                    ->value(Html::encode($form->getPayment_date() instanceof DateTimeImmutable ? $form->getPayment_date()->format('Y-m-d') : ''));
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::textarea($form, 'note')
    ->label($translator->translate('note'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('note'),
        'value'       => Html::encode($form->getNote() ?? ''),
        'class'       => 'form-control',
        'id'          => 'note',
        'readonly'    => 'readonly',
        'disabled'    => 'disabled',
    ]);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'amount')
    ->label($translator->translate('amount'))
    ->placeholder($translator->translate('amount'))
    ->value(Html::encode($form->getAmount() ?? ''))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ]);
?>
                <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo $viewCustomFields; ?>
            <?php echo Html::closeTag('div'); ?>    
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>   