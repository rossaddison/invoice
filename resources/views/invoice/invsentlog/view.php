<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\InvSentLog\InvSentLogForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>
<?php echo Html::openTag('h1'); ?><?php echo Html::encode($title); ?><?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?><?php echo Html::openTag('div', ['class' => 'card-header']); ?>
<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('InvSentLogForm')
    ->open();
?>

<?php echo $button::back(); ?>

<?php echo Html::openTag('div', ['class' => 'container']); ?>
<?php echo Html::openTag('div', ['class' => 'row']); ?>
<?php echo Html::openTag('div', ['class' => 'col card mb-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
    <?php echo Html::openTag('h5'); ?>
        <?php echo Html::encode($title); ?>
    <?php echo Html::closeTag('h5'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'id')
    ->value(Html::encode($form->getId()))
    ->readonly(true);
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'inv_id')
    ->label($translator->translate('number'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode($form->getInv()?->getNumber() ?? '#'))
    ->placeholder($translator->translate('number'))
    ->readonly(true);
?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?>
        <?php echo Field::text($form, 'date_sent')
    ->label($translator->translate('email.date'))
    ->addInputAttributes([
        'class' => 'form-control',
    ])
    ->value(Html::encode(!is_string($form->getDate_sent()) ? $form->getDate_sent()?->format('l, d-M-y H:i:s T') : ''))
    ->placeholder($translator->translate('date.sent'))
    ->readonly(true);
?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('form'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
