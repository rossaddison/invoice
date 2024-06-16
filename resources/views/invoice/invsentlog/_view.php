<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

?>
<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>
<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?><?= Html::openTag('div',['class'=>'card-header']); ?>
<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
     <?= $translator->translate('i.add'); ?>
<?= Html::closeTag('h1'); ?>
<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('InvSentLogForm')
    ->open()
?>

<?= $button::back_save(); ?>

<?= Html::openTag('div', ['class' => 'container']); ?>
<?= Html::openTag('div', ['class' => 'row']); ?>
<?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
<?= Html::openTag('div',['class' => 'card-header']); ?>
    <?= Html::openTag('h5'); ?>
        <?= Html::encode($title); ?>
    <?= Html::closeTag('h5'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::select($form, 'client_id')
            ->addInputAttributes([
                 'class' => 'form-control'
            ])
            ->value($form->getClient_id())
            ->prompt($translator->translate('i.none'))
            ->optionsData($clients)
        ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::select($form, 'inv_id')
            ->addInputAttributes([
                 'class' => 'form-control'
            ])
            ->value($form->getInv_id())
            ->prompt($translator->translate('i.none'))
            ->optionsData($invs)
        ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div'); ?>
        <?= Field::text($form,'date_sent')
            ->label($translator->translate(date_sent))
            ->addInputAttributes([
                'class' => 'form-control'
            ])
            ->value(Html::encode($form->getdate_sent))
            ->placeholder($translator->translate('date_sent'))
         ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('form'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
