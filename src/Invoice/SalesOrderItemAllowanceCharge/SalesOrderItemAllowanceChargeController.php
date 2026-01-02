<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItemAllowanceCharge;

use App\Invoice\BaseController;
use App\Invoice\Entity\SalesOrderItemAllowanceCharge;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrderAmount\SalesOrderAmountService;
use App\Invoice\SalesOrderAmount\SalesOrderAmountRepository;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\SalesOrderAllowanceCharge\SalesOrderAllowanceChargeRepository;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository;
use App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository;
use App\Invoice\SalesOrderTaxRate\SalesOrderTaxRateRepository;
use App\Invoice\Setting\SettingRepository as sR;
use App\User\UserService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class SalesOrderItemAllowanceChargeController extends BaseController
{
    protected string $controllerName = 'invoice/salesorderitemallowancecharge';

    public function __construct(
        private NumberHelper $numberHelper,
        private SalesOrderItemAllowanceChargeService $acsoiService,
        private SalesOrderAmountService $salesorderAmountService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
            $viewRenderer, $session, $sR, $flash);
        $this->numberHelper = $numberHelper;
        $this->acsoiService = $acsoiService;
        $this->salesorderAmountService = $salesorderAmountService;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $acR
     * @param SalesOrderItemAllowanceChargeRepository $acsoiR
     * @param SalesOrderItemRepository $soiR
     * @param SalesOrderAmountRepository $soaR
     * @param SalesOrderItemAmountRepository $soiaR
     * @param SalesOrderTaxRateRepository $sotrR
     * @return Response
     */
    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $acR,
        SalesOrderItemAllowanceChargeRepository $acsoiR,
        SalesOrderItemRepository $soiR,
        SalesOrderAmountRepository $soaR,
        SalesOrderItemAmountRepository $soiaR,
        SalesOrderTaxRateRepository $sotrR,
    ): Response {
        $sales_order_item_id = $currentRoute->getArgument('sales_order_item_id');
        $sales_order_item =
            $soiR->repoSalesOrderItemquery((string) $sales_order_item_id);
        if ($sales_order_item) {
            $sales_order_item_ac = new SalesOrderItemAllowanceCharge();
            $form = new SalesOrderItemAllowanceChargeForm($sales_order_item_ac,
                (int) $sales_order_item_id);
            $sales_order_id = $sales_order_item->getSales_order_id();
            $parameters = [
                'title' => $this->translator->translate('add'),
                'actionName' => 'salesorderitemallowancecharge/add',
                'actionArguments' => ['sales_order_item_id' => $sales_order_item_id],
                'errors' => [],
                'form' => $form,
                'allowance_charges' => $acR->findAllPreloaded(),
                'sales_order_item_id' => $sales_order_item_id,
            ];

            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $body['sales_order_id'] = $sales_order_id;
                    $body['sales_order_item_id'] = $sales_order_item_id;
                    /** @var string $allowance_charge_id */
                    $allowance_charge_id = $body['allowance_charge_id'];
                    $allowance_charge = $acR->repoAllowanceChargequery(
                        $allowance_charge_id);
                    if ($allowance_charge) {
                        $amount = (float) $body['amount'];
                        $percent =
                            $allowance_charge->getTaxRate()?->getTaxRatePercent()
                                ?? 0.00;
                        $vat = $amount * $percent / 100.00;
                        if ($formHydrator->populateFromPostAndValidate(
                                $form, $request)) {
                            $this->acsoiService->saveSalesOrderItemAllowanceCharge(
                                $sales_order_item_ac, $body, $vat);
                            $all_charges = 0.00;
                            $all_charges_vat = 0.00;
                            $all_allowances = 0.00;
                            $all_allowances_vat = 0.00;
                            $acsois = $acsoiR->repoSalesOrderItemquery(
                                (string) $sales_order_item_id);
                            $sales_order_item_amount = $soiaR->repoSalesOrderItemAmountquery(
                                (string) $sales_order_item_id);
                            if (null !== $sales_order_item_amount) {
                                /** @var SalesOrderItemAllowanceCharge $acsoi */
                                foreach ($acsois as $acsoi) {
                                    // charge add
                                    $ac = $acsoi->getAllowanceCharge();
                                    if (($ac)?->getIdentifier() == '1') {
                                        $all_charges +=
                                            (float) $acsoi->getAmount();
                                        $all_charges_vat +=
                                            (float) $acsoi->getVatOrTax();
                                    } else {
                                        // allowance subtract
                                        $all_allowances +=
                                            (float) $acsoi->getAmount();
                                        $all_allowances_vat +=
                                            (float) $acsoi->getVatOrTax();
                                    }
                                }
                                // Record the charges and allowances in the
                                // SalesOrderItemAmount Entity
                                $sales_order_item_amount->setCharge($all_charges);
                                $sales_order_item_amount->setAllowance($all_allowances);
                                $all_vat_or_tax =
                                    $all_charges_vat - $all_allowances_vat;
                                $soi = $sales_order_item_amount->getSalesOrderItem();
                                $current_item_quantity = $soi?->getQuantity()
                                    ?? 0.00;
                                $current_item_price = $soi?->getPrice() ?? 0.00;
                                $discount_per_item = $soi?->getDiscount_amount()
                                    ?? 0.00;
                                $quantity_price =
                                    $current_item_quantity * $current_item_price;
                                $current_discount_item_total =
                                    $current_item_quantity * $discount_per_item;
                                $qpIncAc =
                                    $quantity_price + $all_charges -
                                        $all_allowances;
                                $tax_percent =
                                    $soi?->getTaxRate()?->getTaxRatePercent();
                                $current_tax_total = ($quantity_price -
                                    $current_discount_item_total) *
                                        ($tax_percent ?? 0.00) / 100.00;
                                $new_tax_total = $current_tax_total +
                                    $all_vat_or_tax;
                                // include all item allowance charges in the
                                // subtotal
                                $sales_order_item_amount->setSubtotal($qpIncAc);
                                $sales_order_item_amount->setDiscount(
                                    $current_discount_item_total);
                                $sales_order_item_amount->setTax_total($new_tax_total);
                                $overall_total = $qpIncAc -
                                    $current_discount_item_total +
                                        $new_tax_total;
                                $sales_order_item_amount->setTotal($overall_total);
                                $soiaR->save($sales_order_item_amount);
                                // update the salesorder amount
                                $this->salesorderAmountService->updateSalesOrderAmount(
                                    (int) $sales_order_id, $soaR, $soiaR, $sotrR,
                                        $this->numberHelper);
                            }
                            return $this->webService->getRedirectResponse(
                                'salesorder/view', ['id' => $sales_order_id]);
                        }
                        $fvR = $form->getValidationResult();
                        $parameters['errors'] =
                            $fvR->getErrorMessagesIndexedByProperty();
                        $parameters['form'] = $form;
                    } //allowance_charge
                } // is_array
            }   // request
            return $this->viewRenderer->render('_form', $parameters);
        } // if salesorder_item
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param Request $request
     * @param SalesOrderItemAllowanceChargeRepository $soiacR
     * @return Response
     */
    public function index(Request $request,
        SalesOrderItemAllowanceChargeRepository $soiacR): Response
    {
        $params = $request->getQueryParams();
        /** @var string $params['sales_order_item_id'] */
        $sales_order_item_id = $params['sales_order_item_id'] ?? '';
        $this->flashMessage('info',
            $this->translator->translate(
                'peppol.allowance.or.charge.inherit.quote'));
        // retrieve all the allowances or charges associated with
        // the quote_item_id
        $sales_order_item_allowances_or_charges =
            $soiacR->repoSalesOrderItemquery($sales_order_item_id);
        $paginator = (new OffsetPaginator($sales_order_item_allowances_or_charges));
        $parameters = [
            'alert' => $this->alert(),
            'sales_order_item_id' => $sales_order_item_id,
            'paginator' => $paginator,
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param SalesOrderItemRepository $soiR
     * @param SalesOrderItemAmountRepository $soiaR
     * @param SalesOrderAllowanceChargeRepository $acsoR
     * @param SalesOrderItemAllowanceChargeRepository $acsoiR
     * @param SalesOrderAmountRepository $soaR
     * @param SalesOrderRepository $soR
     * @param SalesOrderTaxRateRepository $sotrR
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function delete(
        SalesOrderItemRepository $soiR,
        SalesOrderItemAmountRepository $soiaR,
        SalesOrderAllowanceChargeRepository $acsoR,
        SalesOrderItemAllowanceChargeRepository $acsoiR,
        SalesOrderAmountRepository $soaR,
        SalesOrderRepository $soR,
        SalesOrderTaxRateRepository $sotrR,
        CurrentRoute $currentRoute,
    ): Response {
        $acsoi = $this->acsoi($currentRoute, $acsoiR);
        if (null!==$acsoi) {
            $sales_order_id = $acsoi->getSales_order_id();
            $this->acsoiService->deleteSalesOrderItemAllowanceCharge(
                $acsoi, $soaR, $soiaR, $sotrR, $acsoiR, $this->sR);
            $this->numberHelper->calculate_so(
                $sales_order_id, $acsoR, $soiR, $soiaR, $sotrR, $soaR, $soR);
            $this->flashMessage('info', $this->translator->translate(
                'record.successfully.deleted'));
            return $this->webService->getRedirectResponse(
                'salesorder/view', ['id' => $sales_order_id]);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $acR
     * @param SalesOrderItemAllowanceChargeRepository $acsoiR
     * @param SalesOrderItemRepository $soiR
     * @param SalesOrderAmountRepository $soaR
     * @param SalesOrderItemAmountRepository $soiaR
     * @param SalesOrderTaxRateRepository $sotrR
     * @return Response
     */
    public function edit(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $acR,
        SalesOrderItemAllowanceChargeRepository $acsoiR,
        SalesOrderItemRepository $soiR,
        SalesOrderAmountRepository $soaR,
        SalesOrderItemAmountRepository $soiaR,
        SalesOrderTaxRateRepository $sotrR,
    ): Response {
        $acsoi = $this->acsoi($currentRoute, $acsoiR);
        if ($acsoi) {
            $sales_order_item_id = $acsoi->getSales_order_item_id();
            $sales_order_item = $acsoi->getSalesOrderItem();
            $sales_order_id = $sales_order_item?->getSales_order_id();
            $form = new SalesOrderItemAllowanceChargeForm(
                $acsoi, (int) $sales_order_item_id);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'salesorderitemallowancecharge/edit',
                'actionArguments' => ['id' => $acsoi->getId()],
                'errors' => [],
                'form' => $form,
                'allowance_charges' => $acR->findAllPreloaded(),
                'sales_order_id' => $sales_order_id,
                'sales_order_item_id' => $sales_order_item_id,
            ];
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                /** @var string $body['allowance_charge_id'] */
                $allowance_charge_id = $body['allowance_charge_id'] ?? '';
                /** @var float $body['amount'] */
                $amount = $body['amount'] ?? 0.00;
                if ($request->getMethod() === Method::POST) {
                    if ($allowance_charge_id) {
                        $allowance_charge = $acR->repoAllowanceChargequery(
                            $allowance_charge_id);
                        if ($allowance_charge) {
                            $percent =
                            $allowance_charge->getTaxRate()?->getTaxRatePercent()
                                ?? 0.00;
                            $vat = $amount * $percent / 100.00;
                            if ($formHydrator->populateFromPostAndValidate(
                                    $form, $request)) {
                                $this->acsoiService->saveSalesOrderItemAllowanceCharge(
                                    $acsoi, $body, $vat);
                                $all_charges = 0.00;
                                $all_allowances = 0.00;
                                $all_allowances_vat = 0.00;
                                $all_charges_vat = 0.00;
                                $acsois = $acsoiR->repoSalesOrderItemquery(
                                    $sales_order_item_id);
                                $sales_order_item_amount =
                                    $soiaR->repoSalesOrderItemAmountquery(
                                        $sales_order_item_id);
                                if (null !== $sales_order_item_amount) {
                                    /** @var SalesOrderItemAllowanceCharge $acsoi */
                                    foreach ($acsois as $acsoi) {
                                        $ac = $acsoi->getAllowanceCharge();
                                        if ($ac?->getIdentifier() == '1') {
                                            $all_charges +=
                                                (float) $acsoi->getAmount();
                                            $all_charges_vat +=
                                                (float) $acsoi->getVatOrTax();
                                        } else {
                                            // allowance subtract
                                            $all_allowances +=
                                                (float) $acsoi->getAmount();
                                            $all_allowances_vat +=
                                                (float) $acsoi->getVatOrTax();
                                        }
                                    }
                                    // Record the charges and allowances in the
                                    // SalesOrderItemAmount Entity
                                    $sales_order_item_amount->setCharge($all_charges);
                                    $sales_order_item_amount->setAllowance(
                                        $all_allowances);
                                    $all_vat = $all_charges_vat -
                                        $all_allowances_vat;
                                    $soi = $sales_order_item_amount->getSalesOrderItem();
                                    $current_item_quantity =
                                        $soi?->getQuantity() ?? 0.00;
                                    $current_item_price =
                                        $soi?->getPrice() ?? 0.00;
                                    $discount_per_item =
                                        $soi?->getDiscount_amount() ?? 0.00;
                                    $quantity_price =
                                        $current_item_quantity *
                                            $current_item_price;
                                    $current_discount_item_total =
                                        $current_item_quantity *
                                            $discount_per_item;
                                    $tax_percent =
                                        $soi?->getTaxRate()?->getTaxRatePercent();
                                    $qpIncAc = $quantity_price + $all_charges
                                        - $all_allowances;
                                    $current_tax_total =
                                        ($qpIncAc - $current_discount_item_total)
                                            * ($tax_percent ?? 0.00) / 100.00;
                                    $new_tax_total = $current_tax_total +
                                        ($this->sR->getSetting(
                                            'enable_vat_registration') == '0' ?
                                            0.00 : $all_vat);
                                    // include all item allowance charges in
                                    // the subtotal
                                    $sales_order_item_amount->setSubtotal($qpIncAc);
                                    $sales_order_item_amount->setDiscount(
                                        $current_discount_item_total);
                                    $sales_order_item_amount->setTax_total(
                                        $new_tax_total);
                                    $overall_total = $qpIncAc -
                                        $current_discount_item_total +
                                            $new_tax_total;
                                    $sales_order_item_amount->setTotal($overall_total);
                                    $soiaR->save($sales_order_item_amount);
                                    // update the salesorder amount
                                    $this->salesorderAmountService->updateSalesOrderAmount(
                                        (int) $sales_order_id, $soaR, $soiaR, $sotrR,
                                            $this->numberHelper);
                                    return $this->webService->getRedirectResponse(
                                        'salesorder/view', ['id' => $sales_order_id]);
                                } //null !==$quote_item_amount
                                return $this->webService->getNotFoundResponse();
                            } // $form
                            return $this->webService->getNotFoundResponse();
                        } //allowance_charge
                        return $this->webService->getNotFoundResponse();
                    } // allowance_charge_id
                    $parameters['form'] = $form;
                } // request
                return $this->viewRenderer->render('_form', $parameters);
            } // is_array
        } // if acii
        return $this->webService->getRedirectResponse('index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param SalesOrderItemAllowanceChargeRepository $acsoiRepository
     * @return SalesOrderItemAllowanceCharge|null
     */
    private function acsoi(CurrentRoute $currentRoute,
        SalesOrderItemAllowanceChargeRepository $acsoiRepository):
            ?SalesOrderItemAllowanceCharge
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $acsoiRepository->repoSalesOrderItemAllowanceChargequery($id);
        }
        return null;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param SalesOrderItemAllowanceChargeRepository $acsoiRepository
     * @param AllowanceChargeRepository $acR
     * @return Response|\Yiisoft\DataResponse\DataResponse
     */
    public function view(
        CurrentRoute $currentRoute,
        SalesOrderItemAllowanceChargeRepository $acsoiRepository,
        AllowanceChargeRepository $acR,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $acsoi = $this->acsoi($currentRoute, $acsoiRepository);
        if ($acsoi) {
            $sales_order_item_id = $acsoi->getSales_order_item_id();
            $form = new SalesOrderItemAllowanceChargeForm($acsoi, (int) $sales_order_item_id);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'salesorderitemallowancecharge/view',
                'actionArguments' => ['id' => $acsoi->getId()],
                'allowance_charges' => $acR->findAllPreloaded(),
                'form' => $form,
                'acsoi' => $acsoi,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('acsoi/index');
    }
}
