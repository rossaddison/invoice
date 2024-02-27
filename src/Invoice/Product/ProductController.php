<?php
declare(strict_types=1);

namespace App\Invoice\Product;

use App\Invoice\Entity\Family;
use App\Invoice\Entity\Product;
use App\Invoice\Entity\ProductCustom;
use App\Invoice\Entity\ProductImage;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\TaxRate;
use App\Invoice\Entity\Unit;
use App\Invoice\Entity\InvItem;
use App\Invoice\Family\FamilyRepository as fR;
use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\CustomField\CustomFieldRepository as cfR;
use App\Invoice\Helpers\CountryHelper;
use App\Invoice\Helpers\CustomValuesHelper as CVH;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Helpers\Peppol\PeppolArrays;
// Product
use App\Invoice\Product\ProductService;
use App\Invoice\Product\ProductRepository as pR;
use App\Invoice\Product\ProductForm;
use App\Invoice\ProductCustom\ProductCustomRepository as pcR;
use App\Invoice\ProductCustom\ProductCustomService;
use App\Invoice\ProductCustom\ProductCustomForm;
use App\Invoice\ProductImage\ProductImageRepository as piR;
use App\Invoice\Product\ImageAttachForm;
// Quote
use App\Invoice\QuoteItem\QuoteItemForm;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as qiaS;
// Inv
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItemAmount\InvItemAmountService as iiaS;
// Setting, TaxRate, Unit
use App\Invoice\ProductProperty\ProductPropertyRepository as ppR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\TaxRate\TaxRateRepository as trR;
use App\Invoice\Unit\UnitRepository as uR;
use App\Invoice\UnitPeppol\UnitPeppolRepository as upR;
use App\Invoice\QuoteItem\QuoteItemRepository as qiR;
use App\Invoice\InvItem\InvItemRepository as iiR;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as aciR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as qiaR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as qtrR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as itrR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as qaR;
use App\Invoice\InvAmount\InvAmountRepository as iaR;
use App\Invoice\Quote\QuoteRepository as qR;
use App\Invoice\Inv\InvRepository as iR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as iiaR;
use App\Invoice\Payment\PaymentRepository as pymR;
use App\Service\WebControllerService;
use App\User\UserService;

//  Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Yiisoft
use Yiisoft\Data\Reader\Sort;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Validator;
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\View\ViewRenderer;

class ProductController
{
    private const FILTER_FAMILY = 'ff';
    private const FILTER_PRODUCT = 'fp';
    private const RESET_TRUE = 'rt';
    public  ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private ProductService $productService;
    private ProductCustomService $productCustomService;
    private QuoteItemService $quoteitemService;
    private InvItemService $invitemService;
    private UserService $userService;   
    private DataResponseFactoryInterface $responseFactory;
    private Flash $flash;
    private SessionInterface $session;
    private TranslatorInterface $translator;
    private string $ffc = self::FILTER_FAMILY;
    private string $fpc = self::FILTER_PRODUCT;
    private string $rtc = self::RESET_TRUE;
    
