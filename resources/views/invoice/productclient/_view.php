<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;

/**
 * @var App\Invoice\Entity\ProductClient $productClient
 * @var App\Invoice\Entity\Product $product
 * @var App\Invoice\Entity\Client $client
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $alert
 */
?>

<?= $alert; ?>

<?= Html::openTag('div', ['class' => 'container py-5']); ?>
<?= Html::openTag('div', ['class' => 'row']); ?>
<?= Html::openTag('div', ['class' => 'col-12']); ?>
<?= Html::openTag('div', ['class' =>
    'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('product.client.association.details'); ?>
<?= Html::closeTag('h1'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'card-body']); ?>

<!-- Product Client Association Information -->
<?= Html::openTag('div', ['class' => 'row mb-4']); ?>
    <?= Html::openTag('div', ['class' => 'col-12']); ?>
        <?= Html::openTag('h4', ['class' => 'text-primary']); ?>
            <?= $translator->translate('association.information'); ?>
        <?= Html::closeTag('h4'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'row']); ?>
    <?= Html::openTag('div', ['class' => 'col-md-6']); ?>
        <?= Html::openTag('div', ['class' => 'card h-100']); ?>
            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                <?= Html::openTag('h5', ['class' => 'mb-0']); ?>
                    <?= $translator->translate('product.details'); ?>
                <?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'card-body']); ?>
                <?= Html::openTag('table', ['class' => 'table table-borderless']); ?>
                    <?= Html::openTag('tbody'); ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('strong'); ?>
                                    <?= $translator->translate('id'); ?>:
                                <?= Html::closeTag('strong'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::encode($product->getProduct_id()); ?>
                            <?= Html::closeTag('td'); ?>
                        <?= Html::closeTag('tr'); ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('strong'); ?>
                                    <?= $translator->translate('name'); ?>:
                                <?= Html::closeTag('strong'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::encode($product->getProduct_name()); ?>
                            <?= Html::closeTag('td'); ?>
                        <?= Html::closeTag('tr'); ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('strong'); ?>
                                    <?= $translator->translate('description'); ?>:
                                <?= Html::closeTag('strong'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::encode($product->getProduct_description()); ?>
                            <?= Html::closeTag('td'); ?>
                        <?= Html::closeTag('tr'); ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('strong'); ?>
                                    <?= $translator->translate('price'); ?>:
                                <?= Html::closeTag('strong'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::encode($product->getProduct_price()); ?>
                            <?= Html::closeTag('td'); ?>
                        <?= Html::closeTag('tr'); ?>
                    <?= Html::closeTag('tbody'); ?>
                <?= Html::closeTag('table'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>

    <?= Html::openTag('div', ['class' => 'col-md-6']); ?>
        <?= Html::openTag('div', ['class' => 'card h-100']); ?>
            <?= Html::openTag('div', ['class' => 'card-header bg-'
                . ($client->getClient_active() ? 'success' : 'warning')]); ?>
                <?= Html::openTag('h5', ['class' => 'mb-0 text-white']); ?>
                    <?= $translator->translate('client_details'); ?>
                    <?= Html::openTag('span',
                            ['class' => 'badge bg-light text-dark ms-2']); ?>
                        <?= $client->getClient_active() ?
                                $translator->translate('active') :
                                $translator->translate('inactive'); ?>
                    <?= Html::closeTag('span'); ?>
                <?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'card-body']); ?>
                <?= Html::openTag('table', ['class' => 'table table-borderless']); ?>
                    <?= Html::openTag('tbody'); ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('strong'); ?>
                                    <?= $translator->translate('id'); ?>:
                                <?= Html::closeTag('strong'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::encode($client->getClient_id()); ?>
                            <?= Html::closeTag('td'); ?>
                        <?= Html::closeTag('tr'); ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('strong'); ?>
                                    <?= $translator->translate('name'); ?>:
                                <?= Html::closeTag('strong'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::encode($client->getClient_name()); ?>
                            <?= Html::closeTag('td'); ?>
                        <?= Html::closeTag('tr'); ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('strong'); ?>
                                    <?= $translator->translate('surname'); ?>:
                                <?= Html::closeTag('strong'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::encode($client->getClient_surname()); ?>
                            <?= Html::closeTag('td'); ?>
                        <?= Html::closeTag('tr'); ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('strong'); ?>
                                    <?= $translator->translate('email'); ?>:
                                <?= Html::closeTag('strong'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::encode($client->getClient_email()); ?>
                            <?= Html::closeTag('td'); ?>
                        <?= Html::closeTag('tr'); ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('strong'); ?>
                                    <?= $translator->translate('mobile'); ?>:
                                <?= Html::closeTag('strong'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::encode($client->getClient_mobile()); ?>
                            <?= Html::closeTag('td'); ?>
                        <?= Html::closeTag('tr'); ?>
                        <?php if (strlen($client->getClient_group() ?? '') > 0): ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('strong'); ?>
                                    <?= $translator->translate('group'); ?>:
                                <?= Html::closeTag('strong'); ?>
                            <?= Html::closeTag('td'); ?>
                            <?= Html::openTag('td'); ?>
                                <?= Html::openTag('span',
                                        ['class' => 'badge bg-info']); ?>
                                    <?= Html::encode($client->getClient_group()); ?>
                                <?= Html::closeTag('span'); ?>
                            <?= Html::closeTag('td'); ?>
                        <?= Html::closeTag('tr'); ?>
                        <?php endif; ?>
                    <?= Html::closeTag('tbody'); ?>
                <?= Html::closeTag('table'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<!-- Association Metadata -->
<?= Html::openTag('div', ['class' => 'row mt-4']); ?>
    <?= Html::openTag('div', ['class' => 'col-12']); ?>
        <?= Html::openTag('div', ['class' => 'card']); ?>
            <?= Html::openTag('div', ['class' => 'card-header']); ?>
                <?= Html::openTag('h5', ['class' => 'mb-0']); ?>
                    <?= $translator->translate('association_metadata'); ?>
                <?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'card-body']); ?>
                <?= Html::openTag('div', ['class' => 'row']); ?>
                    <?= Html::openTag('div', ['class' => 'col-md-6']); ?>
                        <?= Html::openTag('p'); ?>
                            <?= Html::openTag('strong'); ?>
                                <?= $translator->translate('created_at'); ?>:
                            <?= Html::closeTag('strong'); ?>
                            <?= Html::encode(
                                $productClient->getCreatedAt()->format(
                                    'Y-m-d H:i:s') ?: 'N/A'); ?>
                        <?= Html::closeTag('p'); ?>
                    <?= Html::closeTag('div'); ?>
                    <?= Html::openTag('div', ['class' => 'col-md-6']); ?>
                        <?= Html::openTag('p'); ?>
                            <?= Html::openTag('strong'); ?>
                                <?= $translator->translate('updated_at'); ?>:
                            <?= Html::closeTag('strong'); ?>
                            <?= Html::encode(
                                $productClient->getUpdatedAt()->format(
                                    'Y-m-d H:i:s') ?: 'N/A'); ?>
                        <?= Html::closeTag('p'); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::closeTag('div'); ?>

<!-- Action Buttons -->
<?= Html::openTag('div', ['class' => 'card-footer']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'col-12']); ?>
            <?= A::tag()
                ->addAttributes(['class' => 'btn btn-primary me-2'])
                ->content($translator->translate('i.edit'))
                ->href($urlGenerator->generate(
                    'productclient/edit', ['id' => $productClient->getId()]))
                ->render()
            ?>
            
            <?= A::tag()
                ->addAttributes(['class' => 'btn btn-secondary me-2'])
                ->content($translator->translate('back.to.list'))
                ->href($urlGenerator->generate('productclient/index'))
                ->render()
            ?>
            
            <?= A::tag()
                ->addAttributes(['class' => 'btn btn-info me-2'])
                ->content($translator->translate('view.product'))
                ->href($urlGenerator->generate(
                    'product/view', ['id' => $product->getProduct_id()]))
                ->render()
            ?>
            
            <?= A::tag()
                ->addAttributes(['class' => 'btn btn-info'])
                ->content($translator->translate('view.client'))
                ->href($urlGenerator->generate(
                    'client/view', ['id' => $client->getClient_id()]))
                ->render()
            ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>