<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\HomeCareVisit\HomeCareVisit;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\Client\ClientRepositoryInterface;
use App\Invoice\HomeCareVisit\HomeCareVisitRepositoryInterface;
use App\Invoice\Inv\InvRepositoryInterface;
use App\Invoice\Inv\HomeCareCleaningEligibilityService;
use App\Invoice\InvItem\InvItemRepositoryInterface;
use App\Invoice\Product\ProductRepositoryInterface;
use App\Invoice\Setting\SettingRepositoryInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers every branch of the home-care-cleaning QR auto-invoice eligibility
 * rule: a new invoice is only generated for a client when the feature is
 * enabled (globally and not paused for this client), the last invoice this
 * facility itself generated (if any) has been paid, and at least one of the
 * client's paid invoices — searched most-recent-first — contains a
 * Service-type product to copy from.
 *
 * Deliberately does NOT check "any invoice dated after the last payment"
 * (an earlier version of this rule did) — that heuristic let an unrelated
 * admin-raised invoice or credit note silently block the automation. See
 * docs/HOMECARE_AUTOINVOICE_PITFALLS_AUGUST_2026.md.
 *
 * The most-recent-first backward search (rather than checking only the
 * single latest paid invoice) fixes pitfall #4 from that same doc: a client
 * whose latest paid invoice happened to be an unrelated one-off,
 * non-Service sale previously went permanently dormant even though an
 * earlier paid invoice had a perfectly good Service item to copy from.
 */
#[Test]
final class HomeCareCleaningEligibilityServiceTest
{
    private function makeService(
        InvRepositoryInterface $invR,
        ?InvItemRepositoryInterface $invItemR = null,
        ?ProductRepositoryInterface $productR = null,
        ?SettingRepositoryInterface $settingR = null,
        ?HomeCareVisitRepositoryInterface $visitR = null,
        ?ClientRepositoryInterface $clientR = null,
    ): HomeCareCleaningEligibilityService {
        /** @var InvItemRepositoryInterface&m\MockInterface $invItemR */
        $invItemR = $invItemR ?? m::mock(InvItemRepositoryInterface::class);
        /** @var ProductRepositoryInterface&m\MockInterface $productR */
        $productR = $productR ?? m::mock(ProductRepositoryInterface::class);
        if ($settingR === null) {
            /** @var SettingRepositoryInterface&m\MockInterface $settingR */
            $settingR = m::mock(SettingRepositoryInterface::class);
            $e = $settingR->shouldReceive('getSetting');
            $e->once()->with('homecare_auto_invoice_enabled')->andReturn('1');
        }
        if ($visitR === null) {
            /** @var HomeCareVisitRepositoryInterface&m\MockInterface $visitR */
            $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
            $e2 = $visitR->shouldReceive('repoLatestGeneratedVisitquery');
            $e2->once()->andReturn(null);
        }
        if ($clientR === null) {
            /** @var ClientRepositoryInterface&m\MockInterface $clientR */
            $clientR = m::mock(ClientRepositoryInterface::class);
            $e3 = $clientR->shouldReceive('repoClientqueryOrig');
            $e3->once()->andReturn(null);
        }
        return new HomeCareCleaningEligibilityService(
            $invR,
            $invItemR,
            $productR,
            $settingR,
            $visitR,
            $clientR
        );
    }

    /**
     * @return Product&m\MockInterface
     */
    private function productOfType(string $type): Product
    {
        /** @var Product&m\MockInterface $product */
        $product = m::mock(Product::class);
        $e = $product->shouldReceive('getProductType');
        $e->andReturn($type);
        return $product;
    }

    /**
     * @return InvItem&m\MockInterface
     */
    private function itemForProduct(int $productId): InvItem
    {
        /** @var InvItem&m\MockInterface $item */
        $item = m::mock(InvItem::class);
        $e = $item->shouldReceive('getProductId');
        $e->andReturn($productId);
        return $item;
    }

