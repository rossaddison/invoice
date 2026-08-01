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
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers every branch of the home-care-cleaning QR auto-invoice eligibility
 * rule: a new invoice is only generated for a client when the feature is
 * enabled (globally and not paused for this client), the last invoice this
 * facility itself generated (if any) has been paid, and the client's most
 * recently paid invoice overall contains at least one Service-type product
 * to copy from.
 *
 * Deliberately does NOT check "any invoice dated after the last payment"
 * (an earlier version of this rule did) — that heuristic let an unrelated
 * admin-raised invoice or credit note silently block the automation. See
 * docs/HOMECARE_AUTOINVOICE_PITFALLS_AUGUST_2026.md.
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
        $invItemR = $invItemR ?? m::mock(InvItemRepositoryInterface::class);
        $productR = $productR ?? m::mock(ProductRepositoryInterface::class);
        if ($settingR === null) {
            $settingR = m::mock(SettingRepositoryInterface::class);
            /** @var \Mockery\Expectation $e */
            $e = $settingR->shouldReceive('getSetting');
            $e->once()->with('homecare_auto_invoice_enabled')->andReturn('1');
        }
        if ($visitR === null) {
            $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
            /** @var \Mockery\Expectation $e2 */
            $e2 = $visitR->shouldReceive('repoLatestGeneratedVisitquery');
            $e2->once()->andReturn(null);
        }
        if ($clientR === null) {
            $clientR = m::mock(ClientRepositoryInterface::class);
            /** @var \Mockery\Expectation $e3 */
            $e3 = $clientR->shouldReceive('repoClientqueryOrig');
            $e3->once()->andReturn(null);
        }
        return new HomeCareCleaningEligibilityService(
            $invR, $invItemR, $productR, $settingR, $visitR, $clientR);
    }

    public function settingDisabledIsNotEligible(): void
    {
        $invR = m::mock(InvRepositoryInterface::class);
        $invR->shouldNotReceive('repoClientInvoiceCountquery');

        $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
        $visitR->shouldNotReceive('repoLatestGeneratedVisitquery');

        $clientR = m::mock(ClientRepositoryInterface::class);
        $clientR->shouldNotReceive('repoClientqueryOrig');

        $settingR = m::mock(SettingRepositoryInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $settingR->shouldReceive('getSetting');
        $e->once()->with('homecare_auto_invoice_enabled')->andReturn('0');

        $service = $this->makeService($invR, settingR: $settingR, visitR: $visitR, clientR: $clientR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function noInvoicesAtAllIsNotEligible(): void
    {
        $invR = m::mock(InvRepositoryInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e->once()->with(1)->andReturn(0);
        $invR->shouldNotReceive('repoClientLatestPaidInvoicequery');

        $clientR = m::mock(ClientRepositoryInterface::class);
        $clientR->shouldNotReceive('repoClientqueryOrig');

        $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
        $visitR->shouldNotReceive('repoLatestGeneratedVisitquery');

        $service = $this->makeService($invR, clientR: $clientR, visitR: $visitR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function pausedForThisClientIsNotEligible(): void
    {
        $invR = m::mock(InvRepositoryInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e->once()->with(1)->andReturn(2);
        $invR->shouldNotReceive('repoClientLatestPaidInvoicequery');

        $client = m::mock(Client::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $client->shouldReceive('getHomecareAutoInvoicePaused');
        $e2->once()->andReturn(true);

        $clientR = m::mock(ClientRepositoryInterface::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $clientR->shouldReceive('repoClientqueryOrig');
        $e3->once()->with(1)->andReturn($client);

        $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
        $visitR->shouldNotReceive('repoLatestGeneratedVisitquery');

        $service = $this->makeService($invR, clientR: $clientR, visitR: $visitR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function invoicesExistButNoneEverPaidIsNotEligible(): void
    {
        $invR = m::mock(InvRepositoryInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e->once()->with(1)->andReturn(2);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $invR->shouldReceive('repoClientLatestPaidInvoicequery');
        $e2->once()->with(1)->andReturn(null);

        $service = $this->makeService($invR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function lastGeneratedVisitInvoiceStillUnpaidIsNotEligible(): void
    {
        $invR = m::mock(InvRepositoryInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e->once()->with(1)->andReturn(2);
        $invR->shouldNotReceive('repoClientLatestPaidInvoicequery');

        $unpaidInvoice = m::mock(Inv::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $unpaidInvoice->shouldReceive('reqStatusId');
        $e2->once()->andReturn(2);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $invR->shouldReceive('repoInvUnLoadedquery');
        $e3->once()->with(99)->andReturn($unpaidInvoice);

        $visit = m::mock(HomeCareVisit::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $visit->shouldReceive('getInvoiceId');
        $e4->once()->andReturn(99);

        $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $visitR->shouldReceive('repoLatestGeneratedVisitquery');
        $e5->once()->with(1)->andReturn($visit);

        $service = $this->makeService($invR, visitR: $visitR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function lastPaidInvoiceWithOnlyProductItemsIsNotEligible(): void
    {
        $lastPaid = m::mock(Inv::class);
        /** @var \Mockery\Expectation $e */
        $e = $lastPaid->shouldReceive('reqId');
        $e->once()->andReturn(42);

        $invR = m::mock(InvRepositoryInterface::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e2->once()->with(1)->andReturn(3);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $invR->shouldReceive('repoClientLatestPaidInvoicequery');
        $e3->once()->with(1)->andReturn($lastPaid);

        $item = m::mock(InvItem::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $item->shouldReceive('getProductId');
        $e4->once()->andReturn(7);

        $invItemR = m::mock(InvItemRepositoryInterface::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e5->once()->with(42)->andReturn([$item]);

        $product = m::mock(Product::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $product->shouldReceive('getProductType');
        $e6->once()->andReturn('product');

        $productR = m::mock(ProductRepositoryInterface::class);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $productR->shouldReceive('repoProductquery');
        $e7->once()->with(7)->andReturn($product);

        $service = $this->makeService($invR, $invItemR, $productR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function noPriorGeneratedVisitAndServiceItemIsEligibleAndReturnsLastPaidInvoice(): void
    {
        $lastPaid = m::mock(Inv::class);
        /** @var \Mockery\Expectation $e */
        $e = $lastPaid->shouldReceive('reqId');
        $e->once()->andReturn(42);

        $invR = m::mock(InvRepositoryInterface::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e2->once()->with(1)->andReturn(3);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $invR->shouldReceive('repoClientLatestPaidInvoicequery');
        $e3->once()->with(1)->andReturn($lastPaid);
        $invR->shouldNotReceive('repoInvUnLoadedquery');

        $item = m::mock(InvItem::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $item->shouldReceive('getProductId');
        $e4->once()->andReturn(7);

        $invItemR = m::mock(InvItemRepositoryInterface::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e5->once()->with(42)->andReturn([$item]);

        $product = m::mock(Product::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $product->shouldReceive('getProductType');
        $e6->once()->andReturn('service');

        $productR = m::mock(ProductRepositoryInterface::class);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $productR->shouldReceive('repoProductquery');
        $e7->once()->with(7)->andReturn($product);

        $service = $this->makeService($invR, $invItemR, $productR);

        Assert::same($lastPaid, $service->findInvoiceToCopyIfEligible(1));
    }

    public function lastGeneratedVisitInvoicePaidIsEligibleAndReturnsLastPaidInvoice(): void
    {
        $lastPaid = m::mock(Inv::class);
        /** @var \Mockery\Expectation $e */
        $e = $lastPaid->shouldReceive('reqId');
        $e->once()->andReturn(42);

        $paidInvoice = m::mock(Inv::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $paidInvoice->shouldReceive('reqStatusId');
        $e2->once()->andReturn(4);

        $invR = m::mock(InvRepositoryInterface::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e3->once()->with(1)->andReturn(3);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $invR->shouldReceive('repoClientLatestPaidInvoicequery');
        $e4->once()->with(1)->andReturn($lastPaid);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $invR->shouldReceive('repoInvUnLoadedquery');
        $e5->once()->with(99)->andReturn($paidInvoice);

        $visit = m::mock(HomeCareVisit::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $visit->shouldReceive('getInvoiceId');
        $e6->once()->andReturn(99);

        $visitR = m::mock(HomeCareVisitRepositoryInterface::class);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $visitR->shouldReceive('repoLatestGeneratedVisitquery');
        $e7->once()->with(1)->andReturn($visit);

        $item = m::mock(InvItem::class);
        /** @var \Mockery\Expectation $e8 */
        $e8 = $item->shouldReceive('getProductId');
        $e8->once()->andReturn(7);

        $invItemR = m::mock(InvItemRepositoryInterface::class);
        /** @var \Mockery\Expectation $e9 */
        $e9 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e9->once()->with(42)->andReturn([$item]);

        $product = m::mock(Product::class);
        /** @var \Mockery\Expectation $e10 */
        $e10 = $product->shouldReceive('getProductType');
        $e10->once()->andReturn('service');

        $productR = m::mock(ProductRepositoryInterface::class);
        /** @var \Mockery\Expectation $e11 */
        $e11 = $productR->shouldReceive('repoProductquery');
        $e11->once()->with(7)->andReturn($product);

        $service = $this->makeService($invR, $invItemR, $productR, visitR: $visitR);

        Assert::same($lastPaid, $service->findInvoiceToCopyIfEligible(1));
    }
}
