<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 */
 if ($invEdit && $invView) { 
    $this->setTitle($translator->translate('invoice.productimage.gallery'));
 }
 ?>

<div class="panel panel-default no-margin">
  <div class="panel-heading">
      <i tooltip="data-bs-toggle" title="<?= $s->isDebugMode(9);?>"><?= $translator->translate('invoice.productimage.gallery'); ?><?= $product->getProduct_name(); ?></i>
  </div>
  <div class="panel-body clearfix">
    <div class="container">
        <?php if ($invView && $invEdit) { ?> 
          <?= Html::openTag('div', ['class' => 'row']); ?>
            <?php foreach ($product_images as $productimage) { ?>
                <a data-toggle="lightbox" data-gallery="example-gallery" class="col-sm-4">
                    <img src="<?= '/products/'. $productimage->getFile_name_original(); ?>"   class="img-fluid">
                </a>
             <?php } ?> 
          </div>
        <?php } ?>
    </div>
  </div>
</div>
