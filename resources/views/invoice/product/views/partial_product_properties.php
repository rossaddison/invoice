<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see App\Invoice\Product\ProductController function view $parameters['partial_product_properties']
 * @var App\Invoice\Entity\Product $product
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $language
 * @var string $productpropertys
 * @psalm-var array<string, Stringable|null|scalar> $languageArgument
 * @psalm-var array<array-key, array<array-key, string>|string> $productPropertyArguments
 */

$languageArgument = ['_language' => $language];
$productPropertyArguments = ['product_id' => $product->getProduct_id(), '_language' => $language];
?>
<div>
<label class="btn btn-info">
    <?= Html::a(
        $translator->translate('product.property.table'),
        $urlGenerator->generate(
            'productproperty/index',
            $languageArgument,
        ),
        ['style' => 'text-decoration:none'],
    ); ?>
</label>
    <?= Html::a(
        $translator->translate('product.property'),
        $urlGenerator->generate(
            'productproperty/add',
            $productPropertyArguments,
        ),
        ['class' => 'btn btn-primary fa fa-plus'],
    ); ?>
</div>
<br>
<div class="table-responsive btn btn-info">
     <?= $productpropertys; ?>
</div>    
