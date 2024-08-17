<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Body;
use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\EmailTemplate\EmailTemplateForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>
<?= Html::openTag('h1'); ?><?= Html::closeTag('h1'); ?>
<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>
<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>
     <?= $translator->translate('i.preview'); ?>
<?= Html::closeTag('h1'); ?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('EmailTemplateForm')
    ->open()
?>

<?= Html::openTag('div', ['class' => 'container']); ?>
<?= Html::openTag('div', ['class' => 'row']); ?>
<?= Html::openTag('div', ['class' => 'col card mb-3']); ?>
<?= Html::openTag('div',['class' => 'card-header']); ?>
    <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div'); ?>
            <?= Html::openTag('body'); ?>                
                <?= $form->getEmail_template_body(); ?>
        <?= Html::closeTag('body'); ?>
        <?= Br::tag(); ?>
    <?= Html::closeTag('div'); ?>
    <?= $button::back(); ?>
<?= Html::closeTag('form'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
