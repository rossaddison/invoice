<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\BaseController;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Invoice\Helpers\InvRecalculator;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\QuoteRecalculator;
use App\Invoice\InvItem\IiAddProductDeps;
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItemAmount\InvItemAmountService as iiaS;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\QuoteItem\QiAddProductDeps;
use App\Invoice\QuoteItem\QuoteItemForm;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as qiaR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as qiaS;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as uR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Json\Json;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Handles product-lookup selection for quotes and invoices.
 * Extracted from ProductController to satisfy S1448 (≤20 methods per class).
 */
final class ProductSelectionController extends BaseController
{
    protected string $controllerName = 'invoice/product';

    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private InvRecalculator $invRecalculator,
        private QuoteRecalculator $quoteRecalculator,
        private QuoteItemService $quoteitemService,
        private InvItemService $invitemService,
        WebControllerService $webService,
        UserService $userService,
        TranslatorInterface $translator,
        WebViewRenderer $webViewRenderer,
        SessionInterface $session,
        \App\Invoice\Setting\SettingRepository $sR,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    /**
     * Related logic: see resources/views/invoice/product/modal-product-lookups-quote.php
     */
    public function selectionQuote(
        FormHydrator $formHydrator,
        Request $request,
        ProductRepository $pR,
        trR $trR,
        uR $uR,
        qiaR $qiaR,
        qiaS $qiaS,
    ): Response {
        $select_items = $request->getQueryParams();
        /** @var array $select_items['product_ids'] */
        $product_ids  = ($select_items['product_ids'] ?: []);
        /** @var string $quote_id */
        $quote_id     = $select_items['quote_id'];
        $products     = $pR->findinProducts($product_ids);
        $numberHelper = new NumberHelper($this->sR);
        $order        = 1;

        /** @var Product $product */
        foreach ($products as $product) {
            $product->setProductPrice(
                (float) $numberHelper->formatAmount($product->getProductPrice())
            );
            $this->saveProductLookupItemQuote(
                $order, $product, (int) $quote_id,
                new ProductLookupQuoteDeps($pR, $trR, $uR, $qiaR, $qiaS),
                $formHydrator
            );
            $order++;
        }

        $this->quoteRecalculator->recalculate((int) $quote_id);
        return $this->responseFactory->createResponse(Json::encode($products));
    }

    /**
     * Related logic: see resources/views/invoice/product/modal-product-lookups-inv.php
     */
    public function selectionInv(
        FormHydrator $formHydrator,
        Request $request,
        ProductRepository $pR,
        trR $trR,
        uR $uR,
        iiaR $iiaR,
        iiR $iiR,
    ): Response {
        $select_items = $request->getQueryParams();
        /** @var array $select_items['product_ids'] */
        $product_ids  = ($select_items['product_ids'] ?: []);
        /** @var string $inv_id */
        $inv_id       = $select_items['inv_id'];
        $products     = $pR->findinProducts($product_ids);
        $numberHelper = new NumberHelper($this->sR);
        $order        = 1;

        /** @var Product $product */
        foreach ($products as $product) {
            $product->setProductPrice(
                (float) $numberHelper->formatAmount($product->getProductPrice())
            );
            $this->saveProductLookupItemInv(
                $order, $product, (int) $inv_id,
                new ProductLookupInvDeps($pR, $trR, $uR, $iiaR, $iiR),
                $formHydrator
            );
            $order++;
        }

        $this->invRecalculator->recalculate((int) $inv_id);
        return $this->responseFactory->createResponse(Json::encode($products));
    }

    private function saveProductLookupItemQuote(
        int $order,
        Product $product,
        int $quote_id,
        ProductLookupQuoteDeps $deps,
        FormHydrator $formHydrator,
    ): void {
        $quoteItem    = new QuoteItem();
        $form         = new QuoteItemForm();
        $ajax_content = [
            'name'         => $product->getProductName(),
            'quote_id'     => $quote_id,
            'tax_rate_id'  => $product->reqTaxRateId(),
            'product_id'   => $product->reqId(),
            'date_added'   => new \DateTimeImmutable(),
            'description'  => $product->getProductDescription(),
            'quantity'     => $product->getProductPriceBaseQuantity() > 0
                ? $product->getProductPriceBaseQuantity()
                : (float) 1,
            'price'           => $product->getProductPrice(),
            'discount_amount' => (float) 0,
            'order'           => $order,
            'product_unit'    => $deps->uR->singularOrPluralName($product->reqUnitId(), 1),
            'product_unit_id' => $product->reqUnitId(),
        ];
        if ($formHydrator->populateAndValidate($form, $ajax_content)) {
            $this->quoteitemService->addQuoteItemProduct(
                $quoteItem, $ajax_content, (string) $quote_id,
                new QiAddProductDeps(
                    $deps->pR, $deps->qiaR, $deps->qiaS, $deps->uR, $deps->trR, $this->translator
                )
            );
        }
    }

    private function saveProductLookupItemInv(
        int $order,
        Product $product,
        int $inv_id,
        ProductLookupInvDeps $deps,
        FormHydrator $formHydrator,
    ): void {
        $invItem      = new InvItem();
        $form         = new InvItemForm();
        $ajax_content = [
            'name'         => $product->getProductName(),
            'inv_id'       => $inv_id,
            'tax_rate_id'  => $product->reqTaxRateId(),
            'product_id'   => $product->reqId(),
            'task_id'      => null,
            'description'  => $product->getProductDescription(),
            'quantity'     => $product->getProductPriceBaseQuantity() > 0
                ? $product->getProductPriceBaseQuantity()
                : (float) 1,
            'price'           => $product->getProductPrice(),
            'discount_amount' => (float) 0,
            'order'           => $order,
            'product_unit'    => $deps->uR->singularOrPluralName($product->reqUnitId(), 1),
            'product_unit_id' => $product->reqUnitId(),
        ];
        if ($formHydrator->populateAndValidate($form, $ajax_content)) {
            $this->invitemService->addInvItemProduct(
                $invItem, $ajax_content, (string) $inv_id,
                new IiAddProductDeps(
                    $deps->pR, $deps->trR,
                    new iiaS($deps->iiaR, $deps->iiR),
                    $deps->iiaR, $this->sR, $deps->uR
                )
            );
        }
    }
}
