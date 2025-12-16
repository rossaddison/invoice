<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\BaseController;
use App\Invoice\Entity\Family;
use App\Invoice\Entity\FamilyCustom;
use App\Invoice\Entity\Product;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as cpR;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as csR;
use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\Family\FamilyRepository as fR;
use App\Invoice\Product\ProductRepository as pR;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as uR;
use App\Invoice\FamilyCustom\FamilyCustomForm;
use App\Invoice\FamilyCustom\FamilyCustomService;
use App\Invoice\FamilyCustom\FamilyCustomRepository as fcR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use Yiisoft\Injector\Inject;

final class FamilyController extends BaseController
{
    protected string $controllerName = 'invoice/family';

    public function __construct(
        private FamilyService $familyService,
        private FamilyCustomService $familyCustomService,
        private FamilyCustomFieldProcessor $familyCustomFieldProcessor,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->familyService = $familyService;
        $this->factory = $factory;
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param fR $familyRepository
     * @param cpR $cpR
     * @param csR $csR
     * @param trR $taxRateRepository
     * @param uR $unitRepository
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function index(
        Request $request,
        CurrentRoute $currentRoute,
        fR $familyRepository,
        cpR $cpR,
        csR $csR,
        trR $taxRateRepository,
        uR $unitRepository,
    ): \Yiisoft\DataResponse\DataResponse {
        $families = $this->familys($familyRepository);
        $pageNum = (int) $currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $pageNum > 0 ? $pageNum : 1;
        $query_params = $request->getQueryParams();
        $parameters = [
            'alert' => $this->alert(),
            'families' => $families,
            'page' => $currentPageNeverZero,
            'sortString' => $query_params['sort'] ?? '-id',
            /**
             * The family repository does not include a loaded query
             * because there are no relations (for backward compatibility purposes) therefore
             * pass the dependent repositories so that we can identify
             * the respective names of each repository.
             */
            'cpR' => $cpR,
            'csR' => $csR,
            'modal_generate_products' => $this->index_modal_generate_products($taxRateRepository, $unitRepository),
            'defaultPageSizeOffsetPaginator' => (int) $this->sR->getSetting('default_list_limit'),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * Build a 3 tiered dependency drop down search form
     * @param cpR $cpR
     * @param csR $csR
     * @return Response
     */
    public function search(cpR $cpR, csR $csR): Response
    {
        $family = new Family();
        $form = new FamilyForm($family);
        $parameters = [
            'title' => $this->translator->translate('search.family'),
            'form' => $form,
            'actionName' => 'family/search',
            'actionArguments' => [],
            'categoryPrimaries' => $cpR->optionsDataCategoryPrimaries(),
            'categorySecondaries' => [],
            'familyNames' => [],
        ];
        return $this->viewRenderer->render('_search', $parameters);
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
        $family = new Family();
        $form = new FamilyForm($family);
        $familyCustom = new FamilyCustom();
        $familyCustomForm = new FamilyCustomForm($familyCustom);
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
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->familyService->saveFamily($family, $body);
                    if (null !== $family_id = $family->getFamily_id()) {
                        if (isset($body['custom'])) {
                            // Retrieve the custom array
                            /** @var array $custom */
                            $custom = $body['custom'];
                            /**
                             * @var int $custom_field_id
                             * @var array|string $value
                             */
                            foreach ($custom as $custom_field_id => $value) {
                                $familyCustom = new FamilyCustom();
                                $familyCustomForm = new FamilyCustomForm($familyCustom);
                                $family_custom = [];
                                $family_custom['family_id'] = $family_id;
                                $family_custom['custom_field_id'] = $custom_field_id;
                                $family_custom['value'] = is_array($value) ? serialize($value) : $value;
                                if ($formHydrator->populateAndValidate($familyCustomForm, $family_custom)) {
                                    $this->familyCustomService->saveFamilyCustom($familyCustom, $family_custom);
                                }
                                // These two can be used to create customised labels for custom field error validation on the form
                                // Currently not used.
                                $parameters['familyCustomForm'] = $familyCustomForm;
                                $parameters['errorsCustom'] = $familyCustomForm->getValidationResult()->getErrorMessagesIndexedByProperty();
                            }
                        }
                        $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
                        return $this->webService->getRedirectResponse('family/index');
                    }
                }
            } else {
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
        }
        $parameters['form'] = $form;
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param Request $request
     * @param fR $fR
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function names(Request $request, fR $fR): \Yiisoft\DataResponse\DataResponse
    {
        $queryParams = $request->getQueryParams();

        $categorySecondaryId = (string) $queryParams['category_secondary_id'];

        if ($categorySecondaryId) {
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
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function secondaries(Request $request, csR $csR): \Yiisoft\DataResponse\DataResponse
    {
        $queryParams = $request->getQueryParams();

        $categoryPrimaryId = (string) $queryParams['category_primary_id'];

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

    /**
     * @param string $id
     * @param Request $request
     * @param FamilyRepository $familyRepository
     * @param fcR $fcR
     * @param cfR $cfR
     * @param cvR $cvR
     * @param cpR $cpR
     * @param csR $csR
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(
        #[RouteArgument('id')] string $id,
        Request $request,
        fR $familyRepository,
        fcR $fcR,
        cfR $cfR,
        cvR $cvR,    
        cpR $cpR,
        csR $csR,
        FormHydrator $formHydrator
    ): Response
    {
        $family = $this->family($id, $familyRepository);
        if ($family) {
            $form = new FamilyForm($family);
            $familyCustom = new FamilyCustom();
            $familyCustomForm = new FamilyCustomForm($familyCustom);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'family/edit',
                'actionArguments' => ['id' => $familyId = $family->getFamily_id()],
                'categoryPrimaries' => $cpR->optionsDataCategoryPrimaries(),
                'categorySecondaries' => $csR->optionsDataCategorySecondaries(),
                'errors' => [],
                'errorsCustom' => [],
                'family' => $family,
                'form' => $form,
                'customFields' => $this->fetchCustomFieldsAndValues($cfR, $cvR, 'family_custom')['customFields'],
                'customValues' => $this->fetchCustomFieldsAndValues($cfR, $cvR, 'family_custom')['customValues'],
                'familyCustomValues' => $this->family_custom_values((string) $familyId, $fcR),
                'familyCustomForm' => $familyCustomForm,
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $returned_form = $this->save_form_fields($body, $form, $family, $formHydrator);
                    $parameters['body'] = $body;
                    if (!$returned_form->isValid()) {
                        $parameters['form'] = $returned_form;
                        $parameters['errors'] = 
                            $returned_form->getValidationResult()
                                          ->getErrorMessagesIndexedByProperty();
                        return $this->viewRenderer->render('_form', $parameters);
                    }
                    $this->processCustomFields(
                        $body,
                        $formHydrator,
                        $this->familyCustomFieldProcessor,
                        (string) $familyId
                    );
                } // is_array
                $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                return $this->webService->getRedirectResponse('family/index');
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('family/index');
    }
    
    /**
     * @param array $body
     * @param FamilyForm $form
     * @param Family $family
     * @param FormHydrator $formHydrator
     * @return FamilyForm
     */
    public function save_form_fields(array $body, FamilyForm $form, Family $family, FormHydrator $formHydrator): FamilyForm
    {
        if ($formHydrator->populateAndValidate($form, $body)) {
            $this->familyService->saveFamily($family, $body);
        }
        return $form;
    }
    
    /**
     * @param string $family_id
     * @param fcR $fcR
     * @return array
     */
    public function family_custom_values(string $family_id, fcR $fcR): array
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
            $family = $this->family($id, $familyRepository);
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
    public function view(#[RouteArgument('id')] string $id, fR $familyRepository, fcR $fcR, cfR $cfR, cvR $cvR, cpR $cpR, csR $csR): Response
    {
        $family = $this->family($id, $familyRepository);
        if ($family) {
            $form = new FamilyForm($family);
            $familyCustom = new FamilyCustom();
            $familyCustomForm = new FamilyCustomForm($familyCustom);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'familyCustomForm' => $familyCustomForm,
                'custom_fields' => $cfR->repoTablequery('family_custom'),
                'customValues' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('family_custom')),
                'cpR' => $cpR,
                'actionName' => 'family/view',
                'actionArguments' => ['id' => $familyId = $family->getFamily_id()],                
                'familyCustomValues' => $this->family_custom_values((string) $familyId, $fcR),
                'categoryPrimaries' => $cpR->optionsDataCategoryPrimaries(),
                'categorySecondaries' => $csR->optionsDataCategorySecondaries(),
                'errors' => [],
                'family' => $family,
                'form' => $form,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('family/index');
    }

    /**
     * @param string $id
     * @param FamilyRepository $familyRepository
     * @return Family|null
     */
    private function family(#[RouteArgument('id')] string $id, fR $familyRepository): ?Family
    {
        return $familyRepository->repoFamilyquery($id);
    }

    /**
     * Generate products from selected families using their comma lists
     * @param Request $request
     * @param fR $familyRepository
     * @param pR $productRepository
     * @param trR $taxRateRepository
     * @param uR $unitRepository
     * @return Response
     */
    public function generate_products(
        Request $request,
        fR $familyRepository,
        pR $productRepository,
        trR $taxRateRepository,
        uR $unitRepository
    ): Response {
        // Debug: Log that method was called
        error_log("FamilyController::generate_products called at " . date('Y-m-d H:i:s'));
        $applicationJson = 'application/json';
        $body = $request->getParsedBody();
        if ($request->getMethod() === Method::POST && isset($body['family_ids'])) {
            $familyIds = (array) $body['family_ids'];
            $taxRateId = (string) ($body['tax_rate_id'] ?? null);
            $unitId = (string) ($body['unit_id'] ?? null);
            
            if (empty($familyIds) || ($taxRateId <= 0)  || ($unitId <= 0)) {
                return $this->factory->createResponse(Json::encode([
                    'success' => false,
                    'message' => 'Missing required parameters: family_ids, tax_rate_id, or unit_id'
                ]))->withHeader('Content-Type', $applicationJson);
            }
            
            $generatedCount = 0;
            $errors = [];
            
            try {
                /**
                 * @var string $familyId
                 */
                foreach ($familyIds as $familyId) {
                    $family = $familyRepository->repoFamilyquery($familyId);
                    if (!$family) {
                        $errors[] = "Family with ID $familyId not found";
                        continue;
                    }
                    
                    $commalist = $family->getFamily_commalist();
                    $productPrefix = $family->getFamily_productprefix();
                    
                    if (strlen($cl = $commalist ?? '') === 0 || strlen($pp = $productPrefix ?? '') === 0) {
                        $errors[] = "Family '{$family->getFamily_name()}' missing comma list or product prefix";
                        continue;
                    }
                    
                    // Split comma list and generate products
                    $items = array_map('trim', explode(',', $cl));
                    $items = array_filter($items); // Remove empty items
                    
                    foreach ($items as $item) {
                        $productName = $pp . ' ' . $item;
                        
                        // Check if product already exists
                        $existingProducts = $productRepository->repoProductWithFamilyIdQuery($productName, $familyId);
                        if ($existingProducts->count() > 0) {
                            $errors[] = "Product '$productName' already exists";
                            continue;
                        }
                        
                        // Create new product
                        $product = new Product();
                        $product->setProduct_name($productName);
                        $product->setProduct_description($productName);
                        $product->setProduct_price(0.00);
                        $product->setFamily_id((int) $familyId);
                        $product->setTax_rate_id((int) $taxRateId);
                        $product->setUnit_id((int) $unitId);
                        $product->setProduct_sku($item); // Use the item as SKU
                        
                        $productRepository->save($product);
                        $generatedCount++;
                    }
                }
                
                $responseData = [
                    'success' => true,
                    'count' => $generatedCount,
                    'message' => $generatedCount == 0 ? "No products generated becau" : "Generated $generatedCount products"
                ];
                
                if (!empty($errors)) {
                    $responseData['warnings'] = $errors;
                }
                
                return $this->factory->createResponse(Json::encode($responseData))
                    ->withHeader('Content-Type', 'application/json');
                    
            } catch (\Exception $e) {
                return $this->factory->createResponse(Json::encode([
                    'success' => false,
                    'message' => 'Error generating products: ' . $e->getMessage()
                ]))->withHeader('Content-Type', 'application/json');
            }
        }
        
        return $this->factory->createResponse(Json::encode([
            'success' => false,
            'message' => 'Invalid request method or missing data'
        ]))->withHeader('Content-Type', 'application/json');
    }

    /**
     * Generate the product generation modal
     */
    private function index_modal_generate_products(trR $taxRateRepository, uR $unitRepository): string
    {
        return $this->viewRenderer->renderPartialAsString('//invoice/family/modal_generate_products', [
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
