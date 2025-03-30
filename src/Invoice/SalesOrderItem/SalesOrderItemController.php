<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItem;

use App\Invoice\Entity\SalesOrderItem;
use App\Invoice\Entity\SalesOrderItemAmount;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountService as SOIAS;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository as SOIAR;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
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
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class SalesOrderItemController
{
    public function __construct(
        private readonly SessionInterface $session,
        private ViewRenderer $viewRenderer,
        private readonly WebControllerService $webService,
        private readonly UserService $userService,
        private readonly SalesOrderItemService $salesorderitemService,
        private readonly DataResponseFactoryInterface $factory,
        private readonly Flash $flash,
        private readonly TranslatorInterface $translator
    ) {
        if ($this->userService->hasPermission('viewInv') && !$this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $this->viewRenderer->withControllerName('invoice/salesorderitem')
                                                     ->withLayout('@views/invoice/layout/fullpage-loader.php')
                                                     ->withLayout('@views/layout/guest.php');
        }
        if ($this->userService->hasPermission('viewInv') && $this->userService->hasPermission('editInv')) {
            $this->viewRenderer = $this->viewRenderer->withControllerName('invoice/salesorderitem')
                                                 ->withLayout('@views/invoice/layout/fullpage-loader.php')
                                                 ->withLayout('@views/layout/invoice.php');
        }
    }

    public function edit(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        SOIR $soiR,
        SettingRepository $sR,
        TRR $trR,
        PR $pR,
        UR $uR,
        SOR $qR
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $so_item = $this->salesorderitem($currentRoute, $soiR);
        if ($so_item) {
            $form = new SalesOrderItemForm($so_item);
            $parameters = [
                'title' => $this->translator->translate('invoice.edit'),
                'actionName' => 'salesorderitem/edit',
                'actionArguments' => ['id' => $currentRoute->getArgument('id')],
                'errors' => [],
                'form' => $form,
                'so_id' => $so_item->getSales_order_id(),
                'tax_rates' => $trR->findAllPreloaded(),
                'products' => $pR->findAllPreloaded(),
                'quotes' => $qR->findAllPreloaded(),
                'units' => $uR->findAllPreloaded(),
                'numberHelper' => new NumberHelper($sR),
            ];
            if ($request->getMethod() === Method::POST) {
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $body = $request->getParsedBody() ?? [];
                    if (is_array($body)) {
                        // The only item that is different from the quote is the customer's purchase order number
                        $this->salesorderitemService->savePeppol_po_itemid($so_item, $body);
                        $this->salesorderitemService->savePeppol_po_lineid($so_item, $body);
                        return $this->factory->createResponse($this->viewRenderer->renderPartialAsString(
                            '//invoice/setting/salesorder_successful',
                            [
                                'heading' => $this->translator->translate('invoice.successful'),
                                'message' => $this->translator->translate('i.record_successfully_updated'),
                                'url' => 'salesorder/view',
                                'id' => $so_item->getSales_order_id(),
                            ]
                        ));
                    } // is_array
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_item_edit_form', $parameters);
        } //so_item
        return $this->webService->getNotFoundResponse();
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param SalesOrderItemRepository $salesorderitemRepository
     * @return SalesOrderItem|null
     */
    private function salesorderitem(CurrentRoute $currentRoute, SOIR $salesorderitemRepository): SalesOrderItem|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $salesorderitemRepository->repoSalesOrderItemquery($id);
        }
        return null;
    }

    public function taxrate_percentage(int $id, TRR $trr): float|null
    {
        $taxrate = $trr->repoTaxRatequery((string)$id);
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
     * @param SettingRepository $sR
     */
    public function saveSalesOrderItemAmount(int $so_item_id, float $quantity, float $price, float $discount, float $tax_rate_percentage, SOIAS $soias, SOIAR $soiar, SettingRepository $sR): void
    {
        $soias_array = [];
        if ($so_item_id) {
            $soias_array['so_item_id'] = $so_item_id;
            $sub_total = $quantity * $price;
            $discount_total = ($quantity * $discount);
            $tax_total = 0.00;
            // NO VAT
            if ($sR->getSetting('enable_vat_registration') === '0') {
                $tax_total = ($sub_total * ($tax_rate_percentage / 100.00));
            }
            // VAT
            if ($sR->getSetting('enable_vat_registration') === '1') {
                // EARLY SETTLEMENT CASH DISCOUNT MUST BE REMOVED BEFORE VAT DETERMINED
                // @see https://informi.co.uk/finance/how-vat-affected-discounts
                $tax_total = (($sub_total - $discount_total) * ($tax_rate_percentage / 100.00));
            }
            $soias_array['discount'] = $discount_total;
            $soias_array['subtotal'] = $sub_total;
            $soias_array['taxtotal'] = $tax_total;
            $soias_array['total'] = $sub_total - $discount_total + $tax_total;
            if ($soiar->repoCount((string)$so_item_id) === 0) {
                $soias->saveSalesOrderItemAmountNoForm(new SalesOrderItemAmount(), $soias_array);
            } else {
                $so_item_amount = $soiar->repoSalesOrderItemAmountquery((string)$so_item_id);
                if ($so_item_amount) {
                    $soias->saveSalesOrderItemAmountNoForm($so_item_amount, $soias_array);
                }
            }
        } // $quote_item_id
    }
}
