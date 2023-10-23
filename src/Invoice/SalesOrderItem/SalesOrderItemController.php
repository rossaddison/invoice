<?php   
declare(strict_types=1); 

namespace App\Invoice\SalesOrderItem;

use App\Invoice\Entity\SalesOrderItem;
use App\Invoice\Entity\SalesOrderItemAmount;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\SalesOrderItem\SalesOrderItemService;
use App\Invoice\SalesOrderItem\SalesOrderItemForm;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountService as SOIAS;

use App\Invoice\Product\ProductRepository as PR; 
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SOIAR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository aS TRR;
use App\Invoice\Unit\UnitRepository as UR;

use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

use \Exception;

final class SalesOrderItemController
{
    private SessionInterface $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private SalesOrderItemService $salesorderitemService;
    private DataResponseFactoryInterface $factory;
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        SalesOrderItemService $salesorderitemService,
        DataResponseFactoryInterface $factory,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->viewRenderer = $viewRenderer;
        $this->webService = $webService;
        $this->userService = $userService;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/salesorderitem')
                                                 ->withLayout('@views/invoice/layout/fullpage-loader.php')
                                                 ->withLayout('@views/layout/guest.php');
        }      
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/salesorderitem')
                                                 ->withLayout('@views/invoice/layout/fullpage-loader.php')
                                                 ->withLayout('@views/layout/invoice.php');
        }
        $this->salesorderitemService = $salesorderitemService;
        $this->factory = $factory;
        $this->translator = $translator;
    }
        
    /**
     * @return string
     */    
    private function alert() : string {
        return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
        [
            'flash'=>$this->flash('', ''),
            'errors' => [],
        ]);
    }
    
    /**
     * @param SalesOrderItem $salesorderitem
     * @return array
     */
    private function body(SalesOrderItem $salesorderitem) : array {
        $body = [
          'id'=>$salesorderitem->getId(),
         
          //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-BuyersItemIdentification/
          'peppol_po_itemid'=>$salesorderitem->getPeppol_po_itemid(),
          
          //https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-OrderLineReference/
          'peppol_po_lineid'=>$salesorderitem->getPeppol_po_lineid(),
          
          'date_added'=>$salesorderitem->getDate_added(),
          'name'=>$salesorderitem->getName(),
          'description'=>$salesorderitem->getDescription(),
          'quantity'=>$salesorderitem->getQuantity(),
          'price'=>$salesorderitem->getPrice(),
          'discount_amount'=>$salesorderitem->getDiscount_amount(),
          'order'=>$salesorderitem->getOrder(),
          'product_unit'=>$salesorderitem->getProduct_unit(),
          'so_id'=>$salesorderitem->getSales_order_id(),
          'tax_rate_id'=>$salesorderitem->getTax_rate_id(),
          'product_id'=>$salesorderitem->getProduct_id(),
          'product_unit_id'=>$salesorderitem->getProduct_unit_id()
        ];
        return $body;
    }
    
    public function edit(ViewRenderer $head, CurrentRoute $currentRoute, Request $request, FormHydrator $formHydrator,
                        SOIR $soiR, SettingRepository $sR, TRR $trR, PR $pR, UR $uR, SOR $qR): \Yiisoft\DataResponse\DataResponse|Response {
        $so_item = $this->salesorderitem($currentRoute, $soiR);
        if ($so_item) {
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'action' => ['salesorderitem/edit', ['id' => $currentRoute->getArgument('id')]],
                'errors' => [],
                'body' => $this->body($so_item),
                'so_id'=>$so_item->getSales_order_id(),
                'head'=>$head,
                's'=>$sR,
                'tax_rates'=>$trR->findAllPreloaded(),
                'products'=>$pR->findAllPreloaded(),
                'quotes'=>$qR->findAllPreloaded(),            
                'units'=>$uR->findAllPreloaded(),
                'numberhelper'=>new NumberHelper($sR)
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new SalesOrderItemForm();            
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    // The only item that is different from the quote is the customer's purchase order number
                    $this->salesorderitemService->savePeppol_po_itemid($so_item, $form);
                    $this->salesorderitemService->savePeppol_po_lineid($so_item, $form);
                    return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/salesorder_successful',
                    ['heading'=> $this->translator->translate('invoice.successful'), 'message'=>$sR->trans('record_successfully_updated'),'url'=>'salesorder/view','id'=>$so_item->getSales_order_id()])); 
                }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            } 
            return $this->viewRenderer->render('_item_edit_form', $parameters);
        } //so_item
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash(string $level, string $message): Flash{
        $flash = new Flash($this->session);
        $flash->set($level, $message); 
        return $flash;
    }
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param SalesOrderItemRepository $salesorderitemRepository
     * @return SalesOrderItem|null
     */
    private function salesorderitem(CurrentRoute $currentRoute,SalesOrderItemRepository $salesorderitemRepository) : SalesOrderItem|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $salesorderitem = $salesorderitemRepository->repoSalesOrderItemquery($id);
            return $salesorderitem;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function salesorderitems(SalesOrderItemRepository $salesorderitemRepository) : \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $salesorderitems = $salesorderitemRepository->findAllPreloaded();        
        return $salesorderitems;
    }
    
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
     * 
     * @param int $so_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float $tax_rate_percentage
     * @param SOIAS $soias
     * @param SOIAR $soiar
     * @param SettingRepository $sR
     * @return void
     */
    public function saveSalesOrderItemAmount(int $so_item_id, float $quantity, float $price, float $discount, float $tax_rate_percentage, SOIAS $soias, SOIAR $soiar, SettingRepository $sR): void
    {  
       $soias_array = [];
       if ($so_item_id) {
            $soias_array['so_item_id'] = $so_item_id;
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
            $soias_array['discount'] = $discount_total;
            $soias_array['subtotal'] = $sub_total;
            $soias_array['taxtotal'] = $tax_total;
            $soias_array['total'] = $sub_total - $discount_total + $tax_total;       
            if ($soiar->repoCount((string)$so_item_id) === 0) {
              $soias->saveSalesOrderItemAmountNoForm(new SalesOrderItemAmount() , $soias_array);
            } else {
                $so_item_amount = $soiar->repoSalesOrderItemAmountquery((string)$so_item_id);
                if ($so_item_amount) {
                    $soias->saveSalesOrderItemAmountNoForm($so_item_amount , $soias_array);  
                }    
            }
        } // $quote_item_id    
    }
}

