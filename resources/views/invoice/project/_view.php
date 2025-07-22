<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Project\ProjectForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $clients
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ProjectForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?php
                    $optionsDataClient = [];
/**
 * @var App\Invoice\Entity\Client $client
 */
foreach ($clients as $client) {
    $clientName = $client->getClient_name();
    $clientSurname = $client->getClient_surname() ?? '';
    $clientId = $client->getClient_id();
    // Only add to the dropdown if the following conditions are satisfied
    if ((strlen($clientName) > 0) && (strlen(($clientSurname)) > 0) && (null !== $clientId)) {
        $optionsDataClient[$clientId] = $clientName . ' ' . $clientSurname;
    }
}
echo Field::select($form, 'client_id')
->label($translator->translate('client'))
->addInputAttributes([
    'id' => 'client_id',
    'class' => 'form-control',
    'readonly' => 'readonly',
    'disabled' => 'disabled',
])
->optionsData($optionsDataClient)
?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::text($form, 'name')
    ->label($translator->translate('project.name'))
    ->addInputAttributes([
        'id' => 'name',
        'class' => 'form-control',
        'placeholder' => $translator->translate('project.name'),
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>