<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
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
<?php foreach ($all as $product_property) { ?>
<td> 
    <?= Html::a($product_property->getName(), $urlGenerator->generate('productproperty/view',['id'=>$product_property->getProperty_id(),'_language'=>$language])); ?>
</td>
<td>
    <?= $product_property->getValue(); ?>
</td>
<?php } ?>
</tr>
</tbody>
</table>