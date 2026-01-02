<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Button;

/**
 * @var App\Invoice\ProductClient\ProductClientForm $form
 * @var App\Invoice\Client\ClientRepository $clientRepository
 * @var App\Invoice\Product\ProductRepository $productRepository
 * @var App\Widget\Button $button
 * @var App\Widget\FormFields $formFields
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<string, string> $clients
 * @psalm-var array<array-key, array<array-key, string>|string> $products
 * @var bool $showClientCreation
 * @var string $suggestedClientGroup
 * @var int $productId
 * @var int $currentIndex
 * @var int $totalProducts
 * @var int $remainingProducts
 */
?>

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' =>
    'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 
    'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('i.product_client_association'); ?>
<?= Html::closeTag('h1'); ?>

<?= Form::tag()
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
            $currentIndex ? 'data-current-index => ' .
                Html::encode($currentIndex) : '',
            $totalProducts ? 'data-total-products => ' .
                Html::encode($totalProducts) : '']); ?>
    <?= Html::openTag('h4'); ?>
        <?= $translator->translate('product'); ?>
    <?= Html::closeTag('h4'); ?>
    <?php if ($productId): ?>
        <?= Html::openTag('div', ['class' => 'alert alert-info']); ?>
            <?= Html::openTag('strong'); ?>
                <?= $translator->translate('product.id'); ?>:
            <?= Html::closeTag('strong'); ?>
             <?= Html::encode($productId); ?>
            <?php if ($remainingProducts): ?>
                <?= Html::openTag('small', ['class' => 'd-block mt-1']); ?>
                    <?= Html::openTag('i', ['class' => 'fa fa-info-circle']); ?>
                    <?= Html::closeTag('i'); ?>
                    <?= $remainingProducts; ?>
                    <?= $translator->translate('products.remaining.after.this.one'); ?>
                <?= Html::closeTag('small'); ?>
            <?php endif; ?>
        <?= Html::closeTag('div'); ?>
    <?php endif; ?>
<?= Html::closeTag('div'); ?>

<!-- Client Association Options -->
<?= Html::openTag('div', ['class' => 'mb-3']); ?>
    <?= Html::openTag('h4'); ?>
        <?= $translator->translate('client.association.options'); ?>
    <?= Html::closeTag('h4'); ?>
    <!-- Option 1: Select Existing Client -->
    <?= Html::openTag('div', ['class' => 'form-check mb-3']); ?>
        <?= Html::openTag('input', [
            'class' => 'form-check-input',
            'type' => 'radio',
            'name' => 'association_type',
            'id' => 'existing_client',
            'value' => 'existing',
            'checked']); ?>
        <?= Html::openTag('label', [
                    'class' => 'form-check-label',
                    'for' => 'existing_client']); ?>
            <?= $translator->translate('select.existing.client'); ?>
        <?= Html::closeTag('label'); ?>
    <?= Html::closeTag('div'); ?>
    
    <?= Html::openTag('div',
            ['id' => 'existing-client-section', 'class' => 'mb-3']); ?>
        <?= Field::select($form, 'client_id')
            ->label($translator->translate('client'))
            ->addInputAttributes(['class' => 'form-control'])
            ->prompt($translator->translate('select.client'))
            ->optionsData($clients)
            ->render()
        ?>
    <?= Html::closeTag('div'); ?>

    <!-- Option 2: Create New Client -->
    <?= Html::openTag('div', ['class' => 'form-check mb-3']); ?>
        <?= Html::openTag('input', [
            'class' => 'form-check-input',
            'type' => 'radio',
            'name' => 'association_type',
            'id' => 'new_client',
            'value' => 'new']); ?>
        <?= Html::openTag('label', [
                    'class' => 'form-check-label',
                    'for' => 'new_client']); ?>
            <?= $translator->translate('create.new.client'); ?>
        <?= Html::closeTag('label'); ?>
    <?= Html::closeTag('div'); ?>
    
    <?= Html::openTag('div', [
        'id' => 'new-client-section',
        'class' => 'mb-3',
        'style' => 'display: none;']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'col-md-6']); ?>
                <?= Field::text($form, 'new_client_name')
                    ->label($translator->translate('name'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->render()
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col-md-6']); ?>
                <?= Field::text($form, 'new_client_surname')
                    ->label($translator->translate('surname'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->render()
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'col-md-6']); ?>
                <?= Field::email($form, 'new_client_email')
                    ->label($translator->translate('email'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->render()
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'col-md-6']); ?>
                <?= Field::text($form, 'new_client_mobile')
                    ->label($translator->translate('mobile'))
                    ->addInputAttributes(['class' => 'form-control'])
                    ->render()
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
        
        <?= Html::openTag('div', ['class' => 'mb-3']); ?>
            <?= Field::text($form, 'new_client_group')
                ->label($translator->translate('client_group'))
                ->addInputAttributes(['class' => 'form-control',
                    'value' => $suggestedClientGroup])
                ->render()
            ?>
            <?php if ($suggestedClientGroup): ?>
                <?= Html::openTag('small',
                        ['class' => 'form-text text-muted']); ?>
                    <?php echo $translator->translate(
                        'suggested.from.previous.selection'); ?>
                <?= Html::closeTag('small'); ?>
            <?php endif; ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<!-- Hidden Fields -->
<?= Html::hiddenInput('product_id', (string) $productId); ?>

<!-- Form Actions -->
<?= Html::openTag('div', ['class' => 'row mb-3']); ?>
    <?= Html::openTag('div', ['class' => 'col-sm-12']); ?>
        <?= Button::tag()
            ->content($translator->translate('save'))
            ->class('btn btn-success')
            ->type('submit')
            ->render()
        ?>
        
        <?= A::tag()
            ->addAttributes(['class' => 'btn btn-secondary ms-2'])
            ->content($translator->translate('cancel'))
            ->href($urlGenerator->generate('family/index'))
            ->render()
        ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Form::tag()->close(); ?>

<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('script'); ?>
document.addEventListener('DOMContentLoaded', function() {
    const existingClientRadio = document.getElementById('existing_client');
    const newClientRadio = document.getElementById('new_client');
    const existingClientSection = document.getElementById('existing-client-section');
    const newClientSection = document.getElementById('new-client-section');
    
    function toggleSections() {
        if (existingClientRadio.checked) {
            existingClientSection.style.display = 'block';
            newClientSection.style.display = 'none';
        } else {
            existingClientSection.style.display = 'none';
            newClientSection.style.display = 'block';
        }
    }
    
    existingClientRadio.addEventListener('change', toggleSections);
    newClientRadio.addEventListener('change', toggleSections);
    
    // Initialize
    toggleSections();

    // Keyboard shortcuts for efficiency
    document.addEventListener('keydown', function(event) {
        // Alt + 1: Select existing client
        if (event.altKey && event.key === '1') {
            existingClientRadio.checked = true;
            toggleSections();
            event.preventDefault();
        }
        
        // Alt + 2: Create new client  
        if (event.altKey && event.key === '2') {
            newClientRadio.checked = true;
            toggleSections();
            event.preventDefault();
        }
        
        // Ctrl + Enter: Submit form
        if (event.ctrlKey && event.key === 'Enter') {
            document.getElementById('ProductClientForm').submit();
            event.preventDefault();
        }
    });
});
<?= Html::closeTag('script'); ?>