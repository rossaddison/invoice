<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\ClientNote\ClientNoteForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @var array $clients
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClient
 */

?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ClientNoteForm')
    ->open(); ?>

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
    <?php echo $button::back(); ?>
    <?php echo Html::openTag('div', ['id' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div'); ?>
                <?php
                    $optionsDataClient = [];
/**
 * @var App\Invoice\Entity\Client $client
 */
foreach ($clients as $client) {
    if (null !== ($clientId = $client->getClient_id())) {
        $optionsDataClient[$clientId] = $client->getClient_name().' '.($client->getClient_surname() ?? '#');
    }
}
echo Field::select($form, 'client_id')
    ->label($translator->translate('client'))
    ->addInputAttributes([
        'id'       => 'client_id',
        'class'    => 'form-control',
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->optionsData($optionsDataClient);
?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::date($form, 'date_note')
    ->label($translator->translate('date'))
    ->disabled(true)
    ->value(!is_string($dateNote = $form->getDate_note()) ? $dateNote->format('Y-m-d') : '');
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php echo Field::textarea($form, 'note')
    ->label($translator->translate('note'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('note'),
        'value'       => Html::encode($form->getNote() ?? ''),
        'class'       => 'form-control',
        'id'          => 'note',
        'readonly'    => 'readonly',
        'disabled'    => 'disabled',
    ]);
?>
                <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>