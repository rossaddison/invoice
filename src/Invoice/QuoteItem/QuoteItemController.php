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
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

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
    
    // Quoteitem/add accessed from quote/view renderpartialasstring add_quote_item
    // Triggered by clicking on the save button on the item view appearing above the quote view
    
    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SR $sR
     * @param PR $pR
     * @param UR $uR
     * @param TRR $trR
     * @param QIAR $qiar
     */
    public function add(Request $request,  
      FormHydrator $formHydrator,
      SR $sR,
      PR $pR,
      UR $uR,                                                
      TRR $trR,
      QIAR $qiar,
    ) : \Yiisoft\DataResponse\DataResponse|Response
    {
        // This function is used 
        $quote_id = (string)$this->session->get('quote_id');
        $quoteItem = new QuoteItem(); 
        $form = new QuoteItemForm($quoteItem, $quote_id);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['quoteitem/add'],
            'errors' => [],
            'form' => $form,
            'quote_id' => $quote_id,
            'taxRates' => $trR->findAllPreloaded(),
            'products' => $pR->findAllPreloaded(),
            'units' => $uR->findAllPreloaded(),
            'numberHelper' => new NumberHelper($sR)
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body
                 */
                $this->quoteitemService->addQuoteItem($quoteItem, $body, $quote_id, $pR, $qiar, new QIAS($qiar), $uR, $trR, $this->translator);
                $this->flash_message('success', $this->translator->translate('i.record_successfully_created'));
                return $this->webService->getRedirectResponse('quote/view', ['id'=>$quote_id]);  
            }    
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_item_form', $parameters);
    }
    
  /**
   * @return string
   */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert',
     [ 
       'flash' => $this->flash,
     ]);
   }

    /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flash_message(string $level, string $message): Flash|null {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }
    
    /**
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
    public function edit(CurrentRoute $currentRoute, Request $request, FormHydrator $formHydrator,
                        QIR $qiR, SR $sR, TRR $trR, PR $pR, UR $uR, QR $qR, QIAS $qias, QIAR $qiar): \Yiisoft\DataResponse\DataResponse|Response {
        $quote_id = (string)$this->session->get('quote_id');
        $quoteItem = $this->quoteitem($currentRoute, $qiR);
        if (null!==$quoteItem) {
            $form = new QuoteItemForm($quoteItem, $quote_id);
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'actionName' => 'quoteitem/edit',
                'actionArguments' => ['id' => $currentRoute->getArgument('id')],
                'errors' => [],
                'form' => $form,
                'quote_id' => $quote_id,
                'taxRates' => $trR->findAllPreloaded(),
                'products' => $pR->findAllPreloaded(),
                'quotes' => $qR->findAllPreloaded(),            
                'units' => $uR->findAllPreloaded(),
                'numberHelper' => new NumberHelper($sR)
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody();
                    $quantity = null!==$form->getQuantity() ? $form->getQuantity() : 0.00;
                    $price = null!==$form->getPrice() ? $form->getPrice() : 0.00;
                    $discount = null!==$form->getDiscount_amount() ? $form->getDiscount_amount() : 0.00;
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body
                     */
                    $tax_rate_id = $this->quoteitemService->saveQuoteItem($quoteItem, $body, $quote_id, $pR, $uR, $this->translator) ?: 1;
                    $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                    if (null!==$tax_rate_percentage) {
                        /**
                         * @psalm-suppress PossiblyNullReference getId
                         */
                        $request_quote_item = (int)$this->quoteitem($currentRoute, $qiR)->getId();
                        $this->saveQuoteItemAmount($request_quote_item, 
                                                   $quantity, $price, $discount, $tax_rate_percentage, $qias, $qiar, $sR);    
                        $this->flash_message('success', $this->translator->translate('i.record_successfully_updated'));
                        return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
                    }
                }    
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            } 
            return $this->viewRenderer->render('_item_edit_form', $parameters);
        }
        return $this->webService->getNotFoundResponse();
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
            $percentage = $taxrate->getTaxRatePercent();        
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
        if ($sR->getSetting('enable_vat_registration') === '0') { 
         $tax_total = (($sub_total * ($tax_rate_percentage/100)));
        }
        // VAT
        if ($sR->getSetting('enable_vat_registration') === '1') { 
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
            $quote_item_amount = $qiar->repoQuoteItemAmountquery($quote_item_id);
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
     */
    public function view(CurrentRoute $currentRoute,
                         PR $pR,
                         UR $uR,                                                
                         TRR $trR,   
                         QIR $qiR): \Yiisoft\DataResponse\DataResponse|Response 
    {
        $quoteItem = $this->quoteitem($currentRoute, $qiR);
        if ($quoteItem) {
            $form = new QuoteItemForm($quoteItem, $quoteItem->getQuote_id()); 
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['quoteitem/edit', ['id' => $quoteItem->getId()]],
                'errors' => [],
                'form' => $form,
                'tax_rates' => $trR->findAllPreloaded(),
                'products' => $pR->findAllPreloaded(),
                'units' => $uR->findAllPreloaded(),
                'quoteitem' => $qiR->repoQuoteItemquery($quoteItem->getId()),
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
        if (null!== $id) {
            $quoteitem = $qiR->repoQuoteItemquery($id);
            if ($quoteitem) {
              return $quoteitem;
            }  
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function quoteitems(QIR $qiR): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
      $quoteitems = $qiR->findAllPreloaded();        
      return $quoteitems;
    }    
}