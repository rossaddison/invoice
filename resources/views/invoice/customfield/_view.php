<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\CustomField\CustomFieldForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $custom_tables
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CustomFieldForm')
    ->open();
?>

<?php echo Html::openTag('h1'); ?>
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div'); ?>
    <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>

            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'id')
                ->addInputAttributes(['style' => 'background:lightblue'])
                ->label($translator->translate('id'))
                ->value(Html::encode($form->getId() ?? ''))
                ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>

            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'table')
                ->addInputAttributes(['style' => 'background:lightblue'])
                ->label($translator->translate('table'))
                ->value(Html::encode(strlen($table = $form->getTable() ?? '') > 0 ? ucfirst($s->lang((string) $custom_tables[$table])) : ''))
                ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>

            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'label')
                ->addInputAttributes(['style' => 'background:lightblue'])
                ->label($translator->translate('label'))
                ->value(Html::encode($form->getLabel() ?? ''))
                ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>

            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'type')
                ->addInputAttributes(['style' => 'background:lightblue'])
                ->label($translator->translate('type'))
                ->value(Html::encode($translator->translate(''.str_replace('-', '_', strtolower($form->getType() ?? '')).'')))
                ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>

            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'location')
                ->addInputAttributes(['style' => 'background:lightblue'])
                ->label($translator->translate('custom.field.location'))
                ->value(Html::encode($form->getLocation() ?? ''))
                ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>

            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'order')
                ->addInputAttributes(['style' => 'background:lightblue'])
                ->label($translator->translate('order'))
                ->value(Html::encode($form->getOrder() ?? ''))
                ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>    
<?php echo Html::closeTag('div'); ?>
<?php echo $button::back(); ?>
<?php echo Form::tag()->close(); ?>