<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Widget\FormFields;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductCustom\ProductCustom;
use App\Infrastructure\Persistence\ProductImage\ProductImage;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\Unit\Unit;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Invoice\Family\FamilyRepository as fR;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Helpers\InvRecalculator;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\Helpers\QuoteRecalculator;
// Product
use App\Invoice\Product\ProductRepository as pR;
use App\Invoice\ProductCustom\ProductCustomRepository as pcR;
use App\Invoice\ProductCustom\ProductCustomService;
use App\Invoice\ProductCustom\ProductCustomForm;
use App\Invoice\ProductImage\ProductImageRepository as piR;
// Quote
use App\Invoice\QuoteItem\QuoteItemForm;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as qiaS;
// Inv
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItemAmount\InvItemAmountService as iiaS;
// Setting, TaxRate, Unit
use App\Invoice\ProductClient\ProductClientRepository as productClientR;
use App\Invoice\ProductProperty\ProductPropertyRepository as ppR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as uR;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as qiaR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Service\WebControllerService;
use App\User\UserService;
//  Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Yiisoft
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\FastRoute\UrlGenerator as FastRouteGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ProductController extends BaseController
{
    protected string $controllerName = 'invoice/product';

    private const string FILTER_FAMILY = 'ff';
    private const string FILTER_PRODUCT = 'fp';
    private const string RESET_TRUE = 'rt';
    private string $ffc = self::FILTER_FAMILY;
    private string $fpc = self::FILTER_PRODUCT;
    private string $rtc = self::RESET_TRUE;

    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private FormFields $formFields,
        private ProductService $productService,
        private ProductCustomService $productCustomService,
        private ProductFormDependencies $formDeps,
        private InvRecalculator $invRecalculator,
        private QuoteRecalculator $quoteRecalculator,
        private QuoteItemService $quoteitemService,
        private InvItemService $invitemService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->responseFactory = $responseFactory;
        $this->productService = $productService;
        $this->productCustomService = $productCustomService;
        $this->formDeps = $formDeps;
        $this->invRecalculator = $invRecalculator;
        $this->quoteRecalculator = $quoteRecalculator;
        $this->quoteitemService = $quoteitemService;
        $this->invitemService = $invitemService;
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $d = $this->formDeps;
        $countries = new CountryHelper();
        $peppolarrays = new PeppolArrays();
        $form = new ProductForm();
        $productCustomForm = new ProductCustomForm();
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'product/add',
            'actionArguments' => [],
            'countries' =>
                $countries->getCountryList(
                        (string) $this->session->get('_language')),
            'alert' => $this->alert(),
            'form' => $form,
            'errors' => [],
            'errorsCustom' => [],
            'standard_item_identification_schemeids' =>
                $peppolarrays->getIso6523Icd(),
            'item_classification_code_listids' => $peppolarrays->getUncl7143(),
            'families' => $this->families($d->familyRepository->findAllPreloaded()),
            'units' => $this->units($d->unitRepository->findAllPreloaded()),
            'taxRates' => $this->taxRates($d->taxRateRepository->findAllPreloaded()),
            'unitPeppols' => $this->unitPeppols($d->unitPeppolRepository->findAllPreloaded()),
            'customFields' => $this->fetchCustomFieldsAndValues(
                    $d->customFieldRepository, $d->customValueRepository, 'product_custom')['customFields'],
            'customValues' => $this->fetchCustomFieldsAndValues(
                    $d->customFieldRepository, $d->customValueRepository, 'product_custom')['customValues'],
            'cvH' => new CVH($this->sR, $d->customValueRepository),
            'productCustomValues' => [],
            'productCustomForm' => $productCustomForm,
            'formFields' => $this->formFields,
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $product = new Product();
                    $this->productService->saveProduct($product, $body);
                    if ($product->hasIdentity()) {
                        if (isset($body['custom'])) {
                            // Retrieve the custom array
                            /** @var array $custom */
                            $custom = $body['custom'];
                            /**
                             * @var int $custom_field_id
                             * @var array|string $value
                             */
                            foreach ($custom as $custom_field_id => $value) {
                                $productCustom = new ProductCustom();
                                $formProductCustom = new ProductCustomForm();
                                $product_custom = [];
                                $product_custom['product_id'] = $product->reqId();
                                $product_custom['custom_field_id'] = $custom_field_id;
                                $product_custom['value'] = is_array($value) ? serialize($value) : $value;
                                if ($formHydrator->populateAndValidate(
                                        $formProductCustom, $product_custom)) {
                                    $this->productCustomService->saveProductCustom(
                                            $productCustom, $product_custom);
                                }
// These two can be used to create customised labels for custom field error
// validation on the form. Currently not used.
                                $parameters['formProductCustom'] = $formProductCustom;
                                $parameters['errorsCustom'] = $formProductCustom->getValidationResult()->getErrorMessagesIndexedByProperty();
                            }
                        }
                        $this->flashMessage('info',
                                $this->translator->translate(
                                        'record.successfully.created'));
                        return $this->webService->getRedirectResponse('product/index');
                    }
                }
            } else {
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
        }
        $parameters['form'] = $form;
        return $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param int $id
     * @param FormHydrator $formHydrator
     * @param pR $pR
     * @param pcR $pcR
     * @return Response
     */
    public function edit(
        Request $request,
        #[RouteArgument('id')]
        int $id,
        FormHydrator $formHydrator,
        pR $pR,
        pcR $pcR,
    ): Response {
        $d = $this->formDeps;
        $countries = new CountryHelper();
        $peppolarrays = new PeppolArrays();
        $product = $this->product($id, $pR);
        if ($product) {
            $product_id = $product->reqId();
            $form = ProductForm::show($product);
            $productCustomForm = new ProductCustomForm();
            if ($product_id) {
                $parameters = [
                    'title' => $this->translator->translate('edit'),
                    'actionName' => 'product/edit',
                    'actionArguments' => ['id' => $product_id],
                    'alert' => $this->alert(),
                    'countries' =>
                        $countries->getCountryList(
                                (string) $this->session->get('_language')),
                    'form' => $form,
                    'errors' => [],
                    'errorsCustom' => [],
                    'standard_item_identification_schemeids' =>
                        $peppolarrays->getIso6523Icd(),
                    'item_classification_code_listids' => $peppolarrays->getUncl7143(),
                    'families' => $this->families($d->familyRepository->findAllPreloaded()),
                    'units' => $this->units($d->unitRepository->findAllPreloaded()),
                    'taxRates' => $this->taxRates($d->taxRateRepository->findAllPreloaded()),
                    'unitPeppols' => $this->unitPeppols($d->unitPeppolRepository->findAllPreloaded()),
                    'customFields' => $this->fetchCustomFieldsAndValues(
                            $d->customFieldRepository, $d->customValueRepository, 'product_custom')['customFields'],
                    'customValues' => $this->fetchCustomFieldsAndValues(
                            $d->customFieldRepository, $d->customValueRepository, 'product_custom')['customValues'],
                    'cvH' => new CVH($this->sR, $d->customValueRepository),
                    'productCustomValues' =>
                        $this->productCustomValues($product_id, $pcR),
                    'productCustomForm' => $productCustomForm,
                    'formFields' => $this->formFields,
                ];
                if ($request->getMethod() === Method::POST) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $returned_form = $this->saveFormFields($body, $form, $product, $formHydrator);
                        $parameters['body'] = $body;
                        if (!$returned_form->isValid()) {
                            $parameters['form'] = $returned_form;
                            $parameters['errors'] =
            $returned_form->getValidationResult()->getErrorMessagesIndexedByProperty();
                            return $this->webViewRenderer->render('_form', $parameters);
                        }
                        // Only save custom fields if they exist
                        if ($d->customFieldRepository->repoTableCountquery('product_custom') > 0 && isset($body['custom'])) {
                            $custom = (array) $body['custom'];
                            $errorsCustom = [];
                            /** @var array|string $value */
                            foreach ($custom as $custom_field_id => $value) {
                                $product_custom =
                                    $pcR->repoFormValuequery(
                                        $product_id, (int) $custom_field_id);

                                // If product_custom doesn't exist, create a new one
                                if (null === $product_custom) {
                                    $product_custom = new ProductCustom();
                                }

                                $product_custom_input = [
                                    'product_id' => $product_id,
                                    'custom_field_id' => (int) $custom_field_id,
                                    'value' => is_array($value) ? serialize($value) : $value,
                                ];

                                $productCustomForm = new ProductCustomForm();
                                if ($formHydrator->populateAndValidate(
                                    $productCustomForm, $product_custom_input)) {
                                    $this->productCustomService->saveProductCustom(
                                        $product_custom, $product_custom_input);
                                } else {
                                    $errorsCustom = array_merge($errorsCustom,                                                       $productCustomForm->getValidationResult()
                                            ->getErrorMessagesIndexedByProperty());
                                }
                                $parameters['productCustomForm'] = $productCustomForm;
                            } //foreach

                            // If there are custom field errors, return to form
                            if (count($errorsCustom) > 0) {
                                $parameters['errorsCustom'] = $errorsCustom;
                                return $this->webViewRenderer->render('_form', $parameters);
                            }
                        } // cfR && isset
                    } // is_array
                    $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                    return $this->webService->getRedirectResponse('product/index');
                }
                return $this->webViewRenderer->render('_form', $parameters);
            } // null!==product_id
        } // product
        return $this->webService->getRedirectResponse('product/index');
    }

    /**
     * @param pR $pR
     * @param string $id
     * @return Response
     */
    public function delete(pR $pR, #[RouteArgument('id')] string $id): Response
    {
        try {
            $product = $this->product((int) $id, $pR);
            if ($product) {
                $this->productService->deleteProduct($product);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
            }
            return $this->webService->getRedirectResponse('product/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('product.history'));
            return $this->webService->getRedirectResponse('product/index');
        }
    }

    /**
     * Prepare optionsData $data value for ...resources/view/product/_form select
     * @param EntityReader $families
     * @return array
     */
    private function families(EntityReader $families): array
    {
        $array = [];
        /**
         * @var Family $family
         */
        foreach ($families as $family) {
            $family_id = $family->reqId();
            $array[$family_id] = $family->getFamilyName();
        }
        return $array;
    }

    /**
     * Prepare optionsData $data value for ...resources/view/product/_form select
     * @param EntityReader $units
     * @return array
     */
    private function units(EntityReader $units): array
    {
        $array = [];
        /**
         * @var Unit $unit
         */
        foreach ($units as $unit) {
            $array[$unit->reqId()] = $unit->getUnitName() . ' ' . $unit->getUnitNamePlrl();
        }
        return $array;
    }

    /**
     * Prepare optionsData $data value for ...resources/view/product/_form select
     * @param EntityReader $unit_peppols
     * @return array
     */
    private function unitPeppols(EntityReader $unit_peppols): array
    {
        $array = [];
        /**
         * @var \\App\Infrastructure\Persistence\UnitPeppol\UnitPeppol $unit_peppol
         */
        foreach ($unit_peppols as $unit_peppol) {
            $array[$unit_peppol->reqId()] = $unit_peppol->getCode()
                    . ' --- '
                    . $unit_peppol->getName()
                    . ' --- '
                    . $unit_peppol->getDescription();
        }
        return $array;
    }

    /**
     * Prepare optionsData $data value for ...resources/view/product/_form select
     * @param EntityReader $taxRates
     * @return array
     */
    private function taxRates(EntityReader $taxRates): array
    {
        $array = [];
        /**
         * @var TaxRate $taxRate
         */
        foreach ($taxRates as $taxRate) {
            $taxRateId = $taxRate->reqId();
            $array[$taxRateId] = $taxRate->getTaxRateName();
        }
        return $array;
    }

    /**
     * @param array $body
     * @param ProductForm $form
     * @param Product $product
     * @param FormHydrator $formHydrator
     * @reclsturn ProductForm
     */
    public function saveFormFields(array $body, ProductForm $form, Product $product, FormHydrator $formHydrator): ProductForm
    {
        if ($formHydrator->populateAndValidate($form, $body)) {
            $this->productService->saveProduct($product, $body);
        }
        return $form;
    }

    /**
     * @param FastRouteGenerator $urlFastRouteGenerator
     * @param Request $request
     * @param productClientR $pcR
     * @param pR $pR
     * @param fR $fR
     * @param string $page
     * @return Response
     */
    public function index(FastRouteGenerator $urlFastRouteGenerator,
            Request $request, productClientR $pcR, pR $pR, fR $fR,
            #[RouteArgument('page')] string $page = '1'): Response
    {
        $this->rbac();
        $this->flashMessage('info', $this->translator->translate('productimage.view'));
        $query_params = $request->getQueryParams();

        /**
         * @var string $query_params['page']
         */
        $currentPage = $query_params['page'] ?? $page;
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = (int) $currentPage > 0 ? (int) $currentPage : 1;
        $products = $pR->findAllPreloaded();
        if (isset($query_params['family_id'])
                && !empty($query_params['family_id'])) {
            $products = $pR->filterFamilyId((int) $query_params['family_id']);
        }
        if (isset($query_params['product_sku'])
                && !empty($query_params['product_sku'])) {
            $products = $pR->filterProductSku((string) $query_params['product_sku']);
        }
        if (isset($query_params['product_price'])
                && !empty($query_params['product_price'])) {
            $products = $pR->filterProductPrice((string) $query_params['product_price']);
        }
        if ((isset($query_params['product_sku'])
                && !empty($query_params['product_sku']))
           && (isset($query_params['product_price'])
                   && !empty($query_params['product_price']))) {
            $products =
                    $pR->filterProductSkuPrice(
                            (string) $query_params['product_price'],
                            (string) $query_params['product_sku']);
        }

        $parameters = [
            'alert' => $this->alert(),
            'page' => $currentPageNeverZero,
            'productClientR' => $pcR,
            'defaultPageSizeOffsetPaginator' =>
                (int) $this->sR->getSetting('default_list_limit'),
            'optionsDataProductsDropdownFilter' =>
                $this->optionsDataProducts($pR),
            'optionsDataFamiliesDropdownFilter' =>
                $this->optionsDataFamilies($fR),
            'products' => $products,
            /** @var string $query_params['sort'] */
            'sortString' => $query_params['sort'] ?? '-id, -product_sku',
            'urlFastRouteGenerator' => $urlFastRouteGenerator,
            'visible' => $this->sR->getSetting('columns_all_visible') == '0' ? false : true,
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * Related logic: see ...\invoice\src\Invoice\Asset\rebuild-1.13\js\product.js $(document).on('click', '#product_filters_submit', function ()
     * Related logic: see ...\product\index.php
     * @param Request $request
     * @return Response
     */
    public function search(Request $request): Response
    {
        $query_params = $request->getQueryParams();
        $product_sku = (string) $query_params['product_sku'];
        if ($product_sku) {
            $parameters = [
                'success' => 1,
                'message' => $this->translator->translate('product.found'),
            ];
        } else {
            $parameters = [
                'success' => 0,
                'messeage' => $this->translator->translate('product.not.found'),
            ];
        }
        return $this->responseFactory->createResponse(Json::encode($parameters));
    }

    /**
     * Related logic: see  ...\src\Invoice\Asset\rebuild-1.13\js\modal_product_lookups.js
     * @param WebViewRenderer $head
     * @param Request $request
     * @param fR $fR
     * @param sR $sR
     * @param pR $pR
     */
    public function lookup(WebViewRenderer $head, Request $request,
            fR $fR, sR $sR, pR $pR): Response
    {
        $queryparams = $request->getQueryParams();
        /** @var string $queryparams[$this->fpc] */
        /** @var string $queryparams[$this->ffc] */
        /** @var string $queryparams[$this->rtc] */
        /** @var string $fp */
        $fp = $queryparams[$this->fpc] ?? '';
        /** @var string $ff */
        $ff = $queryparams[$this->ffc] ?? '';
        /** @var string $rt */
        $rt = $queryparams[$this->rtc] ?? '';

        // Determine which products to fetch
        $useAllProducts = $rt || ($ff == '' && $fp == '');
        $products = $useAllProducts ?
                $pR->findAllPreloadedWithPrice() :
            $pR->repoProductwithfamilyquery($fp, $ff);

        // Debug logging (remove in production)
        error_log("Product lookup - fp: '$fp', ff: '$ff', rt: '$rt',"
                . " useAllProducts: " . ($useAllProducts ? 'true' : 'false'));

        $parameters = [
            'families' => $fR->findAllPreloaded(),
            'filter_product' => $fp,
            'filter_family' => $ff,
            'reset_table' => $rt,
            'head' => $head,
            'products' => $products,
            'default_item_tax_rate' =>
                $sR->getSetting('default_item_tax_rate') !== '' ?: 0,
        ];
        return $this->webViewRenderer->renderPartial(
                '_partial_product_table_modal', $parameters);
    }

    /**
     * @param int $order
     * @param Product $product
     * @param int $quote_id
     * @param pR $pR
     * @param trR $trR
     * @param uR $unR
     * @param QIAR $qiaR
     * @param QIAS $qiaS
     * @param FormHydrator $formHydrator
     */
    private function saveProductLookupItemQuote(int $order,
            Product $product, int $quote_id, pR $pR, trR $trR, uR $unR,
            qiaR $qiaR, qiaS $qiaS, FormHydrator $formHydrator): void
    {
        $quoteItem = new QuoteItem();
        $form = new QuoteItemForm();
        $ajax_content = [
            'name' => $product->getProductName(),
            'quote_id' => $quote_id,
            'tax_rate_id' => $product->reqTaxRateId(),
            'product_id' => $product->reqId(),
            'date_added' => new \DateTimeImmutable(),
            'description' => $product->getProductDescription(),
// A default quantity of 1 is used to initialize the item if there is no
// existing product_price_base_quantity
            'quantity' => $product->getProductPriceBaseQuantity() > 0 ? $product->getProductPriceBaseQuantity() : (float) 1,
            'price' => $product->getProductPrice(),
// The user will determine how much discount to give on this item later
            'discount_amount' => (float) 0,
            'order' => $order,
// The default quantity is 1 so the singular name will be used.
            'product_unit' => $unR->singularOrPluralName($product->reqUnitId(), 1),
            'product_unit_id' => $product->reqUnitId(),
        ];
        if ($formHydrator->populateAndValidate($form, $ajax_content)) {
            $this->quoteitemService->addQuoteItemProduct(
                    $quoteItem, $ajax_content, (string) $quote_id, $pR, $qiaR, $qiaS,
                    $unR, $trR, $this->translator);
        }
    }

    /**
     * @param int $order
     * @param Product $product
     * @param int $inv_id
     * @param pR $pR
     * @param trR $trR
     * @param uR $unR
     * @param iiaR $iiaR
     * @param iiR $iiR
     * @param uR $uR
     * @param FormHydrator $formHydrator
     */
    private function saveProductLookupItemInv(int $order, Product $product,
            int $inv_id, pR $pR, trR $trR, uR $unR, iiaR $iiaR, iiR $iiR,
                uR $uR, FormHydrator $formHydrator): void
    {
        $invItem = new InvItem();
        $form = new InvItemForm();
        $ajax_content = [
            'name' => $product->getProductName(),
            'inv_id' => $inv_id,
            'tax_rate_id' => $product->reqTaxRateId(),
            'product_id' => $product->reqId(),
            'task_id' => null,
            'description' => $product->getProductDescription(),
// A default quantity of 1 is used to initialize the item if there is no
// existing product_price_base_quantity
            'quantity' =>
                $product->getProductPriceBaseQuantity() > 0 ?
                $product->getProductPriceBaseQuantity() : (float) 1,
            'price' => $product->getProductPrice(),
// Vat: Early Settlement Cash Discount subtracted before VAT is calculated
            'discount_amount' => (float) 0,
            'order' => $order,
// The default quantity is 1 so the singular name will be used.
            'product_unit' =>
                $unR->singularOrPluralName($product->reqUnitId(), 1),
            'product_unit_id' => $product->reqUnitId(),
        ];
        if ($formHydrator->populateAndValidate($form, $ajax_content)) {
            $this->invitemService->addInvItemProduct(
                    $invItem, $ajax_content, (string) $inv_id, $pR, $trR,
                    new iiaS($iiaR, $iiR), $iiaR, $this->sR, $uR);
        }
    }

    /**
     * Related logic:
        see  ...resources/views/invoice/product/modal-product-lookups-quote.php
     * Related logic:
        see  ...src\Invoice\Asset\rebuild\js invoice-typescript-iife.js compiled from product.ts
     *  HandleQuoteConfirm selectionQuote
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param pR $pR
     * @param trR $trR
     * @param uR $uR
     * @param qiaR $qiaR
     * @param qiaS $qiaS
     */
    public function selectionQuote(
        FormHydrator $formHydrator,
        Request $request,
        pR $pR,
        trR $trR,
        uR $uR,
        qiaR $qiaR,
        qiaS $qiaS,
    ): Response {
        $select_items = $request->getQueryParams();
        /** @var array $select_items['product_ids'] */
        $product_ids = ($select_items['product_ids'] ?: []);
        /** @var string $quote_id */
        $quote_id = $select_items['quote_id'];
        $products = $pR->findinProducts($product_ids);
        $numberHelper = new NumberHelper($this->sR);
        $order = 1;
        /** @var Product $product */
        foreach ($products as $product) {
            $product->setProductPrice((float) $numberHelper->formatAmount(
                $product->getProductPrice()));
            $this->saveProductLookupItemQuote($order, $product, (int) $quote_id,
                    $pR, $trR, $uR, $qiaR, $qiaS, $formHydrator);
            $order++;
        }
        $this->quoteRecalculator->recalculate((int) $quote_id);
        return $this->responseFactory->createResponse(Json::encode($products));
    }

    /**
     * Related logic: see ...views\invoice\product\modal-product-lookups-inv.php
     * Related logic: see ... src\Invoice\Asset\rebuild-1.13\js\modal_product_lookups.js $(document).on('click', '.select-items-confirm-inv', function ()
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param pR $pR
     * @param trR $trR
     * @param uR $uR
     * @param iiaR $iiaR
     * @param iiR $iiR
     */
    public function selectionInv(
        FormHydrator $formHydrator,
        Request $request,
        pR $pR,
        trR $trR,
        uR $uR,
        iiaR $iiaR,
        iiR $iiR,
    ): Response {
        $select_items = $request->getQueryParams();
        /** @var array $select_items['product_ids'] */
        $product_ids = ($select_items['product_ids'] ?: []);
        /** @var string $inv_id */
        $inv_id = $select_items['inv_id'];
        $products = $pR->findinProducts($product_ids);
        $numberHelper = new NumberHelper($this->sR);
        $order = 1;
        /** @var Product $product */
        foreach ($products as $product) {
            $product->setProductPrice(
                    (float) $numberHelper->formatAmount($product->getProductPrice()));
            $this->saveProductLookupItemInv(
                    $order, $product, (int) $inv_id, $pR, $trR, $uR, $iiaR, $iiR,
                    $uR, $formHydrator);
            $order++;
        }
        $this->invRecalculator->recalculate((int) $inv_id);
        return $this->responseFactory->createResponse(Json::encode($products));
    }

    /**
     * @param int $id
     * @param pR $pR
     * @return Product|null
     */
    private function product(int $id, pR $pR): ?Product
    {
        return $pR->repoProductquery($id);
    }

    /**
     * @param int $product_id
     * @param pcR $pcR
     * @return array
     */
    public function productCustomValues(int $product_id, pcR $pcR): array
    {
// Get all the custom fields that have been registered with this product on
// creation, retrieve existing values via repo, and populate
        // custom_field_form_values array
        $custom_field_form_values = [];
        if ($pcR->repoProductCount($product_id) > 0) {
            $product_custom_fields = $pcR->repoFields($product_id);
            /**
             * @var int $key
             * @var string $val
             */
            foreach ($product_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . (string) $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }

    /**
     * @return Response|true
     */
    /** @psalm-suppress UnusedReturnValue */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('product/index');
        }
        return $canEdit;
    }

    /**
     * @param pR $pR
     * @param pcR $pcR
     * @param ppR $ppR
     * @param piR $piR
     * @param string $id
     */
    public function view(
        pR $pR,
        pcR $pcR,
        ppR $ppR,
        piR $piR,
        #[RouteArgument('id')]
        string $id,
    ): Response {
        $d = $this->formDeps;
        $product = $this->product((int) $id, $pR);
        $language = (string) $this->session->get('_language');
        $peppolarrays = new PeppolArrays();
        if ($product) {
            $productForm = ProductForm::show($product);
            $productCustomForm = new ProductCustomForm();
            $product_id = $product->reqId();
            $product_images = $piR->repoProductImageProductquery($product_id);
            $cfRepo = $d->customFieldRepository;
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'product/view',
                'actionArguments' => ['id' => $product_id],
                'partial_product_details' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/product/views/partial_product_details',
                    [
                        'form' => $productForm,
                        'standard_item_identification_schemeids' => $peppolarrays->getIso6523Icd(),
                        'item_classification_code_listids' => $peppolarrays->getUncl7143(),
                        'families' => $this->families($d->familyRepository->findAllPreloaded()),
                        'units' => $this->units($d->unitRepository->findAllPreloaded()),
                        'tax_rates' => $this->taxRates($d->taxRateRepository->findAllPreloaded()),
                        'unit_peppols' => $this->unitPeppols($d->unitPeppolRepository->findAllPreloaded()),
                        'custom_fields' => $cfRepo->repoTablequery('product_custom'),
                        'custom_values' => $d->customValueRepository->fixCfValueToCf(
                                $cfRepo->repoTablequery('product_custom')),
                        'cvH' => new CVH($this->sR, $d->customValueRepository),
                        'product_custom_values' => $this->productCustomValues($product_id, $pcR),
                        'productCustomForm' => $productCustomForm,
                        'upR' => $d->unitPeppolRepository,
                        'product' => $pR->repoProductquery($product_id),
                    ],
                ),
                'partial_product_properties' =>
                    $this->webViewRenderer->renderPartialAsString(
                    '//invoice/product/views/partial_product_properties',
                    [
                        'product' => $pR->repoProductquery($product_id),
                        'language' => $language,
                        'productpropertys' =>
                            $this->webViewRenderer->renderPartialAsString(
                                    '//invoice/product/views/property_index', [
                            'all' => $ppR->findAllProduct($product_id),
                            'language' => $language,
                        ]),
                    ],
                ),
                'partial_product_images' =>
                    $this->viewPartialProductImage($language, $product_id, $piR),
                'partial_product_gallery' =>
                    $this->webViewRenderer->renderPartialAsString(
                            '//invoice/product/views/partial_product_gallery', [
                    'product' => $product,
                    'productImages' => $product_images,
                    'invEdit' => $this->userService->hasPermission(Permissions::EDIT_INV),
                    'invView' => $this->userService->hasPermission(Permissions::VIEW_INV),
                ]),
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('product/index');
    }

    /**
     * @param string $tmp
     * @param string $target
     * @param int $product_id
     * @param string $fileName
     * @param piR $piR
     * @return bool
     */
    private function imageAttachmentMoveTo(
        string $tmp,
        string $target,
        int $product_id,
        string $fileName,
        piR $piR,
    ): bool {
        $file_exists = file_exists($target);
// The file does not exist yet in the target path but it exists in the tmp
// folder on the server
        if (!$file_exists) {
            if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $target)) {
                $track_file = new ProductImage();
                $track_file->setProductId($product_id);
                $track_file->setFileNameOriginal($fileName);
                $track_file->setFileNameNew($fileName);
                $track_file->setUploadedDate(new \DateTimeImmutable());
                $piR->save($track_file);
                $this->flashMessage('info',
                        $this->translator->translate('productimage.uploaded.to')
                            . $target);
                return true;
            }
            $this->flashMessage('warning',
                $this->translator->translate('productimage.possible.file.upload.attack')
                    . $tmp);
            return false;
        }
        $this->flashMessage('warning',
                $this->translator->translate('error_duplicate_file'));
        return false;
    }

    /**
     * Upload a product image file
     *
     * @param string $id
     * @param PR $pR
     * @param PIR $piR
     */
    public function imageAttachment(#[RouteArgument('id')] string $id, pR $pR, piR $piR): Response
    {
        $aliases = $this->sR->getProductimagesFilesFolderAliases();
        // https://github.com/yiisoft/yii2/issues/3566
        // Save the image directly to the web accessible folder - assets/publc/product
        $targetPath = $aliases->get('@public_product_images');
        $product_id = $id;
        if ($product_id) {
            if (!is_writable($targetPath)) {
                return $this->responseFactory->createResponse(
                    $this->imageAttachmentNotWritable((int) $product_id));
            }
            $product = $pR->repoProductquery((int) $product_id) ?: null;
            if ($product instanceof Product) {
                $product_id = $product->reqId();
                if ($product_id) {
                    if (!empty($_FILES)) {
                        // Related logic: see https://github.com/vimeo/psalm/issues/5458
                        /** @var array $_FILES['ImageAttachForm'] */
                        /** @var string $_FILES['ImageAttachForm']['tmp_name']['attachFile'] */
                        $temporary_file = $_FILES['ImageAttachForm']['tmp_name']['attachFile'];
                        /** @var string $_FILES['ImageAttachForm']['name']['attachFile'] */
                        $original_file_name = preg_replace('/\s+/', '_', $_FILES['ImageAttachForm']['name']['attachFile']);
                        if (null !== $original_file_name) {
                            $target_path_with_filename = $targetPath . '/' . $original_file_name;
                            if ($this->imageAttachmentMoveTo($temporary_file,
                                    $target_path_with_filename, $product_id,
                                    $original_file_name, $piR)) {
                                return $this->responseFactory->createResponse(
                                        $this->imageAttachmentSuccessfullyCreated(
                                                $product_id));
                            }
                            return $this->responseFactory->createResponse($this->imageAttachmentNoFileUploaded($product_id));
                        }
                    } else {
                        return $this->responseFactory->createResponse(
                            $this->imageAttachmentNoFileUploaded($product_id));
                    }
                } // $product_id
            } // $product
            return $this->webService->getRedirectResponse('product/index');
        } //null!==$product_id
        return $this->webService->getRedirectResponse('product/index');
    }

    /**
     * @param string $_language
     * @param int $product_id
     * @param piR $piR
     * @return string
     */
    private function viewPartialProductImage(string $_language, int $product_id, piR $piR): string
    {
        $productimages = $piR->repoProductImageProductquery($product_id);
        $paginator = new OffsetPaginator($productimages);
        $invEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        $invView = $this->userService->hasPermission(Permissions::VIEW_INV);
        return $this->webViewRenderer->renderPartialAsString('//invoice/product/views/partial_product_image', [
            'form' => new ImageAttachForm(),
            'invEdit' => $invEdit,
            'invView' => $invView,
            'partial_product_image_info' => $this->webViewRenderer->renderPartialAsString('//invoice/product/views/partial_product_image_info'),
            'partial_product_image_list' => $this->webViewRenderer->renderPartialAsString('//invoice/product/views/partial_product_image_list', [
                'paginator' => $paginator,
                'invEdit' => $invEdit,
            ]),
            'actionName' => 'product/imageAttachment',
            'actionArguments' => ['id' => $product_id, '_language' => $_language],
        ]);
    }

    /**
     * @param int product_id
     * @return string
     */
    private function imageAttachmentNotWritable(int $product_id): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/inv_message',
            [
                'heading' => $this->translator->translate('errors'),
                'message' => $this->translator->translate('path') . $this->translator->translate('is.not.writable'),
                'url' => 'product/view', 'id' => $product_id,
            ],
        );
    }

    /**
     * @param int $product_id
     * @return string
     */
    private function imageAttachmentSuccessfullyCreated(int $product_id): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/inv_message',
            [
                'heading' => '',
                'message' => $this->translator->translate('record.successfully.created'),
                'url' => 'product/view', 'id' => $product_id,
            ],
        );
    }

    /**
     * @param int $product_id
     * @return string
     */
    private function imageAttachmentNoFileUploaded(int $product_id): string
    {
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/inv_message',
            [
                'heading' => $this->translator->translate('errors'),
                'message' => $this->translator->translate('productimage.no.file.uploaded'),
                'url' => 'product/view', 'id' => $product_id,
            ],
        );
    }

    /**
     * @param string $product_image_id
     * @param piR $piR
     */
    public function downloadImageFile(
        #[RouteArgument('product_image_id')]
        string $product_image_id,
        piR $piR,
        sR $sR,
    ): void {
        if ($product_image_id) {
            $product_image = $piR->repoProductImagequery((int) $product_image_id);
            if (null !== $product_image) {
                $aliases = $sR->getProductimagesFilesFolderAliases();
                $targetPath = $aliases->get('@productimages_files');
                $original_file_name = $product_image->getFileNameOriginal();
                $target_path_with_filename = $targetPath . '/' . $original_file_name;
                $path_parts = pathinfo($target_path_with_filename);
                $file_ext = $path_parts['extension'] ?? '';
                if (file_exists($target_path_with_filename)) {
                    $file_size = filesize($target_path_with_filename);
                    if ($file_size != false) {
                        $allowed_content_type_array = $piR->getContentTypes();
                        // Check extension against allowed content file types Related logic: see ProductImageRepository getContentTypes
                        $save_ctype = isset($allowed_content_type_array[$file_ext]);
                        /** @var string $ctype */
                        $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] : $piR->getContentTypeDefaultOctetStream();
                        // https://www.php.net/manual/en/function.header.php
                        // Remember that header() must be called before any actual output is sent, either by normal HTML tags,
                        // blank lines in a file, or from PHP.
                        header('Expires: -1');
                        header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
                        header("Content-Disposition: attachment; filename=\"$original_file_name\"");
                        header('Content-Type: ' . $ctype);
                        header('Content-Length: ' . (string) $file_size);
                        echo file_get_contents($target_path_with_filename, true);
                    } // if file_size <> false
                    exit;
                } //if file_exists
                exit;
            } //null!==product_image
            exit;
        } //null!==$product_image_id
        exit;
    }

    /**
     * @param ProductRepository $pR
     * @return array
     */
    public function optionsDataProducts(pR $pR): array
    {
        $optionsDataProducts = [];
        $products = $pR->findAllPreloaded();
        /**
         * @var Product $product
         */
        foreach ($products as $product) {
            $productSku = $product->getProductSku();
            // Remove repeats
            if (!in_array($product->getProductSku(), $optionsDataProducts)
                // Include the $productSku as 'key' so that Url Query Parameter
                // picks it up.
                // Tip: After selecting a value in the dropdown, or inputting into an input box always see
                // how the browser's query url Parameter is being influenced by the selection or input
                && null !== $productSku) {
                $optionsDataProducts[$productSku] = $product->getProductSku();
            }
        }
        return $optionsDataProducts;
    }

    /**
     * @param fR $fR
     * @return array
     */
    public function optionsDataFamilies(fR $fR): array
    {
        $optionsDataFamilies = [];
        $families = $fR->findAllPreloaded();
        /**
         * @var Family $family
         */
        foreach ($families as $family) {
            $familyId = $family->reqId();
            // Remove repeats
            if (!in_array($family->reqId(), $optionsDataFamilies) && $familyId > 0) {
                $optionsDataFamilies[(string) $familyId] = (string) $family->getFamilyName();
            }
        }
        return $optionsDataFamilies;
    }
}
