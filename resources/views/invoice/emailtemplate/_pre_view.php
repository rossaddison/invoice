<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\EmailTemplate\EmailTemplateForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>
<?php echo Html::openTag('h1'); ?><?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
     <?php echo $translator->translate('preview'); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('EmailTemplateForm')
    ->open();
?>

<?php echo Html::openTag('div', ['class' => 'container']); ?>
<?php echo Html::openTag('div', ['class' => 'row']); ?>
<?php echo Html::openTag('div', ['class' => 'col card mb-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
    <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div'); ?>
            <?php echo Html::openTag('body'); ?>                
                <?php echo $form->getEmail_template_body(); ?>
        <?php echo Html::closeTag('body'); ?>
        <?php echo Br::tag(); ?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo $button::back(); ?>
<?php echo Html::closeTag('form'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
