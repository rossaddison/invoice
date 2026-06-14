<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItem;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\QuoteItemAmount\QuoteItemAmountService as QIAS;
use App\Invoice\Setting\SettingRepository as SR;
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
        QuoteItemHtmxDependencies $d,
    ): Response {
        $quote_id = (int) $this->session->get('quote_id');
        $form = new QuoteItemForm();

        if ($request->getMethod() === Method::POST && $request->hasHeader('Hx-Request')) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body) && empty($body['order'])) {
                $body['order'] = (string) $d->qiR->repoQuotequery($quote_id)->count();
                $request = $request->withParsedBody($body);
            }
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->quoteItemService->addQuoteItemProduct(
                        new QuoteItem(), $body, (string) $quote_id,
                        new QiAddProductDeps($d->pR, $d->qiar, new QIAS($d->qiar, $d->qiR), $d->uR, $d->trR, $this->translator),
                    );
                    return $this->renderPartial($quote_id, $d);
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
        QuoteItemHtmxDependencies $d,
    ): Response {
        $quote_id = (int) $this->session->get('quote_id');
        $form = new QuoteItemForm();

        if ($request->getMethod() === Method::POST && $request->hasHeader('Hx-Request')) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body) && empty($body['order'])) {
                $body['order'] = (string) $d->qiR->repoQuotequery($quote_id)->count();
                $request = $request->withParsedBody($body);
            }
            if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    $this->quoteItemService->addQuoteItemTask(
                        new QuoteItem(), $body, $quote_id,
                        $d->taskR, $d->qiar, new QIAS($d->qiar, $d->qiR), $d->trR,
                    );
                    return $this->renderPartial($quote_id, $d);
                }
            }
            return $this->htmlResponseFactory->createResponse('', 422);
        }

        return $this->webService->getRedirectResponse(
            'quote/view', ['id' => (string) $quote_id]
        );
    }

    private function renderPartial(int $quote_id, QuoteItemHtmxDependencies $d): Response
    {
        $numberHelper = new NumberHelper($this->sR);
        $numberHelper->calculateQuote($quote_id, $d->acqR, $d->qiR, $d->qiar, $d->qtrR, $d->qaR, $d->qR);
        $quoteAmount = $d->qaR->repoQuoteAmountquery($quote_id);
        if ($quoteAmount === null) {
            return $this->htmlResponseFactory->createResponse()
                ->withHeader('HX-Refresh', 'true');
        }
        $html = $this->webViewRenderer->renderPartialAsString(
            '//invoice/quote/partial_item_table',
            [
                'acqiR'               => $d->acqiR,
                'packHandleShipTotal' => $d->acqR->getPackHandleShipTotal($quote_id),
                'included'            => $this->translator->translate('item.tax.included'),
                'excluded'            => $this->translator->translate('item.tax.excluded'),
                'invEdit'             => $this->userService->hasPermission(Permissions::EDIT_INV),
                'piR'                 => $d->piR,
                'products'            => $d->pR->findAllPreloaded(),
                'quoteItems'          => $d->qiR->repoQuotequery($quote_id),
                'qiaR'                => $d->qiar,
                'quoteTaxRates'       => $d->qtrR->repoCount($quote_id) > 0
                    ? $d->qtrR->repoQuotequery($quote_id)
                    : null,
                'quoteAmount'         => $quoteAmount,
                'quote'               => $d->qR->repoQuoteLoadedquery($quote_id),
                'taxRates'            => $d->trR->findAllPreloaded(),
                'tasks'               => $d->taskR->findAllPreloaded(),
                'units'               => $d->uR->findAllPreloaded(),
                'numberHelper'        => $numberHelper,
                'dateHelper'          => new DateHelper($this->sR),
            ],
        );
        return $this->htmlResponseFactory->createResponse($html);
    }
}
