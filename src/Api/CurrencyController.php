<?php

declare(strict_types=1);

namespace App\Api;

use App\Invoice\Setting\SettingRepository;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

/**
 * Read-only feed of this shop's Peppol dual-currency setup for external
 * partners (e.g. the headless webshop storefront — see
 * docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md), gated by
 * ApiKeyAuthMiddleware — same convention as ProductsController/
 * OrdersController.
 *
 * Reuses the exact same four `Setting` rows
 * `SettingRepository::currencyConverter()` already converts real invoice
 * amounts with (`currency_code_from`, `peppol_document_currency`,
 * `currency_from_to`, `currency_to_from` — see that method's own
 * docblock, and `config/common/params.php`'s own
 * `peppol.invoice.TaxCurrencyCode` comment for the Peppol BT-110/BT-111
 * background). `native`/`document`
 * are this shop's native (accounting/tax) currency and its currently
 * configured document (invoice) currency respectively — a single
 * admin-configured pair, not an arbitrary currency list; the rates are
 * whatever the admin last entered (manually, via xe.com — no live FX
 * feed exists anywhere in this app), not a live market rate.
 */
final readonly class CurrencyController
{
    public function __construct(private DataResponseFactoryInterface $responseFactory)
    {
    }

    public function index(SettingRepository $settingRepository): ResponseInterface
    {
        return $this->responseFactory->createResponse([
            'native' => $settingRepository->getSetting('currency_code_from'),
            'document' => $settingRepository->getSetting('peppol_document_currency'),
            'native_to_document_rate' => $settingRepository->getSetting('currency_from_to'),
            'document_to_native_rate' => $settingRepository->getSetting('currency_to_from'),
        ]);
    }
}
