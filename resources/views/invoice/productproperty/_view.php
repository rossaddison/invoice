<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bulma\Breadcrumbs;

/**
* @var \Yiisoft\View\View $this
* @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
* @var array $body
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
          'url' => $urlGenerator->generate('productproperty/edit',['id'=>$productproperty->getProperty_id()]),
          'icon' => 'fa fa-lg fa-pencil',
          'iconAttributes' => ['class' => 'icon']
        ], 
        [
          'label' => $translator->translate('invoice.product.property.add'),
          'url' => $urlGenerator->generate('productproperty/add',['product_id'=>$productproperty->getProduct()?->getProduct_id()]),
          'icon' => 'fa fa-lg fa-plus',
          'iconAttributes' => ['class' => 'icon']
        ]
    ])
    ->render(); 


if (!empty($errors)) {
foreach ($errors as $field => $error) {
echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
}
}

?>
<h1><?= Html::encode($title) ?></h1>
<div class="row">
 <div class="row mb3 form-group">
<label for="name" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('name'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['name'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="value" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('value'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['value'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
 <label for="product_id" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $translator->translate('invoice.product.id'); ?></label>
 <label class="text-bg col-sm-10 col-form-label"><?= Html::a($productproperty->getProduct()->getProduct_id(), $urlGenerator->generate('product/view',['id'=>$productproperty->getProduct()->getProduct_id()]));?></label>
 </div>
 <div class="row mb3 form-group">
 <label for="product_id" class="text-bg col-sm-2 col-form-label" style="background:lightblue"><?= $s->trans('product'); ?></label>
 <label class="text-bg col-sm-10 col-form-label"><?= $productproperty->getProduct()?->getProduct_name();?></label>
 </div>   
</div>
