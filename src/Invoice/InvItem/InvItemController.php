<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Invoice\BaseController;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvItemAmount\InvItemAmountService as IIAS;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Service\WebControllerService;
use App\User\UserService;
// Psr
use Psr\Http\Message\ServerRequestInterface as Request;
// Yii
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class InvItemController extends BaseController
{
    protected string $controllerName = 'invoice/invitem';

    public function __construct(
        private InvItemService $invitemService,
        private DataResponseFactoryInterface $factory,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->invitemService = $invitemService;
        $this->factory = $factory;
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param InvItemAddDeps $d
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function addProduct(
        Request $request,
        FormHydrator $formHydrator,
        InvItemAddDeps $d,
    ): \Psr\Http\Message\ResponseInterface {
        $inv_id = (string) $this->session->get('inv_id');
        $invitem = new InvItem();
        $is_recurring = ($d->irR->repoCount((int) $this->session->get('inv_id')) > 0 ?
            true : false);
        $form = new InvItemForm();
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'invitemhtmx/addProduct',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'inv_id' => $inv_id,
            'isRecurring' => $is_recurring,
            'taxRates' => $d->trR->findAllPreloaded(),
            // Only tasks that are complete are put on the invoice
            'products' => $d->pR->findAllPreloaded(),
            'units' => $d->uR->findAllPreloaded(),
        ];
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if ($formHydrator->populateFromPostAndValidate($form, $request) && is_array($body)) {
                    $this->invitemService->addInvItemProduct($invitem,
                            $body, $inv_id,
                            new IiAddProductDeps($d->pR, $d->trR, new IIAS($d->iiar, $d->iiR), $d->iiar, $this->sR, $d->uR));
                    $this->flashMessage('info',
                        $this->translator->translate('record.successfully.created'));
                    return $this->webService->getRedirectResponse('inv/view',
                        ['id' => $inv_id]);
            }
            $parameters['errors'] =
                $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->render('_item_form_product', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param InvItemAddDeps $d
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function addTask(
        Request $request,
        FormHydrator $formHydrator,
        InvItemAddDeps $d,
    ): \Psr\Http\Message\ResponseInterface {
        $inv_id = (string) $this->session->get('inv_id');
        $invitem = new InvItem();
        $is_recurring = ($d->irR->repoCount((int) $this->session->get('inv_id')) > 0 ? true : false);
        $form = new InvItemForm();
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'invitemhtmx/addTask',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'inv_id' => $inv_id,
            'isRecurring' => $is_recurring,
            'taxRates' => $d->trR->findAllPreloaded(),
            // Only tasks that are complete are put on the invoice
            'tasks' => $d->taskR->repoTaskStatusquery(3),
            'units' => $d->uR->findAllPreloaded(),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            if ($formHydrator->populateFromPostAndValidate($form, $request) && is_array($body)) {
                    $this->invitemService->addInvItemTask($invitem, $body,
                            $inv_id, $d->taskR, $d->trR, new IIAS($d->iiar, $d->iiR), $d->iiar);
                    $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
                    return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->webViewRenderer->renderPartial('_item_form_task', $parameters);
    }

    /**
     * Used with function editProduct
     * @param EntityReader $inv_item_allowances_charges
     * @return float
     */
    public function accumulativeAllowances(EntityReader $inv_item_allowances_charges): float
    {
        $allowances = 0.00;
        /** @var InvItemAllowanceCharge $acii */
        foreach ($inv_item_allowances_charges as $acii) {
            if ($acii->getAllowanceCharge()?->getIdentifier() == '0') {
                $allowances += (float) $acii->getAmount();
            }
        }
        return $allowances;
    }

    /**
     * Used with function editProduct
     * @param EntityReader $inv_item_allowances_charges
     * @return float
     */
    public function accumulativeCharges(EntityReader $inv_item_allowances_charges): float
    {
        $charges = 0.00;
        /** @var InvItemAllowanceCharge $acii */
        foreach ($inv_item_allowances_charges as $acii) {
            if ($acii->getAllowanceCharge()?->getIdentifier() == '1') {
                $charges += (float) $acii->getAmount();
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
     * @param InvItemEditDeps $d
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function editProduct(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        InvItemEditDeps $d,
    ): \Psr\Http\Message\ResponseInterface {
        $inv_id = (int) $this->session->get('inv_id');
        $inv_item = $this->invitem($currentRoute, $d->iiR);
        $is_recurring = ($d->irR->repoCount($inv_id) > 0 ? true : false);
        if (null !== $inv_item) {
            $form = InvItemForm::show($inv_item, $inv_id);
            $inv_item_id = $inv_item->reqId();
            $this->session->set('inv_item_id', $inv_item_id);
            // How many allowances or charges does this specific item have?
            $inv_item_allowances_charges_count = $d->aciiR->repoInvItemcount($inv_item_id);
            $inv_item_allowances_charges = $d->aciiR->repoInvItemquery($inv_item_id);
            $parameters = [
                'title' => $this->translator->translate('product.edit'),
                'actionName' => 'invitem/editProduct',
                'actionArguments' => ['id' => $currentRoute->getArgument('id')],
                'addItemActionName' => 'invitemallowancecharge/add',
                'addItemActionArguments' => ['inv_item_id' => $inv_item_id],
                'indexItemActionName' => 'invitemallowancecharge/index',
                'indexItemActionArguments' => ['inv_item_id' => $inv_item_id],
                'errors' => [],
                'form' => $form,
                'inv_id' => $inv_id,
                'inv_item_id' => $inv_item_id,
                'invItemAllowancesChargesCount' => $inv_item_allowances_charges_count,
                'invItemAllowancesCharges' => $inv_item_allowances_charges,
                'isRecurring' => $is_recurring,
                'taxRates' => $d->trR->findAllPreloaded(),
                'products' => $d->pR->findAllPreloaded(),
                'invs' => $d->iR->findAllPreloaded(),
                'units' => $d->uR->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    // Goal: Use the invitem/item_edit_product form data
                    // to build invitemamount->subtotal=(form[quantity]*form[price])
                    // to build invitemamount->discount=(quantity*form[discount])
                    // Preparation here: Collect the data for this purpose
                    // form[quantity]
                    $quantity = (float) ($body['quantity'] ?? 0.00);
                    // form[price]
                    $price = (float) ($body['price'] ?? 0.00);
                    // form[discount]
                    $discount = (float) ($body['discount_amount'] ?? 0.00);
                    // Goal: Accumulate all charges from invitemallowancecharge
                    // and save in invitemamount->charge
                    $charge = $this->accumulativeCharges($inv_item_allowances_charges) ?: 0.00;
                    // Goal: Accumulate all allowances from invitemallowancecharge
                    // and save in invitemamount->allowance
                    $allowance = $this->accumulativeAllowances($inv_item_allowances_charges) ?: 0.00;
                    if (is_array($body)) {
                        $tax_rate_id = $this->invitemService->saveInvItemProduct($inv_item, $body, (string) $inv_id, $d->pR, $d->uR) ?: 1;
                        $tax_rate_percentage = $this->taxratePercentage($tax_rate_id, $d->trR);
                        if (null !== $tax_rate_percentage) {
                            /**
                             * @psalm-suppress PossiblyNullReference getId
                             */
                            $request_inv_item = $this->invitem($currentRoute, $d->iiR)->reqId();
                            $this->saveInvItemAmount(
                                new InvItemAmountData(
                                    inv_item_id: $request_inv_item,
                                    quantity: $quantity,
                                    price: $price,
                                    discount: $discount,
                                    charge: $charge,
                                    allowance: $allowance,
                                    tax_rate_percentage: $tax_rate_percentage,
                                ),
                                $d->iias,
                                $d->iiaR,
                                $this->sR,
                            );
                            $numberHelper = new NumberHelper($this->sR);
                            $numberHelper->calculateInv($inv_id, $d->aciR, $d->iiR, $d->iiaR, $d->itrR, $d->iaR, $d->iR, $d->pymR);
                            $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                            return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
                        }
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->webViewRenderer->render('_item_edit_product', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param int $id
     * @param TRR $trr
     * @return float|null
     */
    public function taxratePercentage(int $id, TRR $trr): ?float
    {
        $taxrate = $trr->repoTaxRatequery($id);
        if ($taxrate) {
            return $taxrate->getTaxRatePercent();
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
     * @param InvItemAmountData $data
     * @param IIAS $iias
     * @param IIAR $iiar
     * @param SR $s
     */
    public function saveInvItemAmount(InvItemAmountData $data, IIAS $iias, IIAR $iiar, SR $s): void
    {
        $iias_array = [];
        $iias_array['inv_item_id'] = $data->inv_item_id;
        $sub_total = $data->quantity * $data->price;
        $discount_total = ($data->quantity * $data->discount);
        $charge_total = $data->charge;
        $allowance_total = $data->allowance;
        $tax_total = 0.00;
        // NO VAT
        if ($s->getSetting('enable_vat_registration') === '0') {
            $tax_total = (($sub_total - $discount_total + $charge_total - $allowance_total) * ($data->tax_rate_percentage / 100.00));
        }
        // VAT
        if ($s->getSetting('enable_vat_registration') === '1') {
            // EARLY SETTLEMENT CASH DISCOUNT MUST BE REMOVED BEFORE VAT DETERMINED
            // Related logic: see https://informi.co.uk/finance/how-vat-affected-discounts
            $tax_total = (($sub_total - $discount_total + $charge_total) * ($data->tax_rate_percentage / 100.00));
        }
        $iias_array['discount'] = $discount_total;
        $iias_array['charge'] = $charge_total;
        $iias_array['allowance'] = $allowance_total;
        // show the peppol allowances and charges in the sub total
        $iias_array['subtotal'] = $sub_total - $allowance_total + $charge_total;
        $iias_array['taxtotal'] = $tax_total;
        $iias_array['total'] = ($sub_total - $discount_total + $charge_total - $allowance_total + $tax_total);
        if ($iiar->repoCount($data->inv_item_id) === 0) {
            $iias->saveInvItemAmountNoForm(new InvItemAmount(), $iias_array);
        } else {
            $inv_item_amount = $iiar->repoInvItemAmountquery($data->inv_item_id);
            if ($inv_item_amount) {
                $iias->saveInvItemAmountNoForm($inv_item_amount, $iias_array);
            }
        }
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param InvItemEditDeps $d
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function editTask(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        InvItemEditDeps $d,
    ): \Psr\Http\Message\ResponseInterface {
        $inv_id = (int) $this->session->get('inv_id');
        $inv_item = $this->invitem($currentRoute, $d->iiR);
        if ($inv_item) {
            $is_recurring = ($d->irR->repoCount($inv_id) > 0 ? true : false);
            $form = InvItemForm::show($inv_item, $inv_id);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'invitem/editTask',
                'actionArguments' => ['id' => $currentRoute->getArgument('id')],
                'errors' => [],
                // if null inv_item, initialize it => prevent psalm PossiblyNullArgument error
                'form' => $form,
                'inv_id' => $inv_id,
                'isRecurring' => $is_recurring,
                'taxRates' => $d->trR->findAllPreloaded(),
                // Only tasks that are complete are put on the invoice
                'tasks' => $d->taskR->repoTaskStatusquery(3),
                'invs' => $d->iR->findAllPreloaded(),
                'units' => $d->uR->findAllPreloaded(),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $quantity = (float) ($body['quantity'] ?? 0.00);
                    $price = (float) ($body['price'] ?? 0.00);
                    $discount = (float) ($body['discount_amount'] ?? 0.00);
                    // Goal: Accumulate all charges from invitemallowancecharge
                    // and save in invitemamount->charge
                    $inv_item_allowances_charges = $d->aciiR->repoInvItemquery($inv_item->reqId());
                    $charge = $this->accumulativeCharges($inv_item_allowances_charges) ?: 0.00;
                    // Goal: Accumulate all allowances from invitemallowancecharge
                    // and save in invitemamount->allowance
                    $allowance = $this->accumulativeAllowances($inv_item_allowances_charges) ?: 0.00;
                    if (is_array($body)) {
                        $tax_rate_id = $this->invitemService->saveInvItemTask($inv_item, $body, (string) $inv_id, $d->taskR) ?: 1;
                        $tax_rate_percentage = $this->taxratePercentage($tax_rate_id, $d->trR);
                        if (null !== $tax_rate_percentage) {
                            $request_inv_item = $inv_item->reqId();
                            $this->saveInvItemAmount(
                                new InvItemAmountData(
                                    inv_item_id: $request_inv_item,
                                    quantity: $quantity,
                                    price: $price,
                                    discount: $discount,
                                    charge: $charge,
                                    allowance: $allowance,
                                    tax_rate_percentage: $tax_rate_percentage,
                                ),
                                $d->iias,
                                $d->iiaR,
                                $this->sR,
                            );
                            $numberHelper = new NumberHelper($this->sR);
                            $numberHelper->calculateInv($inv_id, $d->aciR, $d->iiR, $d->iiaR, $d->itrR, $d->iaR, $d->iR, $d->pymR);
                            $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                            return $this->webService->getRedirectResponse('inv/view', ['id' => $inv_id]);
                        }
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->webViewRenderer->render('_item_form_task', $parameters);
        } // $inv_item
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currR
     * @param IIR $iiR
     * @return InvItem|null
     */
    private function invitem(CurrentRoute $currR, IIR $iiR): ?InvItem
    {
        return $iiR->repoInvItemquery((int) $currR->getArgument('id'));
    }

    /**
     * @param Request $request
     * @param IIR $iiR
     */
    public function multiple(Request $request, IIR $iiR): \Psr\Http\Message\ResponseInterface
    {
        $select_items = $request->getQueryParams();
        $result = false;
        $item_ids = (array) ($select_items['item_ids'] ?? []);

        // Early return if no items selected
        if (empty($item_ids)) {
            return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => 'No items selected']));
        }

        $items = $iiR->findinInvItems($item_ids);
        // If one item is deleted, the result is positive
        /** @var InvItem $item */
        foreach ($items as $item) {
            $this->invitemService->deleteInvItem($item);
            $result = true;
        }
        return $this->factory->createResponse(Json::encode($result ? ['success' => 1] : ['success' => 0]));
    }
}
