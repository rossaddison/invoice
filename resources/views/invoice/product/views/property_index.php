<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @see ...$parameters['partial_product_properties' => ['productpropertys']]
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $all
 * @var string $language
 * @psalm-var array<array-key, array<array-key, string>|string> $productPropertyArguments
 */

?>
<table class="table">
<thead>
<tr>
<th><?= $translator->translate('invoice.product.property.name'); ?></th>
<th><?= $translator->translate('invoice.product.property.value'); ?></th>
</tr>
</thead>
<tbody>
<tr>
<?php
    /**
     * @var App\Invoice\Entity\ProductProperty $productProperty
     */
    foreach ($all as $productProperty) {
        $productPropertyArguments = ['id' => $productProperty->getProperty_id(), '_language' => $language];
        echo Html::openTag('td');
        echo Html::a($productProperty->getName() ?? '#', $urlGenerator->generate(
            'productproperty/view',
            $productPropertyArguments
        ));
        echo Html::closeTag('td');
        echo Html::openTag('td');
        echo $productProperty->getValue();
        echo Html::closeTag('td');
    }
?>  
</tr>
</tbody>
</table>