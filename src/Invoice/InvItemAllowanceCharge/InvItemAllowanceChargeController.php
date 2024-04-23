<?php
declare(strict_types=1); 

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeService;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository;

use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\Setting\SettingRepository;

use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\ViewRenderer;

final class InvItemAllowanceChargeController
{
    private SessionInterface $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private InvItemAllowanceChargeService $aciiService;
    private TranslatorInterface $translator;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        InvItemAllowanceChargeService $aciiService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer;
        $this->webService = $webService;
        $this->userService = $userService;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/acii')
                                               ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/acii')
                                               ->withLayout('@views/layout/invoice.php');
        }
        $this->aciiService = $aciiService;
        $this->translator = $translator;
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $acR
     * @param InvItemAllowanceChargeRepository $aciiR
     * @param InvItemRepository $iiR
     * @param InvItemAmountRepository $iiaR
     * @return Response
     */
    public function add(CurrentRoute $currentRoute, Request $request, 
                        FormHydrator $formHydrator,
                        AllowanceChargeRepository $acR,
                        InvItemAllowanceChargeRepository $aciiR,
                        InvItemRepository $iiR,
                        InvItemAmountRepository $iiaR 
            
    ) : Response
    {   
        $inv_item_id = $currentRoute->getArgument('inv_item_id');
        $inv_item = $iiR->repoInvItemquery((string)$inv_item_id);
        if ($inv_item) {
            $inv_item_ac = new InvItemAllowanceCharge();
            $form = new InvItemAllowanceChargeForm($inv_item_ac, (int)$inv_item_id);
            $inv_id = $inv_item->getInv_id();
            $parameters = [
                'title' => $this->translator->translate('invoice.add'),
                'action' => ['acii/add', ['inv_item_id'=> $inv_item_id]],
                'errors' => [],
                'form' => $form,
                'allowance_charges' => $acR->findAllPreloaded(),
                'inv_item_id' => $inv_item_id,
            ];

            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if (is_array($body)) {
                    $body['inv_id'] = $inv_id;
                    $body['inv_item_id'] = $inv_item_id;
                    /** @var string $allowance_charge_id */
                    $allowance_charge_id = $body['allowance_charge_id'];
                    $allowance_charge = $acR->repoAllowanceChargequery($allowance_charge_id);            
                    if ($allowance_charge) {
                        $amount = (float)$body['amount'];
                        $percent = $allowance_charge->getTaxRate()?->getTax_rate_percent() ?? 0.00;
                        $vat = $amount * $percent / 100;
                        if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                            /**
                             * @psalm-suppress PossiblyInvalidArgument $body
                             */
                            $this->aciiService->saveInvItemAllowanceCharge($inv_item_ac, $body, $vat);
                            $all_charges = 0.00;
                            $all_charges_vat = 0.00;                        
                            $all_allowances = 0.00;
                            $all_allowances_vat = 0.00;
                            $aciis = $aciiR->repoInvItemquery((string)$inv_item_id);                        
                            $inv_item_amount = $iiaR->repoInvItemAmountquery((string)$inv_item_id);
                            /** @var InvItemAllowanceCharge $acii */
                            foreach ($aciis as $acii) {
                                // charge add
                                if ($acii->getAllowanceCharge()?->getIdentifier() === true) {
                                    $all_charges += (float)$acii->getAmount();
                                    $all_charges_vat += (float)$acii->getVat();
                                } else {
                                    // allowance subtract
                                    $all_allowances += (float)$acii->getAmount();
                                    $all_allowances_vat += (float)$acii->getVat();
                                }
                            }
                            // Record the charges and allowances in the InvItemAmount Entity
                            $inv_item_amount?->setCharge($all_charges);
                            $inv_item_amount?->setAllowance($all_allowances);
                            $all_vat = $all_charges_vat - $all_allowances_vat;
                            $current_item_quantity = $inv_item_amount?->getInvItem()?->getQuantity() ?? 0.00;
                            $current_item_price = $inv_item_amount?->getInvItem()?->getPrice() ?? 0.00;
                            $current_subtotal = $current_item_quantity * $current_item_price;
                            $vat_percent = $inv_item_amount?->getInvItem()->getTaxRate()->getTax_rate_percent();
                            $current_tax_total = $current_subtotal * ($vat_percent ?? 0.00) / 100;
                            $new_tax_total = $current_tax_total + $all_vat;
                            $inv_item_amount?->setTax_total($new_tax_total);
                            $overall_total = ($inv_item_amount?->getSubtotal() ?? 0.00) - ($inv_item_amount?->getDiscount() ?? 0.00)  + $new_tax_total + $all_charges - $all_allowances; 
                            $inv_item_amount?->setTotal($overall_total);
                            $iiaR->save($inv_item_amount);
                            return $this->webService->getRedirectResponse('inv/view',['id'=>$inv_id]);
                        }
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByAttribute();
                        $parameters['form'] = $form;
                    } //allowance_charge
                } // is_array    
            }   // request 
            return $this->viewRenderer->render('/invoice/invitemallowancecharge/_form', $parameters);
        } // if inv_item
        return $this->webService->getNotFoundResponse();
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
     * @param Request $request
     * @param InvItemAllowanceChargeRepository $iiacR
     * @param SettingRepository $settingRepository
     * @return Response
     */
    public function index(Request $request, InvItemAllowanceChargeRepository $iiacR, SettingRepository $settingRepository): Response
    {      
      /**
       * @see ...www\invoice\resources\views\invoice\inv\partial_item_table.php search ... Make sure to fill
       */
      $params = $request->getQueryParams();
      /** @var string $params['inv_item_id'] */
      $inv_item_id = $params['inv_item_id'] ?? '';
      $this->flash_message('info', $this->translator->translate('invoice.peppol.allowance.or.charge.inherit'));
      // retrieve all the allowances or charges associated with the inv_item_id
      $invoice_item_allowances_or_charges = $iiacR->repoInvItemquery($inv_item_id);
      $paginator = (new OffsetPaginator($invoice_item_allowances_or_charges));
      $parameters = [
          'alert'=> $this->alert(),
          'inv_item_id'=>$inv_item_id,
          'paginator' => $paginator,
          'grid_summary' => $settingRepository->grid_summary($paginator, $this->translator, (int)$settingRepository->get_setting('default_list_limit'), $this->translator->translate('invoice.invoice.allowance.or.charge.item'), ''),    
      ];
      return $this->viewRenderer->render('/invoice/invitemallowancecharge/index', $parameters);
    }
        
    /**
     * @param InvItemAmountRepository $iiaR
     * @param CurrentRoute $currentRoute
     * @param InvItemAllowanceChargeRepository $aciiRepository
     * @return Response
     */
    public function delete(InvItemAmountRepository $iiaR, CurrentRoute $currentRoute,InvItemAllowanceChargeRepository $aciiRepository 
    ): Response {
        $acii = $this->acii($currentRoute, $aciiRepository);
        if (null!==$acii) {
            $inv_id = $acii->getInv_id();
            $this->aciiService->deleteInvItemAllowanceCharge($acii, $iiaR);               
            $this->flash_message('info', $this->translator->translate('i.record_successfully_deleted'));
            return $this->webService->getRedirectResponse('inv/view',['id'=>$inv_id]);
        }
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $acR
     * @param InvItemAllowanceChargeRepository $aciiR
     * @param InvItemAmountRepository $iiaR
     * @return Response
     */ 
    public function edit(CurrentRoute $currentRoute, 
                         Request $request, 
                         FormHydrator $formHydrator,
                         AllowanceChargeRepository $acR,
                         InvItemAllowanceChargeRepository $aciiR,
                         InvItemAmountRepository $iiaR            
    ) : Response
    {   
        $acii = $this->acii($currentRoute, $aciiR);
        if ($acii) {
            $inv_item_id = $acii->getInv_item_id();
            $inv_item = $acii->getInvItem();
            $inv_id = $inv_item?->getInv_id();
            $form = new InvItemAllowanceChargeForm($acii, (int)$inv_item_id);
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'action' => ['acii/edit', ['id'=> $acii->getId()]],
                'errors' => [],
                'form' => $form,
                'allowance_charges' => $acR->findAllPreloaded(),
                'inv_id' => $inv_id,
                'inv_item_id' => $inv_item_id,
            ];
            $body = $request->getParsedBody();
            /** @var string $body['allowance_charge_id'] */
            $allowance_charge_id = $body['allowance_charge_id'] ?? '';
            /** @var float $body['amount'] */
            $amount = $body['amount'] ?? 0.00;
            if ($request->getMethod() === Method::POST) {
                if ($allowance_charge_id) {
                    $allowance_charge = $acR->repoAllowanceChargequery($allowance_charge_id);            
                    if ($allowance_charge && null!==$body) {
                        $percent = $allowance_charge->getTaxRate()?->getTax_rate_percent() ?? 0.00;
                        $vat = $amount * $percent/100;  
                        if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                            /**
                             * @psalm-suppress PossiblyInvalidArgument $body
                             */
                            $this->aciiService->saveInvItemAllowanceCharge($acii, $body, $vat);
                            $all_charges = 0.00;
                            $all_allowances = 0.00;
                            $all_allowances_vat = 0.00;
                            $all_charges_vat = 0.00;
                            $aciis = $aciiR->repoInvItemquery($inv_item_id);                        
                            $inv_item_amount = $iiaR->repoInvItemAmountquery($inv_item_id);
                            if (null!==$inv_item_amount) {
                                /** @var InvItemAllowanceCharge $acii */
                                foreach ($aciis as $acii) {
                                    // charge add
                                    if ($acii->getAllowanceCharge()?->getIdentifier() === true) {
                                        $all_charges += (float)$acii->getAmount();
                                        $all_charges_vat += (float)$acii->getVat();
                                    } else {
                                        // allowance subtract
                                        $all_allowances += (float)$acii->getAmount();
                                        $all_allowances_vat += (float)$acii->getVat();
                                    }
                                }
                                // Record the charges and allowances in the InvItemAmount Entity
                                $inv_item_amount->setCharge($all_charges);
                                $inv_item_amount->setAllowance($all_allowances);
                                $all_vat = $all_charges_vat - $all_allowances_vat;
                                $current_item_quantity = $inv_item_amount->getInvItem()?->getQuantity() ?? 0.00;
                                $current_item_price = $inv_item_amount->getInvItem()?->getPrice() ?? 0.00;
                                $current_subtotal = $current_item_quantity * $current_item_price;
                                $vat_percent = $inv_item_amount->getInvItem()?->getTaxRate()?->getTax_rate_percent();
                                $current_tax_total = $current_subtotal * ($vat_percent ?? 0.00) / 100;
                                $new_tax_total = $current_tax_total + $all_vat;
                                $inv_item_amount->setTax_total($new_tax_total);
                                $overall_total = ($inv_item_amount->getSubtotal() ?? 0.00)
                                                 // This discount relates to the edit...form...discount value
                                                 // and is unrelated to peppol allowances and charges 
                                               - ($inv_item_amount->getDiscount() ?? 0.00)  
                                               + $new_tax_total + $all_charges - $all_allowances; 
                                $inv_item_amount->setTotal($overall_total);
                                $iiaR->save($inv_item_amount);
                                return $this->webService->getRedirectResponse('inv/view',['id'=>$inv_id]);
                            } //null !==$inv_item_amount
                            return $this->webService->getNotFoundResponse();
                        } // $form
                        
                        return $this->webService->getNotFoundResponse(); 
                    } //allowance_charge
                    return $this->webService->getNotFoundResponse();                        
                } // allowance_charge_id
                $parameters['form'] = $form;
            } // request
            return $this->viewRenderer->render('/invoice/invitemallowancecharge/_form', $parameters);
        } // if acii
        return $this->webService->getRedirectResponse('acii/index');
    }
    
    //For rbac refer to AccessChecker    
    
    /**
     * @param CurrentRoute $currentRoute
     * @param InvItemAllowanceChargeRepository $aciiRepository
     * @return InvItemAllowanceCharge|null
     */
    private function acii(CurrentRoute $currentRoute,InvItemAllowanceChargeRepository $aciiRepository) : InvItemAllowanceCharge|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $acii = $aciiRepository->repoInvItemAllowanceChargequery($id);
            return $acii;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function aciis(InvItemAllowanceChargeRepository $aciiRepository) : \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $aciis = $aciiRepository->findAllPreloaded();        
        return $aciis;
    }
        
    /**
     * @param CurrentRoute $currentRoute
     * @param InvItemAllowanceChargeRepository $aciiRepository
     * @param AllowanceChargeRepository $acR
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(
            CurrentRoute $currentRoute, 
            InvItemAllowanceChargeRepository $aciiRepository,
            AllowanceChargeRepository $acR
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $acii = $this->acii($currentRoute, $aciiRepository); 
        if ($acii) {
            $inv_item_id = $acii->getInv_item_id();
            $form = new InvItemAllowanceChargeForm($acii, (int)$inv_item_id);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['acii/view', ['id' => $acii->getId()]],
                'allowance_charges' => $acR->findAllPreloaded(),
                'form' => $form,
                'acii' => $acii,
            ];        
            return $this->viewRenderer->render('/invoice/invitemallowancecharge/_view', $parameters);
        }
        return $this->webService->getRedirectResponse('acii/index');
    }
}