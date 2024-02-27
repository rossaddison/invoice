<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Yii\Bulma\Breadcrumbs;

/**
* @var \Yiisoft\View\View $this
* @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
* @var string $csrf
* @var string $action
* @var string $title
*/

echo Breadcrumbs::widget()
    // Bulma's is-centered replaced with centered ie. remove is-
    ->attributes(['class' => 'centered'])
    ->homeItem([
        'label' => $translator->translate('invoice.breadcrumb.product.index'),
        'url' => $urlGenerator->generate('product/index'),
        'icon' => 'fa fa-lg fa-home',
        'iconAttributes' => ['class' => 'icon']
    ])
    ->items([
        [
          'label' => $translator->translate('invoice.breadcrumb.product.property.index'),
          'url' => $urlGenerator->generate('productproperty/index'),
          'icon' => 'fa fa-lg fa-thumbs-up',
          'iconAttributes' => ['class' => 'icon']
        ],
        [
          'label' => $translator->translate('invoice.product.property.edit'),
          'url' => $urlGenerator->generate('productproperty/edit',['id' => $productproperty->getProperty_id()]),
          'icon' => 'fa fa-lg fa-pencil',
          'iconAttributes' => ['class' => 'icon']
        ], 
        [
          'label' => $translator->translate('invoice.product.property.add'),
          'url' => $urlGenerator->generate('productproperty/add',['product_id' => $productproperty->getProduct()?->getProduct_id()]),
          'icon' => 'fa fa-lg fa-plus',
          'iconAttributes' => ['class' => 'icon']
        ]
    ])
    ->render(); 
?>
<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['class' => 'row']); ?>
<?= Html::openTag('div', ['class' => 'row mb-3 form-group']); ?>
    <?= Label::tag()
        ->forId('name')
        ->addClass('text-bg col-sm-2 col-form-label')
        ->addAttributes(['style' => 'background:lightblue'])
        ->content($translator->translate('i.name'));
    ?>
    <?= Label::tag()
        ->addClass('text-bg col-sm-10 col-form-label')
        ->addAttributes(['style' => 'background:lightblue'])
        ->content(Html::encode($form->getName() ?? ''));
    ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['class' => 'row mb-3 form-group']); ?>
    <?= Label::tag()
        ->forId('value')
        ->addClass('text-bg col-sm-2 col-form-label')
        ->addAttributes(['style' => 'background:lightblue'])
        ->content($translator->translate('i.value'));
    ?>
    <?= Label::tag()
        ->addClass('text-bg col-sm-10 col-form-label')
        ->addAttributes(['style' => 'background:lightblue'])
        ->content(Html::encode($form->getValue() ?? ''));
    ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['class' => 'row mb-3 form-group']); ?>
    <?= Label::tag()
        ->forId('product_id')
        ->addClass('text-bg col-sm-2 col-form-label')
        ->addAttributes(['style' => 'background:lightblue'])
        ->content($translator->translate('invoice.product.id'));
    ?>
    <?= Label::tag()
        ->addClass('text-bg col-sm-10 col-form-label')
        ->addAttributes(['style' => 'background:lightblue'])
        ->content(Html::a($form->getProduct()?->getProduct_id(), $urlGenerator->generate('product/view',['id'=>$form->getProduct()?->getProduct_id()])));
    ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['class' => 'row mb-3 form-group']); ?>
    <?= Label::tag()
        ->forId('product_id')
        ->addClass('text-bg col-sm-2 col-form-label')
        ->addAttributes(['style' => 'background:lightblue'])
        ->content($translator->translate('i.product'));
    ?>
    <?= Label::tag()
        ->addClass('text-bg col-sm-10 col-form-label')
        ->addAttributes(['style' => 'background:lightblue'])
        ->content(Html::a($form->getProduct()?->getProduct_name()));
    ?>
<?= Html::closeTag('div'); ?>