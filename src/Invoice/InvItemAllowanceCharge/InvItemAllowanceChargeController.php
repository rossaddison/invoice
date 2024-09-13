<?php

declare(strict_types=1); 

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Inv\InvRepository;
use App\Invoice\InvAmount\InvAmountService;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeService;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository;

use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItemAmount\InvItemAmountRepository;
use App\Invoice\InvTaxRate\InvTaxRateRepository;
use App\Invoice\Payment\PaymentRepository;
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
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class InvItemAllowanceChargeController
{
    private SessionInterface $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private InvItemAllowanceChargeService $aciiService;
    private InvAmountService $invAmountService;
    private TranslatorInterface $translator;
    private NumberHelper $numberHelper;
    private SettingRepository $sR;
    
    public function __construct(
        SessionInterface $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        InvItemAllowanceChargeService $aciiService,
        InvAmountService $invAmountService,
        TranslatorInterface $translator,
        SettingRepository $sR    
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer;
        $this->webService = $webService;
        $this->userService = $userService;
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/invitemallowancecharge')
                                               ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $viewRenderer->withControllerName('invoice/invitemallowancecharge')
                                               ->withLayout('@views/layout/invoice.php');
        }
        $this->aciiService = $aciiService;
        $this->invAmountService = $invAmountService;
        $this->translator = $translator;
        $this->sR = $sR;
        $this->numberHelper = new NumberHelper($sR);
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $acR
     * @param InvItemAllowanceChargeRepository $aciiR
     * @param InvItemRepository $iiR
     * @param InvAmountRepository $iaR
     * @param InvItemAmountRepository $iiaR
     * @param InvTaxRateRepository $itrR
     * @return Response
     */
    public function add(CurrentRoute $currentRoute, Request $request, 
                        FormHydrator $formHydrator,
                        AllowanceChargeRepository $acR,
                        InvItemAllowanceChargeRepository $aciiR,
                        InvItemRepository $iiR,
                        InvAmountRepository $iaR,
                        InvItemAmountRepository $iiaR,
                        InvTaxRateRepository $itrR
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
                'actionName' => 'invitemallowancecharge/add',
                'actionArguments' => ['inv_item_id'=> $inv_item_id],
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
                            if (null!==$inv_item_amount) {
                                /** @var InvItemAllowanceCharge $acii */
                                foreach ($aciis as $acii) {
                                    // charge add
                                    if ($acii->getAllowanceCharge()?->getIdentifier() == '1') {
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
                                $discount_per_item = $inv_item_amount->getInvItem()?->getDiscount_amount() ?? 0.00;
                                $quantity_price = $current_item_quantity * $current_item_price;
                                $current_discount_item_total = $current_item_quantity * $discount_per_item;
                                $tax_percent = $inv_item_amount->getInvItem()?->getTaxRate()?->getTax_rate_percent();
                                $qpIncAc = $quantity_price + $all_charges - $all_allowances;
                                $current_tax_total = ($qpIncAc - $current_discount_item_total) * ($tax_percent ?? 0.00) / 100;
                                $new_tax_total = $current_tax_total + ($this->sR->get_setting('enable_vat_registration') == '0' ? 0.00 : $all_vat);
                                // include all item allowance charges in the subtotal
                                $inv_item_amount->setSubtotal($qpIncAc);
                                $inv_item_amount->setDiscount($current_discount_item_total);
                                $inv_item_amount->setTax_total($new_tax_total);
                                $overall_total = $qpIncAc - $current_discount_item_total + $new_tax_total; 
                                $inv_item_amount->setTotal($overall_total);
                                $iiaR->save($inv_item_amount);
                                // update the inv amount
                                $this->invAmountService->updateInvAmount((int)$inv_id, $iaR, $iiaR, $itrR, $this->numberHelper);
                            }
                            return $this->webService->getRedirectResponse('inv/view', ['id'=>$inv_id]);
                        }
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                        $parameters['form'] = $form;
                    } //allowance_charge
                } // is_array    
            }   // request 
            return $this->viewRenderer->render('_form', $parameters);
        } // if inv_item
        return $this->webService->getNotFoundResponse();
    }    
    
  /**
   * @return string
   */
   private function alert(): string {
     return $this->viewRenderer->renderPartialAsString('//invoice/layout/alert',
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
          'inv_item_id' => $inv_item_id,
          'paginator' => $paginator,    
      ];
      return $this->viewRenderer->render('index', $parameters);
    }
        
    /**
     * @param InvItemRepository $iiR
     * @param InvItemAmountRepository $iiaR
     * @param InvAllowanceChargeRepository $aciR
     * @param InvItemAllowanceChargeRepository $aciiR
     * @param InvAmountRepository $iaR
     * @param InvRepository $iR
     * @param InvTaxRateRepository $itrR
     * @param PaymentRepository $pymR
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function delete(InvItemRepository $iiR,
                           InvItemAmountRepository $iiaR, 
                           InvAllowanceChargeRepository $aciR, 
                           InvItemAllowanceChargeRepository $aciiR,
                           InvAmountRepository $iaR, 
                           InvRepository $iR, 
                           InvTaxRateRepository $itrR,
                           PaymentRepository $pymR, 
                           SettingRepository $sR,
                           CurrentRoute $currentRoute 
    ): Response {
        $acii = $this->acii($currentRoute, $aciiR);
        if (null!==$acii) {
            $inv_id = $acii->getInv_id();
            // delete the inv item allowance/charge and update the related inv item amount record
            $this->aciiService->deleteInvItemAllowanceCharge($acii, $iaR, $iiaR, $itrR, $aciiR, $sR);
            // update the inv amount record 
            $this->numberHelper->calculate_inv($inv_id, $aciR, $iiR, $iiaR, $itrR, $iaR, $iR, $pymR);
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
     * @param InvItemRepository $iiR
     * @param InvAmountRepository $iaR
     * @param InvItemAmountRepository $iiaR
     * @param InvTaxRateRepository $itrR
     * @return Response
     */
    public function edit(CurrentRoute $currentRoute, Request $request, 
                        FormHydrator $formHydrator,
                        AllowanceChargeRepository $acR,
                        InvItemAllowanceChargeRepository $aciiR,
                        InvItemRepository $iiR,
                        InvAmountRepository $iaR,
                        InvItemAmountRepository $iiaR,
                        InvTaxRateRepository $itrR
    
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
                'actionName' => 'invitemallowancecharge/edit',
                'actionArguments' => ['id'=> $acii->getId()],
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
                                    if ($acii->getAllowanceCharge()?->getIdentifier() == '1') {
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
                                $discount_per_item = $inv_item_amount->getInvItem()?->getDiscount_amount() ?? 0.00;
                                $quantity_price = $current_item_quantity * $current_item_price;
                                $current_discount_item_total = $current_item_quantity * $discount_per_item;
                                $tax_percent = $inv_item_amount->getInvItem()?->getTaxRate()?->getTax_rate_percent();
                                $qpIncAc = $quantity_price + $all_charges - $all_allowances;
                                $current_tax_total = ($qpIncAc - $current_discount_item_total) * ($tax_percent ?? 0.00) / 100;
                                $new_tax_total = $current_tax_total + ($this->sR->get_setting('enable_vat_registration') == '0' ? 0.00 : $all_vat);
                                // include all item allowance charges in the subtotal
                                $inv_item_amount->setSubtotal($qpIncAc);
                                $inv_item_amount->setDiscount($current_discount_item_total);
                                $inv_item_amount->setTax_total($new_tax_total);
                                $overall_total = $qpIncAc - $current_discount_item_total + $new_tax_total; 
                                $inv_item_amount->setTotal($overall_total);
                                $iiaR->save($inv_item_amount);
                                // update the inv amount
                                $this->invAmountService->updateInvAmount((int)$inv_id, $iaR, $iiaR, $itrR, $this->numberHelper);
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
            return $this->viewRenderer->render('_form', $parameters);
        } // if acii
        return $this->webService->getRedirectResponse('index');
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
                'actionName' => 'invitemallowancecharge/view', 
                'actionArguments' => ['id' => $acii->getId()],
                'allowance_charges' => $acR->findAllPreloaded(),
                'form' => $form,
                'acii' => $acii,
            ];        
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('acii/index');
    }
}