<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder\Trait;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Invoice\As4\As4OrderResponseException;
use App\Invoice\As4\OrderResponseAdvancedService;
use App\Invoice\SalesOrder\SalesOrderRepository as SoR;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SoIR;
use App\Invoice\Ubl\OrderResponseCode;
use App\Invoice\Ubl\OrderResponseLineStatusCode;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamFactoryInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * The Peppol OrderResponseAdvanced send/preview actions (whole-order and
 * per-line) -- split out of SalesOrderController purely to stay under
 * SonarQube's php:S1448 method-count ceiling (max 20), the same technique
 * ClientRepositoryFilterTrait/InvItemTaskTrait already use. Relies on
 * $this->webService, $this->translator, and $this->flashMessage() all
 * being visible here exactly as they would be on a normal method, since
 * trait methods run in the composing class's own scope (all three come
 * from BaseController, which SalesOrderController extends).
 */
trait SalesOrderOrderResponseTrait
{
    /**
     * Staff picks a Peppol OrderResponseAdvanced code (AB/AP/RE/CA) for a
     * SalesOrder that came in as an inbound Peppol Order and this sends it
     * back to the buyer over AS4. See OrderResponseAdvancedService's own
     * docblock -- this app always plays Seller for Ordering, this is the
     * only document it ever sends for this profile.
     */
    public function sendOrderResponse(
        Request $request,
        SoR $soR,
        OrderResponseAdvancedService $service,
        #[RouteArgument('id')] string $id = '',
    ): Response {
        $so = $soR->repoSalesOrderUnLoadedquery((int) $id);
        if (!$so) {
            return $this->webService->getNotFoundResponse();
        }
        /** @var array<string, mixed> $body */
        $body = $request->getParsedBody();
        /** @var string $codeValue */
        $codeValue = $body['peppol_order_response_code'] ?? '';
        $code = OrderResponseCode::tryFrom($codeValue);
        if ($code === null) {
            $this->flashMessage('danger', $this->translator->translate('salesorder.peppol.response.failed'));
            return $this->webService->getRedirectResponse('salesorder/view', ['id' => $so->reqId()]);
        }

        try {
            $service->send($so, $code);
            $this->flashMessage('success', $this->translator->translate('salesorder.peppol.response.sent'));
        } catch (As4OrderResponseException $e) {
            $this->flashMessage('danger', $e->getMessage());
        }

        return $this->webService->getRedirectResponse('salesorder/view', ['id' => $so->reqId()]);
    }

    /**
     * Renders the OrderResponseAdvanced XML sendOrderResponse() would
     * dispatch, without dispatching it -- lets staff sanity-check the
     * document without needing a working AS4 signing cert or a reachable
     * Peppol peer configured. Opened in a new tab (see the "Preview XML"
     * link in modal_acknowledge_order_response.php), same permission gate
     * as actually sending.
     */
    public function previewOrderResponse(
        Request $request,
        SoR $soR,
        OrderResponseAdvancedService $service,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        #[RouteArgument('id')] string $id = '',
    ): Response {
        $so = $soR->repoSalesOrderUnLoadedquery((int) $id);
        if (!$so) {
            return $this->webService->getNotFoundResponse();
        }
        /** @var string $codeValue */
        $codeValue = $request->getQueryParams()['peppol_order_response_code'] ?? OrderResponseCode::Accepted->value;
        $code = OrderResponseCode::tryFrom($codeValue) ?? OrderResponseCode::Accepted;

        try {
            $xml = $service->previewXml($so, $code);
        } catch (As4OrderResponseException $e) {
            return $responseFactory->createResponse(422)
                ->withHeader('Content-Type', 'text/plain; charset=utf-8')
                ->withBody($streamFactory->createStream($e->getMessage()));
        }

        return $responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/xml; charset=utf-8')
            ->withBody($streamFactory->createStream($xml));
    }

    /**
     * Real per-line response: staff decide each SalesOrderItem
     * independently instead of one code for the whole order. See
     * OrderResponseAdvancedService::sendPerLine()'s own docblock -- the
     * header OrderResponseCode is derived from what staff picked, never
     * chosen directly here.
     */
    public function sendOrderResponsePerLine(
        Request $request,
        SoR $soR,
        SoIR $soiR,
        OrderResponseAdvancedService $service,
        #[RouteArgument('id')] string $id = '',
    ): Response {
        $so = $soR->repoSalesOrderUnLoadedquery((int) $id);
        if (!$so) {
            return $this->webService->getNotFoundResponse();
        }
        /** @var array<array-key, mixed> $body */
        $body = $request->getParsedBody();
        $lineStatusCodes = $this->parseLineStatusCodes($body, $so, $soiR);

        try {
            $service->sendPerLine($so, $lineStatusCodes);
            $this->flashMessage('success', $this->translator->translate('salesorder.peppol.response.sent'));
        } catch (As4OrderResponseException $e) {
            $this->flashMessage('danger', $e->getMessage());
        }

        return $this->webService->getRedirectResponse('salesorder/view', ['id' => $so->reqId()]);
    }

    /**
     * Renders the OrderResponseAdvanced XML sendOrderResponsePerLine()
     * would dispatch, without dispatching it -- same rationale as
     * previewOrderResponse().
     */
    public function previewOrderResponsePerLine(
        Request $request,
        SoR $soR,
        SoIR $soiR,
        OrderResponseAdvancedService $service,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        #[RouteArgument('id')] string $id = '',
    ): Response {
        $so = $soR->repoSalesOrderUnLoadedquery((int) $id);
        if (!$so) {
            return $this->webService->getNotFoundResponse();
        }
        $lineStatusCodes = $this->parseLineStatusCodes($request->getQueryParams(), $so, $soiR);

        try {
            $xml = $service->previewPerLineXml($so, $lineStatusCodes);
        } catch (As4OrderResponseException $e) {
            return $responseFactory->createResponse(422)
                ->withHeader('Content-Type', 'text/plain; charset=utf-8')
                ->withBody($streamFactory->createStream($e->getMessage()));
        }

        return $responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/xml; charset=utf-8')
            ->withBody($streamFactory->createStream($xml));
    }

    /**
     * Parses `line_response_code[<item_id>]` from a request body/query
     * array into `array<int, OrderResponseLineStatusCode>`, keeping only
     * entries whose item id actually belongs to $so -- defends against a
     * tampered form posting another SalesOrder's item ids -- and whose
     * value is a real OrderResponseLineStatusCode. Anything else (missing,
     * unowned, unrecognised) is silently dropped; OrderResponseAdvancedService
     * defaults a missing item to Accepted on its own.
     *
     * @param array<array-key, mixed> $source
     * @return array<int, OrderResponseLineStatusCode>
     */
    private function parseLineStatusCodes(array $source, SalesOrder $so, SoIR $soiR): array
    {
        /** @var mixed $raw */
        $raw = $source['line_response_code'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $validItemIds = [];
        /** @var SalesOrderItem $item */
        foreach ($soiR->repoSalesOrderquery($so->reqId()) as $item) {
            $validItemIds[$item->reqId()] = true;
        }

        $codes = [];
        /** @var mixed $value */
        foreach ($raw as $itemId => $value) {
            $itemId = (int) $itemId;
            if (!isset($validItemIds[$itemId]) || !is_string($value)) {
                continue;
            }
            $code = OrderResponseLineStatusCode::tryFrom($value);
            if ($code !== null) {
                $codes[$itemId] = $code;
            }
        }
        return $codes;
    }
}
