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
?>
<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('tasks.form'); ?>
<?= Html::closeTag('h1'); ?>
<?= Form::tag()
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
            'class' => 'form-control',
        ])
        ->value(Html::encode($form->getName()))
        ->placeholder($translator->translate('name'))
        ->hint($translator->translate('hint.this.field.is.required')); ?>
    <?= Html::tag('br'); ?>
    <?= Field::text($form, 'description')
        ->label($translator->translate('description'))
        ->addInputAttributes([
            'class' => 'form-control',
        ])
        ->value(Html::encode($form->getDescription()))
        ->placeholder($translator->translate('description'))
        ->hint($translator->translate('hint.this.field.is.required')); ?>                    
    <?= Html::tag('br'); ?>
    <?= Field::select($form, 'project_id')
        ->label($translator->translate('project'))
        ->addInputAttributes([
            'class' => 'form-control',
        ])
        ->optionsData($projects)
        ->value($form->getProject_id())
        ->prompt($translator->translate('none'))
        ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?= Html::tag('br'); ?>
    <?= Field::select($form, 'tax_rate_id')
    ->label($translator->translate('tax.rate'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->optionsData($taxRates)
    ->value($form->getTax_rate_id())
    ->prompt($translator->translate('none'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?= Html::tag('br'); ?>
    <?= Field::text($form, 'price')
    ->label($translator->translate('price'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value($s->format_amount(($form->getPrice() ?? 0.00)))
    ->placeholder($translator->translate('price'))
    ->hint($translator->translate('hint.this.field.is.required')); ?>         
    <?= Html::tag('br'); ?>
    <?= Field::date($form, 'finish_date')
    ->label($translator->translate('task.finish.date'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode($form->getFinish_date() instanceof \DateTimeImmutable ?
                         $form->getFinish_date()->format('Y-m-d') : (is_string(
                             $form->getFinish_date(),
                         ) ?
                         $form->getFinish_date() : '')))
    ->hint($translator->translate('hint.this.field.is.required')); ?>         
    <?= Html::tag('br'); ?>
    <?php
        $optionsDataStatus = [];
$statuses = [
    1 => [
        'label' => $translator->translate('not.started'),
        'class' => 'draft',
    ],
    2 => [
        'label' => $translator->translate('in.progress'),
        'class' => 'viewed',
    ],
    3 => [
        'label' => $translator->translate('complete'),
        'class' => 'sent',
    ],
    4 => [
        'label' => $translator->translate('invoiced'),
        'class' => 'paid',
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
        'class' => 'form-control',
        'id' => 'status',
    ])
    ->optionsData($optionsDataStatus)
    ->value($form->getStatus())
    ->hint($translator->translate('hint.this.field.is.required'));
?>
<?= Html::closeTag('div'); ?>     
<?= $button::backSave(); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
