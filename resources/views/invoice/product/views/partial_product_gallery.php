<?php

declare(strict_types=1);

/**
 * Related logic: see ...src\Invoice\Product\ProductController function view $parameters['partial_product_gallery']
 * @var App\Invoice\Entity\Product $product
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var array $productImages
 * @var bool $invEdit
 * @var bool $invView
 */

if ($invEdit && $invView) {
    $this->setTitle($translator->translate('productimage.gallery'));
}
?>

<div class="panel panel-default no-margin">
  <div class="panel-heading">
      <i tooltip="data-bs-toggle" title="<?= $s->isDebugMode(9);?>">
        <?= $translator->translate('productimage.gallery'); ?>
        <?= $product->getProduct_name(); ?></i>
  </div>
  <div class="panel-body clearfix">
    <div class="container">
        <?php if ($invView && $invEdit) { ?> 
        <div class='row'>
            <?php
               /**
                * @var App\Invoice\Entity\ProductImage $productImage
                */
               foreach ($productImages as $productImage) { ?>
                <a data-bs-toggle="lightbox" data-gallery="example-gallery" class="col-sm-4">
                    <img src="<?= '/products/' . $productImage->getFile_name_original(); ?>"   class="img-fluid">
                </a>
             <?php } ?> 
        </div>
        <?php } ?>
    </div>
  </div>
</div>
