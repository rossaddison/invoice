<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Entity\QuoteItem $quoteitem
 * @var Yiisoft\View\View $this
 * @var Yiisoft\Translator\Translator $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @psalm-var array<string, Stringable|null|scalar> $actionArgumentsTax
 * @psalm-var array<string, Stringable|null|scalar> $actionArgumentsProduct
 * @psalm-var array<string, Stringable|null|scalar> $actionArgumentsQuote
 * @var string $title
 */

?>
<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>

 <div class="mb3 form-group">
   <label for="tax_rate_id" class="form-label" style="background:lightblue"><?= $translator->translate('i.tax_rate'); ?></label>
   <?php  $actionNameTax = 'taxrate/view';
        $actionArgumentsTax = ['tax_rate_id' => $quoteitem->getTaxRate()?->getTaxRateId()];
        $taxRateName = $quoteitem->getTaxRate()?->getTaxRateName();
        if (null!==$taxRateName) {
            echo Html::a($taxRateName, $urlGenerator->generate($actionNameTax, $actionArgumentsTax))->render();
        } 
    ?>
 </div>
 <div class="mb3 form-group">
   <label for="product_id" class="form-label" style="background:lightblue"><?= $translator->translate('i.product'); ?></label>
   <?php  $actionNameProduct = 'product/view';
        $actionArgumentsProduct = ['id' => $quoteitem->getProduct()?->getProduct_id()];
        $productName = $quoteitem->getProduct()?->getProduct_name();
        if (null!==$productName) {
            echo Html::a($productName, $urlGenerator->generate($actionNameProduct, $actionArgumentsProduct))->render(); 
        }    
    ?>
 </div>
 <div class="mb3 form-group">
   <label for="quote_id" class="form-label" style="background:lightblue"><?= $translator->translate('i.quote'); ?></label>
   <?php  $actionNameQuote = 'quote/view';
        $actionArgumentsQuote = ['id' => $quoteitem->getQuote()?->getId()];
        $quoteNumber = $quoteitem->getQuote()?->getNumber(); 
        if (null!==$quoteNumber) {
            echo Html::a($quoteNumber, $urlGenerator->generate($actionNameQuote, $actionArgumentsQuote))->render();
        }    
    ?>
 </div>
