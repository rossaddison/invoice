<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Product\ProductRepository as PR;
use App\Invoice\ProductImage\ProductImageRepository as PIR;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Task\TaskRepository as TaskR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;
use App\Invoice\Unit\UnitRepository as UR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class QuoteItemHtmxController extends BaseController
{
    protected string $controllerName = 'invoice/quoteitem';

    public function __construct(
        private readonly QuoteItemService $quoteItemService,
        private readonly HtmlResponseFactory $htmlResponseFactory,
        SessionInterface $session,
        SR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator,
            $webViewRenderer, $session, $sR, $flash);
    }

    public function addProduct(
        Request $request,
        FormHydrator $formHydrator,
        PR $pR,
        UR $uR,
        TRR $trR,
        QIAR $qiar,
        QuoteItemRepository $qiR,
        QR $qR,
        QAR $qaR,
        QTRR $qtrR,
        TaskR $taskR,
        PIR $piR,
        ACQIR $acqiR,
        ACQR $acqR,
    ): Response {
        $quote_id = (int) $this->session->get('quote_id');
        $form = new QuoteItemForm();

        if ($request->getMethod() === Method::POST && $request->hasHeader('Hx-Request')) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body) && empty($body['order'])) {
                $body['order'] = (string) $qiR->repoQuotequery($quote_id)->count();
                $request = $request->withParsedBody($body);
            }
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->quoteItemService->addQuoteItemProduct(
                        new QuoteItem(), $body, (string) $quote_id,
                        $pR, $qiar, new QIAS($qiar, $qiR), $uR, $trR, $this->translator,
                    );
                    return $this->renderPartial(
                        $quote_id, $pR, $uR, $trR, $qiar, $qiR, $qR, $qaR, $qtrR, $taskR, $piR, $acqiR, $acqR,
                    );
                }
            }
            return $this->htmlResponseFactory->createResponse('', 422);
        }

        return $this->webService->getRedirectResponse(
            'quote/view', ['id' => (string) $quote_id]
        );
    }

    public function addTask(
        Request $request,
        FormHydrator $formHydrator,
        PR $pR,
        UR $uR,
        TRR $trR,
        QIAR $qiar,
        QuoteItemRepository $qiR,
        QR $qR,
        QAR $qaR,
        QTRR $qtrR,
        TaskR $taskR,
        PIR $piR,
        ACQIR $acqiR,
        ACQR $acqR,
    ): Response {
        $quote_id = (int) $this->session->get('quote_id');
        $form = new QuoteItemForm();

        if ($request->getMethod() === Method::POST && $request->hasHeader('Hx-Request')) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body) && empty($body['order'])) {
                $body['order'] = (string) $qiR->repoQuotequery($quote_id)->count();
                $request = $request->withParsedBody($body);
            }
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->quoteItemService->addQuoteItemTask(
                        new QuoteItem(), $body, $quote_id,
                        $taskR, $qiar, new QIAS($qiar, $qiR), $trR,
                    );
                    return $this->renderPartial(
                        $quote_id, $pR, $uR, $trR, $qiar, $qiR, $qR, $qaR, $qtrR, $taskR, $piR, $acqiR, $acqR,
                    );
                }
            }
            return $this->htmlResponseFactory->createResponse('', 422);
        }

        return $this->webService->getRedirectResponse(
            'quote/view', ['id' => (string) $quote_id]
        );
    }

    private function renderPartial(
        int $quote_id,
        PR $pR,
        UR $uR,
        TRR $trR,
        QIAR $qiar,
        QuoteItemRepository $qiR,
        QR $qR,
        QAR $qaR,
        QTRR $qtrR,
        TaskR $taskR,
        PIR $piR,
        ACQIR $acqiR,
        ACQR $acqR,
    ): Response {
        $numberHelper = new NumberHelper($this->sR);
        $numberHelper->calculateQuote($quote_id, $acqR, $qiR, $qiar, $qtrR, $qaR, $qR);
        $quoteAmount = $qaR->repoQuoteAmountquery($quote_id);
        if ($quoteAmount === null) {
            return $this->htmlResponseFactory->createResponse()
                ->withHeader('HX-Refresh', 'true');
        }
        $html = $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/partial_item_table',
            [
                'acqiR'               => $acqiR,
                'packHandleShipTotal' => $acqR->getPackHandleShipTotal($quote_id),
                'included'            => $this->translator->translate('item.tax.included'),
                'excluded'            => $this->translator->translate('item.tax.excluded'),
                'invEdit'             => $this->userService->hasPermission(Permissions::EDIT_INV),
                'piR'                 => $piR,
                'products'            => $pR->findAllPreloaded(),
                'quoteItems'          => $qiR->repoQuotequery($quote_id),
                'qiaR'                => $qiar,
                'quoteTaxRates'       => $qtrR->repoCount($quote_id) > 0
                    ? $qtrR->repoQuotequery($quote_id)
                    : null,
                'quoteAmount'         => $quoteAmount,
                'quote'               => $qR->repoQuoteLoadedquery($quote_id),
                'taxRates'            => $trR->findAllPreloaded(),
                'tasks'               => $taskR->findAllPreloaded(),
                'units'               => $uR->findAllPreloaded(),
                'numberHelper'        => $numberHelper,
                'dateHelper'          => new DateHelper($this->sR),
            ],
        );
        return $this->htmlResponseFactory->createResponse($html);
    }
}
