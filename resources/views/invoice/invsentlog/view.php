<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\FormModel\Field;

/**
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
<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?><?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('InvSentLogForm')
    ->open()
?>

<?= $button::back(); ?>

<?= Html::openTag('div', ['class' => 'container']); ?>
<?= Html::openTag('div', ['class' => 'row']); ?>
<?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
    <?= Html::openTag('h5'); ?>
        <?= Html::encode($title); ?>
    <?= Html::closeTag('h5'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'id')
            ->value(Html::encode($form->getId()))
            ->readonly(true)
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'inv_id')
   ->label($translator->translate('invoice.invoice.number'))
   ->addInputAttributes([
       'class' => 'form-control'
   ])
   ->value(Html::encode($form->getInv()?->getNumber() ?? '#'))
   ->placeholder($translator->translate('invoice.invoice.number'))
   ->readonly(true)
?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form, 'date_sent')
   ->label($translator->translate('invoice.email.date'))
   ->addInputAttributes([
       'class' => 'form-control'
   ])
   ->value(Html::encode(!is_string($form->getDate_sent()) ? $form->getDate_sent()?->format('l, d-M-y H:i:s T') : ''))
   ->placeholder($translator->translate('date_sent'))
   ->readonly(true)
?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('form'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
