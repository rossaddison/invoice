<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

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

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
$selectLabel = static function (array $optionsData, int|string|null $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $key = (string) $value;
    /** @var string|array|null $label */
    $label = $optionsData[$key] ?? null;
    return is_string($label) ? $label : $key;
};

$optionsDataPaymentMethod = [];
/**
 * @var App\Infrastructure\Persistence\PaymentMethod\PaymentMethod $paymentMethod
 */
foreach ($paymentMethods as $paymentMethod) {
    $paymentMethodId = $paymentMethod->reqId();
    $paymentMethodName = $paymentMethod->getName();
    if ($paymentMethodId > 0
        && (strlen(($paymentMethodName ?? '')) > 0) && (null !== $paymentMethodName)) {
        $optionsDataPaymentMethod[$paymentMethodId] = $paymentMethodName;
    }
}
?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-body']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= Html::encode($translator->translate('payment.form')) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?php
                ReadOnlyField::render(
                    $translator->translate('payment.method'),
                    $selectLabel($optionsDataPaymentMethod, $form->getPaymentMethodId()),
                );
ReadOnlyField::render(
    $translator->translate('invoice'),
    $form->getInv()?->getNumber() ?? $translator->translate('number.no'),
);
ReadOnlyField::render(
    $translator->translate('date'),
    $form->getPaymentDate() instanceof DateTimeImmutable
        ? $form->getPaymentDate()->format('Y-m-d')
        : '',
);
ReadOnlyField::render($translator->translate('note'), $form->getNote());
ReadOnlyField::render(
    $translator->translate('amount'),
    $form->getAmount() !== null ? (string) $form->getAmount() : '',
);
?>
            <?= Html::openTag('div'); ?>
                <?= $viewCustomFields; ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
