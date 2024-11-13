<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Invoice\Entity\InvItem;
use App\Invoice\Entity\InvItemAmount;
use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\InvItem\InvItemForm;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\InvRecurring\InvRecurringRepository as IRR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Payment\PaymentRepository as PYMR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;
use App\Service\WebControllerService;
use App\User\UserService;
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
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

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
    ) {
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
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SR $sR
     * @param PR $pR
     * @param UR $uR
     * @param TRR $trR
     * @param IIAR $iiar
     * @return \Yiisoft\DataResponse\DataResponse|\Psr\Http\Message\ResponseInterface
     */
    public function add_product(
        Request $request,
        FormHydrator $formHydrator,
        SR $sR,
        PR $pR,
        UR $uR,
        TRR $trR,
        IRR $irR,
        IIAR $iiar,
    ): \Yiisoft\DataResponse\DataResponse|\Psr\Http\Message\ResponseInterface {
        $inv_id = (string)$this->session->get('inv_id');
        $invitem = new InvItem();
        $is_recurring = ($irR->repoCount((string) $this->session->get('inv_id')) > 0 ? true : false);
        $form = new InvItemForm($invitem, (int)$inv_id);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'invitem/add_product',
            'errors' => [],
            'form' => $form,
            'inv_id' => $inv_id,
            'isRecurring' => $is_recurring,
            'taxRates' => $trR->findAllPreloaded(),
              // Only tasks that are complete are put on the invoice
            'products' => $pR->findAllPreloaded(),
            'units' => $uR->findAllPreloaded()
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->invitemService->addInvItem_product($invitem, $body, $inv_id, $pR, $trR, new IIAS($iiar), $iiar, $sR, $uR);
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_created'));
                    return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
                }
            }    
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_item_form_product', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SR $sR
     * @param TaskR $taskR
     * @param UR $uR
     * @param TRR $trR
     * @param IRR $irR
     * @param IIAR $iiar
     */
    public function add_task(
        Request $request,
        FormHydrator $formHydrator,
        SR $sR,
        TaskR $taskR,
        UR $uR,
        TRR $trR,
        IRR $irR,
        IIAR $iiar,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $inv_id = (string)$this->session->get('inv_id');
        $invitem = new InvItem();
        $is_recurring = ($irR->repoCount((string) $this->session->get('inv_id')) > 0 ? true : false);
        $form = new InvItemForm($invitem, (int)$inv_id);
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'actionName' => 'invitem/add_task',
            'errors' => [],
            'form' => $form,
            'inv_id' => $inv_id,
            'isRecurring' => $is_recurring,
            'taxRrates' => $trR->findAllPreloaded(),
              // Only tasks that are complete are put on the invoice
            'tasks' => $taskR->repoTaskStatusquery(3),
            'units' => $uR->findAllPreloaded()
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                if (is_array($body)) {
                    $this->invitemService->addInvItem_task($invitem, $body, $inv_id, $taskR, $trR, new IIAS($iiar), $iiar, $sR);
                    $this->flash_message('info', $this->translator->translate('i.record_successfully_created'));
                    return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
                }
            }    
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->renderPartial('_item_form_task', $parameters);
    }

    /**
     * Used with function edit_product
     * @param EntityReader $inv_item_allowances_charges
     * @return float
     */
    public function accumulative_allowances(EntityReader $inv_item_allowances_charges): float
    {
        $allowances = 0.00;
        /** @var InvItemAllowanceCharge $acii */
        foreach ($inv_item_allowances_charges as $acii) {
            if ($acii->getAllowanceCharge()?->getIdentifier() == '0') {
                $allowances += (float)$acii->getAmount();
            }
        }
        return $allowances;
    }

    /**
     * Used with function edit_product
     * @param EntityReader $inv_item_allowances_charges
     * @return float
     */
    public function accumulative_charges(EntityReader $inv_item_allowances_charges): float
    {
        $charges = 0.00;
        /** @var InvItemAllowanceCharge $acii */
        foreach ($inv_item_allowances_charges as $acii) {
            if ($acii->getAllowanceCharge()?->getIdentifier() == '1') {
                $charges += (float)$acii->getAmount();
            }
        }
        return $charges;
    }

    /**
     * This function receives the data from the form that appears if you
     * click on the pencil icon in the line item
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param IIR $iiR
     * @param SR $sR
     * @param TRR $trR
     * @param PYMR $pymR
     * @param PR $pR
     * @param UR $uR
     * @param IAR $iaR
     * @param IR $iR
     * @param IIAS $iias
     * @param IRR $irR
     * @param IIAR $iiaR
     * @param ITRR $itrR
     * @param ACIR $aciR
     * @param ACIIR $aciiR
     * @return \Yiisoft\DataResponse\DataResponse|\Psr\Http\Message\ResponseInterface
     */
    public function edit_product(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        IIR $iiR,
        SR $sR,
        TRR $trR,
        PYMR $pymR,
        PR $pR,
        UR $uR,
        IAR $iaR,
        IR $iR,
        IIAS $iias,
        IRR $irR,
        IIAR $iiaR,
        ITRR $itrR,
        ACIR $aciR,
        ACIIR $aciiR
    ): \Yiisoft\DataResponse\DataResponse|\Psr\Http\Message\ResponseInterface {
        $inv_id = (string)$this->session->get('inv_id');
        $inv_item = $this->invitem($currentRoute, $iiR);
        $is_recurring = ($irR->repoCount((string) $this->session->get('inv_id')) > 0 ? true : false);
        if (null !== $inv_item) {
            $form = new InvItemForm($inv_item, (int)$inv_id);
            $inv_item_id = $inv_item->getId();
            $this->session->set('inv_item_id', $inv_item_id);
            // How many allowances or charges does this specific item have?
            $inv_item_allowances_charges_count = $aciiR->repoInvItemcount((string)$inv_item_id);
            $inv_item_allowances_charges = $aciiR->repoInvItemquery((string)$inv_item_id);
            $parameters = [
                'title' => $this->translator->translate('invoice.product.edit'),
                'actionName' => 'invitem/edit_product',
                'actionArguments' => ['id' => $currentRoute->getArgument('id')],
                'addItemActionName' => 'invitemallowancecharge/add',
                'addItemActionArguments' => ['inv_item_id' => $inv_item_id],
                'indexItemActionName' => 'invitemallowancecharge/index',
                'indexItemActionArguments' => ['inv_item_id' => $inv_item_id],
                'errors' => [],
                'form' => $form,
                'inv_id' => $inv_id,
                'invItemAllowancesChargesCount' => $inv_item_allowances_charges_count,
                'invItemAllowancesCharges' => $inv_item_allowances_charges,
                'isRecurring' => $is_recurring,
                'taxRates' => $trR->findAllPreloaded(),
                'products' => $pR->findAllPreloaded(),
                'invs' => $iR->findAllPreloaded(),
                'units' => $uR->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    // Goal: Use the invitem/item_edit_product form data
                    // to build invitemamount->subtotal=(form[quantity]*form[price])
                    // to build invitemamount->discount=(quantity*form[discount])
                    // Preparation here: Collect the data for this purpose
                    // form[quantity]
                    $quantity = (float)($body['quantity'] ?? 0.00);
                    // form[price]
                    $price = (float)($body['price'] ?? 0.00);
                    // form[discount]
                    $discount = (float)($body['discount_amount'] ?? 0.00);
                    // Goal: Accumulate all charges from invitemallowancecharge
                    // and save in invitemamount->charge
                    $charge = $this->accumulative_charges($inv_item_allowances_charges) ?: 0.00;
                    // Goal: Accumulate all allowances from invitemallowancecharge
                    // and save in invitemamount->allowance
                    $allowance = $this->accumulative_allowances($inv_item_allowances_charges) ?: 0.00;
                    if (is_array($body)) {
                        $tax_rate_id = $this->invitemService->saveInvItem_product($inv_item, $body, $inv_id, $pR, $sR, $uR) ?: 1;
                        $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                        if (null !== $tax_rate_percentage) {
                            /**
                             * @psalm-suppress PossiblyNullReference getId
                             */
                            $request_inv_item = (int)$this->invitem($currentRoute, $iiR)->getId();
                            $this->saveInvItemAmount(
                                $request_inv_item,
                                $quantity,
                                $price,
                                $discount,
                                $charge,
                                $allowance,
                                $tax_rate_percentage,
                                $iias,
                                $iiaR,
                                $sR
                            );
                            $numberHelper = new NumberHelper($sR);
                            $numberHelper->calculate_inv($inv_id, $aciR, $iiR, $iiaR, $itrR, $iaR, $iR, $pymR);
                            $this->flash_message('info', $this->translator->translate('i.record_successfully_updated'));
                            return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
                        }
                    }    
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
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
            $percentage = $taxrate->getTaxRatePercent();
            return $percentage;
        }
        return null;
    }

    /**
     * If an item is edited, these changes will have to be shown in the InvItemAmount table
     * The subtotal on the line item is inclusive of any peppol allowances or charges for the item.
     *
     * Any adjustments to this function should be reflected also in the similar InvItemService function saveInvItemAmount
     * which is used for duplicating or copying invoices.
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
        $discount_total = ($quantity * $discount);
        $charge_total = $charge;
        $allowance_total = $allowance;
        $tax_total = 0.00;
        // NO VAT
        if ($s->getSetting('enable_vat_registration') === '0') {
            $tax_total = ((($sub_total - $discount_total + $charge_total - $allowance_total) * ($tax_rate_percentage / 100)));
        }
        // VAT
        if ($s->getSetting('enable_vat_registration') === '1') {
            // EARLY SETTLEMENT CASH DISCOUNT MUST BE REMOVED BEFORE VAT DETERMINED
            // @see https://informi.co.uk/finance/how-vat-affected-discounts
            $tax_total = ((($sub_total - $discount_total + $charge_total) * ($tax_rate_percentage / 100)));
        }
        $iias_array['discount'] = $discount_total;
        $iias_array['charge'] = $charge_total;
        $iias_array['allowance'] = $allowance_total;
        // show the peppol allowances and charges in the sub total
        $iias_array['subtotal'] = $sub_total - $allowance_total + $charge_total;
        $iias_array['taxtotal'] = $tax_total;
        $iias_array['total'] = ($sub_total - $discount_total + $charge_total - $allowance_total + $tax_total);
        if ($iiar->repoCount((string)$inv_item_id) === 0) {
            $iias->saveInvItemAmountNoForm(new InvItemAmount(), $iias_array);
        } else {
            $inv_item_amount = $iiar->repoInvItemAmountquery((string)$inv_item_id);
            if ($inv_item_amount) {
                $iias->saveInvItemAmountNoForm($inv_item_amount, $iias_array);
            }
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param ACIR $aciR
     * @param ACIIR $aciiR
     * @param IAR $iaR
     * @param IIR $iiR
     * @param IRR $irR
     * @param ITRR $itrR
     * @param PYMR $pymR
     * @param SR $sR
     * @param TRR $trR
     * @param PR $pR
     * @param TaskR $taskR
     * @param UR $uR
     * @param IR $iR
     * @param IIAS $iias
     * @param IIAR $iiaR
     * @return \Yiisoft\DataResponse\DataResponse|\Psr\Http\Message\ResponseInterface
     */
    public function edit_task(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        ACIR $aciR,
        ACIIR $aciiR,
        IAR $iaR,
        IIR $iiR,
        IRR $irR,
        ITRR $itrR,
        PYMR $pymR,
        SR $sR,
        TRR $trR,
        TaskR $taskR,
        UR $uR,
        IR $iR,
        IIAS $iias,
        IIAR $iiaR
    ): \Yiisoft\DataResponse\DataResponse|\Psr\Http\Message\ResponseInterface {
        $inv_id = (string)$this->session->get('inv_id');
        $inv_item = $this->invitem($currentRoute, $iiR);
        if ($inv_item) {
            $is_recurring = ($irR->repoCount((string) $this->session->get('inv_id')) > 0 ? true : false);
            $form = new InvItemForm($inv_item, (int)$inv_id);
            $parameters = [
                'title' =>  $this->translator->translate('i.edit'),
                'actionName' => 'invitem/edit_task',
                'actionArguments' => ['id' => $currentRoute->getArgument('id')],
                'errors' => [],
                // if null inv_item, initialize it => prevent psalm PossiblyNullArgument error
                'form' => $form,
                'inv_id' => $inv_id,
                'isRecurring' => $is_recurring,
                'taxRates' => $trR->findAllPreloaded(),
                // Only tasks that are complete are put on the invoice
                'tasks' => $taskR->repoTaskStatusquery(3),
                'invs' => $iR->findAllPreloaded(),
                'units' => $uR->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $quantity = (float)($body['quantity'] ?? 0.00);
                    $price = (float)($body['price'] ?? 0.00);
                    $discount = (float)($body['discount_amount'] ?? 0.00);
                    // Goal: Accumulate all charges from invitemallowancecharge
                    // and save in invitemamount->charge
                    $inv_item_allowances_charges = $aciiR->repoInvItemquery((string)$inv_item->getId());
                    $charge = $this->accumulative_charges($inv_item_allowances_charges) ?: 0.00;
                    // Goal: Accumulate all allowances from invitemallowancecharge
                    // and save in invitemamount->allowance
                    $allowance = $this->accumulative_allowances($inv_item_allowances_charges) ?: 0.00;
                    if (is_array($body)) {
                        $tax_rate_id = $this->invitemService->saveInvItem_task($inv_item, $body, $inv_id, $taskR, $sR) ?: 1;
                        $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                        if (null !== $tax_rate_percentage) {
                            $request_inv_item = (int)$inv_item->getId();
                            $this->saveInvItemAmount($request_inv_item, $quantity, $price, $discount, $charge, $allowance, $tax_rate_percentage, $iias, $iiaR, $sR);
                            $numberHelper = new NumberHelper($sR);
                            $numberHelper->calculate_inv($inv_id, $aciR, $iiR, $iiaR, $itrR, $iaR, $iR, $pymR);
                            $this->flash_message('info', $this->translator->translate('i.record_successfully_updated'));
                            return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
                        }    
                    }    
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_item_form_task', $parameters);
        } // $inv_item
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
       'flash' => $this->flash
     ]
        );
    }

    /**
     * @param string $level
     * @param string $message
     * @return Flash|null
     */
    private function flash_message(string $level, string $message): Flash|null
    {
        if (strlen($message) > 0) {
            $this->flash->add($level, $message, true);
            return $this->flash;
        }
        return null;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param IIR $iiR
     * @return InvItem|null
     */
    private function invitem(CurrentRoute $currentRoute, IIR $iiR): InvItem|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            $invitem = $iiR->repoInvItemquery($id);
            if ($invitem) {
                return $invitem;
            }
        }
        return null;
    }

    /**
     * @param IIR $iiR
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function invitems(IIR $iiR): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $invitems = $iiR->findAllPreloaded();
        return $invitems;
    }

    /**
     * @param Request $request
     * @param IIR $iiR
     */
    public function multiple(Request $request, IIR $iiR): \Yiisoft\DataResponse\DataResponse
    {
        //jQuery parameters from inv.js function delete-items-confirm-inv 'item_ids' and 'inv_id'
        $select_items = $request->getQueryParams();
        $result = false;
        $item_ids = ($select_items['item_ids'] ? (array)$select_items['item_ids'] : []);
        $items = $iiR->findinInvItems($item_ids);
        // If one item is deleted, the result is positive
        /** @var InvItem $item */
        foreach ($items as $item) {
            ($this->invitemService->deleteInvItem($item));
            $result = true;
        }
        return $this->factory->createResponse(Json::encode(($result ? ['success' => 1] : ['success' => 0])));
    }
}