    public function settingDisabledIsNotEligible(): void
    {
        /** @var InvRepositoryInterface&m\MockInterface $invR */
        $invR = m::mock(InvRepositoryInterface::class);
        $invR->shouldNotReceive('repoClientInvoiceCountquery');

        /** @var HomeCareVisitRepositoryInterface&m\MockInterface $visitR */
        $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
        $visitR->shouldNotReceive('repoLatestGeneratedVisitquery');

        /** @var ClientRepositoryInterface&m\MockInterface $clientR */
        $clientR = m::mock(ClientRepositoryInterface::class);
        $clientR->shouldNotReceive('repoClientqueryOrig');

        /** @var SettingRepositoryInterface&m\MockInterface $settingR */
        $settingR = m::mock(SettingRepositoryInterface::class);
        $e = $settingR->shouldReceive('getSetting');
        $e->once()->with('homecare_auto_invoice_enabled')->andReturn('0');

        $service = $this->makeService($invR, settingR: $settingR, visitR: $visitR, clientR: $clientR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function noInvoicesAtAllIsNotEligible(): void
    {
        /** @var InvRepositoryInterface&m\MockInterface $invR */
        $invR = m::mock(InvRepositoryInterface::class);
        $e = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e->once()->with(1)->andReturn(0);
        $invR->shouldNotReceive('repoClientPaidInvoicesquery');

        /** @var ClientRepositoryInterface&m\MockInterface $clientR */
        $clientR = m::mock(ClientRepositoryInterface::class);
        $clientR->shouldNotReceive('repoClientqueryOrig');

        /** @var HomeCareVisitRepositoryInterface&m\MockInterface $visitR */
        $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
        $visitR->shouldNotReceive('repoLatestGeneratedVisitquery');

        $service = $this->makeService($invR, clientR: $clientR, visitR: $visitR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function pausedForThisClientIsNotEligible(): void
    {
        /** @var InvRepositoryInterface&m\MockInterface $invR */
        $invR = m::mock(InvRepositoryInterface::class);
        $e = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e->once()->with(1)->andReturn(2);
        $invR->shouldNotReceive('repoClientPaidInvoicesquery');

        /** @var Client&m\MockInterface $client */
        $client = m::mock(Client::class);
        $e2 = $client->shouldReceive('getHomecareAutoInvoicePaused');
        $e2->once()->andReturn(true);

        /** @var ClientRepositoryInterface&m\MockInterface $clientR */
        $clientR = m::mock(ClientRepositoryInterface::class);
        $e3 = $clientR->shouldReceive('repoClientqueryOrig');
        $e3->once()->with(1)->andReturn($client);

        /** @var HomeCareVisitRepositoryInterface&m\MockInterface $visitR */
        $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
        $visitR->shouldNotReceive('repoLatestGeneratedVisitquery');

        $service = $this->makeService($invR, clientR: $clientR, visitR: $visitR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function invoicesExistButNoneEverPaidIsNotEligible(): void
    {
        /** @var InvRepositoryInterface&m\MockInterface $invR */
        $invR = m::mock(InvRepositoryInterface::class);
        $e = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e->once()->with(1)->andReturn(2);
        $e2 = $invR->shouldReceive('repoClientPaidInvoicesquery');
        $e2->once()->with(1)->andReturn([]);

        $service = $this->makeService($invR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function lastGeneratedVisitInvoiceStillUnpaidIsNotEligible(): void
    {
        /** @var InvRepositoryInterface&m\MockInterface $invR */
        $invR = m::mock(InvRepositoryInterface::class);
        $e = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e->once()->with(1)->andReturn(2);
        $invR->shouldNotReceive('repoClientPaidInvoicesquery');

        /** @var Inv&m\MockInterface $unpaidInvoice */
        $unpaidInvoice = m::mock(Inv::class);
        $e2 = $unpaidInvoice->shouldReceive('reqStatusId');
        $e2->once()->andReturn(2);
        $e3 = $invR->shouldReceive('repoInvUnLoadedquery');
        $e3->once()->with(99)->andReturn($unpaidInvoice);

        /** @var HomeCareVisit&m\MockInterface $visit */
        $visit = m::mock(HomeCareVisit::class);
        $e4 = $visit->shouldReceive('getInvoiceId');
        $e4->once()->andReturn(99);

        /** @var HomeCareVisitRepositoryInterface&m\MockInterface $visitR */
        $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
        $e5 = $visitR->shouldReceive('repoLatestGeneratedVisitquery');
        $e5->once()->with(1)->andReturn($visit);

        $service = $this->makeService($invR, visitR: $visitR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function allPaidInvoicesLackServiceItemIsNotEligible(): void
    {
        /** @var Inv&m\MockInterface $mostRecent */
        $mostRecent = m::mock(Inv::class);
        $e = $mostRecent->shouldReceive('reqId');
        $e->andReturn(42);

        /** @var Inv&m\MockInterface $older */
        $older = m::mock(Inv::class);
        $e2 = $older->shouldReceive('reqId');
        $e2->andReturn(41);

        /** @var InvRepositoryInterface&m\MockInterface $invR */
        $invR = m::mock(InvRepositoryInterface::class);
        $e3 = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e3->once()->with(1)->andReturn(3);
        $e4 = $invR->shouldReceive('repoClientPaidInvoicesquery');
        $e4->once()->with(1)->andReturn([$mostRecent, $older]);

        /** @var InvItemRepositoryInterface&m\MockInterface $invItemR */
        $invItemR = m::mock(InvItemRepositoryInterface::class);
        $e5 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e5->once()->with(42)->andReturn([$this->itemForProduct(7)]);
        $e6 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e6->once()->with(41)->andReturn([$this->itemForProduct(8)]);

        /** @var ProductRepositoryInterface&m\MockInterface $productR */
        $productR = m::mock(ProductRepositoryInterface::class);
        $e7 = $productR->shouldReceive('repoProductquery');
        $e7->once()->with(7)->andReturn($this->productOfType('product'));
        $e8 = $productR->shouldReceive('repoProductquery');
        $e8->once()->with(8)->andReturn($this->productOfType('product'));

        $service = $this->makeService($invR, $invItemR, $productR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function noPriorGeneratedVisitAndServiceItemIsEligibleAndReturnsLastPaidInvoice(): void
    {
        /** @var Inv&m\MockInterface $lastPaid */
        $lastPaid = m::mock(Inv::class);
        $e = $lastPaid->shouldReceive('reqId');
        $e->once()->andReturn(42);

        /** @var InvRepositoryInterface&m\MockInterface $invR */
        $invR = m::mock(InvRepositoryInterface::class);
        $e2 = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e2->once()->with(1)->andReturn(3);
        $e3 = $invR->shouldReceive('repoClientPaidInvoicesquery');
        $e3->once()->with(1)->andReturn([$lastPaid]);
        $invR->shouldNotReceive('repoInvUnLoadedquery');

        /** @var InvItemRepositoryInterface&m\MockInterface $invItemR */
        $invItemR = m::mock(InvItemRepositoryInterface::class);
        $e5 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e5->once()->with(42)->andReturn([$this->itemForProduct(7)]);

        /** @var ProductRepositoryInterface&m\MockInterface $productR */
        $productR = m::mock(ProductRepositoryInterface::class);
        $e7 = $productR->shouldReceive('repoProductquery');
        $e7->once()->with(7)->andReturn($this->productOfType('service'));

        $service = $this->makeService($invR, $invItemR, $productR);

        Assert::same($lastPaid, $service->findInvoiceToCopyIfEligible(1));
    }

    /**
     * Pitfall #4 fix: the most recent paid invoice has no Service item, but
     * an earlier paid one does — the earlier one is found and returned
     * instead of leaving the facility dormant.
     */
    public function searchesBackwardsPastNonServiceInvoiceToFindServiceItem(): void
    {
        /** @var Inv&m\MockInterface $mostRecent */
        $mostRecent = m::mock(Inv::class);
        $e = $mostRecent->shouldReceive('reqId');
        $e->andReturn(42);

        /** @var Inv&m\MockInterface $olderWithService */
        $olderWithService = m::mock(Inv::class);
        $e2 = $olderWithService->shouldReceive('reqId');
        $e2->andReturn(41);

        /** @var InvRepositoryInterface&m\MockInterface $invR */
        $invR = m::mock(InvRepositoryInterface::class);
        $e3 = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e3->once()->with(1)->andReturn(3);
        $e4 = $invR->shouldReceive('repoClientPaidInvoicesquery');
        $e4->once()->with(1)->andReturn([$mostRecent, $olderWithService]);

        /** @var InvItemRepositoryInterface&m\MockInterface $invItemR */
        $invItemR = m::mock(InvItemRepositoryInterface::class);
        $e5 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e5->once()->with(42)->andReturn([$this->itemForProduct(7)]);
        $e6 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e6->once()->with(41)->andReturn([$this->itemForProduct(8)]);

        /** @var ProductRepositoryInterface&m\MockInterface $productR */
        $productR = m::mock(ProductRepositoryInterface::class);
        $e7 = $productR->shouldReceive('repoProductquery');
        $e7->once()->with(7)->andReturn($this->productOfType('product'));
        $e8 = $productR->shouldReceive('repoProductquery');
        $e8->once()->with(8)->andReturn($this->productOfType('service'));

        $service = $this->makeService($invR, $invItemR, $productR);

        Assert::same($olderWithService, $service->findInvoiceToCopyIfEligible(1));
    }

    public function lastGeneratedVisitInvoicePaidIsEligibleAndReturnsLastPaidInvoice(): void
    {
        /** @var Inv&m\MockInterface $lastPaid */
        $lastPaid = m::mock(Inv::class);
        $e = $lastPaid->shouldReceive('reqId');
        $e->once()->andReturn(42);

        /** @var Inv&m\MockInterface $paidInvoice */
        $paidInvoice = m::mock(Inv::class);
        $e2 = $paidInvoice->shouldReceive('reqStatusId');
        $e2->once()->andReturn(4);

        /** @var InvRepositoryInterface&m\MockInterface $invR */
        $invR = m::mock(InvRepositoryInterface::class);
        $e3 = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e3->once()->with(1)->andReturn(3);
        $e4 = $invR->shouldReceive('repoClientPaidInvoicesquery');
        $e4->once()->with(1)->andReturn([$lastPaid]);
        $e5 = $invR->shouldReceive('repoInvUnLoadedquery');
        $e5->once()->with(99)->andReturn($paidInvoice);

        /** @var HomeCareVisit&m\MockInterface $visit */
        $visit = m::mock(HomeCareVisit::class);
        $e6 = $visit->shouldReceive('getInvoiceId');
        $e6->once()->andReturn(99);

        /** @var HomeCareVisitRepositoryInterface&m\MockInterface $visitR */
        $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
        $e7 = $visitR->shouldReceive('repoLatestGeneratedVisitquery');
        $e7->once()->with(1)->andReturn($visit);

        /** @var InvItemRepositoryInterface&m\MockInterface $invItemR */
        $invItemR = m::mock(InvItemRepositoryInterface::class);
        $e9 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e9->once()->with(42)->andReturn([$this->itemForProduct(7)]);

        /** @var ProductRepositoryInterface&m\MockInterface $productR */
        $productR = m::mock(ProductRepositoryInterface::class);
        $e11 = $productR->shouldReceive('repoProductquery');
        $e11->once()->with(7)->andReturn($this->productOfType('service'));

        $service = $this->makeService($invR, $invItemR, $productR, visitR: $visitR);

        Assert::same($lastPaid, $service->findInvoiceToCopyIfEligible(1));
    }
}
