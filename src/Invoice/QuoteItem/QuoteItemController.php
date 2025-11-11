<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use App\Invoice\BaseController;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\QuoteItemAmount;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
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
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class QuoteItemController extends BaseController
{
    protected string $controllerName = 'invoice/quoteitem';

    public function __construct(
        private QuoteItemService $quoteitemService,
        private readonly DataResponseFactoryInterface $factory,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->quoteitemService = $quoteitemService;
    }

    /**
     * Quoteitem/add_product accessed from quote/view renderpartialasstring add_quote_product
     * Triggered by clicking on the save button on the item view appearing above the quote view
     *
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param PR $pR
     * @param UR $uR
     * @param TRR $trR
     * @param QIAR $qiar
     */
    public function add_product(
        Request $request,
        FormHydrator $formHydrator,
        PR $pR,
        UR $uR,
        TRR $trR,
        QIAR $qiar,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        // This function is used
        $quote_id = (string) $this->session->get('quote_id');
        $quoteItem = new QuoteItem();
        $form = new QuoteItemForm($quoteItem, $quote_id);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'quoteitem/add_product',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'quote_id' => $quote_id,
            'taxRates' => $trR->findAllPreloaded(),
            'products' => $pR->findAllPreloaded(),
            'units' => $uR->findAllPreloaded(),
            'numberHelper' => new NumberHelper($this->sR),
        ];
        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? '';
                if (is_array($body)) {
                    $this->quoteitemService->addQuoteItemProduct($quoteItem, $body, $quote_id, $pR, $qiar, new QIAS($qiar), $uR, $trR, $this->translator);
                    $this->flashMessage('success', $this->translator->translate('record.successfully.created'));
                    return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
                }
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_item_form', $parameters);
    }

    /**
     * QuoteItem/add_task accessed from quote/view renderpartialasstring add_quote_item_task
     * Triggered by clicking on the save button on the task item view appearing above the quote view
     *
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param TaskR $taskR
     * @param TRR $trR
     * @param QIAR $qiar
     * @param QIAS $qias
     */
    public function add_task(
        Request $request,
        FormHydrator $formHydrator,
        TaskR $taskR,
        TRR $trR,
        QIAR $qiar,
        QIAS $qias,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $quote_id = (string) $this->session->get('quote_id');
        $quoteItem = new QuoteItem();
        $form = new QuoteItemForm($quoteItem, $quote_id);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'quoteitem/add_task',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'quote_id' => $quote_id,
            'taxRates' => $trR->findAllPreloaded(),
            // Only tasks with complete or status of 3 are made available for selection
            'tasks' => $taskR->repoTaskStatusquery(1),
            'numberHelper' => new NumberHelper($this->sR),
        ];

        if ($request->getMethod() === Method::POST) {
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? '';
                if (is_array($body)) {
                    $this->quoteitemService->addQuoteItemTask($quoteItem, $body, $quote_id, $taskR, $qiar, $qias, $trR, $this->translator);
                    $this->flashMessage('success', $this->translator->translate('record.successfully.created'));
                    return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
                }
            }
            $parameters['form'] = $form;
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $this->viewRenderer->render('_item_form_task', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param QIR $qiR
     * @param TRR $trR
     * @param PR $pR
     * @param UR $uR
     * @param QR $qR
     * @param QIAS $qias
     * @param QIAR $qiar
     */
    public function edit_product(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        QIR $qiR,
        TRR $trR,
        PR $pR,
        UR $uR,
        QR $qR,
        QIAS $qias,
        QIAR $qiar,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $quote_id = (string) $this->session->get('quote_id');
        $quoteItem = $this->quoteitem($currentRoute, $qiR);
        if (null !== $quoteItem) {
            $form = new QuoteItemForm($quoteItem, $quote_id);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'quoteitem/edit_product',
                'actionArguments' => ['id' => $currentRoute->getArgument('id')],
                'errors' => [],
                'form' => $form,
                'quote_id' => $quote_id,
                'taxRates' => $trR->findAllPreloaded(),
                'products' => $pR->findAllPreloaded(),
                'quotes' => $qR->findAllPreloaded(),
                'units' => $uR->findAllPreloaded(),
                'numberHelper' => new NumberHelper($this->sR),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $quantity = (float) ($body['quantity'] ?? 0.00);
                    $price = (float) ($body['price'] ?? 0.00);
                    $discount = (float) ($body['discount_amount'] ?? 0.00);
                    if (is_array($body)) {
                        $tax_rate_id = $this->quoteitemService->saveQuoteItemProduct($quoteItem, $body, $quote_id, $pR, $uR, $this->translator) ?: 1;
                        $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                        if (null !== $tax_rate_percentage) {
                            /**
                             * @psalm-suppress PossiblyNullReference getId
                             */
                            $request_quote_item = (int) $this->quoteitem($currentRoute, $qiR)->getId();
                            $this->saveQuoteItemAmount(
                                $request_quote_item,
                                $quantity,
                                $price,
                                $discount,
                                $tax_rate_percentage,
                                $qias,
                                $qiar,
                            );
                            $this->flashMessage('success', $this->translator->translate('record.successfully.updated'));
                            return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
                        }
                    } // is_array
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_item_edit_form_product', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param QIR $qiR
     * @param TRR $trR
     * @param TASKR $taskR
     * @param QR $qR
     * @param QIAS $qias
     * @param QIAR $qiar
     */
    public function edit_task(
        CurrentRoute $currentRoute,
        Request $request,
        FormHydrator $formHydrator,
        QIR $qiR,
        TRR $trR,
        TASKR $taskR,
        QIAS $qias,
        QIAR $qiar,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $quote_id = (string) $this->session->get('quote_id');
        $quoteItem = $this->quoteitem($currentRoute, $qiR);
        if (null !== $quoteItem) {
            $form = new QuoteItemForm($quoteItem, $quote_id);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'quoteitem/edit_task',
                'actionArguments' => ['id' => $currentRoute->getArgument('id')],
                'errors' => [],
                'form' => $form,
                'quote_id' => $quote_id,
                'taxRates' => $trR->findAllPreloaded(),
                'tasks' => $taskR->repoTaskStatusquery(1),
                'numberHelper' => new NumberHelper($this->sR),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    $quantity = (float) ($body['quantity'] ?? 0.00);
                    $price = (float) ($body['price'] ?? 0.00);
                    $discount = (float) ($body['discount_amount'] ?? 0.00);
                    if (is_array($body)) {
                        $tax_rate_id = $this->quoteitemService->saveQuoteItemTask($quoteItem, $body, $quote_id, $taskR, $this->translator) ?: 1;
                        $tax_rate_percentage = $this->taxrate_percentage($tax_rate_id, $trR);
                        if (null !== $tax_rate_percentage) {
                            /**
                             * @psalm-suppress PossiblyNullReference getId
                             */
                            $request_quote_item = (int) $this->quoteitem($currentRoute, $qiR)->getId();
                            $this->saveQuoteItemAmount(
                                $request_quote_item,
                                $quantity,
                                $price,
                                $discount,
                                $tax_rate_percentage,
                                $qias,
                                $qiar,
                            );
                            $this->flashMessage('success', $this->translator->translate('record.successfully.updated'));
                            return $this->webService->getRedirectResponse('quote/view', ['id' => $quote_id]);
                        }
                    } // is_array
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_item_edit_form_task', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param int $id
     * @param TRR $trr
     * @return float|null
     */
    public function taxrate_percentage(int $id, TRR $trr): float|null
    {
        $taxrate = $trr->repoTaxRatequery((string) $id);
        if ($taxrate) {
            return $taxrate->getTaxRatePercent();
        }
        return null;
    }

    /**
     * @param int $quote_item_id
     * @param float $quantity
     * @param float $price
     * @param float $discount
     * @param float $tax_rate_percentage
     * @param QIAS $qias
     * @param QIAR $qiar
     */
    public function saveQuoteItemAmount(int $quote_item_id, float $quantity, float $price, float $discount, float $tax_rate_percentage, QIAS $qias, QIAR $qiar): void
    {
        $qias_array = [];
        if ($quote_item_id) {
            $qias_array['quote_item_id'] = $quote_item_id;
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
                $tax_total = (($sub_total - $discount_total) * ($tax_rate_percentage / 100.00));
            }
            $qias_array['discount'] = $discount_total;
            $qias_array['subtotal'] = $sub_total;
            $qias_array['taxtotal'] = $tax_total;
            $qias_array['total'] = $sub_total - $discount_total + $tax_total;
            if ($qiar->repoCount((string) $quote_item_id) === 0) {
                $qias->saveQuoteItemAmountNoForm(new QuoteItemAmount(), $qias_array);
            } else {
                $quote_item_amount = $qiar->repoQuoteItemAmountquery((string) $quote_item_id);
                if ($quote_item_amount) {
                    $qias->saveQuoteItemAmountNoForm($quote_item_amount, $qias_array);
                }
            }
        } // $quote_item_id
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     */
    public function delete(CurrentRoute $currentRoute, QIR $qiR): \Yiisoft\DataResponse\DataResponse|Response
    {
        $quote_item = $this->quoteitem($currentRoute, $qiR);
        if ($quote_item) {
            if ($qiR->repoQuoteItemCount($quote_item->getId()) === 1) {
                $this->quoteitemService->deleteQuoteItem($quote_item);
            }
            return $this->viewRenderer->render('quote/index');
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param Request $request
     * @param QIR $qiR
     */
    public function multiple(Request $request, QIR $qiR): \Yiisoft\DataResponse\DataResponse
    {
        //jQuery parameters from quote.js function delete-items-confirm-quote 'item_ids' and 'quote_id'
        $select_items = $request->getQueryParams();
        $result = false;
        /** @var array $item_ids */
        $item_ids = (array) ($select_items['item_ids'] ?? []);

        // Early return if no items selected
        if (empty($item_ids)) {
            return $this->factory->createResponse(Json::encode(['success' => 0, 'message' => 'No items selected']));
        }

        $items = $qiR->findinQuoteItems($item_ids);
        // If one item is deleted, the result is positive
        /** @var QuoteItem $item */
        foreach ($items as $item) {
            $this->quoteitemService->deleteQuoteItem($item);
            $result = true;
        }
        return $this->factory->createResponse(Json::encode($result ? ['success' => 1] : ['success' => 0]));
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     */
    public function view(
        CurrentRoute $currentRoute,
        PR $pR,
        UR $uR,
        TRR $trR,
        QIR $qiR,
    ): \Yiisoft\DataResponse\DataResponse|Response {
        $quoteItem = $this->quoteitem($currentRoute, $qiR);
        if ($quoteItem) {
            $form = new QuoteItemForm($quoteItem, $quoteItem->getQuote_id());
            $parameters = [
                'title' => $this->translator->translate('view'),
                'action' => ['quoteitem/edit', ['id' => $quoteItem->getId()]],
                'errors' => [],
                'form' => $form,
                'tax_rates' => $trR->findAllPreloaded(),
                'products' => $pR->findAllPreloaded(),
                'units' => $uR->findAllPreloaded(),
                'quoteitem' => $qiR->repoQuoteItemquery($quoteItem->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getNotFoundResponse();
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param QIR $qiR
     * @return QuoteItem|null
     */
    private function quoteitem(CurrentRoute $currentRoute, QIR $qiR): QuoteItem|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            $quoteitem = $qiR->repoQuoteItemquery($id);
            if ($quoteitem) {
                return $quoteitem;
            }
        }
        return null;
    }
}
