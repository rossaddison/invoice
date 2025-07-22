<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Widget\Button $button
 * @var App\Invoice\DeliveryParty\DeliveryPartyForm $form
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
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
    ->id('DeliveryPartyForm')
    ->open(); ?>

<?php echo Html::openTag('h1'); ?>
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div'); ?>
    <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>            
            <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::text($form, 'party_name')
                ->addInputAttributes(['style' => 'background:lightblue'])
                ->label($translator->translate('delivery.party.name'))
                ->value(Html::encode($form->getParty_name() ?? ''))
                ->readonly(true);
?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>    
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo $button::backSave(); ?>
<?php echo Form::tag()->close(); ?>