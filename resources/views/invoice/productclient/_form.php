<?php

declare(strict_types=1);

use App\Widget\Button as Btn;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\ProductClient\ProductClientForm $form
 * @var App\Invoice\Client\ClientRepository $clientRepository
 * @var App\Invoice\Product\ProductRepository $productRepository
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<string, string> $clients
 * @psalm-var array<array-key, array<array-key, string>|string> $products
 * @var int $productId
 * @var int $currentIndex
 * @var int $totalProducts
 * @var int $remainingProducts
 */
?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' =>
    'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' =>
    'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('product.client.association'); ?>
<?= Html::closeTag('h1'); ?>

<?=  new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->csrf($csrf)
    ->id('ProductClientForm')
    ->open()
?>

<?= Field::errorSummary($form)
    ->errors($errors)
    ->headerTag('p')
    ->header($translator->translate('invoice.validation.errors'))
    ->render()
?>

<!-- Product Information -->
<?= Html::openTag('div',
        [ 'class' => 'mb-3',
            'data-current-index' => Html::encode($currentIndex),
            'data-total-products' => Html::encode($totalProducts)]); ?>
    <?= Html::openTag('h4'); ?>
        <?= $translator->translate('product'); ?>
    <?= Html::closeTag('h4'); ?>
    <?php if ($productId): ?>
        <?= Html::openTag('div', ['class' => 'alert alert-info']); ?>
            <?= Html::openTag('strong'); ?>
                <?= $translator->translate('product.name'); ?>:
            <?= Html::closeTag('strong'); ?>
            <?= Html::encode(($productRepository->repoProductquery(
                    $productId))?->getProductName() ??
                    $translator->translate('product.name'));
            ?>
            <?php if ($remainingProducts): ?>
                <?= Html::openTag('small', ['class' => 'd-block mt-1']); ?>
                    <?= Html::openTag('i', ['class' => 'bi bi-info-circle']); ?>
                    <?= Html::closeTag('i'); ?>
                    <?= $remainingProducts; ?>
                    <?= $translator->translate('products.remaining.after.this'); ?>
                <?= Html::closeTag('small'); ?>
            <?php endif; ?>
        <?= Html::closeTag('div'); ?>
    <?php endif; ?>
<?= Html::closeTag('div'); ?>

<!-- Client Association Options -->
<?= Html::openTag('div', ['class' => 'mb-3']); ?>
    <?= Html::openTag('h4'); ?>
        <?= $translator->translate('product.client.association.options'); ?>
    <?= Html::closeTag('h4'); ?>
    <?= Html::openTag('div', ['id' => 'existing-client-section', 'class' => 'mb-3']); ?>
        <?= Field::select($form, 'client_id')
            ->label($translator->translate('client'))
            ->addInputAttributes(['class' => 'form-control form-control-lg',])
            ->prompt($translator->translate('select.existing.client'))
            ->optionsData($clients)
            ->render()
        ?>
    <?= Html::closeTag('div'); ?>

<?= Html::closeTag('div'); ?>

<!-- Hidden Fields -->
<?= Html::hiddenInput('product_id', (string) $productId); ?>

<!-- Form Actions -->
<?= new Btn($translator, $urlGenerator)->saveCancel(); ?>

<?=  new Form()->close(); ?>

<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

