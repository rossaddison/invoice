<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItem;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Entity\SalesOrder;
use App\Invoice\Entity\SalesOrderItem;
use App\Invoice\Entity\SalesOrderItemAmount;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountService as SOIAS;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SOIAR;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use App\Invoice\Unit\UnitRepository as UR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class SalesOrderItemController extends BaseController
{
    protected string $controllerName = 'invoice/salesorderitem';

    public function __construct(
        private readonly SalesOrderItemService $salesorderitemService,
        private readonly DataResponseFactoryInterface $factory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer,
                $session, $sR, $flash);
    }

    // The observer user will edit the salesorder line items by entering their
    // specific purchase order line items
    public function edit(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        SOIR $soiR,
        TRR $trR,
        PR $pR,
        TaskR $taskR,
        UCR $ucR,
        UIR $uiR,    
        UR $uR,
        SOR $qR,
    ): \Psr\Http\Message\ResponseInterface {
        $so_item = $this->salesorderitem($currentRoute, $soiR);
        if ($so_item) {
            $so = $so_item->getSales_order();
            if (null!== $so && ($this->rbacObserver($so, $ucR, $uiR)
             || $this->rbacAccountant()
             || $this->rbacAdmin())) {
                $so_id = $so_item->getSales_order_id();
                $form = new SalesOrderItemForm($so_item, $so_id);
                $parameters = [
                    'title' => $this->translator->translate('edit'),
                    'actionName' => 'salesorderitem/edit',
                    'actionArguments' => ['id' => $currentRoute->getArgument('id')],
                    'errors' => [],
                    'form' => $form,
                    'so_id' => $so_id,
                    'tax_rates' => $trR->findAllPreloaded(),
                    'products' => $pR->findAllPreloaded(),
                    'tasks' => $taskR->findAllPreloaded(),
                    'quotes' => $qR->findAllPreloaded(),
                    'units' => $uR->findAllPreloaded(),
                    'numberHelper' => new NumberHelper($this->sR),
                ];
                if ($request->getMethod() === Method::POST) {
                    if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                        $body = $request->getParsedBody() ?? [];
                        if (is_array($body)) {
    // The only item that is different from the quote is the customer's purchase
    // order number
                            $this->salesorderitemService->savePeppol_po_itemid(
                                                                    $so_item, $body);
                            $this->salesorderitemService->savePeppol_po_lineid(
                                                                    $so_item, $body);
                            return $this->factory->createResponse(
                                        $this->webViewRenderer->renderPartialAsString(
                                '//invoice/setting/salesorder_successful',
                                [
                                    'heading' => $this->translator->translate(
                                                                        'successful'),
                                    'message' => $this->translator->translate(
                                                        'record.successfully.updated'),
                                    'url' => 'salesorder/view',
                                    'id' => $so_item->getSales_order_id(),
                                ],
                            ));
                        } // is_array
                    }
                    $parameters['errors'] =
                            $form->getValidationResult()
                                 ->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
                return $this->webViewRenderer->render('_item_edit_form', $parameters);
            } // rbac 
        } //so_item
        return $this->webService->getNotFoundResponse();
    }
    
    /**
     * Purpose:
     * Prevent browser manipulation and ensure that views are only accessible
     * to users 1. with the observer role's VIEW_INV permission and 2. supervise a
     * client requested salesorder and are an active current user for these
     * client's salesorders.
     * @param SalesOrder $so
     * @param UCR $ucR
     * @param UIR $uiR
     * @return bool
     */
    private function rbacObserver(SalesOrder $so, UCR $ucR, UIR $uiR) : bool {
        $statusId = $so->getStatus_id();
        if (null!==$statusId) {
            // has observer role
            if ($this->userService->hasPermission(Permissions::VIEW_INV)
                && !($this->userService->hasPermission(Permissions::EDIT_INV))
                // the salesorder has passed the 'draft' stage i.e sent / appears
                // in the observer user's guest index
                && !($statusId === 1)
                // the salesorder is intended for the current user
                && ($so->getUser_id() === $this->userService->getUser()?->getId())
                // the salesorder client is associated with the above user
                && ($ucR->repoUserClientqueryCount($so->getUser_id(),
                                                $so->getClient_id()) > 0)) {
                $userInv = $uiR->repoUserInvUserIdquery((string) $statusId);
                // the current observer user is active
                if (null !== $userInv && $userInv->getActive()) {
                    return true;
                }
            }
        }    
        return false;
    }
    
    private function rbacAccountant() : bool {
        // has accountant role
        if (($this->userService->hasPermission(Permissions::VIEW_INV)
            && ($this->userService->hasPermission(Permissions::VIEW_PAYMENT))
            && ($this->userService->hasPermission(Permissions::EDIT_PAYMENT)))) {
            return true;
        } else {
            return false;
        }
    }
    
    private function rbacAdmin() : bool {
        // has observer role
        if ($this->userService->hasPermission(Permissions::VIEW_INV)
            && ($this->userService->hasPermission(Permissions::EDIT_INV))) {
            return true;
        } else {
            return false;
        }
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param SalesOrderItemRepository $salesorderitemRepository
     * @return SalesOrderItem|null
     */
    private function salesorderitem(CurrentRoute $currentRoute,
                                SOIR $salesorderitemRepository): ?SalesOrderItem
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $salesorderitemRepository->repoSalesOrderItemquery($id);
        }
        return null;
    }

    public function taxrate_percentage(int $id, TRR $trr): ?float
    {
        $taxrate = $trr->repoTaxRatequery((string) $id);
        if ($taxrate) {
            return $taxrate->getTaxRatePercent();
        }
        return null;
    }

    /**
     * @param int $so_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float $tax_rate_percentage
     * @param SOIAS $soias
     * @param SOIAR $soiar
     */
    public function saveSalesOrderItemAmount(int $so_item_id, float $quantity,
            float $price, float $discount, float $tax_rate_percentage,
                                                SOIAS $soias, SOIAR $soiar): void
    {
        $soias_array = [];
        if ($so_item_id) {
            $soias_array['so_item_id'] = $so_item_id;
            $sub_total = $quantity * $price;
            $discount_total = ($quantity * $discount);
            $tax_total = 0.00;
            // NO VAT
            if ($this->sR->getSetting('enable_vat_registration') === '0') {
                $tax_total = ($sub_total * ($tax_rate_percentage / 100.00));
            }
            // VAT
            if ($this->sR->getSetting('enable_vat_registration') === '1') {
// EARLY SETTLEMENT CASH DISCOUNT MUST BE REMOVED BEFORE VAT DETERMINED
// Related logic: see https://informi.co.uk/finance/how-vat-affected-discounts
                $tax_total = (($sub_total - $discount_total)
                        * ($tax_rate_percentage / 100.00));
            }
            $soias_array['discount'] = $discount_total;
            $soias_array['subtotal'] = $sub_total;
            $soias_array['taxtotal'] = $tax_total;
            $soias_array['total'] = $sub_total - $discount_total + $tax_total;
            if ($soiar->repoCount((string) $so_item_id) === 0) {
                $soias->saveSalesOrderItemAmountNoForm(
                                        new SalesOrderItemAmount(), $soias_array);
            } else {
                $so_item_amount = $soiar->repoSalesOrderItemAmountquery(
                                                            (string) $so_item_id);
                if ($so_item_amount) {
                    $soias->saveSalesOrderItemAmountNoForm(
                                                    $so_item_amount, $soias_array);
                }
            }
        } // $quote_item_id
    }
}
