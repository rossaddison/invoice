<?php

declare(strict_types=1);

namespace App\Invoice\Product;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Widget\ProductFormFields;
use App\Infrastructure\Persistence\Product\Product;
use App\Infrastructure\Persistence\ProductCustom\ProductCustom;
use App\Infrastructure\Persistence\ProductImage\ProductImage;
use App\Invoice\Family\FamilyRepository as fR;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Helpers\Peppol\PeppolArrays;
use App\Invoice\ProductCustom\ProductCustomRepository as pcR;
use App\Invoice\ProductCustom\ProductCustomService;
use App\Invoice\ProductCustom\ProductCustomForm;
use App\Invoice\ProductImage\ProductImageRepository as piR;
use App\Invoice\ProductProperty\ProductPropertyRepository as ppR;
use App\Invoice\ProductClient\ProductClientRepository as productClientR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\Product\Widget\ProductsListWidget;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ProductController extends BaseController
{
    protected string $controllerName = 'invoice/product';

    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private ProductFormFields $formFields,
        private ProductService $productService,
        private ProductCustomService $productCustomService,
        private ProductFormDependencies $formDeps,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    public function add(Request $request, FormHydrator $formHydrator): Response
    {
        $d           = $this->formDeps;
        $countries   = new CountryHelper();
        $peppolarrays = new PeppolArrays();
        $form        = new ProductForm();
        $productCustomForm = new ProductCustomForm();
        $customData  = $this->fetchCustomFieldsAndValues(
            $d->customFieldRepository, $d->customValueRepository, 'product_custom'
        );
        $parameters  = [
            'title'          => $this->translator->translate('add'),
            'actionName'     => 'product/add',
            'actionArguments' => [],
            'countries'      => $countries->getCountryList((string) $this->session->get('_language')),
            'alert'          => $this->alert(),
            'form'           => $form,
            'errors'         => [],
            'errorsCustom'   => [],
            'standard_item_identification_schemeids' => $peppolarrays->getIso6523Icd(),
            'item_classification_code_listids'        => $peppolarrays->getUncl7143(),
            'families'       => ProductSelectData::families($d->familyRepository->findAllPreloaded()),
            'units'          => ProductSelectData::units($d->unitRepository->findAllPreloaded()),
            'taxRates'       => ProductSelectData::taxRates($d->taxRateRepository->findAllPreloaded()),
            'unitPeppols'    => ProductSelectData::unitPeppols($d->unitPeppolRepository->findAllPreloaded()),
            'customFields'   => $customData['customFields'],
            'customValues'   => $customData['customValues'],
            'cvH'            => new CVH($this->sR, $d->customValueRepository),
            'productCustomValues'  => [],
            'productCustomForm'    => $productCustomForm,
            'formFields'     => $this->formFields,
        ];
        if ($request->getMethod() === Method::POST) {
            $redirect = $this->handleAddPost($request, $formHydrator, $form, $parameters);
            if ($redirect !== null) {
                return $redirect;
            }
        }
        $parameters['form'] = $form;
        return $this->webViewRenderer->render('_form', $parameters);
    }

    /** @param array<string, mixed> $parameters */
    private function handleAddPost(
        Request $request,
        FormHydrator $formHydrator,
        ProductForm $form,
        array &$parameters,
    ): ?Response {
        if (!$formHydrator->populateFromPostAndValidate($form, $request)) {
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            return null;
        }
        $body = $request->getParsedBody() ?? [];
        if (!is_array($body)) {
            return null;
        }
        return $this->persistNewProduct($body, $formHydrator, $parameters);
    }

    /** @param array<string, mixed> $parameters */
    private function persistNewProduct(
        array $body,
        FormHydrator $formHydrator,
        array &$parameters,
    ): ?Response {
        $product = new Product();
        $this->productService->saveProduct($product, $body);
        if (!$product->hasIdentity()) {
            return null;
        }
        if (isset($body['custom']) && is_array($body['custom'])) {
            /** @var array<array-key, array|string> $custom */
            $custom = $body['custom'];
            $this->saveProductCustomFields($product, $custom, $formHydrator, $parameters);
        }
        $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
        return $this->webService->getRedirectResponse('product/index');
    }

    /**
     * @param array<array-key, array|string> $custom
     * @param array<string, mixed> $parameters
     */
    private function saveProductCustomFields(
        Product $product,
        array $custom,
        FormHydrator $formHydrator,
        array &$parameters,
    ): void {
        foreach ($custom as $custom_field_id => $value) {
            $productCustom      = new ProductCustom();
            $formProductCustom  = new ProductCustomForm();
            $product_custom     = [
                'product_id'      => $product->reqId(),
                'custom_field_id' => $custom_field_id,
                'value'           => is_array($value) ? serialize($value) : $value,
            ];
            if ($formHydrator->populateAndValidate($formProductCustom, $product_custom)) {
                $this->productCustomService->saveProductCustom($productCustom, $product_custom);
            }
            $parameters['formProductCustom'] = $formProductCustom;
            $parameters['errorsCustom']      = $formProductCustom
                ->getValidationResult()
                ->getErrorMessagesIndexedByProperty();
        }
    }

    public function edit(
        Request $request,
        #[RouteArgument('id')]
        int $id,
        FormHydrator $formHydrator,
        ProductRepository $pR,
        pcR $pcR,
    ): Response {
        $d           = $this->formDeps;
        $countries   = new CountryHelper();
        $peppolarrays = new PeppolArrays();
        $product     = $this->product($id, $pR);
        if (!$product) {
            return $this->webService->getRedirectResponse('product/index');
        }
        $product_id        = $product->reqId();
        $form              = ProductForm::show($product);
        $productCustomForm = new ProductCustomForm();
        $customData = $this->fetchCustomFieldsAndValues(
            $d->customFieldRepository, $d->customValueRepository, 'product_custom'
        );
        $parameters = [
            'title'          => $this->translator->translate('edit'),
            'actionName'     => 'product/edit',
            'actionArguments' => ['id' => $product_id],
            'alert'          => $this->alert(),
            'countries'      => $countries->getCountryList((string) $this->session->get('_language')),
            'form'           => $form,
            'errors'         => [],
            'errorsCustom'   => [],
            'standard_item_identification_schemeids' => $peppolarrays->getIso6523Icd(),
            'item_classification_code_listids'        => $peppolarrays->getUncl7143(),
            'families'       => ProductSelectData::families($d->familyRepository->findAllPreloaded()),
            'units'          => ProductSelectData::units($d->unitRepository->findAllPreloaded()),
            'taxRates'       => ProductSelectData::taxRates($d->taxRateRepository->findAllPreloaded()),
            'unitPeppols'    => ProductSelectData::unitPeppols($d->unitPeppolRepository->findAllPreloaded()),
            'customFields'   => $customData['customFields'],
            'customValues'   => $customData['customValues'],
            'cvH'            => new CVH($this->sR, $d->customValueRepository),
            'productCustomValues' => $this->productCustomValues($product_id, $pcR),
            'productCustomForm'   => $productCustomForm,
            'formFields'     => $this->formFields,
        ];
        $redirect = null;
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $returned_form = $this->saveFormFields($body, $form, $product, $formHydrator);
                $parameters['body'] = $body;
                if ($returned_form->isValid()) {
                    $hasCustomErrors = false;
                    if ($d->customFieldRepository->repoTableCountquery('product_custom') > 0
                        && isset($body['custom'])
                    ) {
                        $custom       = (array) $body['custom'];
                        $errorsCustom = [];
                        /** @var array|string $value */
                        foreach ($custom as $custom_field_id => $value) {
                            $product_custom = $pcR->repoFormValuequery($product_id, (int) $custom_field_id);
                            if (null === $product_custom) {
                                $product_custom = new ProductCustom();
                            }
                            $product_custom_input = [
                                'product_id'      => $product_id,
                                'custom_field_id' => (int) $custom_field_id,
                                'value'           => is_array($value) ? serialize($value) : $value,
                            ];
                            $productCustomForm = new ProductCustomForm();
                            if ($formHydrator->populateAndValidate($productCustomForm, $product_custom_input)) {
                                $this->productCustomService->saveProductCustom(
                                    $product_custom, $product_custom_input
                                );
                            } else {
                                $errorsCustom = array_merge(
                                    $errorsCustom,
                                    $productCustomForm->getValidationResult()->getErrorMessagesIndexedByProperty()
                                );
                            }
                            $parameters['productCustomForm'] = $productCustomForm;
                        }
                        if (count($errorsCustom) > 0) {
                            $parameters['errorsCustom'] = $errorsCustom;
                            $hasCustomErrors = true;
                        }
                    }
                    if (!$hasCustomErrors) {
                        $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                        $redirect = $this->webService->getRedirectResponse('product/index');
                    }
                } else {
                    $parameters['form']   = $returned_form;
                    $parameters['errors'] =
                        $returned_form->getValidationResult()->getErrorMessagesIndexedByProperty();
                }
            } else {
                $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                $redirect = $this->webService->getRedirectResponse('product/index');
            }
        }
        return $redirect ?? $this->webViewRenderer->render('_form', $parameters);
    }

    public function delete(ProductRepository $pR, #[RouteArgument('id')] string $id): Response
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

    public function saveFormFields(
        array $body,
        ProductForm $form,
        Product $product,
        FormHydrator $formHydrator,
    ): ProductForm {
        if ($formHydrator->populateAndValidate($form, $body)) {
            $this->productService->saveProduct($product, $body);
        }
        return $form;
    }

    public function index(
        Request $request,
        productClientR $pcR,
        ProductRepository $pR,
        fR $fR,
        HtmlResponseFactory $htmlResponseFactory,
        #[RouteArgument('page')] string $page = '1',
    ): Response {
        $this->rbac();
        $this->flashMessage('info', $this->translator->translate('productimage.view'));
        $q                    = $request->getQueryParams();
        $queryPage            = isset($q['page']) ? (int) $q['page'] : null;
        $currentPageNeverZero = max(1, $queryPage ?? (int) $page);
        /** @psalm-suppress MixedAssignment */
        $sortString = isset($q['sort']) ? (string) $q['sort'] : '-id';

        $products = $pR->findAllPreloaded();
        if (!empty($q['family_id'])) {
            $products = $pR->filterFamilyId((int) $q['family_id']);
        }
        if (!empty($q['product_sku']) && !empty($q['product_price'])) {
            $products = $pR->filterProductSkuPrice((string) $q['product_price'], (string) $q['product_sku']);
        } elseif (!empty($q['product_sku'])) {
            $products = $pR->filterProductSku((string) $q['product_sku']);
        } elseif (!empty($q['product_price'])) {
            $products = $pR->filterProductPrice((string) $q['product_price']);
        }

        $sort = Sort::only([
            'id', 'family_id', 'unit_id', 'tax_rate_id',
            'product_name', 'product_sku', 'product_price',
            'product_description', 'product_price_base_quantity',
        ])->withOrderString($sortString);

        /** @psalm-suppress InvalidArgument */
        $paginator = (new OffsetPaginator($products))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPageNeverZero)
            ->withSort($sort);

        $gridSummary = $this->sR->gridSummary(
            $paginator, $this->translator,
            (int) $this->sR->getSetting('default_list_limit'),
            $this->translator->translate('products'), '',
        );

        $visible                           = $this->sR->getSetting('columns_all_visible') !== '0';
        $optionsDataProductsDropdownFilter = ProductSelectData::optionsDataProducts($pR);
        $optionsDataFamiliesDropdownFilter = ProductSelectData::optionsDataFamilies($fR);

        if ($request->hasHeader('Hx-Request')) {
            return $htmlResponseFactory->createResponse(
                ProductsListWidget::widget()
                    ->withPaginator($paginator)
                    ->withSR($this->sR)
                    ->withPcR($pcR)
                    ->withCsrf((string) ($request->getParsedBody()['_csrf'] ?? ''))
                    ->withVisible($visible)
                    ->withGridSummary($gridSummary)
                    ->withSortString($sortString)
                    ->withOptionsDataProductsDropdownFilter($optionsDataProductsDropdownFilter)
                    ->withOptionsDataFamiliesDropdownFilter($optionsDataFamiliesDropdownFilter)
                    ->render()
            );
        }

        $parameters = [
            'alert'                             => $this->alert(),
            'paginator'                         => $paginator,
            'productClientR'                    => $pcR,
            'defaultPageSizeOffsetPaginator'    => (int) $this->sR->getSetting('default_list_limit'),
            'optionsDataProductsDropdownFilter' => $optionsDataProductsDropdownFilter,
            'optionsDataFamiliesDropdownFilter' => $optionsDataFamiliesDropdownFilter,
            'sortString'                        => $sortString,
            'visible'                           => $visible,
            'gridSummary'                       => $gridSummary,
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    public function search(Request $request): Response
    {
        $query_params = $request->getQueryParams();
        $product_sku  = (string) $query_params['product_sku'];
        if ($product_sku) {
            $parameters = [
                'success' => 1,
                'message' => $this->translator->translate('product.found'),
            ];
        } else {
            $parameters = [
                'success'  => 0,
                'messeage' => $this->translator->translate('product.not.found'),
            ];
        }
        return $this->responseFactory->createResponse(Json::encode($parameters));
    }

    public function lookup(
        WebViewRenderer $head,
        Request $request,
        fR $fR,
        sR $sR,
        ProductRepository $pR,
    ): Response {
        $queryparams = $request->getQueryParams();
        /** @var string $fp */
        $fp = $queryparams['fp'] ?? '';
        /** @var string $ff */
        $ff = $queryparams['ff'] ?? '';
        /** @var string $rt */
        $rt = $queryparams['rt'] ?? '';

        $useAllProducts = $rt || ($ff == '' && $fp == '');
        $products       = $useAllProducts
            ? $pR->findAllPreloadedWithPrice()
            : $pR->repoProductwithfamilyquery($fp, $ff);

        error_log("Product lookup - fp: '$fp', ff: '$ff', rt: '$rt',"
            . " useAllProducts: " . ($useAllProducts ? 'true' : 'false'));

        $parameters = [
            'families'              => $fR->findAllPreloaded(),
            'filter_product'        => $fp,
            'filter_family'         => $ff,
            'reset_table'           => $rt,
            'head'                  => $head,
            'products'              => $products,
            'default_item_tax_rate' => $sR->getSetting('default_item_tax_rate') !== '' ?: 0,
        ];
        return $this->webViewRenderer->renderPartial('_partial_product_table_modal', $parameters);
    }

    public function view(
        ProductRepository $pR,
        pcR $pcR,
        ppR $ppR,
        piR $piR,
        #[RouteArgument('id')]
        string $id,
    ): Response {
        $d        = $this->formDeps;
        $product  = $this->product((int) $id, $pR);
        $language = (string) $this->session->get('_language');
        $peppolarrays = new PeppolArrays();
        if ($product) {
            $productForm       = ProductForm::show($product);
            $productCustomForm = new ProductCustomForm();
            $product_id        = $product->reqId();
            $product_images    = $piR->repoProductImageProductquery($product_id);
            $cfRepo            = $d->customFieldRepository;
            $parameters        = [
                'title'          => $this->translator->translate('view'),
                'actionName'     => 'product/view',
                'actionArguments' => ['id' => $product_id],
                'partial_product_details' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/product/views/partial_product_details',
                    [
                        'form'                                   => $productForm,
                        'standard_item_identification_schemeids' => $peppolarrays->getIso6523Icd(),
                        'item_classification_code_listids'        => $peppolarrays->getUncl7143(),
                        'families'    => ProductSelectData::families($d->familyRepository->findAllPreloaded()),
                        'units'       => ProductSelectData::units($d->unitRepository->findAllPreloaded()),
                        'tax_rates'   => ProductSelectData::taxRates($d->taxRateRepository->findAllPreloaded()),
                        'unit_peppols' => ProductSelectData::unitPeppols($d->unitPeppolRepository->findAllPreloaded()),
                        'custom_fields' => $cfRepo->repoTablequery('product_custom'),
                        'custom_values' => $d->customValueRepository->fixCfValueToCf(
                            $cfRepo->repoTablequery('product_custom')
                        ),
                        'cvH'                  => new CVH($this->sR, $d->customValueRepository),
                        'product_custom_values' => $this->productCustomValues($product_id, $pcR),
                        'productCustomForm'     => $productCustomForm,
                        'upR'                   => $d->unitPeppolRepository,
                        'product'               => $pR->repoProductquery($product_id),
                    ],
                ),
                'partial_product_properties' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/product/views/partial_product_properties',
                    [
                        'product'  => $pR->repoProductquery($product_id),
                        'language' => $language,
                        'productpropertys' => $this->webViewRenderer->renderPartialAsString(
                            '//invoice/product/views/property_index',
                            [
                                'all'      => $ppR->findAllProduct($product_id),
                                'language' => $language,
                            ]
                        ),
                    ],
                ),
                'partial_product_images'  => $this->viewPartialProductImage($language, $product_id, $piR),
                'partial_product_gallery' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/product/views/partial_product_gallery',
                    [
                        'product'       => $product,
                        'productImages' => $product_images,
                        'invEdit'       => $this->userService->hasPermission(Permissions::EDIT_INV),
                        'invView'       => $this->userService->hasPermission(Permissions::VIEW_INV),
                    ]
                ),
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('product/index');
    }

    public function productCustomValues(int $product_id, pcR $pcR): array
    {
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

    private function product(int $id, ProductRepository $pR): ?Product
    {
        return $pR->repoProductquery($id);
    }

    private function viewPartialProductImage(string $_language, int $product_id, piR $piR): string
    {
        $productimages = $piR->repoProductImageProductquery($product_id);
        $paginator     = new OffsetPaginator($productimages);
        $invEdit       = $this->userService->hasPermission(Permissions::EDIT_INV);
        $invView       = $this->userService->hasPermission(Permissions::VIEW_INV);
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/product/views/partial_product_image',
            [
                'form'    => new ImageAttachForm(),
                'invEdit' => $invEdit,
                'invView' => $invView,
                'partial_product_image_info' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/product/views/partial_product_image_info'
                ),
                'partial_product_image_list' => $this->webViewRenderer->renderPartialAsString(
                    '//invoice/product/views/partial_product_image_list',
                    ['paginator' => $paginator, 'invEdit' => $invEdit]
                ),
                'actionName'      => 'product/imageAttachment',
                'actionArguments' => ['id' => $product_id, '_language' => $_language],
            ]
        );
    }
}
