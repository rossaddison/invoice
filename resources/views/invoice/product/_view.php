<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

echo $alert;

?>

<div id="headerbar">
    <h1 class="headerbar-title"><?= str_repeat("&nbsp;", 2).Html::encode($title) ?></h1>
</div>

<ul id="product-tabs" class="nav nav-tabs nav-tabs-noborder">
    <li class="active">
        <a data-toggle="tab" href="#product-details" style="text-decoration: none"><?= $translator->translate('invoice.product.view.tab.details'); ?> </a>
    </li>
    <li>
        <a data-toggle="tab" href="#product-properties" style="text-decoration: none"><?= $translator->translate('invoice.product.view.tab.properties'); ?> </a>
    </li>
    <li>
        <a data-toggle="tab" href="#product-images" style="text-decoration: none"><?= $translator->translate('invoice.product.view.tab.images'); ?> </a>
    </li>
    <li>
        <a data-toggle="tab" href="#product-gallery" style="text-decoration: none"><?= $translator->translate('invoice.product.view.tab.gallery'); ?> </a>
    </li>
</ul>

<div class="tabbable tabs-below">

    <div class="tab-content">

        <div id="product-details" class="tab-pane active">
            <?= $partial_product_details; ?>
        </div>

        <div id="product-properties" class="tab-pane">
            <?= $partial_product_properties; ?>
        </div>

        <div id="product-images" class="tab-pane">
            <?= $partial_product_images; ?>
        </div>
        
        <div id="product-gallery" class="tab-pane">
            <?= $partial_product_gallery; ?>
        </div>

    </div>

</div>

