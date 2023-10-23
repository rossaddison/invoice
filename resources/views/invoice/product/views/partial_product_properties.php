<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

?>
<div>
<label class="btn btn-info"><?php echo Html::a($translator->translate('invoice.product.property.table'),$urlGenerator->generate('productproperty/index',['_language'=>$language]), ['style' => 'text-decoration:none']); ?></label>
<?= Html::a($translator->translate('invoice.product.property'), $urlGenerator->generate('productproperty/add',['product_id' => $product->getProduct_id(),'_language'=>$language]),['class' => 'btn btn-primary fa fa-plus']); ?>
</div>
<br>
<div class="table-responsive btn btn-info">
     <?= $productpropertys; ?>
</div>    
