<?php
declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Task\TaskForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var array $statuses
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataStatus
 * @psalm-var array<array-key, array<array-key, string>|string> $projects
 * @psalm-var array<array-key, array<array-key, string>|string> $taxRates
 */
$formControl = 'form-control form-control-lg';
?>
<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('tasks.form'); ?>
<?= Html::closeTag('h1'); ?>
<?=  new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('TaskForm')
    ->open()
?>
<?= Html::openTag('div'); ?>
    <?= Field::text($form, 'name')
        ->label($translator->translate('name'))
        ->addInputAttributes([
            'class' => $formControl,
        ])
        ->value(Html::encode($form->getName()))
        ->placeholder($translator->translate('name'))
        ->hint($translator->translate('hint.this.field.is.required')); ?>
    <?= Html::tag('br'); ?>
    <?= Field::text($form, 'description')
        ->label($translator->translate('description'))
        ->addInputAttributes([
            'class' => $formControl,
        ])
        ->value(Html::encode($form->getDescription()))
        ->placeholder($translator->translate('description'))
        ->hint($translator->translate('hint.this.field.is.required')); ?>
    <?= Html::tag('br'); ?>
    <?= Field::select($form, 'project_id')
        ->label($translator->translate('project'))
        ->addInputAttributes([
            'class' => $formControl,
        ])
        ->optionsData($projects)
        ->value($form->getProjectId())
        ->prompt($translator->translate('none'))
        ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?= Html::tag('br'); ?>
    <?= Field::select($form, 'tax_rate_id')
    ->label($translator->translate('tax.rate'))
    ->addInputAttributes([
        'class' => $formControl,
    ])
    ->optionsData($taxRates)
    ->value($form->getTaxRateId())
    ->prompt($translator->translate('none'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?= Html::tag('br'); ?>
    <?= Field::text($form, 'price')
    ->label($translator->translate('price'))
    ->addInputAttributes([
        'class' => $formControl,
    ])
    ->value($s->formatAmount(($form->getPrice() ?? 0.00)))
    ->placeholder($translator->translate('price'))
    ->hint($translator->translate('hint.this.field.is.required')); ?>
    <?= Html::tag('br'); ?>
    <?= Field::date($form, 'finish_date')
    ->label($translator->translate('task.finish.date'))
    ->addInputAttributes([
        'class' => $formControl,
    ])
    ->value(Html::encode($form->getFinishDate() instanceof \DateTimeImmutable
                         ? $form->getFinishDate()->format('Y-m-d') : (is_string(
                             $form->getFinishDate(),
                         )
                         ? $form->getFinishDate() : '')))
    ->hint($translator->translate('hint.this.field.is.required')); ?>
    <?= Html::tag('br'); ?>
    <?php
        $optionsDataStatus = [];
$statuses = [
    1 => [
        'label' => $translator->translate('not.started'),
        'class' => 'secondary',
    ],
    2 => [
        'label' => $translator->translate('in.progress'),
        'class' => 'warning',
    ],
    3 => [
        'label' => $translator->translate('complete'),
        'class' => 'success',
    ],
    4 => [
        'label' => $translator->translate('invoiced'),
        'class' => 'primary',
    ],
];
/**
 * @var int $key
 * @var array $status
 * @var string $status['label']
 */
foreach ($statuses as $key => $status) {
    if ($form->getStatus() !== 4 && $key === 4) {
        continue;
    }
    $optionsDataStatus[$key] = $status['label'];
}
?>
    <?= Field::select($form, 'status')
    ->label($translator->translate('status'))
    ->addInputAttributes([
        'class' => $formControl,
        'id' => 'status',
    ])
    ->optionsData($optionsDataStatus)
    ->value($form->getStatus())
    ->hint($translator->translate('hint.this.field.is.required'));
?>
<?= Html::closeTag('div'); ?>
<?= $button::backSave(); ?>
<?=  new Form()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
