<?php
declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\Task\TaskForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var \Yiisoft\View\View $this
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var array $statuses
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $projects
 * @psalm-var array<array-key, array<array-key, string>|string> $taxRates
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataStatus
 */
?>
<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?php echo $translator->translate('tasks.form'); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('TaskForm')
    ->open();
?> 
<?php echo Html::openTag('div'); ?>
    <?php echo Field::text($form, 'name')
    ->label($translator->translate('name'))
    ->required(true)
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->value(Html::encode($form->getName()))
    ->placeholder($translator->translate('name'));
?>
    <?php echo Html::tag('br'); ?>
    <?php echo Field::text($form, 'description')
    ->label($translator->translate('description'))
    ->required(true)
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->value(Html::encode($form->getDescription()))
    ->placeholder($translator->translate('description'));
?>                    
    <?php echo Html::tag('br'); ?>
    <?php echo Field::select($form, 'project_id')
        ->label($translator->translate('project'))
        ->addInputAttributes([
            'readonly' => 'readonly',
            'disabled' => 'disabled',
        ])
        ->optionsData($projects)
        ->value($form->getProject_id())
        ->prompt($translator->translate('none'));
?>
    <?php echo Html::tag('br'); ?>
    <?php echo Field::select($form, 'tax_rate_id')
    ->label($translator->translate('tax.rate'))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->optionsData($taxRates)
    ->value($form->getTax_rate_id())
    ->prompt($translator->translate('none'));
?>
    <?php echo Html::tag('br'); ?>
    <?php echo Field::text($form, 'price')
    ->label($translator->translate('price'))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->value($s->format_amount($form->getPrice() ?? 0.00))
    ->placeholder($translator->translate('price'))
    ->hint($translator->translate('hint.this.field.is.required')); ?>         
    <?php echo Html::tag('br'); ?>
    <?php echo Field::date($form, 'finish_date')
        ->label($translator->translate('task.finish.date'))
        ->addInputAttributes([
            'readonly' => 'readonly',
            'disabled' => 'disabled',
        ])
        ->value(Html::encode($form->getFinish_date() instanceof DateTimeImmutable ?
                             $form->getFinish_date()->format('Y-m-d') : (is_string(
                                 $form->getFinish_date(),
                             ) ?
                             $form->getFinish_date() : '')));
?>    
    <?php echo Html::tag('br'); ?>
    <?php
    $optionsDataStatus = [];
$statuses              = [
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
 * @var int    $key
 * @var array  $status
 * @var string $status['label']
 */
foreach ($statuses as $key => $status) {
    if (4 !== $form->getStatus() && 4 === $key) {
        continue;
    }
    $optionsDataStatus[$key] = $status['label'];
}
?>
    <?php echo Field::select($form, 'status')
    ->label($translator->translate('status'))
    ->addInputAttributes([
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->optionsData($optionsDataStatus)
    ->value($form->getStatus());
?>
<?php echo Html::closeTag('div'); ?>     
<?php echo $button::back(); ?>
<?php echo Form::tag()->close(); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
