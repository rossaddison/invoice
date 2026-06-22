<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\BaseController;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\FamilyCustom\FamilyCustom;
use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\Family\Widget\FamilyListWidget;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as cpR;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as csR;
use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\Family\FamilyRepository as fR;
use App\Invoice\Product\ProductRepository as pR;
use App\Invoice\Product\ProductService;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as uR;
use App\Invoice\FamilyCustom\FamilyCustomForm;
use App\Invoice\FamilyCustom\FamilyCustomService;
use App\Invoice\FamilyCustom\FamilyCustomRepository as fcR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class FamilyController extends BaseController
{
    protected string $controllerName = 'invoice/family';

    public function __construct(
        private FamilyService $familyService,
        private FamilyCustomService $familyCustomService,
        private FamilyCustomFieldProcessor $familyCustomFieldProcessor,
        private DataResponseFactoryInterface $factory,
        private UrlGeneratorInterface $urlGenerator,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
                $webViewRenderer, $session, $sR, $flash);
        $this->familyService = $familyService;
        $this->factory = $factory;
        $this->urlGenerator = $urlGenerator;
    }

    public function index(
        Request $request,
        HtmlResponseFactory $htmlResponseFactory,
        fR $familyRepository,
        cpR $cpR,
        csR $csR,
        trR $taxRateRepository,
        uR $unitRepository,
    ): Response {
        $q = $request->getQueryParams();
        /** @psalm-suppress MixedAssignment */
        $sortString = isset($q['sort']) ? (string) $q['sort'] : '-id';
        $sort = Sort::only(['id', 'family_name', 'family_commalist', 'family_productprefix'])
            ->withOrderString($sortString);
        $currentPage = max(1, isset($q['page']) ? (int) $q['page'] : 1);
        /** @psalm-suppress InvalidArgument */
        $paginator = (new OffsetPaginator($this->familys($familyRepository)))
            ->withPageSize($this->sR->positiveListLimit())
            ->withCurrentPage($currentPage)
            ->withSort($sort);
        $gridSummary = $this->sR->gridSummary(
            $paginator,
            $this->translator,
            (int) $this->sR->getSetting('default_list_limit'),
            $this->translator->translate('families'),
            '',
        );
        $body = $request->getParsedBody();
        $widget = FamilyListWidget::widget()
            ->withPaginator($paginator)
            ->withCpR($cpR)
            ->withCsR($csR)
            ->withCsrf((string) (is_array($body) ? ($body['_csrf'] ?? '') : ''))
            ->withGridSummary($gridSummary)
            ->withSortString($sortString);
        if ($request->hasHeader('Hx-Request')) {
            return $htmlResponseFactory->createResponse($widget->render());
        }
        $parameters = [
            'alert'                   => $this->alert(),
            'cpR'                     => $cpR,
            'csR'                     => $csR,
            'paginator'               => $paginator,
            'gridSummary'             => $gridSummary,
            'sortString'              => $sortString,
            'modal_generate_products' => $this->indexModalGenerateProducts($taxRateRepository, $unitRepository),
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * Build a 3 tiered dependency drop down search form.
     * On POST the family_name field value is a family ID (keyed by
     * optionsDataFamilyNamesWithCategorySecondaryId), so redirect to view.
     */
    public function search(Request $request, cpR $cpR): Response
    {
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $familyId = (int) ($body['family_name'] ?? 0);
                if ($familyId > 0) {
                    return $this->webService->getRedirectResponse('family/view', ['id' => $familyId]);
                }
            }
        }
        $form = new FamilyForm();
        $parameters = [
            'title' => $this->translator->translate('search.family'),
            'form' => $form,
            'actionName' => 'family/search',
            'actionArguments' => [],
            'categoryPrimaries' => $cpR->optionsDataCategoryPrimaries(),
            'categorySecondaries' => [],
            'familyNames' => [],
        ];
        return $this->webViewRenderer->render('_search', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param cfR $cfR
     * @param cvR $cvR
     * @param cpR $cpR
     * @param csR $csR
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
        cfR $cfR,
        cvR $cvR,
        cpR $cpR,
        csR $csR
    ): Response
    {
        $form = new FamilyForm();
        $familyCustomForm = new FamilyCustomForm();
        $custom = $this->fetchCustomFieldsAndValues($cfR, $cvR, 'family_custom');
        $parameters = [
            'title' => $this->translator->translate('add.family'),
            'actionName' => 'family/add',
            'actionArguments' => [],
            'categoryPrimaries' => $cpR->optionsDataCategoryPrimaries(),
            'categorySecondaries' => $csR->optionsDataCategorySecondaries(),
            'errors' => [],
            'errorsCustom' => [],
            'form' => $form,
            'customFields' => $custom['customFields'],
            'customValues' => $custom['customValues'],
            'familyCustomValues' => [],
            'familyCustomForm' => $familyCustomForm,
        ];
        if ($request->getMethod() !== Method::POST) {
            return $this->webViewRenderer->render('_form', $parameters);
        }
        if (!$formHydrator->populateFromPostAndValidate($form, $request)) {
            $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            return $this->webViewRenderer->render('_form', $parameters);
        }
        $body = $request->getParsedBody() ?? [];
        if (!is_array($body)) {
            return $this->webViewRenderer->render('_form', $parameters);
        }
        $family = new Family();
        $this->familyService->saveFamily($family, $body);
        if (!$family->hasIdentity()) {
            return $this->webViewRenderer->render('_form', $parameters);
        }
        $family_id = $family->reqId();
        if (isset($body['custom'])) {
            /** @var array $custom */
            $custom = $body['custom'];
            $this->saveFamilyCustomFields($family_id, $custom, $formHydrator);
        }
        $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
        return $this->webService->getRedirectResponse('family/index');
    }

    private function saveFamilyCustomFields(
        int $familyId,
        array $custom,
        FormHydrator $formHydrator,
    ): void {
        /** @psalm-suppress MixedAssignment */
        foreach ($custom as $customFieldId => $value) {
            $familyCustom = new FamilyCustom();
            $familyCustomForm = new FamilyCustomForm();
            $familyCustomArray = [
                'family_id'       => $familyId,
                'custom_field_id' => $customFieldId,
                'value'           => is_array($value) ? serialize($value) : $value,
            ];
            if ($formHydrator->populateAndValidate($familyCustomForm, $familyCustomArray)) {
                $this->familyCustomService->saveFamilyCustom($familyCustom, $familyCustomArray);
            }
        }
    }

    /**
     * @param Request $request
     * @param fR $fR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function names(Request $request, fR $fR): \Psr\Http\Message\ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $categorySecondaryId = (int) $queryParams['category_secondary_id'];

        if ($categorySecondaryId > 0) {
            $familyNames = $fR->optionsDataFamilyNamesWithCategorySecondaryId($categorySecondaryId);

            $parameters = [
                'success' => 1,
                'family_names' => $familyNames,
            ];
            return $this->factory->createResponse(Json::encode($parameters));
        }

        $parameters = [
            'success' => 0,
        ];

        //return response to family.js
        return $this->factory->createResponse(Json::encode($parameters));
    }

    /**
     * @param Request $request
     * @param csR $csR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function secondaries(Request $request, csR $csR): \Psr\Http\Message\ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $categoryPrimaryId = (int) $queryParams['category_primary_id'];

        if ($categoryPrimaryId) {
            $secondaryCategories = $csR->optionsDataCategorySecondariesWithCategoryPrimaryId($categoryPrimaryId);

            $parameters = [
                'success' => 1,
                'secondary_categories' => $secondaryCategories,
            ];
            return $this->factory->createResponse(Json::encode($parameters));
        }

        $parameters = [
            'success' => 0,
        ];

        //return response to family.js to reload page at location
        return $this->factory->createResponse(Json::encode($parameters));
    }

    public function edit(
        #[RouteArgument('id')] string $id,
        Request $request,
        FamilyEditDeps $deps,
        FormHydrator $formHydrator
    ): Response
    {
        $family = $this->family((int) $id, $deps->fR);
        if (!$family) {
            return $this->webService->getRedirectResponse('family/index');
        }
        $form = FamilyForm::show($family);
        $familyCustomForm = new FamilyCustomForm();
        $parameters = [
            'title' => $this->translator->translate('edit'),
            'actionName' => 'family/edit',
            'actionArguments' => ['id' => $familyId = $family->reqId()],
            'categoryPrimaries' => $deps->cpR->optionsDataCategoryPrimaries(),
            'categorySecondaries' => $deps->csR->optionsDataCategorySecondaries(),
            'errors' => [],
            'errorsCustom' => [],
            'family' => $family,
            'form' => $form,
            'customFields' => $this->fetchCustomFieldsAndValues(
                    $deps->cfR, $deps->cvR, 'family_custom')['customFields'],
            'customValues' => $this->fetchCustomFieldsAndValues(
                    $deps->cfR, $deps->cvR, 'family_custom')['customValues'],
            'familyCustomValues' => $this->familyCustomValues($familyId, $deps->fcR),
            'familyCustomForm' => $familyCustomForm,
        ];
        $redirect = null;
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $returned_form = $this->saveFormFields($body, $form, $family, $formHydrator);
                $parameters['body'] = $body;
                if (!$returned_form->isValid()) {
                    $parameters['form'] = $returned_form;
                    $parameters['errors'] =
                        $returned_form->getValidationResult()
                                      ->getErrorMessagesIndexedByProperty();
                    return $this->webViewRenderer->render('_form', $parameters);
                }
                $this->processCustomFields(
                    $body,
                    $formHydrator,
                    $this->familyCustomFieldProcessor,
                    $familyId
                );
            } // is_array
            $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
            $redirect = $this->webService->getRedirectResponse('family/index');
        }
        return $redirect ?? $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * @param array $body
     * @param FamilyForm $form
     * @param Family $family
     * @param FormHydrator $formHydrator
     * @return FamilyForm
     */
    public function saveFormFields(array $body, FamilyForm $form, Family $family, FormHydrator $formHydrator): FamilyForm
    {
        if ($formHydrator->populateAndValidate($form, $body)) {
            $commalist = trim((string) ($body['family_commalist'] ?? ''));
            $prefix    = trim((string) ($body['family_productprefix'] ?? ''));
            if ($commalist !== '' && $prefix === '') {
                $this->flash->add(
                    'warning',
                    $this->translator->translate('family.product.prefix.required.when.commalist.filled'),
                    true
                );
                return $form;
            }
            $this->familyService->saveFamily($family, $body);
        }
        return $form;
    }

    /**
     * @param int $family_id
     * @param fcR $fcR
     * @return array
     */
    public function familyCustomValues(int $family_id, fcR $fcR): array
    {
        $custom_field_form_values = [];
        if ($fcR->repoFamilyCount($family_id) > 0) {
            $family_custom_fields = $fcR->repoFields($family_id);
            /**
             * @var int $key
             * @var string $val
             */
            foreach ($family_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . (string) $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }

    /**
     * @param string $id
     * @param FamilyRepository $familyRepository
     * @return Response
     */
    public function delete(#[RouteArgument('id')] string $id, fR $familyRepository): Response
    {
        try {
            $family = $this->family((int) $id, $familyRepository);
            if ($family) {
                $this->familyService->deleteFamily($family);
                return $this->webService->getRedirectResponse('family/index');
            }
            return $this->webService->getRedirectResponse('family/index');
        } catch (\Exception $e) {
            unset($e);
            $this->flashMessage('danger', $this->translator->translate('family.history'));
            return $this->webService->getRedirectResponse('family/index');
        }
    }

    /**
     * @param string $id
     * @param FamilyRepository $familyRepository
     * @param fcR $fcR
     * @param cfR $cfR
     * @param cvR $cvR
     * @param cpR $cpR
     * @param csR $csR
     */
    public function view(#[RouteArgument('id')] string $id, fR $familyRepository,
        fcR $fcR, cfR $cfR, cvR $cvR, cpR $cpR, csR $csR): Response
    {
        $family = $this->family((int) $id, $familyRepository);
        if ($family) {
            $form = FamilyForm::show($family);
            $familyCustomForm = new FamilyCustomForm();
            $parameters = [
                'title' => $this->translator->translate('view'),
                'familyCustomForm' => $familyCustomForm,
                'custom_fields' => $cfR->repoTablequery('family_custom'),
                'customValues' => $cvR->fixCfValueToCf($cfR->repoTablequery('family_custom')),
                'cpR' => $cpR,
                'actionName' => 'family/view',
                'actionArguments' => ['id' => $familyId = $family->reqId()],
                'familyCustomValues' => $this->familyCustomValues($familyId, $fcR),
                'categoryPrimaries' => $cpR->optionsDataCategoryPrimaries(),
                'categorySecondaries' => $csR->optionsDataCategorySecondaries(),
                'errors' => [],
                'family' => $family,
                'form' => $form,
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('family/index');
    }

    /**
     * Renders the drag-and-drop street order page for the cleaning run.
     */
    public function streetOrder(fR $familyRepository): Response
    {
        return $this->webViewRenderer->render('street_order', [
            'alert'       => $this->alert(),
            'streets'     => $familyRepository->findAllByStreetOrder(),
            'reorderUrl'  => $this->urlGenerator->generate('family/reorder'),
        ]);
    }

    /**
     * Accepts a POST with form field order[] (family IDs in new sequence) and
     * bulk-updates street_sort_order. Returns JSON {success: true|false}.
     */
    public function reorder(Request $request): Response
    {
        $body = $request->getParsedBody() ?? [];
        if (!is_array($body) || !isset($body['order']) || !is_array($body['order'])) {
            return $this->factory->createResponse(['success' => false, 'message' => 'Invalid payload']);
        }
        $orderedIds = array_values(array_map('intval', $body['order']));
        try {
            $this->familyService->saveStreetOrders($orderedIds);
            return $this->factory->createResponse(['success' => true]);
        } catch (\Throwable $e) {
            return $this->factory->createResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param int $id
     * @param FamilyRepository $familyRepository
     * @return Family|null
     */
    private function family(#[RouteArgument('id')] int $id, fR $familyRepository): ?Family
    {
        return $familyRepository->repoFamilyquery($id);
    }

    /**
     * Generate products from selected families using their comma lists
     * @param Request $request
     * @param fR $familyRepository
     * @param pR $productRepository
     * @return Response
     */
    public function generateProducts(
        Request $request,
        fR $familyRepository,
        pR $productRepository,
        ProductService $productService
    ): Response {
        $body = $request->getParsedBody();
        if (!($request->getMethod() === Method::POST && isset($body['family_ids']))) {
            return $this->factory->createResponse(Json::encode([
                'success' => false,
                'message' => 'Invalid request method or missing data',
            ]))->withHeader('Content-Type', 'application/json');
        }
        $familyIds = (array) $body['family_ids'];
        $taxRateId = (string) ($body['tax_rate_id'] ?? '');
        $unitId    = (string) ($body['unit_id'] ?? '');

        if (empty($familyIds) || $taxRateId === '' || $unitId === '') {
            return $this->factory->createResponse(Json::encode([
                'success' => false,
                'message' => 'Missing required parameters: family_ids, tax_rate_id, or unit_id',
            ]))->withHeader('Content-Type', 'application/json');
        }

        $generatedCount = 0;
        /** @var string[] $errors */
        $errors         = [];
        /** @var string[] $newProductIds */
        $newProductIds  = [];

        try {
            /** @var string $familyId */
            foreach ($familyIds as $familyId) {
                $family = $familyRepository->repoFamilyquery((int) $familyId);
                if (!$family) {
                    $errors[] = "Family with ID $familyId not found";
                    continue;
                }
                if (strlen($cl = $family->getFamilyCommalist() ?? '') === 0
                    || strlen($pp = $family->getFamilyProductprefix() ?? '') === 0
                ) {
                    $errors[] = "Family '{$family->getFamilyName()}' missing comma list or product prefix";
                    continue;
                }
                $result = $this->createProductsFromCommalist(
                    $cl, $pp, (int) $familyId, $taxRateId, $unitId, $productRepository, $productService);
                $generatedCount += $result['count'];
                $newProductIds   = array_merge($newProductIds, $result['productIds']);
                $errors          = array_merge($errors, $result['errors']);
            }
            $responseData = $this->buildGenerateResponse($generatedCount, $newProductIds, $errors);
        } catch (\Throwable $e) {
            $responseData = [
                'success' => false,
                'message' => 'Error generating products: ' . $e->getMessage(),
            ];
        }
        return $this->factory->createResponse(Json::encode($responseData))
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param string[] $newProductIds
     * @param string[] $errors
     * @return array<string, mixed>
     */
    private function buildGenerateResponse(int $count, array $newProductIds, array $errors): array
    {
        if ($count > 0 && !empty($newProductIds)) {
            $redirectUrl = $this->urlGenerator->generate(
                'productclient/associate-multiple', [
                    'product_ids' => implode(',', $newProductIds),
                    'index'       => '0',
                ]);
            $data = [
                'success'      => true,
                'count'        => $count,
                'message'      => "Generated $count products. Redirecting to client association.",
                'redirect_url' => $redirectUrl,
            ];
        } else {
            $data = [
                'success' => true,
                'count'   => $count,
                'message' => $count === 0 ? 'No products generated' : "Generated $count products",
            ];
        }
        if (!empty($errors)) {
            $data['warnings'] = $errors;
        }
        return $data;
    }

    /**
     * @return array{count: int, productIds: string[], errors: string[]}
     */
    private function createProductsFromCommalist(
        string $commalist,
        string $prefix,
        int $familyId,
        string $taxRateId,
        string $unitId,
        pR $productRepository,
        ProductService $productService,
    ): array {
        $count      = 0;
        $productIds = [];
        $errors     = [];
        foreach (array_filter(array_map('trim', explode(',', $commalist))) as $item) {
            $productName = $prefix . ' ' . $item;
            if ($productRepository->repoProductWithFamilyIdQuery($productName, $familyId)->count() > 0) {
                $errors[] = "Product '$productName' already exists";
                continue;
            }
            $newProduct = new Product();
            $productService->saveProduct($newProduct, [
                'ProductForm' => [
                    'product_name'        => $productName,
                    'product_description' => $productName,
                    'product_sku'         => $item,
                    'product_price'       => 0.00,
                    'family_id'           => (string) $familyId,
                    'tax_rate_id'         => $taxRateId,
                    'unit_id'             => $unitId,
                ],
            ]);
            if ($newProduct->hasIdentity()) {
                $productIds[] = (string) $newProduct->reqId();
            }
            $count++;
        }
        return ['count' => $count, 'productIds' => $productIds, 'errors' => $errors];
    }

    /**
     * Generate the product generation modal
     */
    private function indexModalGenerateProducts(trR $taxRateRepository, uR $unitRepository): string
    {
        return $this->webViewRenderer->renderPartialAsString('//invoice/family/modal_generate_products', [
            'taxRates' => $taxRateRepository->findAllPreloaded(),
            'units' => $unitRepository->findAllPreloaded(),
        ]);
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function familys(fR $familyRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $familyRepository->findAllPreloaded();
    }
}
