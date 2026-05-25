<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Infrastructure\Persistence\Client\Client|null                              $client
 * @var array<int,App\Infrastructure\Persistence\ProductClient\ProductClient>           $productClients
 * @var array<string,string>                                                            $frequencies
 * @var Yiisoft\Translator\TranslatorInterface                                          $translator
 * @var Yiisoft\Router\UrlGeneratorInterface                                            $urlGenerator
 * @var string                                                                          $csrf
 * @var bool                                                                            $canEdit
 */

$clientId = $client?->reqId() ?? 0;
$clientName = $client?->getClientName() ?? '';
?>
<?= H::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= H::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= H::openTag('div', ['class' => 'col-12 col-lg-8']); ?>
<?= H::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>

<?= H::openTag('div', ['class' => 'card-header']); ?>
<?= H::tag('h3', H::encode($translator->translate('recurring.create.from.productclient')), ['class' => 'fw-normal text-center mb-0']); ?>
<?= H::closeTag('div'); ?>

<?= H::openTag('div', ['class' => 'card-body']); ?>

<?= H::openTag('p'); ?>
<?= H::encode($translator->translate('recurring.client')); ?>: <?= H::encode($clientName); ?>
<?= H::closeTag('p'); ?>

<?php if (count($productClients) === 0): ?>
    <?= H::tag('div', H::encode($translator->translate('recurring.no.products.associated')), ['class' => 'alert alert-warning']); ?>
<?php else: ?>
    <?= H::openTag('table', ['class' => 'table table-sm table-bordered mb-4']); ?>
    <?= H::openTag('thead', ['class' => 'table-light']); ?>
    <?= H::openTag('tr'); ?>
    <?= H::tag('th', H::encode($translator->translate('product'))); ?>
    <?= H::tag('th', H::encode($translator->translate('item.price'))); ?>
    <?= H::closeTag('tr'); ?>
    <?= H::closeTag('thead'); ?>
    <?= H::openTag('tbody'); ?>
    <?php foreach ($productClients as $pc):
        $product = $pc->getProduct(); ?>
    <?= H::openTag('tr'); ?>
    <?= H::tag('td', H::encode($product?->getProductName() ?? '')); ?>
    <?= H::tag('td', H::encode((string) ($product?->getProductPrice() ?? 0.00))); ?>
    <?= H::closeTag('tr'); ?>
    <?php endforeach; ?>
    <?= H::closeTag('tbody'); ?>
    <?= H::closeTag('table'); ?>

    <?= new Form()
        ->post($urlGenerator->generate('invrecurring/create-from-productclient', ['client_id' => $clientId]))
        ->csrf($csrf)
        ->id('createFromProductClientForm')
        ->open(); ?>

    <?= H::openTag('div', ['class' => 'mb-3']); ?>
    <?= H::tag('label', H::encode($translator->translate('recurring.frequency')), ['class' => 'form-label fw-bold', 'for' => 'frequency']); ?>
    <?= H::openTag('select', ['class' => 'form-select', 'name' => 'frequency', 'id' => 'frequency']); ?>
    <?php foreach ($frequencies as $key => $labelKey): ?>
        <?= H::tag('option', H::encode($translator->translate($labelKey)), ['value' => $key]); ?>
    <?php endforeach; ?>
    <?= H::closeTag('select'); ?>
    <?= H::closeTag('div'); ?>

    <?= H::openTag('div', ['class' => 'd-flex gap-2']); ?>
    <?= H::tag('button', H::encode($translator->translate('recurring.create')), [
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ]); ?>
    <?= H::tag('a', H::encode($translator->translate('cancel')), [
        'href' => $urlGenerator->generate('invrecurring/index'),
        'class' => 'btn btn-secondary',
    ]); ?>
    <?= H::closeTag('div'); ?>

    <?= new Form()->close(); ?>
<?php endif; ?>

<?= H::closeTag('div'); ?>
<?= H::closeTag('div'); ?>
<?= H::closeTag('div'); ?>
<?= H::closeTag('div'); ?>