    public function __construct(
      ViewRenderer $viewRenderer,
      WebControllerService $webService,
      ProductService $productService,
      ProductCustomService $productCustomService,
      QuoteItemService $quoteitemService,
      InvItemService $invitemService,
      UserService $userService,
      DataResponseFactoryInterface $responseFactory,
      SessionInterface $session,
      TranslatorInterface $translator
    )
    {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/product')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->productService = $productService;
        $this->productCustomService = $productCustomService;
        $this->quoteitemService = $quoteitemService;
        $this->invitemService = $invitemService;
        $this->userService = $userService;
        $this->responseFactory = $responseFactory;
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->translator = $translator;
    }
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param sR $sR
     * @param fR $fR
     * @param uR $uR
     * @param trR $trR
     * @param cvR $cvR
     * @param cfR $cfR
     * @param pcR $pcR
     * @param upR $upR
     * @return Response
     */
    public function add(Request $request, FormHydrator $formHydrator, sR $sR, fR $fR, uR $uR, trR $trR, cvR $cvR, cfR $cfR, pcR $pcR, upR $upR): Response
    {
        $countries = new CountryHelper();
        $peppolarrays = new PeppolArrays();
        $product = new Product();
        $form = new ProductForm($product);
        $productCustom = new ProductCustom();
        $productCustomForm = new ProductCustomForm($productCustom);
        $parameters = [
            'title' => $this->translator->translate('i.add'),
            'action' => ['product/add'],
            'countries' => $countries->get_country_list((string)$this->session->get('_language')),
            'alert' => $this->alert(),
            'form' => $form,
            'errors' => [],
            'errors_custom' => [],
            'standard_item_identification_schemeids' => $peppolarrays->getIso_6523_icd(),
            'item_classification_code_listids' => $peppolarrays->getUncl7143(),
            'families' => $this->families($fR->findAllPreloaded()),
            'units' => $this->units($uR->findAllPreloaded()),
            'tax_rates' => $this->tax_rates($trR->findAllPreloaded()),
            'unit_peppols' => $this->unit_peppols($upR->findAllPreloaded()),
            'custom_fields' => $cfR->repoTablequery('product_custom'),
            'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('product_custom')),
            'cvH' => new CVH($sR),
            'product_custom_values' => [],
            'productCustomForm' => $productCustomForm
        ];
        if ($request->getMethod() === Method::POST && $formHydrator->populateFromPostAndValidate($form, $request)) {
            $body = $request->getParsedBody() ?? [];
            /**
             * @psalm-suppress PossiblyInvalidArgument $body
             */
            $product_id = $this->productService->saveProduct($product, $body);
            if ($product_id) {
                if (isset($body['custom'])) {
                    // Retrieve the custom array
                    /** @var array $custom */
                    $custom = $body['custom'];
                    /** 
                     * @var int $custom_field_id
                     * @var string|array $value
                     */
                    foreach($custom as $custom_field_id => $value){
                        $productCustom = new ProductCustom();
                        $formProductCustom = new ProductCustomForm($productCustom);
                        $product_custom = [];
                        $product_custom['product_id'] = $product_id;
                        $product_custom['custom_field_id'] = $custom_field_id;                    
                        $product_custom['value'] = is_array($value) ? serialize($value) : $value;                    
                        if ($formHydrator->populate($formProductCustom, $product_custom) && $formProductCustom->isValid()) {
                          $this->productCustomService->saveProductCustom($productCustom, $product_custom);
                        }
                        // These two can be used to create customised labels for custom field error validation on the form
                        // Currently not used.
                        $parameters['formProductCustom'] = $formProductCustom; 
                        $parameters['errors_custom'] = $formProductCustom->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
                    }
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_created'));
                    return $this->webService->getRedirectResponse('product/index');
                }    
            }
        }
        $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
        $parameters['form'] = $form;
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @return string
     */
    private function alert(): string {
      return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
      [ 
        'flash' => $this->flash
      ]);
    }
    
    
    /**
     * @param pR $pR
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function delete(pR $pR, CurrentRoute $currentRoute) : Response 
    {
        try {
            $product = $this->product($currentRoute, $pR);
            if ($product) { 
                $this->productService->deleteProduct($product);  
                $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
            }
            return $this->webService->getRedirectResponse('product/index');
	} catch (\Exception $e) {
           unset($e);
           $this->flash_message('danger', $this->translator->translate('invoice.product.history'));
           return $this->webService->getRedirectResponse('product/index');   
        }
    }
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
      $this->flash->add($level, $message, true);
      return $this->flash;
    }
    
    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param pR $pR
     * @param sR $sR
     * @param fR $fR
     * @param uR $uR
     * @param trR $trR
     * @param cvR $cvR
     * @param cfR $cfR
     * @param upR $upR
     * @param Validator $validator
     * @return Response
     */
    public function edit(Request $request, CurrentRoute $currentRoute, FormHydrator $formHydrator,
                    pR $pR, sR $sR, fR $fR, uR $uR, trR $trR, cvR $cvR, cfR $cfR, pcR $pcR, upR $upR, Validator $validator 
    ): Response {
        $countries = new CountryHelper();
        $peppolarrays = new PeppolArrays();
        $product = $this->product($currentRoute, $pR);
        if ($product) {
            $product_id = $product->getProduct_id();
            $form = new ProductForm($product);
            $productCustom = new ProductCustom();
            $productCustomForm = new ProductCustomForm($productCustom);
            if ($product_id) {
                $parameters = [
                    'title' => $this->translator->translate('i.edit'),
                    'action' => ['product/edit', ['id' => $product_id]],
                    'alert' => $this->alert(),
                    'countries' => $countries->get_country_list((string)$this->session->get('_language')),
                    'form' => $form,
                    'errors' => [],
                    'errors_custom' => [],
                    'standard_item_identification_schemeids' => $peppolarrays->getIso_6523_icd(),
                    'item_classification_code_listids' => $peppolarrays->getUncl7143(),
                    'families' => $this->families($fR->findAllPreloaded()),
                    'units' => $this->units($uR->findAllPreloaded()),
                    'tax_rates' => $this->tax_rates($trR->findAllPreloaded()),
                    'unit_peppols' => $this->unit_peppols($upR->findAllPreloaded()),
                    'custom_fields' => $cfR->repoTablequery('product_custom'),
                    'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('product_custom')),
                    'cvH'=> new CVH($sR),
                    'product_custom_values' => $this->product_custom_values($product_id, $pcR),
                    'productCustomForm' => $productCustomForm
                ];
                if ($request->getMethod() === Method::POST) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        $returned_form = $this->save_form_fields($body, $form, $product, $formHydrator);
                        $parameters['body'] = $body;
                        if (!$returned_form->isValid()) {
                            $parameters['form'] = $returned_form;
                            $parameters['errors'] = $returned_form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
                            return $this->viewRenderer->render('_form', $parameters);
                        } 
                        // Only save custom fields if they exist
                        if ($cfR->repoTableCountquery('product_custom') > 0) { 
                             if (isset($body['custom'])) {
                                 $custom = (array)$body['custom'];
                                 /** @var string|array $value */
                                 foreach ($custom as $custom_field_id => $value) {
                                     $product_custom = $pcR->repoFormValuequery($product_id, (string)$custom_field_id);
                                     if (null!==$product_custom) {
                                         $product_custom_input = [
                                             'product_id' => $product_id,
                                             'custom_field_id' => (int)$custom_field_id,
                                             'value' => is_array($value) ? serialize($value) : $value 
                                         ];
                                         $productCustomForm = new ProductCustomForm($product_custom);
                                         if ($formHydrator->populate($productCustomForm, $product_custom_input) 
                                            && $productCustomForm->isValid()
                                         )
                                         {
                                             $this->productCustomService->saveProductCustom($product_custom, $product_custom_input);     
                                         }
                                         $parameters['errors_custom'] = $productCustomForm->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
                                         $parameters['productCustomForm'] = $productCustomForm;
                                     }
                                 } //foreach
                                 $errors_custom = $parameters['errors_custom'];
                                 if (count($errors_custom) > 0) {
                                     return $this->viewRenderer->render('__form', $parameters);
                                 }
                             } //isset  
                        } // cfR
                    } // is_array    
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_updated'));
                    return $this->webService->getRedirectResponse('product/index');
                }
                return $this->viewRenderer->render('_form', $parameters);
            } // null!==product_id
        } // product    
        return $this->webService->getRedirectResponse('product/index');   
    }
    
    /**
     * Prepare optionsData $data value for ...resources/view/product/_form select
     * @param EntityReader $families
     * @return array
     */
    private function families(EntityReader $families) : array 
    {
        $array = [];
        /**
         * @var Family $family
         */
        foreach ($families as $family) {
          $family_id = $family->getFamily_id();  
          if (null!==$family_id) {  
            $array[$family_id] = $family->getFamily_name();
          }  
        }
        return $array;
    }
    
    /**
     * Prepare optionsData $data value for ...resources/view/product/_form select
     * @param EntityReader $units
     * @return array
     */
    private function units(EntityReader $units) : array 
    {
        $array = [];
        /**
         * @var Unit $unit
         */
        foreach ($units as $unit) {
          $unit_id = $unit->getUnit_id();  
          if (null!==$unit_id) {  
            $array[$unit_id] = $unit->getUnit_name(). ' '.$unit->getUnit_name_plrl();
          } 
        }
        return $array;
    }
    
    /**
     * Prepare optionsData $data value for ...resources/view/product/_form select
     * @param EntityReader $unit_peppols
     * @return array
     */
    private function unit_peppols(EntityReader $unit_peppols) : array 
    {
        $array = [];
        /**
         * @var \\App\Invoice\Entity\UnitPeppol $unit_peppol
         */
        foreach ($unit_peppols as $unit_peppol) {
          $array[$unit_peppol->getId()] = $unit_peppol->getCode() . ' --- '. $unit_peppol->getName(). ' --- ' .$unit_peppol->getDescription();
        }
        return $array;
    }
    
    /**
     * Prepare optionsData $data value for ...resources/view/product/_form select
     * @param EntityReader $tax_rates
     * @return array
     */
    private function tax_rates(EntityReader $tax_rates) : array 
    {
        $array = [];
        /**
         * @var TaxRate $tax_rate
         */
        foreach ($tax_rates as $tax_rate) {
          $tax_rate_id = $tax_rate->getTax_rate_id();   
          if (null!==$tax_rate_id) {  
            $array[$tax_rate_id] = $tax_rate->getTax_rate_name();
          }  
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
    public function save_form_fields(array $body, ProductForm $form, Product $product, FormHydrator $formHydrator) : ProductForm {
        if ($formHydrator->populate($form, $body) && $form->isValid()) {
          $this->productService->saveProduct($product, $body);
        } 
        return $form;
    }
    
    /**
     * @param pR $pR
     * @param sR $sR
     * @param CurrentRoute $currentRoute
     * @param Request $request
     */
    public function index(pR $pR, sR $sR, CurrentRoute $currentRoute, Request $request): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $this->flash_message('info', $this->translator->translate('invoice.productimage.view'));
        $query_params = $request->getQueryParams();
        
        /**
         * @var string $query_params['page']
         */
        $page = $query_params['page'] ?? $currentRoute->getArgument('page', '1');
        
        /** @var string $query_params['sort'] */
        $sort = Sort::only(['id','family_id','unit_id','tax_rate_id','product_name','product_sku'])
                    // (@see vendor\yiisoft\data\src\Reader\Sort
                    // - => 'desc'  so -id => default descending on id
                    // Show the latest quotes first => -id
                    ->withOrderString($query_params['sort'] ?? '-id');
        $products = $this->products_with_sort($pR, $sort); 
        $paginator = (new OffsetPaginator($products))
        ->withPageSize((int)$sR->get_setting('default_list_limit'))
        ->withCurrentPage((int)$page)
        ->withToken(PageToken::next((string)$page)); 
        $parameters = [
            'alert' => $this->alert(),
            'paginator' => $paginator,
            'canEdit' => $canEdit,
            'grid_summary' => $sR->grid_summary($paginator, $this->translator, (int)$sR->get_setting('default_list_limit'), $this->translator->translate('invoice.products'), ''),
            'products' => $this->products($pR),
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @see ...\invoice\src\Invoice\Asset\rebuild-1.13\js\product.js $(document).on('click', '#product_filters_submit', function () 
     * @see ...\product\index.php 
     * @param Request $request
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function search(Request $request): \Yiisoft\DataResponse\DataResponse
    {
        $query_params = $request->getQueryParams();
        $product_sku = (string)$query_params['product_sku'];
        if ($product_sku) {
            $parameters = [
                'success' => 1,
                'message' => $this->translator->translate('invoice.product.found')
            ];
        } else {
            $parameters = [
                'success' => 0,
                'messeage' => $this->translator->translate('invoice.product.not.found')
            ];
        }    
        return $this->responseFactory->createResponse(Json::encode($parameters));
    }
    
    // queryparams coming from modal_product_lookups.js ---> line 165 filter_button_inv
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param fR $fR
     * @param sR $sR
     * @param pR $pR
     */
    public function lookup(ViewRenderer $head, Request $request, fR $fR, sR $sR, pR $pR): \Yiisoft\DataResponse\DataResponse {
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
        $parameters = [
            'numberhelper'=>new NumberHelper($sR),
            'families'=> $fR->findAllPreloaded(),
            'filter_product'=> $fp,            
            'filter_family'=> $ff,
            'reset_table'=> $rt,
            'head'=> $head,
            'products'=> $rt || ($ff=='' && $fp=='') ? $pR->findAllPreloaded() : $pR->repoProductwithfamilyquery($fp, $ff),
            'default_item_tax_rate'=> $sR->get_setting('default_item_tax_rate') !== '' ?: 0,
        ];
        return $this->viewRenderer->renderPartial('_partial_product_table_modal', $parameters);        
    }
    
    /**
     * @param ProductRepository $pR
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, Product>
     */
    private function products_with_sort(ProductRepository $pR, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface {       
        $products = $pR->findAllPreloaded()
                       ->withSort($sort);
        return $products;
    }
    
    /**
     * @param int $order
     * @param Product $product
     * @param string $quote_id
     * @param pR $pR
     * @param trR $trR
     * @param uR $unR
     * @param QIAR $qiaR
     * @param QIAS $qiaS
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function save_product_lookup_item_quote(int $order, Product $product, string $quote_id, pR $pR, trR $trR, uR $unR, QIAR $qiaR, QIAS $qiaS, FormHydrator $formHydrator) : void {
        $quoteItem = new QuoteItem();
        $form = new QuoteItemForm($quoteItem, $quote_id);
        $ajax_content = [
            'name' => $product->getProduct_name(),        
            'quote_id' => $quote_id,            
            'tax_rate_id' => $product->getTax_rate_id(),
            'product_id' => $product->getProduct_id(),
            'date_added' => new \DateTimeImmutable(),
            'description' => $product->getProduct_description(),
            // A default quantity of 1 is used to initialize the item
            'quantity' => floatval(1),
            'price' => $product->getProduct_price(),
            // The user will determine how much discount to give on this item later
            'discount_amount' => floatval(0),
            'order' => $order,
            // The default quantity is 1 so the singular name will be used.
            'product_unit' => $unR->singular_or_plural_name($product->getUnit_id(),1),
            'product_unit_id' => $product->getUnit_id(),
        ];
        if ($formHydrator->populate($form, $ajax_content) && $form->isValid()) {
            $this->quoteitemService->addQuoteItem($quoteItem, $ajax_content, $quote_id, $pR, $qiaR, $qiaS, $unR, $trR, $this->translator);
        }      
    }
    
    /**
     * @param int $order
     * @param Product $product
     * @param string $inv_id
     * @param pR $pR
     * @param sR $sR
     * @param trR $trR
     * @param uR $unR
     * @param iiaR $iiaR
     * @param uR $uR
     * @param FormHydrator $formHydrator
     * @return void
     */
    private function save_product_lookup_item_inv(int $order, Product $product, string $inv_id, pR $pR, sR $sR, trR $trR, uR $unR, iiaR $iiaR, uR $uR, FormHydrator $formHydrator) : void {
           $invItem = new InvItem();
           $form = new InvItemForm($invItem, (int)$inv_id);
           $ajax_content = [
                'name'=> $product->getProduct_name(),        
                'inv_id' => $inv_id,            
                'tax_rate_id' => $product->getTax_rate_id(),
                'product_id' => $product->getProduct_id(),
                'task_id' => null,
                'description' => $product->getProduct_description(),
                // A default quantity of 1 is used to initialize the item
                'quantity' => floatval(1),
                'price' => $product->getProduct_price(),
                // Vat: Early Settlement Cash Discount subtracted before VAT is calculated
                'discount_amount' => floatval(0),
                'charge_amount' => floatval(0),
                'allowance_amount' => floatval(0),
                'order' => $order,
                // The default quantity is 1 so the singular name will be used.
                'product_unit' => $unR->singular_or_plural_name($product->getUnit_id(),1),
                'product_unit_id' => $product->getUnit_id(),
           ];
           if ($formHydrator->populate($form, $ajax_content) && $form->isValid()) {
                $this->invitemService->addInvItem_product($invItem, $ajax_content, $inv_id, $pR, $trR, new iiaS($iiaR),$iiaR, $sR, $uR);                 
           }      
    }
    
    //views/invoice/product/modal-product-lookups-quote.php => modal_product_lookups.js $(document).on('click', '.select-items-confirm-quote', function () => selection_quote
    
    /**
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param pR $pR
     * @param qaR $qaR
     * @param qiR $qiR
     * @param qR $qR
     * @param qtrR $qtrR
     * @param sR $sR
     * @param trR $trR
     * @param uR $uR
     * @param qiaR $qiaR
     * @param qiaS $qiaS
     */
    public function selection_quote(FormHydrator $formHydrator, Request $request,
                                   pR $pR, qaR $qaR, qiR $qiR, qR $qR, qtrR $qtrR,
                                   sR $sR, trR $trR, uR $uR, qiaR $qiaR, qiaS $qiaS) : \Yiisoft\DataResponse\DataResponse {        
        $select_items = $request->getQueryParams();
        /** @var array $select_items['product_ids'] */
        $product_ids = ($select_items['product_ids'] ?: []);
        /** @var string $quote_id */
        $quote_id = $select_items['quote_id'];
        // Use Spiral||Cycle\Database\Injection\Parameter to build 'IN' array of products.
        $products = $pR->findinProducts($product_ids);
        $numberHelper = new NumberHelper($sR);
        // Format the product prices according to comma or point or other setting choice.
        $order = 1;
        /** @var Product $product */
        foreach ($products as $product) {
            $product->setProduct_price((float)$numberHelper->format_amount($product->getProduct_price()));
            $this->save_product_lookup_item_quote($order, $product, $quote_id, $pR, $trR, $uR, $qiaR, $qiaS, $formHydrator);            
            $order++;          
        } 
        $numberHelper->calculate_quote((string)$this->session->get('quote_id'), $qiR, $qiaR, $qtrR, $qaR, $qR); 
        return $this->responseFactory->createResponse(Json::encode($products));
}
    
    //views/invoice/product/modal-product-lookups-inv.php => modal_product_lookups.js $(document).on('click', '.select-items-confirm-inv', function () 
    
    /**
     * @param FormHydrator $formHydrator
     * @param Request $request
     * @param pR $pR
     * @param sR $sR
     * @param trR $trR
     * @param uR $uR
     * @param iiaR $iiaR
     * @param iiR $iiR
     * @param itrR $itrR
     * @param iaR $iaR
     * @param iR $iR
     * @param pymR $pymR
     * @param aciR $aciR
     */
    public function selection_inv(FormHydrator $formHydrator, Request $request, pR $pR, sR $sR, trR $trR, uR $uR, iiaR $iiaR, iiR $iiR, itrR $itrR, iaR $iaR, iR $iR, pymR $pymR, aciR $aciR) : \Yiisoft\DataResponse\DataResponse {        
        $select_items = $request->getQueryParams();
        /** @var array $select_items['product_ids'] */
        $product_ids = ($select_items['product_ids'] ?: []);
        /** @var string $inv_id */
        $inv_id = $select_items['inv_id'];
        // Use Spiral||Cycle\Database\Injection\Parameter to build 'IN' array of products.
        $products = $pR->findinProducts($product_ids);
        $numberHelper = new NumberHelper($sR);
        // Format the product prices according to comma or point or other setting choice.
        $order = 1;
        /** @var Product $product */
        foreach ($products as $product) {
                $product->setProduct_price((float)$numberHelper->format_amount($product->getProduct_price()));
                $this->save_product_lookup_item_inv($order, $product, $inv_id, $pR, $sR, $trR, $uR, $iiaR, $uR, $formHydrator);
                $order++;          
        }
        $numberHelper->calculate_inv((string)$this->session->get('inv_id'), $aciR, $iiR, $iiaR, $itrR, $iaR, $iR, $pymR);
        return $this->responseFactory->createResponse(Json::encode($products));        
    }   
    
    /**
     * @param CurrentRoute $currentRoute
     * @param pR $pR
     * @return Product|null
     */
    private function product(CurrentRoute $currentRoute, pR $pR): Product|null {        
        $id = $currentRoute->getArgument('id');
        if (null!==$id) {
            $product = $pR->repoProductquery($id);
            return $product;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function products(pR $pR): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader{
        $products = $pR->findAllPreloaded();        
        return $products;
    }
    
    /**
     * @param string $product_id
     * @param pcR $pcR
     * @return array
     */
    public function product_custom_values(string $product_id, pcR $pcR) : array
    {
        // Get all the custom fields that have been registered with this product on creation, retrieve existing values via repo, and populate 
        // custom_field_form_values array
        $custom_field_form_values = [];
        if ($pcR->repoProductCount($product_id) > 0) {
            $product_custom_fields = $pcR->repoFields($product_id);
            /**
             * @var int $key
             * @var string $val
             */
            foreach ($product_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
      $canEdit = $this->userService->hasPermission('editInv');
      if (!$canEdit){
          $this->flash_message('warning', $this->translator->translate('invoice.permission'));
          return $this->webService->getRedirectResponse('product/index');
      }
      return $canEdit;
    }
    
    /**
     * @param cfR $cfR
     * @param cvR $cvR
     * @param fR $fR
     * @param pR $pR
     * @param pcR $pcR
     * @param ppR $ppR
     * @param sR $sR
     * @param piR $piR
     * @param trR $trR
     * @param uR $uR
     * @param upR $upR
     * @param CurrentRoute $currentRoute
     */
    public function view(cfR $cfR, cvR $cvR, fR $fR, pR $pR, pcR $pcR, ppR $ppR, sR $sR, piR $piR, trR $trR, uR $uR, upR $upR, CurrentRoute $currentRoute
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $product = $this->product($currentRoute, $pR);
        $language = (string)$this->session->get('_language');
        $peppolarrays = new PeppolArrays();
        if ($product) {
          $productForm = new ProductForm($product);
          $productCustom = new ProductCustom();
          $productCustomForm = new ProductCustomForm($productCustom);
          $product_id = $product->getProduct_id();          
          $product_images = $piR->repoProductImageProductquery((int)$product_id);
          $parameters = [
            'title' => $this->translator->translate('i.view'),
            'action' => ['product/view', ['id' => $product_id]],
            'partial_product_details' => $this->viewRenderer->renderPartialAsString('/invoice/product/views/partial_product_details',
            [
                'form' => $productForm,
                'standard_item_identification_schemeids' => $peppolarrays->getIso_6523_icd(),
                'item_classification_code_listids' => $peppolarrays->getUncl7143(),
                'families' => $this->families($fR->findAllPreloaded()),
                'units' => $this->units($uR->findAllPreloaded()),
                'tax_rates' => $this->tax_rates($trR->findAllPreloaded()),
                'unit_peppols' => $this->unit_peppols($upR->findAllPreloaded()),
                'custom_fields' => $cfR->repoTablequery('product_custom'),
                'custom_values' => $cvR->attach_hard_coded_custom_field_values_to_custom_field($cfR->repoTablequery('product_custom')),
                'cvH'=> new CVH($sR),
                'product_custom_values' => $this->product_custom_values($product_id, $pcR),
                'productCustomForm' => $productCustomForm,  
                'upR' => $upR,
                //load Entity\Product BelongTo relations ie. $family, $tax_rate, $unit by means of repoProductQuery             
                'product' => $pR->repoProductquery($product_id),
            ]),
            'partial_product_properties' => $this->viewRenderer->renderPartialAsString('/invoice/product/views/partial_product_properties',
              [
                'product'=>$pR->repoProductquery($product_id),
                'language'=>$language,
                'productpropertys' => $this->viewRenderer->renderPartialAsString('/invoice/product/views/property_index', [
                  'all' => $ppR->findAllProduct($product_id),
                  'language' => $language
                ]) 
              ]
            ),
            'partial_product_images' => $this->view_partial_product_image($currentRoute, (int) $product_id, $piR, $sR),
            'partial_product_gallery' => $this->viewRenderer->renderPartialAsString('/invoice/product/views/partial_product_gallery', [
              'product' => $product,
              'product_images' => $product_images,             
              'invEdit' => $this->userService->hasPermission('editInv'),
              'invView' => $this->userService->hasPermission('viewInv')
            ])
          ];        
          return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('product/index');
    }
    
    /**
     * @param string $tmp
     * @param string $target
     * @param int $product_id
     * @param string $fileName
     * @param piR $piR
     * @param sR $sR
     * @return bool
     */
    private function image_attachment_move_to(string $tmp, string $target, int $product_id, string $fileName, piR $piR, sR $sR
    ): bool {
        $file_exists = file_exists($target);
        // The file does not exist yet in the target path but it exists in the tmp folder on the server
        if (!$file_exists) {
            if (is_uploaded_file($tmp) && move_uploaded_file($tmp, $target)) {
                $track_file = new ProductImage();
                $track_file->setProduct_id($product_id);
                $track_file->setFile_name_original($fileName);
                $track_file->setFile_name_new($fileName);
                $track_file->setUploaded_date(new \DateTimeImmutable());
                $piR->save($track_file);
                $this->flash_message('info', $this->translator->translate('invoice.productimage.uploaded.to') . $target);
                return true;
            } else {
                $this->flash_message('warning', $this->translator->translate('invoice.productimage.possible.file.upload.attack') . $tmp);
                return false;
            }
        } else {
            $this->flash_message('warning', $this->translator->translate('i.error_duplicate_file'));
            return false;
        }
    }
    
    /**
     * Upload a product image file
     *
     * @param CurrentRoute $currentRoute
     * @param PR $pR
     * @param PIR $piR
     * @param sR $sR
     */
    public function image_attachment(CurrentRoute $currentRoute, PR $pR, PIR $piR, sR $sR): \Yiisoft\DataResponse\DataResponse|Response {
        $aliases = $sR->get_productimages_files_folder_aliases();
        // https://github.com/yiisoft/yii2/issues/3566
        // Save the image directly to the web accessible folder - assets/publc/product
        $targetPath = $aliases->get('@public_product_images');
        $product_id = $currentRoute->getArgument('id');
        if (null !== $product_id) {
            if (!is_writable($targetPath)) {
                return $this->responseFactory->createResponse($this->image_attachment_not_writable((int) $product_id));
            }
            $product = $pR->repoProductquery($product_id) ?: null;
            if ($product instanceof Product) {
                $product_id = $product->getProduct_id();
                if ($product_id) {
                    if (!empty($_FILES)) {
                        // @see https://github.com/vimeo/psalm/issues/5458

                        /** @var array $_FILES['ImageAttachForm'] */
                        /** @var string $_FILES['ImageAttachForm']['tmp_name']['attachFile'] */
                        $temporary_file = $_FILES['ImageAttachForm']['tmp_name']['attachFile'];
                        /** @var string $_FILES['ImageAttachForm']['name']['attachFile'] */
                        $original_file_name = preg_replace('/\s+/', '_', $_FILES['ImageAttachForm']['name']['attachFile']);
                        $target_path_with_filename = $targetPath . '/' . $original_file_name;
                        if ($this->image_attachment_move_to($temporary_file, $target_path_with_filename, (int)$product_id, $original_file_name, $piR, $sR)) {
                            return $this->responseFactory->createResponse($this->image_attachment_successfully_created((int) $product_id));
                        } else {
                            return $this->responseFactory->createResponse($this->image_attachment_no_file_uploaded((int) $product_id));
                        }
                    } else {
                        return $this->responseFactory->createResponse($this->image_attachment_no_file_uploaded((int) $product_id));
                    }
                } // $product_id
            } // $product
            return $this->webService->getRedirectResponse('product/index');
        } //null!==$product_id
        return $this->webService->getRedirectResponse('product/index');
    }
    
    /**
     *
     * @param CurrentRoute $currentRoute
     * @param int $product_id
     * @param piR $piR
     * @param sR $sR
     * @return string
     */
    private function view_partial_product_image(CurrentRoute $currentRoute, int $product_id, piR $piR, sR $sR): string {
        $productimages = $piR->repoProductImageProductquery($product_id);
        $paginator = new OffsetPaginator($productimages);
        $invEdit = $this->userService->hasPermission('editInv');
        $invView = $this->userService->hasPermission('viewInv');
        return $this->viewRenderer->renderPartialAsString('/invoice/product/views/partial_product_image', [
          'form' => new ImageAttachForm(),
          'invEdit' => $invEdit,
          'invView' => $invView,
          'partial_product_image_info' => $this->viewRenderer->renderPartialAsString('/invoice/product/views/partial_product_image_info'),
          'partial_product_image_list' => $this->viewRenderer->renderPartialAsString('/invoice/product/views/partial_product_image_list', [
            'grid_summary' => $sR->grid_summary($paginator, $this->translator, (int) $sR->get_setting('default_list_limit'), $this->translator->translate('invoice.productimage.list'), ''),
            'paginator' => $paginator,
            'invEdit' => $invEdit
          ]),
          'action' => ['product/image_attachment', ['id' => $product_id, '_language' => $currentRoute->getArgument('_language')]]
        ]);
    }
    
    /**
     * @param int product_id
     * @return string
     */
    private function image_attachment_not_writable(int $product_id): string {
        return $this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
        [
            'heading' => $this->translator->translate('i.errors'), 
            'message' => $this->translator->translate('i.path') . $this->translator->translate('i.is_not_writable'),
            'url' => 'product/view', 'id' => $product_id
        ]);
    }

    /**
     * @param int $product_id
     * @return string
     */
    private function image_attachment_successfully_created(int $product_id): string {
        return $this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
        [
            'heading' => '', 
            'message' => $this->translator->translate('i.record_successfully_created'),
            'url' => 'product/view', 'id' => $product_id
        ]);
    }

    /**
     * @param int $product_id
     * @return string
     */
    private function image_attachment_no_file_uploaded(int $product_id): string {
        return $this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
        [
            'heading' => $this->translator->translate('i.errors'), 
            'message' => $this->translator->translate('invoice.productimage.no.file.uploaded'),
            'url' => 'product/view', 'id' => $product_id
        ]);
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param piR $piR
     * @return void
     */
    public function download_image_file(CurrentRoute $currentRoute, piR $piR, sR $sR) : void {
        $product_image_id = $currentRoute->getArgument('product_image_id');
        if (null !== $product_image_id) {
            $product_image = $piR->repoProductImagequery($product_image_id);
            if (null !== $product_image) {
                $aliases = $sR->get_productimages_files_folder_aliases();
                $targetPath = $aliases->get('@productimages_files');
                $original_file_name = $product_image->getFile_name_original();
                $target_path_with_filename = $targetPath . '/' . $original_file_name;
                $path_parts = pathinfo($target_path_with_filename);
                $file_ext = $path_parts['extension'] ?? '';
                if (file_exists($target_path_with_filename)) {
                    $file_size = filesize($target_path_with_filename);
                    $allowed_content_type_array = $piR->getContentTypes();
                    // Check extension against allowed content file types @see ProductImageRepository getContentTypes
                    $save_ctype = isset($allowed_content_type_array[$file_ext]);
                    /** @var string $ctype */
                    $ctype = $save_ctype ? $allowed_content_type_array[$file_ext] : $piR->getContentTypeDefaultOctetStream();
                    // https://www.php.net/manual/en/function.header.php
                    // Remember that header() must be called before any actual output is sent, either by normal HTML tags,
                    // blank lines in a file, or from PHP.
                    header("Expires: -1");
                    header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
                    header("Content-Disposition: attachment; filename=\"$original_file_name\"");
                    header("Content-Type: " . $ctype);
                    header("Content-Length: " . $file_size);
                    echo file_get_contents($target_path_with_filename, true);
                    exit;
                } //if file_exists
                exit;
            } //null!==product_image
            exit;
        } //null!==$product_image_id
        exit;
    }
}
