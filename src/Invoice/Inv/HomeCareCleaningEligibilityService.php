<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Invoice\Enum\ProductType;
use App\Invoice\InvItem\InvItemRepositoryInterface;
use App\Invoice\Payment\PaymentRepositoryInterface;
use App\Invoice\Product\ProductRepositoryInterface;
use App\Invoice\Setting\SettingRepositoryInterface;

final readonly class HomeCareCleaningEligibilityService
{
    public function __construct(
        private InvRepositoryInterface $invRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private InvItemRepositoryInterface $invItemRepository,
        private ProductRepositoryInterface $productRepository,
        private SettingRepositoryInterface $settingRepository,
    ) {
    }

    /**
     * Decides whether a homecare-cleaning QR scan should trigger a new
     * invoice for this client, and if so, which invoice's items/amounts
     * should be copied into the new one.
     *
     * Eligible only when: the feature is enabled in Settings, the client has
     * at least one invoice on file, their most recently paid invoice has a
     * payment date on record, no invoice at all (paid or not) exists dated
     * after that payment date, and that invoice contains at least one
     * Service-type product.
     */
    public function findInvoiceToCopyIfEligible(int $clientId): ?Inv
    {
        if ($this->settingRepository->getSetting('homecare_auto_invoice_enabled') !== '1') {
            return null;
        }

        if ($this->invRepository->repoClientInvoiceCountquery($clientId) === 0) {
            return null;
        }

        $lastPaid = $this->invRepository->repoClientLatestPaidInvoicequery($clientId);
        if ($lastPaid === null) {
            return null;
        }

        $lastPaidId = $lastPaid->reqId();

        $lastPaidDate = $this->paymentRepository->repoLatestPaymentDateForInvquery($lastPaidId);
        if ($lastPaidDate === null) {
            return null;
        }

        if ($this->invRepository->repoClientInvoiceCountAfterDatequery($clientId, $lastPaidDate) > 0) {
            return null;
        }

        if (!$this->hasServiceItem($lastPaidId)) {
            return null;
        }

        return $lastPaid;
    }

    /**
     * True when at least one line item on this invoice references a
     * Service-type product (product_type on the Product entity).
     */
    private function hasServiceItem(int $invId): bool
    {
        /** @var InvItem $item */
        foreach ($this->invItemRepository->repoInvItemIdquery($invId) as $item) {
            $productId = $item->getProductId();
            if ($productId === null || $productId <= 0) {
                continue;
            }
            $product = $this->productRepository->repoProductquery($productId);
            if ($product !== null && $product->getProductType() === ProductType::Service->value) {
                return true;
            }
        }
        return false;
    }
}
