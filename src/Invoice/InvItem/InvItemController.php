<?php

declare(strict_types=1); 

namespace App\Invoice\InvItem;

use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvItemAmount;
use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\Product\ProductRepository as PR; 
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Task\TaskRepository as TaskR;
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
use Yiisoft\Yii\Cycle\Data\Reader\EntityReader;
use Yiisoft\Yii\View\ViewRenderer;

final class InvItemController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private InvItemService $invitemService;    
    private DataResponseFactoryInterface $factory;
    private UrlGenerator $urlGenerator;
    private TranslatorInterface $translator;
    
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        InvItemService $invitemService,        
        DataResponseFactoryInterface $factory,
        UrlGenerator $urlGenerator,
        TranslatorInterface $translator,
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/invitem')
                                           ->withLayout('@views/layout/invoice.php');                                                
        $this->webService = $webService;
        $this->userService = $userService;
        $this->invitemService = $invitemService;
        $this->factory = $factory;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SR $sR
     * @param PR $pR
     * @param UR $uR
     * @param TRR $trR
     * @param IIAR $iiar
     */
    public function add_product(ViewRenderer $head, Request $request, 
                       FormHydrator $formHydrator,
                        SR $sR,
                        PR $pR,
                        UR $uR,                                                
                        TRR $trR,
                        IIAR $iiar,
    ) : \Yiisoft\DataResponse\DataResponse
    {
        $inv_id = (string)$this->session->get('inv_id');
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['invitem/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$sR,
            'head'=>$head,
            'inv_id'=>$inv_id,
            'tax_rates'=>$trR->findAllPreloaded(),
              // Only tasks that are complete are put on the invoice
            'products'=>$pR->findAllPreloaded(),
            'units'=>$uR->findAllPreloaded(),
            'numberhelper'=>new NumberHelper($sR)
        ];
        
        if ($request->getMethod() === Method::POST) {            
            $form = new InvItemForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
              $this->invitemService->addInvItem_product(new InvItem(), $form, $inv_id, $pR, $trR, new IIAS($iiar), $iiar, $sR, $uR);
              $this->flash_message('info', $sR->trans('record_successfully_created'));
              return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                     ['heading'=>'', 'message'=>$sR->trans('record_successfully_created'),'url'=>'inv/view','id'=>$inv_id]));  
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_item_form_product', $parameters);
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SR $sR
     * @param TaskR $taskR
     * @param UR $uR
     * @param TRR $trR
     * @param IIAR $iiar
     */
    public function add_task(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        SR $sR,
                        TaskR $taskR,
                        UR $uR,                                                
                        TRR $trR,
                        IIAR $iiar,
    ) : \Yiisoft\DataResponse\DataResponse
    {
        $inv_id = (string)$this->session->get('inv_id');
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['invitem/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$sR,
            'head'=>$head,
            'inv_id'=>$inv_id,
            'tax_rates'=>$trR->findAllPreloaded(),
              // Only tasks that are complete are put on the invoice
            'tasks'=>$taskR->repoTaskStatusquery(3),
            'units'=>$uR->findAllPreloaded(),
            'numberhelper'=>new NumberHelper($sR)
        ];
        
        if ($request->getMethod() === Method::POST) {            
            $form = new InvItemForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                  $this->invitemService->addInvItem_task(new InvItem(), $form, $inv_id, $taskR, $trR, new IIAS($iiar), $iiar, $sR);
                  $this->flash_message('info', $sR->trans('record_successfully_created'));
                  return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                         ['heading'=>'','message'=>$sR->trans('record_successfully_created'),'url'=>'inv/view','id'=>$inv_id]));  
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }        
        return $this->viewRenderer->renderPartial('_item_form_task', $parameters);
    }
    
   /**
    * 
    * @param InvItem $invitem
    * @return array
    */
    private function body(InvItem $invitem): array {
        $body = [
          'id'=>$invitem->getId(),
          'inv_id'=>$invitem->getInv_id(),
          'tax_rate_id'=>$invitem->getTax_rate_id(),
          'product_id'=>$invitem->getProduct_id(),
          'task_id'=>$invitem->getTask_id(),
          'name'=>$invitem->getName(),
          'description'=>$invitem->getDescription(),
          'quantity'=>$invitem->getQuantity(),
          'price'=>$invitem->getPrice(),
          'discount_amount'=>$invitem->getDiscount_amount(),
          'order'=>$invitem->getOrder(),
          'product_unit'=>$invitem->getProduct_unit(),
          'product_unit_id'=>$invitem->getProduct_unit_id(),
          'belongs_to_vat_invoice'=>$invitem->getBelongs_to_vat_invoice(),
          'delivery_id'=>$invitem->getDelivery_id(),
          'note'=>$invitem->getNote(),
        ];
        return $body;
    }
    
    /**
     * Used with function edit_product
     * @param EntityReader $inv_item_allowances_charges
     * @return float
     */
    public function accumulative_allowances(EntityReader $inv_item_allowances_charges) : float 
    {
        $allowances = 0.00;
        /** @var InvItemAllowanceCharge $acii */
        foreach ($inv_item_allowances_charges as $acii)
        {
            if ($acii->getAllowanceCharge()?->getIdentifier() === true)
            {
                $allowances +=(float)$acii->getAmount();
            }
        }
        return $allowances;
    }
    
    /**
     * Used with function edit_product
     * @param EntityReader $inv_item_allowances_charges
     * @return float
     */
    public function accumulative_charges(EntityReader $inv_item_allowances_charges) : float 
    {
        $charges = 0.00;
        /** @var InvItemAllowanceCharge $acii */
        foreach ($inv_item_allowances_charges as $acii)
        {
            if ($acii->getAllowanceCharge()?->getIdentifier() === false)
            {
                $charges += (float)$acii->getAmount();
            }
        }
        return $charges;
    }
    
    /**
     * This function receives the data from the form that appears if you 
     * click on the pencil icon in the line item 
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param IIR $iiR
     * @param SR $sR
     * @param TRR $trR
     * @param PR $pR
     * @param TaskR $taskR
     * @param UR $uR
     * @param IR $iR
     * @param IIAS $iias
     * @param IIAR $iiar
     * @return \Yiisoft\DataResponse\DataResponse|\Psr\Http\Message\ResponseInterface
     */
    public function edit_product(ViewRenderer $head, CurrentRoute $currentRoute, Request $request, FormHydrator $formHydrator,
                        IIR $iiR, SR $sR, TRR $trR, PR $pR, TaskR $taskR, UR $uR, IR $iR, IIAS $iias, IIAR $iiar, ACIIR $aciiR): \Yiisoft\DataResponse\DataResponse|\Psr\Http\Message\ResponseInterface  {
        $inv_id = (string)$this->session->get('inv_id');
        $inv_item = $this->invitem($currentRoute, $iiR);
        if (null!==$inv_item) {
            $inv_item_id = $inv_item->getId();
            $this->session->set('inv_item_id', $inv_item_id);
            // How many allowances or charges does this specific item have?
            $inv_item_allowances_charges_count = $aciiR->repoInvItemcount((string)$inv_item_id);
            $inv_item_allowances_charges = $aciiR->repoInvItemquery((string)$inv_item_id);
            $parameters = [
                'title' => $this->translator->translate('invoice.product.edit'),
                'action' => ['invitem/edit_product', ['id' => $currentRoute->getArgument('id')]],
                'add_item_action' => ['acii/add', ['inv_item_id'=> $inv_item_id]],
                'index_item_action' => ['acii/index', ['inv_item_id'=> $inv_item_id]],
                'errors' => [],
                'body' => $this->body($inv_item),
                'inv_id'=>$inv_id,
                'inv_item_allowances_charges_count' => $inv_item_allowances_charges_count,
                'inv_item_allowances_charges' => $inv_item_allowances_charges,
                'head'=>$head,
                's'=>$sR,
                'tax_rates'=>$trR->findAllPreloaded(),
                'products'=>$pR->findAllPreloaded(),
                'invs'=>$iR->findAllPreloaded(),                  
                'units'=>$uR->findAllPreloaded(),
                'numberhelper'=>new NumberHelper($sR)
            ];
            if ($request->getMethod() === Method::POST) {
                $form = new InvItemForm();
                $body = $request->getParsedBody();
                if ($formHydrator->populate($form, $body) && $form->isValid()) {
                    // Goal: Use the invitem/item_edit_product form data 
                    // to build invitemamount->subtotal=(form[quantity]*form[price])
                    // to build invitemamount->discount=(quantity*form[discount])
                    // Preparation here: Collect the data for this purpose
                    // form[quantity]
                    $quantity = $form->getQuantity() ?: 0.00;
                    // form[price]
                    $price = $form->getPrice() ?: 0.00;
                    // form[discount]
                    $discount = $form->getDiscount_amount() ?: 0.00;
                    // Goal: Accumulate all charges from invitemallowancecharge 
                    // and save in invitemamount->charge
                    $charge = $this->accumulative_charges($inv_item_allowances_charges) ?: 0.00;
                    // Goal: Accumulate all allowances from invitemallowancecharge 
                    // and save in invitemamount->allowance
                    $allowance = $this->accumulative_allowances($inv_item_allowances_charges) ?: 0.00;
                    $tax_rate_id = $this->invitemService->saveInvItem_product($inv_item, $form, $inv_id, $pR, $sR, $uR) ?: 1;        
                    $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                    if (null!==$tax_rate_percentage) {
                        /**
                         * @psalm-suppress PossiblyNullReference getId
                         */
                        $request_inv_item = (int)$this->invitem($currentRoute, $iiR)->getId();

                        $this->saveInvItemAmount($request_inv_item, 
                                                 $quantity, $price, $discount, $charge, $allowance, $tax_rate_percentage, $iias, $iiar, $sR);
                        //return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                        //['heading'=>'Successful','message'=>$sR->trans('record_successfully_updated'),'url'=>'inv/view','id'=>$inv_id])); 

                        $this->flash_message('info', $sR->trans('record_successfully_updated'));
                        return $this->webService->getRedirectResponse('inv/view',['id'=>$inv_id]);
                }    
                } else {   
                    //return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                    //['heading'=>'Not successful','message'=>'nosussss','url'=>'inv/view','id'=>$inv_id])); 
                    $this->flash_message('info','not successful');
                    $this->webService->getRedirectResponse('inv/view',['id'=>$inv_id]);
                    }
                $parameters['body'] = $body;
                $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
            } 
            return $this->viewRenderer->render('_item_edit_product', $parameters);
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
            $percentage = $taxrate->getTax_rate_percent();        
            return $percentage;
        }
        return null;
    }
    
    /**
     * 
     * @param int $inv_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float $charge
     * @param float $allowance
     * @param float $tax_rate_percentage
     * @param IIAS $iias
     * @param IIAR $iiar
     * @param SR $s
     * @return void
     */
    public function saveInvItemAmount(int $inv_item_id, float $quantity, float $price, float $discount, float $charge, float $allowance, float $tax_rate_percentage, IIAS $iias, IIAR $iiar, SR $s): void
    {       
       $iias_array = [];
       $iias_array['inv_item_id'] = $inv_item_id;       
       $sub_total = $quantity * $price;
       $discount_total = ($quantity*$discount);
       $charge_total = $charge;
       $allowance_total = $allowance;
       $tax_total = 0.00;
       // NO VAT
       if ($s->get_setting('enable_vat_registration') === '0') { 
        $tax_total = (($sub_total * ($tax_rate_percentage/100)));
       }
       // VAT
       if ($s->get_setting('enable_vat_registration') === '1') { 
        // EARLY SETTLEMENT CASH DISCOUNT MUST BE REMOVED BEFORE VAT DETERMINED
        // @see https://informi.co.uk/finance/how-vat-affected-discounts
        $tax_total = ((($sub_total-$discount_total+$charge_total-$allowance_total) * ($tax_rate_percentage/100)));
       }
       $iias_array['discount'] = $discount_total;
       $iias_array['charge'] = $charge_total;
       $iias_array['allowance'] = $allowance_total;
       $iias_array['subtotal'] = $sub_total;
       $iias_array['taxtotal'] = $tax_total;
       $iias_array['total'] = ($sub_total - $discount_total + $charge_total - $allowance_total + $tax_total);       
       if ($iiar->repoCount((string)$inv_item_id) === 0) {
         $iias->saveInvItemAmountNoForm(new InvItemAmount(), $iias_array);} else {
         $inv_item_amount = $iiar->repoInvItemAmountquery((string)$inv_item_id);    
         if ($inv_item_amount) {
            $iias->saveInvItemAmountNoForm($inv_item_amount, $iias_array);     
         }
       }                      
    }   
    
    /**
     * 
     * @param ViewRenderer $head
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param IIR $iiR
     * @param SR $sR
     * @param TRR $trR
     * @param PR $pR
     * @param TaskR $taskR
     * @param UR $uR
     * @param IR $iR
     * @param IIAS $iias
     * @param IIAR $iiar
     * @return Response
     */
    public function edit_task(ViewRenderer $head, CurrentRoute $currentRoute, Request $request, FormHydrator $formHydrator,
                        IIR $iiR, SR $sR, TRR $trR, TaskR $taskR, UR $uR, IR $iR, IIAS $iias, IIAR $iiar): Response {
        $inv_id = (string)$this->session->get('inv_id');
        $inv_item = $this->invitem($currentRoute, $iiR);
        $parameters = [
            'title' =>  $sR->trans('edit'),
            'action' => ['invitem/edit_task', ['id' => $currentRoute->getArgument('id')]],
            'errors' => [],
            // if null inv_item, initialize it => prevent psalm PossiblyNullArgument error
            'body' => $this->body($inv_item ?: New InvItem()),
            'inv_id'=>$inv_id,
            'head'=>$head,
            's'=>$sR,
            'tax_rates'=>$trR->findAllPreloaded(),
            // Only tasks that are complete are put on the invoice
            'tasks'=>$taskR->repoTaskStatusquery(3),
            'invs'=>$iR->findAllPreloaded(),                  
            'units'=>$uR->findAllPreloaded(),
            'numberhelper'=>new NumberHelper($sR)
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new InvItemForm();
            $body = $request->getParsedBody();
            if ($formHydrator->populate($form, $body) && $form->isValid()) {
                $quantity = $form->getQuantity() ?? 0.00;
                $price = $form->getPrice() ?? 0.00;
                $discount = $form->getDiscount_amount() ?? 0.00;
                $tax_rate_id = $this->invitemService->saveInvItem_task($inv_item ?: new InvItem(), $form, $inv_id, $taskR, $sR)  ?: 1;        
                $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                if (null!==$tax_rate_percentage) {
                    /**
                     * @psalm-suppress PossiblyNullReference getId
                     */
                    $request_inv_item = (int)$this->invitem($currentRoute, $iiR)->getId();
                    $this->saveInvItemAmount($request_inv_item, $quantity, $price, $discount, 0.00, 0.00, $tax_rate_percentage, $iias, $iiar, $sR);
                    return $this->factory->createResponse($this->viewRenderer->renderPartialAsString('/invoice/setting/inv_message',
                    ['heading'=>'Successful','message'=>$sR->trans('record_successfully_updated'),'url'=>'inv/view','id'=>$inv_id])); 
                }
            }
            $parameters['body'] = $body;
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        } 
        return $this->viewRenderer->render('_item_edit_task', $parameters);        
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
     * @param CurrentRoute $currentRoute
     * @param IIR $iiR
     * @return InvItem|null
     */
    private function invitem(CurrentRoute $currentRoute, IIR $iiR): InvItem|null
    {
        $id = $currentRoute->getArgument('id');
        if (null!==$id) {
           $invitem = $iiR->repoInvItemquery($id);
           if ($invitem) {
               return $invitem;
           }
        }
        return null;
    }
    
    /**
     * 
     * @param IIR $iiR
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function invitems(IIR $iiR): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader 
    {
        $invitems = $iiR->findAllPreloaded();        
        return $invitems;
    }
    
    /**
     * @param Request $request
     * @param IIR $iiR
     */
    public function multiple(Request $request, IIR $iiR): \Yiisoft\DataResponse\DataResponse {
        //jQuery parameters from inv.js function delete-items-confirm-inv 'item_ids' and 'inv_id'
        $select_items = $request->getQueryParams();
        $result = false;
        $item_ids = ($select_items['item_ids'] ? (array)$select_items['item_ids'] : []);
        $items = $iiR->findinInvItems($item_ids);
        // If one item is deleted, the result is positive
        /** @var InvItem $item */
        foreach ($items as $item){
            ($this->invitemService->deleteInvItem($item));
            $result = true;
        }
        return $this->factory->createResponse(Json::encode(($result ? ['success'=>1]:['success'=>0])));  
    }
        
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param IIR $iiR
     * @param SR $sR
     * @return Response
     */
    public function view(CurrentRoute $currentRoute, IIR $iiR,
        SR $sR 
        ): Response {
        $inv_item = $this->invitem($currentRoute, $iiR);
        if ($inv_item) {
            $parameters = [
              'title' => $sR->trans('view'),
              'action' => ['invitem/edit', ['id' => $inv_item->getId()]],
              'errors' => [],
              'body' => $this->body($inv_item),
              's'=>$sR,             
              'invitem'=>$iiR->repoInvItemquery((string)$inv_item->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    } 
}