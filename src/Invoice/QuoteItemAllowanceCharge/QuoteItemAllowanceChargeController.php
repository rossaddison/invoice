<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAllowanceCharge;

use App\Invoice\BaseController;
use App\Infrastructure\Persistence\QuoteItemAllowanceCharge\QuoteItemAllowanceCharge;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\QuoteAmount\QuoteAmountService;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
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
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class QuoteItemAllowanceChargeController extends BaseController
{
    protected string $controllerName = 'invoice/quoteitemallowancecharge';

    public function __construct(
        private NumberHelper $numberHelper,
        private QuoteItemAllowanceChargeService $acqiService,
        private QuoteAmountService $quoteAmountService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->numberHelper = $numberHelper;
        $this->acqiService = $acqiService;
        $this->quoteAmountService = $quoteAmountService;
    }

    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        QiacAddDeps $deps,
    ): Response {
        $quote_item_id = $currentRoute->getArgument('quote_item_id');
        $quote_item = $deps->qiR->repoQuoteItemquery((int) $quote_item_id);
        if ($quote_item) {
            $quote_item_ac = new QuoteItemAllowanceCharge();
            $form = new QuoteItemAllowanceChargeForm();
            $quote_id = $quote_item->reqQuoteId();
            $parameters = [
                'title' => $this->translator->translate('add'),
                'actionName' => 'quoteitemallowancecharge/add',
                'actionArguments' => ['quote_item_id' => $quote_item_id],
                'errors' => [],
                'form' => $form,
                'allowance_charges' => $deps->acR->findAllPreloaded(),
                'quote_item_id' => $quote_item_id,
            ];

            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $body['quote_id'] = $quote_id;
                    $body['quote_item_id'] = $quote_item_id;
                    $allowance_charge_id = (int) $body['allowance_charge_id'];
                    $allowance_charge = $deps->acR->repoAllowanceChargequery($allowance_charge_id);
                    if ($allowance_charge) {
                        $amount = (float) $body['amount'];
                        $percent = $allowance_charge->getTaxRate()?->getTaxRatePercent() ?? 0.00;
                        $vat = $amount * $percent / 100.00;
                        if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                            $this->acqiService->saveQuoteItemAllowanceCharge($quote_item_ac, $body, $vat);
                            $all_charges = 0.00;
                            $all_charges_vat = 0.00;
                            $all_allowances = 0.00;
                            $all_allowances_vat = 0.00;
                            $acqis = $deps->acqiR->repoQuoteItemquery((int) $quote_item_id);
                            $quote_item_amount = $deps->qiaR->repoQuoteItemAmountquery((int) $quote_item_id);
                            if (null !== $quote_item_amount) {
                                /** @var QuoteItemAllowanceCharge $acqi */
                                foreach ($acqis as $acqi) {
                                    // charge add
                                    $ac = $acqi->getAllowanceCharge();
                                    if (($ac)?->getIdentifier() == '1') {
                                        $all_charges += (float) $acqi->getAmount();
                                        $all_charges_vat += (float) $acqi->getVatOrTax();
                                    } else {
                                        // allowance subtract
                                        $all_allowances += (float) $acqi->getAmount();
                                        $all_allowances_vat += (float) $acqi->getVatOrTax();
                                    }
                                }
                                // Record the charges and allowances in the QuoteItemAmount Entity
                                $quote_item_amount->setCharge($all_charges);
                                $quote_item_amount->setAllowance($all_allowances);
                                $all_vat_or_tax = $all_charges_vat - $all_allowances_vat;
                                $qi = $quote_item_amount->getQuoteItem();
                                $current_item_quantity = $qi?->getQuantity() ?? 0.00;
                                $current_item_price = $qi?->getPrice() ?? 0.00;
                                $discount_per_item = $qi?->getDiscountAmount() ?? 0.00;
                                $quantity_price = $current_item_quantity * $current_item_price;
                                $current_discount_item_total = $current_item_quantity * $discount_per_item;
                                $qpIncAc = $quantity_price + $all_charges - $all_allowances;
                                $tax_percent = $qi?->getTaxRate()?->getTaxRatePercent();
                                $current_tax_total = ($quantity_price - $current_discount_item_total) * ($tax_percent ?? 0.00) / 100.00;
                                $new_tax_total = $current_tax_total + $all_vat_or_tax;
                                // include all item allowance charges in the subtotal
                                $quote_item_amount->setSubtotal($qpIncAc);
                                $quote_item_amount->setDiscount($current_discount_item_total);
                                $quote_item_amount->setTaxTotal($new_tax_total);
                                $overall_total = $qpIncAc - $current_discount_item_total + $new_tax_total;
                                $quote_item_amount->setTotal($overall_total);
                                $deps->qiaR->save($quote_item_amount);
                                // update the quote amount
                                $this->quoteAmountService->updateQuoteAmount($quote_id, $deps->qaR, $deps->qiaR, $deps->qtrR, $this->numberHelper);
                            }
                            return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
                        }
                        $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                        $parameters['form'] = $form;
                    } //allowance_charge
                } // is_array
            }   // request
            return $this->webViewRenderer->render('_form', $parameters);
        } // if quote_item
        return $this->webService->getNotFoundResponse();
    }

    public function index(Request $request, QuoteItemAllowanceChargeRepository $qiacR): Response
    {
        $params = $request->getQueryParams();
        $quote_item_id = (int) ($params['quote_item_id']);
        $this->flashMessage('info', $this->translator->translate('peppol.allowance.or.charge.inherit.quote'));
        // retrieve all the allowances or charges associated with the quote_item_id
        $quote_item_allowances_or_charges = $qiacR->repoQuoteItemquery($quote_item_id);
        $paginator = (new OffsetPaginator($quote_item_allowances_or_charges));
        $parameters = [
            'alert' => $this->alert(),
            'quote_item_id' => $quote_item_id,
            'paginator' => $paginator,
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    public function delete(
        CurrentRoute $currentRoute,
        QiacDeleteSubEntityDeps $subDeps,
        QiacDeleteFinancialDeps $financialDeps,
    ): Response {
        $acqi = $this->acqi($currentRoute, $subDeps->acqiR);
        if (null !== $acqi) {
            $quote_id = $acqi->reqQuoteId();
            // delete the quote item allowance/charge and update the related quote item amount record
            $this->acqiService->deleteQuoteItemAllowanceCharge($acqi, $subDeps->qiaR, $subDeps->acqiR);
            // update the quote amount record
            $this->numberHelper->calculateQuote($quote_id, $subDeps->acqR, $subDeps->qiR, $subDeps->qiaR, $financialDeps->qtrR, $financialDeps->qaR, $financialDeps->qR);
            $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
            return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
        }
        return $this->webService->getNotFoundResponse();
    }

    public function edit(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        QiacEditDeps $deps,
    ): Response {
        $acqi = $this->acqi($currentRoute, $deps->acqiR);
        if (!$acqi) {
            return $this->webService->getRedirectResponse('index');
        }
        $quote_item_id = $acqi->reqQuoteItemId();
        $quote_item = $acqi->getQuoteItem();
        $quote_id = $quote_item?->reqQuoteId();
        $form = QuoteItemAllowanceChargeForm::show($acqi, $quote_item_id);
        $parameters = [
            'title' => $this->translator->translate('edit'),
            'actionName' => 'quoteitemallowancecharge/edit',
            'actionArguments' => ['id' => $acqi->reqId()],
            'errors' => [],
            'form' => $form,
            'allowance_charges' => $deps->acR->findAllPreloaded(),
            'quote_id' => $quote_id,
            'quote_item_id' => $quote_item_id,
        ];
        $body = $request->getParsedBody() ?? [];
        $response = null;
        if (is_array($body)) {
            $allowance_charge_id = (int) ($body['allowance_charge_id'] ?? '');
            $amount = (float) ($body['amount'] ?? 0.00);
            if ($request->getMethod() === Method::POST) {
                if ($allowance_charge_id) {
                    $allowance_charge = $deps->acR->repoAllowanceChargequery($allowance_charge_id);
                    if ($allowance_charge === null || !$formHydrator->populateFromPostAndValidate($form, $request)) {
                        $response = $this->webService->getNotFoundResponse();
                    } else {
                        $percent = $allowance_charge->getTaxRate()?->getTaxRatePercent() ?? 0.00;
                        $vat = $amount * $percent / 100.00;
                        // Add missing IDs to body array for service
                        $body['quote_item_id'] = $quote_item_id;
                        $body['quote_id'] = $quote_id;
                        $this->acqiService->saveQuoteItemAllowanceCharge($acqi, $body, $vat);
                        $all_charges = 0.00;
                        $all_allowances = 0.00;
                        $all_allowances_vat = 0.00;
                        $all_charges_vat = 0.00;
                        $acqis = $deps->acqiR->repoQuoteItemquery($quote_item_id);
                        $quote_item_amount = $deps->qiaR->repoQuoteItemAmountquery($quote_item_id);
                        if (null !== $quote_item_amount) {
                            /** @var QuoteItemAllowanceCharge $acqi */
                            foreach ($acqis as $acqi) {
                                // charge add
                                $ac = $acqi->getAllowanceCharge();
                                if ($ac?->getIdentifier() == '1') {
                                    $all_charges += (float) $acqi->getAmount();
                                    $all_charges_vat += (float) $acqi->getVatOrTax();
                                } else {
                                    // allowance subtract
                                    $all_allowances += (float) $acqi->getAmount();
                                    $all_allowances_vat += (float) $acqi->getVatOrTax();
                                }
                            }
                            // Record the charges and allowances in the QuoteItemAmount Entity
                            $quote_item_amount->setCharge($all_charges);
                            $quote_item_amount->setAllowance($all_allowances);
                            $all_vat = $all_charges_vat - $all_allowances_vat;
                            $qi = $quote_item_amount->getQuoteItem();
                            $current_item_quantity = $qi?->getQuantity() ?? 0.00;
                            $current_item_price = $qi?->getPrice() ?? 0.00;
                            $discount_per_item = $qi?->getDiscountAmount() ?? 0.00;
                            $quantity_price = $current_item_quantity * $current_item_price;
                            $current_discount_item_total = $current_item_quantity * $discount_per_item;
                            $tax_percent = $qi?->getTaxRate()?->getTaxRatePercent();
                            $qpIncAc = $quantity_price + $all_charges - $all_allowances;
                            $current_tax_total = ($qpIncAc - $current_discount_item_total) * ($tax_percent ?? 0.00) / 100.00;
                            $new_tax_total = $current_tax_total + ($this->sR->getSetting('enable_vat_registration') == '0' ? 0.00 : $all_vat);
                            // include all item allowance charges in the subtotal
                            $quote_item_amount->setSubtotal($qpIncAc);
                            $quote_item_amount->setDiscount($current_discount_item_total);
                            $quote_item_amount->setTaxTotal($new_tax_total);
                            $overall_total = $qpIncAc - $current_discount_item_total + $new_tax_total;
                            $quote_item_amount->setTotal($overall_total);
                            $deps->qiaR->save($quote_item_amount);
                            // update the quote amount
                            $this->quoteAmountService->updateQuoteAmount((int) $quote_id, $deps->qaR, $deps->qiaR, $deps->qtrR, $this->numberHelper);
                        }
                        $response = null !== $quote_item_amount
                            ? $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id])
                            : $this->webService->getNotFoundResponse();
                    }
                } else {
                    $parameters['form'] = $form;
                }
            }
        }
        if ($response !== null) {
            return $response;
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    //For rbac refer to AccessChecker

    private function acqi(CurrentRoute $currentRoute, QuoteItemAllowanceChargeRepository $acqiRepository): ?QuoteItemAllowanceCharge
    {
        $id = (int) $currentRoute->getArgument('id');
        return $acqiRepository->repoQuoteItemAllowanceChargequery($id);
    }

    public function view(
        CurrentRoute $currentRoute,
        QuoteItemAllowanceChargeRepository $acqiRepository,
        AllowanceChargeRepository $acR,
    ): \Psr\Http\Message\ResponseInterface {
        $acqi = $this->acqi($currentRoute, $acqiRepository);
        if ($acqi) {
            $quote_item_id = $acqi->reqQuoteItemId();
            $form = QuoteItemAllowanceChargeForm::show($acqi, $quote_item_id);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'quoteitemallowancecharge/view',
                'actionArguments' => ['id' => $acqi->reqId()],
                'allowance_charges' => $acR->findAllPreloaded(),
                'form' => $form,
                'acqi' => $acqi,
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('acqi/index');
    }
}
