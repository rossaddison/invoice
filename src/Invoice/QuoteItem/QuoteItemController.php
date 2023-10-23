<?php
declare(strict_types=1); 

namespace App\Invoice\QuoteItem;

use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\QuoteItemAmount;
use App\Invoice\Product\ProductRepository as PR; 
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteItem\QuoteItemService;
use App\Invoice\QuoteItem\QuoteItemForm;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\TaxRate\TaxRateRepository aS TRR;
use App\Invoice\Unit\UnitRepository as UR;
use App\Service\WebControllerService;
use App\User\UserService;
// Helpers
use App\Invoice\Helpers\NumberHelper;
// Psr
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
// Yii
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

final class QuoteItemController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private QuoteItemService $quoteitemService;    
    private DataResponseFactoryInterface $factory;
    private UrlGenerator $urlGenerator;
    private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        QuoteItemService $quoteitemService,        
        DataResponseFactoryInterface $factory,
        UrlGenerator $urlGenerator,
        TranslatorInterface $translator,
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/quoteitem')
                                           ->withLayout('@views/layout/invoice.php');                                                
        $this->webService = $webService;
        $this->userService = $userService;
        $this->quoteitemService = $quoteitemService;
        $this->factory = $factory;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }
    
    /**
     * @param QIR $qiR
     * @param SR $sR
     */
    public function index(QIR $qiR, SR $sR): \Yiisoft\DataResponse\DataResponse
    {       
         $canEdit = $this->rbac();
         $parameters = [      
          's'=>$sR,
          'quote_id'=>$this->session->get('quote_id'),
          'canEdit' => $canEdit,
          'quoteitems' => $this->quoteitems($qiR),
          'alert' => $this->alert() 
         ];
        
        return $this->viewRenderer->render('index', $parameters);
    }
    
    // Quoteitem/add accessed from quote/view renderpartialasstring add_quote_item
    // Triggered by clicking on the save button on the item view appearing above the quote view
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SR $sR
     * @param PR $pR
     * @param UR $uR
     * @param TRR $trR
     * @param QIAR $qiar
     */
    public function add(ViewRenderer $head, Request $request,  
      FormHydrator $formHydrator,
      SR $sR,
      PR $pR,
      UR $uR,                                                
      TRR $trR,
      QIAR $qiar,
    ) : \Yiisoft\DataResponse\DataResponse
    {
        // This function is used 
        $quote_id = (string)$this->session->get('quote_id');
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['quoteitem/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$sR,
            'head'=>$head,
            'quote_id'=>$quote_id,
            'tax_rates'=>$trR->findAllPreloaded(),
            'products'=>$pR->findAllPreloaded(),
            'units'=>$uR->findAllPreloaded(),
            'numberhelper'=>new NumberHelper($sR)
        ];
        
        if ($request->getMethod() === Method::POST) {            
           $form = new QuoteItemForm();
           if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
               $this->quoteitemService->addQuoteItem(new QuoteItem(), $form, $quote_id, $pR, $qiar, new QIAS($qiar),$uR, $trR);
               return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/quote_successful',
                       ['heading'=>'Successful','message'=>$sR->trans('record_successfully_created'),'url'=>'quote/view','id'=>$quote_id]));  
           }    
           $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_item_form', $parameters);
    }
    
  /**
   * @return string
   */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
     [ 
       'flash' => $this->flash,
       'errors' => [],
     ]);
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
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param QIR $qiR
     * @param SR $sR
     * @param TRR $trR
     * @param PR $pR
     * @param UR $uR
     * @param QR $qR
     * @param QIAS $qias
     * @param QIAR $qiar
     */
    public function edit(ViewRenderer $head, CurrentRoute $currentRoute, Request $request, FormHydrator $formHydrator,
                        QIR $qiR, SR $sR, TRR $trR, PR $pR, UR $uR, QR $qR, QIAS $qias, QIAR $qiar): \Yiisoft\DataResponse\DataResponse|Response {
      $quote_id = (string)$this->session->get('quote_id');
      $quote_item = $this->quoteitem($currentRoute, $qiR);
      $parameters = [
              'title' => $this->translator->translate('invoice.edit'),
              'action' => ['quoteitem/edit', ['id' => $currentRoute->getArgument('id')]],
              'errors' => [],
              'body' => $this->body($quote_item ?: new QuoteItem()),
              'quote_id'=>$quote_id,
              'head'=>$head,
              's'=>$sR,
              'tax_rates'=>$trR->findAllPreloaded(),
              'products'=>$pR->findAllPreloaded(),
              'quotes'=>$qR->findAllPreloaded(),            
              'units'=>$uR->findAllPreloaded(),
              'numberhelper'=>new NumberHelper($sR)
          ];
          if ($request->getMethod() === Method::POST) {
              $form = new QuoteItemForm();            
              $body = $request->getParsedBody();
              if ($formHydrator->populate($form, $body) && $form->isValid()) {
                $quantity = $form->getQuantity() ?? 0.00;
                $price = $form->getPrice() ?? 0.00;
                $discount = $form->getDiscount_amount() ?? 0.00;
                $tax_rate_id = $this->quoteitemService->saveQuoteItem($quote_item ?: new QuoteItem(), $form, $quote_id, $pR, $uR) ?: 1;
                $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                if (null!==$tax_rate_percentage) {
                    /**
                     * @psalm-suppress PossiblyNullReference getId
                     */
                    $request_quote_item = (int)$this->quoteitem($currentRoute, $qiR)->getId();
                    $this->saveQuoteItemAmount($request_quote_item, 
                                               $quantity, $price, $discount, $tax_rate_percentage, $qias, $qiar, $sR);    
                    return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/quote_successful',
                    ['heading'=>'Successful', 'message'=>$sR->trans('record_successfully_updated'),'url'=>'quote/view','id'=>$quote_id])); 
                }
              }
              $parameters['body'] = $body;
              $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
          } 
          return $this->viewRenderer->render('_item_edit_form', $parameters);
          //quote_item
    }
    
    /**
     * 
     * @param int $id
     * @param TRR $trr
     * @return float|null
     */
    public function taxrate_percentage(int $id, TRR $trr): float|null
    {
        $taxrate = $trr->repoTaxRatequery((string)$id);
        if ($taxrate) {
            $percentage = $taxrate->getTax_rate_percent();        
            return $percentage;
        }
        return null;
    }
    
    /**
     * @param int $quote_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float $tax_rate_percentage
     * @param QIAS $qias
     * @param QIAR $qiar
     * @param SR $sR
     * @return void
     */
    public function saveQuoteItemAmount(int $quote_item_id, float $quantity, float $price, float $discount, float $tax_rate_percentage, QIAS $qias, QIAR $qiar, SR $sR): void
    {  
       $qias_array = [];
       if ($quote_item_id) {
        $qias_array['quote_item_id'] = $quote_item_id;
        $sub_total = $quantity * $price;
        $discount_total = ($quantity*$discount);
        $tax_total = 0.00;
        // NO VAT
        if ($sR->get_setting('enable_vat_registration') === '0') { 
         $tax_total = (($sub_total * ($tax_rate_percentage/100)));
        }
        // VAT
        if ($sR->get_setting('enable_vat_registration') === '1') { 
         // EARLY SETTLEMENT CASH DISCOUNT MUST BE REMOVED BEFORE VAT DETERMINED
         // @see https://informi.co.uk/finance/how-vat-affected-discounts
         $tax_total = ((($sub_total-$discount_total) * ($tax_rate_percentage/100)));
        }
        $qias_array['discount'] = $discount_total;
        $qias_array['subtotal'] = $sub_total;
        $qias_array['taxtotal'] = $tax_total;
        $qias_array['total'] = $sub_total - $discount_total + $tax_total;       
        if ($qiar->repoCount((string)$quote_item_id) === 0) {
          $qias->saveQuoteItemAmountNoForm(new QuoteItemAmount() , $qias_array);
        } else {
            $quote_item_amount = $qiar->repoQuoteItemAmountquery((string)$quote_item_id);
            if ($quote_item_amount) {
                $qias->saveQuoteItemAmountNoForm($quote_item_amount , $qias_array);  
            }    
        }
      } // $quote_item_id    
    } 
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     */
    public function delete(CurrentRoute $currentRoute, QIR $qiR): \Yiisoft\DataResponse\DataResponse|Response {
        $quote_item = $this->quoteitem($currentRoute, $qiR);
        if ($quote_item) {
            if ($qiR->repoQuoteItemCount($quote_item->getId()) === 1) { 
                $this->quoteitemService->deleteQuoteItem($quote_item);
            }
            return $this->viewRenderer->render('quote/index');
        }
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * @param Request $request
     * @param QIR $qiR
     */
    public function multiple(Request $request, QIR $qiR): \Yiisoft\DataResponse\DataResponse {
      //jQuery parameters from quote.js function delete-items-confirm-quote 'item_ids' and 'quote_id'
      $select_items = $request->getQueryParams();
      $result = false;
      /** @var array $item_ids */
      $item_ids = ($select_items['item_ids'] ?: []);
      $items = $qiR->findinQuoteItems($item_ids);
      // If one item is deleted, the result is positive
      /** @var QuoteItem $item */
      foreach ($items as $item){
          ($this->quoteitemService->deleteQuoteItem($item));
          $result = true;
      }
      return $this->factory->createResponse(Json::encode(($result ? ['success'=>1]:['success'=>0])));  
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     * @param SR $sR
     */
    public function view(CurrentRoute $currentRoute, QIR $qiR,
      SR $sR 
      ): \Yiisoft\DataResponse\DataResponse|Response {
      $quote_item = $this->quoteitem($currentRoute, $qiR);
      if ($quote_item) {
          $parameters = [
              'title' => $sR->trans('view'),
              'action' => ['quoteitem/edit', ['id' => $quote_item->getId()]],
              'errors' => [],
              'body' => $this->body($quote_item),
              's'=>$sR,             
              'quoteitem'=>$qiR->repoQuoteItemquery($quote_item->getId()),
          ];
          return $this->viewRenderer->render('_view', $parameters);
      }
      return $this->webService->getNotFoundResponse();
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
      $canEdit = $this->userService->hasPermission('editInv');
      if (!$canEdit){
          $this->flash_message('warning', $this->translator->translate('invoice.permission'));
          return $this->webService->getRedirectResponse('quote/index');
      }
      return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     * @return QuoteItem|null
     */
    private function quoteitem(CurrentRoute $currentRoute, QIR $qiR): QuoteItem|null
    {
      $id = $currentRoute->getArgument('id'); 
      if (null!==$id) {
          $quoteitem = $qiR->repoQuoteItemquery($id);
          if ($quoteitem) {
            return $quoteitem;
          }  
      }
      return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function quoteitems(QIR $qiR): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
      $quoteitems = $qiR->findAllPreloaded();        
      return $quoteitems;
    }
    
    /**
     * @param QuoteItem $quoteitem
     * @return array
     */
    private function body(QuoteItem $quoteitem): array {
      $body = [
        'id'=>$quoteitem->getId(),
        'quote_id'=>$quoteitem->getQuote_id(),
        'tax_rate_id'=>$quoteitem->getTax_rate_id(),
        'product_id'=>$quoteitem->getProduct_id(),
        'name'=>$quoteitem->getName(),
        'description'=>$quoteitem->getDescription(),
        'quantity'=>$quoteitem->getQuantity(),
        'price'=>$quoteitem->getPrice(),
        'discount_amount'=>$quoteitem->getDiscount_amount(),
        'order'=>$quoteitem->getOrder(),
        'product_unit'=>$quoteitem->getProduct_unit(),
        'product_unit_id'=>$quoteitem->getProduct_unit_id()
      ];
      return $body;
    }
}