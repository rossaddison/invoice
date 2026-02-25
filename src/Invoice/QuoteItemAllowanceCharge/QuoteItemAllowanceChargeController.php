<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAllowanceCharge;

use App\Invoice\BaseController;
use App\Invoice\Entity\QuoteItemAllowanceCharge;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\QuoteAmount\QuoteAmountService;
use App\Invoice\QuoteAmount\QuoteAmountRepository;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository;
use App\Invoice\QuoteItem\QuoteItemRepository;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository;
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
        parent::__construct($webService, $userService, $translator,
            $webViewRenderer, $session, $sR, $flash);
        $this->numberHelper = $numberHelper;
        $this->acqiService = $acqiService;
        $this->quoteAmountService = $quoteAmountService;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $acR
     * @param QuoteItemAllowanceChargeRepository $acqiR
     * @param QuoteItemRepository $qiR
     * @param QuoteAmountRepository $qaR
     * @param QuoteItemAmountRepository $qiaR
     * @param QuoteTaxRateRepository $qtrR
     * @return Response
     */
    public function add(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $acR,
        QuoteItemAllowanceChargeRepository $acqiR,
        QuoteItemRepository $qiR,
        QuoteAmountRepository $qaR,
        QuoteItemAmountRepository $qiaR,
        QuoteTaxRateRepository $qtrR,
    ): Response {
        $quote_item_id = $currentRoute->getArgument('quote_item_id');
        $quote_item = $qiR->repoQuoteItemquery((string) $quote_item_id);
        if ($quote_item) {
            $quote_item_ac = new QuoteItemAllowanceCharge();
            $form = new QuoteItemAllowanceChargeForm($quote_item_ac,
                (int) $quote_item_id);
            $quote_id = $quote_item->getQuote_id();
            $parameters = [
                'title' => $this->translator->translate('add'),
                'actionName' => 'quoteitemallowancecharge/add',
                'actionArguments' => ['quote_item_id' => $quote_item_id],
                'errors' => [],
                'form' => $form,
                'allowance_charges' => $acR->findAllPreloaded(),
                'quote_item_id' => $quote_item_id,
            ];

            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $body['quote_id'] = $quote_id;
                    $body['quote_item_id'] = $quote_item_id;
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
                            $this->acqiService->saveQuoteItemAllowanceCharge(
                                $quote_item_ac, $body, $vat);
                            $all_charges = 0.00;
                            $all_charges_vat = 0.00;
                            $all_allowances = 0.00;
                            $all_allowances_vat = 0.00;
                            $acqis = $acqiR->repoQuoteItemquery(
                                (string) $quote_item_id);
                            $quote_item_amount = $qiaR->repoQuoteItemAmountquery(
                                (string) $quote_item_id);
                            if (null !== $quote_item_amount) {
                                /** @var QuoteItemAllowanceCharge $acqi */
                                foreach ($acqis as $acqi) {
                                    // charge add
                                    $ac = $acqi->getAllowanceCharge();
                                    if (($ac)?->getIdentifier() == '1') {
                                        $all_charges +=
                                            (float) $acqi->getAmount();
                                        $all_charges_vat +=
                                            (float) $acqi->getVatOrTax();
                                    } else {
                                        // allowance subtract
                                        $all_allowances +=
                                            (float) $acqi->getAmount();
                                        $all_allowances_vat +=
                                            (float) $acqi->getVatOrTax();
                                    }
                                }
                                // Record the charges and allowances in the
                                // QuoteItemAmount Entity
                                $quote_item_amount->setCharge($all_charges);
                                $quote_item_amount->setAllowance($all_allowances);
                                $all_vat_or_tax =
                                    $all_charges_vat - $all_allowances_vat;
                                $qi = $quote_item_amount->getQuoteItem();
                                $current_item_quantity = $qi?->getQuantity()
                                    ?? 0.00;
                                $current_item_price = $qi?->getPrice() ?? 0.00;
                                $discount_per_item = $qi?->getDiscount_amount()
                                    ?? 0.00;
                                $quantity_price =
                                    $current_item_quantity * $current_item_price;
                                $current_discount_item_total =
                                    $current_item_quantity * $discount_per_item;
                                $qpIncAc =
                                    $quantity_price + $all_charges -
                                        $all_allowances;
                                $tax_percent =
                                    $qi?->getTaxRate()?->getTaxRatePercent();
                                $current_tax_total = ($quantity_price -
                                    $current_discount_item_total) *
                                        ($tax_percent ?? 0.00) / 100.00;
                                $new_tax_total = $current_tax_total +
                                    $all_vat_or_tax;
                                // include all item allowance charges in the
                                // subtotal
                                $quote_item_amount->setSubtotal($qpIncAc);
                                $quote_item_amount->setDiscount(
                                    $current_discount_item_total);
                                $quote_item_amount->setTax_total($new_tax_total);
                                $overall_total = $qpIncAc -
                                    $current_discount_item_total +
                                        $new_tax_total;
                                $quote_item_amount->setTotal($overall_total);
                                $qiaR->save($quote_item_amount);
                                // update the quote amount
                                $this->quoteAmountService->updateQuoteAmount(
                                    (int) $quote_id, $qaR, $qiaR, $qtrR,
                                        $this->numberHelper);
                            }
                            return $this->webService->getRedirectResponse(
                                'quote/view', ['id' => $quote_id]);
                        }
                        $fvR = $form->getValidationResult();
                        $parameters['errors'] =
                            $fvR->getErrorMessagesIndexedByProperty();
                        $parameters['form'] = $form;
                    } //allowance_charge
                } // is_array
            }   // request
            return $this->webViewRenderer->render('_form', $parameters);
        } // if quote_item
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param Request $request
     * @param QuoteItemAllowanceChargeRepository $qiacR
     * @return Response
     */
    public function index(Request $request,
        QuoteItemAllowanceChargeRepository $qiacR): Response
    {
        $params = $request->getQueryParams();
        /** @var string $params['quote_item_id'] */
        $quote_item_id = $params['quote_item_id'] ?? '';
        $this->flashMessage('info',
            $this->translator->translate(
                'peppol.allowance.or.charge.inherit.quote'));
        // retrieve all the allowances or charges associated with
        // the quote_item_id
        $quote_item_allowances_or_charges =
            $qiacR->repoQuoteItemquery($quote_item_id);
        $paginator = (new OffsetPaginator($quote_item_allowances_or_charges));
        $parameters = [
            'alert' => $this->alert(),
            'quote_item_id' => $quote_item_id,
            'paginator' => $paginator,
        ];
        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param QuoteItemRepository $qiR
     * @param QuoteItemAmountRepository $qiaR
     * @param QuoteAllowanceChargeRepository $acqR
     * @param QuoteItemAllowanceChargeRepository $acqiR
     * @param QuoteAmountRepository $qaR
     * @param QuoteRepository $qR
     * @param QuoteTaxRateRepository $qtrR
     * @param CurrentRoute $currentRoute
     * @return Response
     */
    public function delete(
        QuoteItemRepository $qiR,
        QuoteItemAmountRepository $qiaR,
        QuoteAllowanceChargeRepository $acqR,
        QuoteItemAllowanceChargeRepository $acqiR,
        QuoteAmountRepository $qaR,
        QuoteRepository $qR,
        QuoteTaxRateRepository $qtrR,
        CurrentRoute $currentRoute,
    ): Response {
        $acqi = $this->acqi($currentRoute, $acqiR);
        if (null !== $acqi) {
            $quote_id = $acqi->getQuote_id();
            // delete the quote item allowance/charge and update the related
            // quote item amount record
            $this->acqiService->deleteQuoteItemAllowanceCharge(
                $acqi, $qaR, $qiaR, $qtrR, $acqiR, $this->sR);
            // update the quote amount record
            $this->numberHelper->calculate_quote(
                $quote_id, $acqR, $qiR, $qiaR, $qtrR, $qaR, $qR);
            $this->flashMessage('info', $this->translator->translate(
                'record.successfully.deleted'));
            return $this->webService->getRedirectResponse(
                'quote/view', ['id' => $quote_id]);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param AllowanceChargeRepository $acR
     * @param QuoteItemAllowanceChargeRepository $acqiR
     * @param QuoteItemRepository $qiR
     * @param QuoteAmountRepository $qaR
     * @param QuoteItemAmountRepository $qiaR
     * @param QuoteTaxRateRepository $qtrR
     * @return Response
     */
    public function edit(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        AllowanceChargeRepository $acR,
        QuoteItemAllowanceChargeRepository $acqiR,
        QuoteItemRepository $qiR,
        QuoteAmountRepository $qaR,
        QuoteItemAmountRepository $qiaR,
        QuoteTaxRateRepository $qtrR,
    ): Response {
        $acqi = $this->acqi($currentRoute, $acqiR);
        if ($acqi) {
            $quote_item_id = $acqi->getQuote_item_id();
            $quote_item = $acqi->getQuoteItem();
            $quote_id = $quote_item?->getQuote_id();
            $form = new QuoteItemAllowanceChargeForm($acqi, (int) $quote_item_id);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'quoteitemallowancecharge/edit',
                'actionArguments' => ['id' => $acqi->getId()],
                'errors' => [],
                'form' => $form,
                'allowance_charges' => $acR->findAllPreloaded(),
                'quote_id' => $quote_id,
                'quote_item_id' => $quote_item_id,
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
                                // Add missing IDs to body array for service
                                $body['quote_item_id'] = $quote_item_id;
                                $body['quote_id'] = $quote_id;
                                $this->acqiService->saveQuoteItemAllowanceCharge(
                                    $acqi, $body, $vat);
                                $all_charges = 0.00;
                                $all_allowances = 0.00;
                                $all_allowances_vat = 0.00;
                                $all_charges_vat = 0.00;
                                $acqis = $acqiR->repoQuoteItemquery(
                                    $quote_item_id);
                                $quote_item_amount =
                                    $qiaR->repoQuoteItemAmountquery(
                                        $quote_item_id);
                                if (null !== $quote_item_amount) {
                                    /** @var QuoteItemAllowanceCharge $acqi */
                                    foreach ($acqis as $acqi) {
                                        // charge add
                                        $ac = $acqi->getAllowanceCharge();
                                        if ($ac?->getIdentifier() == '1') {
                                            $all_charges +=
                                                (float) $acqi->getAmount();
                                            $all_charges_vat +=
                                                (float) $acqi->getVatOrTax();
                                        } else {
                                            // allowance subtract
                                            $all_allowances +=
                                                (float) $acqi->getAmount();
                                            $all_allowances_vat +=
                                                (float) $acqi->getVatOrTax();
                                        }
                                    }
                                    // Record the charges and allowances in the
                                    // QuoteItemAmount Entity
                                    $quote_item_amount->setCharge($all_charges);
                                    $quote_item_amount->setAllowance(
                                        $all_allowances);
                                    $all_vat = $all_charges_vat -
                                        $all_allowances_vat;
                                    $qi = $quote_item_amount->getQuoteItem();
                                    $current_item_quantity =
                                        $qi?->getQuantity() ?? 0.00;
                                    $current_item_price =
                                        $qi?->getPrice() ?? 0.00;
                                    $discount_per_item =
                                        $qi?->getDiscount_amount() ?? 0.00;
                                    $quantity_price =
                                        $current_item_quantity *
                                            $current_item_price;
                                    $current_discount_item_total =
                                        $current_item_quantity *
                                            $discount_per_item;
                                    $tax_percent =
                                        $qi?->getTaxRate()?->getTaxRatePercent();
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
                                    $quote_item_amount->setSubtotal($qpIncAc);
                                    $quote_item_amount->setDiscount(
                                        $current_discount_item_total);
                                    $quote_item_amount->setTax_total(
                                        $new_tax_total);
                                    $overall_total = $qpIncAc -
                                        $current_discount_item_total +
                                            $new_tax_total;
                                    $quote_item_amount->setTotal($overall_total);
                                    $qiaR->save($quote_item_amount);
                                    // update the quote amount
                                    $this->quoteAmountService->updateQuoteAmount(
                                        (int) $quote_id, $qaR, $qiaR, $qtrR,
                                            $this->numberHelper);
                                    return $this->webService->getRedirectResponse(
                                        'quote/view', ['id' => $quote_id]);
                                } //null !==$quote_item_amount
                                return $this->webService->getNotFoundResponse();
                            } // $form
                            return $this->webService->getNotFoundResponse();
                        } //allowance_charge
                        return $this->webService->getNotFoundResponse();
                    } // allowance_charge_id
                    $parameters['form'] = $form;
                } // request
                return $this->webViewRenderer->render('_form', $parameters);
            } // is_array
        } // if acii
        return $this->webService->getRedirectResponse('index');
    }

    //For rbac refer to AccessChecker

    /**
     * @param CurrentRoute $currentRoute
     * @param QuoteItemAllowanceChargeRepository $acqiRepository
     * @return QuoteItemAllowanceCharge|null
     */
    private function acqi(CurrentRoute $currentRoute,
        QuoteItemAllowanceChargeRepository $acqiRepository):
            ?QuoteItemAllowanceCharge
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $acqiRepository->repoQuoteItemAllowanceChargequery($id);
        }
        return null;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param QuoteItemAllowanceChargeRepository $acqiRepository
     * @param AllowanceChargeRepository $acR
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function view(
        CurrentRoute $currentRoute,
        QuoteItemAllowanceChargeRepository $acqiRepository,
        AllowanceChargeRepository $acR,
    ): \Psr\Http\Message\ResponseInterface {
        $acqi = $this->acqi($currentRoute, $acqiRepository);
        if ($acqi) {
            $quote_item_id = $acqi->getQuote_item_id();
            $form = new QuoteItemAllowanceChargeForm($acqi, (int) $quote_item_id);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'quoteitemallowancecharge/view',
                'actionArguments' => ['id' => $acqi->getId()],
                'allowance_charges' => $acR->findAllPreloaded(),
                'form' => $form,
                'acqi' => $acqi,
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('acqi/index');
    }
}
