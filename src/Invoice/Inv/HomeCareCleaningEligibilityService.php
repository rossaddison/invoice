<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Invoice\Client\ClientRepositoryInterface;
use App\Invoice\Enum\ProductType;
use App\Invoice\HomeCareVisit\HomeCareVisitRepositoryInterface;
use App\Invoice\InvItem\InvItemRepositoryInterface;
use App\Invoice\Product\ProductRepositoryInterface;
use App\Invoice\Setting\SettingRepositoryInterface;

final readonly class HomeCareCleaningEligibilityService
{
    /**
     * Invoice status_id meaning "paid" — see the same literal used by
     * InvRepository::repoClientPaidInvoicesquery().
     */
    private const int PAID_STATUS_ID = 4;

    public function __construct(
        private InvRepositoryInterface $invRepository,
        private InvItemRepositoryInterface $invItemRepository,
        private ProductRepositoryInterface $productRepository,
        private SettingRepositoryInterface $settingRepository,
        private HomeCareVisitRepositoryInterface $homeCareVisitRepository,
        private ClientRepositoryInterface $clientRepository,
    ) {
    }

    /**
     * Decides whether a homecare-cleaning QR scan should trigger a new
     * invoice for this client, and if so, which invoice's items/amounts
     * should be copied into the new one.
     *
     * Eligible only when: the feature is enabled (globally and not paused
     * for this specific client), the client has at least one invoice on
     * file, the last invoice *this facility itself generated* (if any) has
     * already been paid, and at least one of the client's paid invoices
     * (searched most-recent-first) contains a Service-type product to copy
     * from.
     *
     * Deliberately does NOT block on "any invoice dated after the last
     * payment" the way an earlier version of this rule did — that heuristic
     * meant any unrelated invoice or credit note an admin raised for other
     * reasons (a one-off product sale, a correction, a refund) silently
     * disabled this client's automation with no indication the two things
     * were connected. Anchoring on this facility's own visit/invoice link
     * instead (via HomeCareVisitRepositoryInterface) decouples it from the
     * rest of the client's invoice history entirely. See
     * docs/HOMECARE_AUTOINVOICE_PITFALLS_AUGUST_2026.md.
     */
    public function findInvoiceToCopyIfEligible(int $clientId): ?Inv
    {
        if (!$this->isFeatureEnabledForClient($clientId) || $this->hasUnpaidGeneratedVisit($clientId)) {
            return null;
        }

        return $this->findMostRecentPaidInvoiceWithServiceItem($clientId);
    }

    /**
     * Searches backwards through the client's paid invoices, most recent
     * first, for the first one containing a Service-type item — rather
     * than strictly requiring the single most-recent paid invoice to have
     * one, which previously left the facility permanently dormant for any
     * client whose latest paid invoice happened to be an unrelated
     * one-off, non-Service sale. See
     * docs/HOMECARE_AUTOINVOICE_PITFALLS_AUGUST_2026.md, pitfall #4.
     */
    private function findMostRecentPaidInvoiceWithServiceItem(int $clientId): ?Inv
    {
        foreach ($this->invRepository->repoClientPaidInvoicesquery($clientId) as $invoice) {
            if ($this->hasServiceItem($invoice->reqId())) {
                return $invoice;
            }
        }
        return null;
    }

    /**
     * True when the feature is switched on globally, not paused for this
     * specific client, and the client has at least one invoice on file.
     */
    private function isFeatureEnabledForClient(int $clientId): bool
    {
        if ($this->settingRepository->getSetting('homecare_auto_invoice_enabled') !== '1') {
            return false;
        }
        if ($this->invRepository->repoClientInvoiceCountquery($clientId) === 0) {
            return false;
        }
        $client = $this->clientRepository->repoClientqueryOrig($clientId);
        return $client === null || !$client->getHomecareAutoInvoicePaused();
    }

    /**
     * True when this facility already generated an invoice for this client
     * and that specific invoice hasn't been paid yet — i.e. the client
     * isn't due for another one regardless of what else has happened on
     * their account since.
     */
    private function hasUnpaidGeneratedVisit(int $clientId): bool
    {
        $invoiceId = $this->homeCareVisitRepository->repoLatestGeneratedVisitquery($clientId)?->getInvoiceId();
        if ($invoiceId === null) {
            return false;
        }
        $invoice = $this->invRepository->repoInvUnLoadedquery($invoiceId);
        return $invoice !== null && $invoice->reqStatusId() !== self::PAID_STATUS_ID;
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
