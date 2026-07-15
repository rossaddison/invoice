<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\Inv\InvRepositoryInterface;
use App\Invoice\Inv\HomeCareCleaningEligibilityService;
use App\Invoice\InvItem\InvItemRepositoryInterface;
use App\Invoice\Payment\PaymentRepositoryInterface;
use App\Invoice\Product\ProductRepositoryInterface;
use App\Invoice\Setting\SettingRepositoryInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers every branch of the home-care-cleaning QR auto-invoice eligibility
 * rule: a new invoice is only generated for a client when the feature is
 * enabled in Settings, their last paid invoice's payment date is on file, no
 * invoice at all (paid or not) has been dated since, and that invoice
 * contains at least one Service-type product.
 */
#[Test]
final class HomeCareCleaningEligibilityServiceTest
{
    private function makeService(
        InvRepositoryInterface $invR,
        PaymentRepositoryInterface $paymentR,
        ?InvItemRepositoryInterface $invItemR = null,
        ?ProductRepositoryInterface $productR = null,
        ?SettingRepositoryInterface $settingR = null,
    ): HomeCareCleaningEligibilityService {
        $invItemR = $invItemR ?? m::mock(InvItemRepositoryInterface::class);
        $productR = $productR ?? m::mock(ProductRepositoryInterface::class);
        if ($settingR === null) {
            $settingR = m::mock(SettingRepositoryInterface::class);
            /** @var \Mockery\Expectation $e */
            $e = $settingR->shouldReceive('getSetting');
            $e->once()->with('homecare_auto_invoice_enabled')->andReturn('1');
        }
        return new HomeCareCleaningEligibilityService(
            $invR, $paymentR, $invItemR, $productR, $settingR);
    }

    public function settingDisabledIsNotEligible(): void
    {
        $invR = m::mock(InvRepositoryInterface::class);
        $invR->shouldNotReceive('repoClientInvoiceCountquery');

        $paymentR = m::mock(PaymentRepositoryInterface::class);
        $paymentR->shouldNotReceive('repoLatestPaymentDateForInvquery');

        $invItemR = m::mock(InvItemRepositoryInterface::class);
        $invItemR->shouldNotReceive('repoInvItemIdquery');

        $productR = m::mock(ProductRepositoryInterface::class);
        $productR->shouldNotReceive('repoProductquery');

        $settingR = m::mock(SettingRepositoryInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $settingR->shouldReceive('getSetting');
        $e->once()->with('homecare_auto_invoice_enabled')->andReturn('0');

        $service = $this->makeService($invR, $paymentR, $invItemR, $productR, $settingR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function noInvoicesAtAllIsNotEligible(): void
    {
        $invR = m::mock(InvRepositoryInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e->once()->with(1)->andReturn(0);
        $invR->shouldNotReceive('repoClientLatestPaidInvoicequery');

        $paymentR = m::mock(PaymentRepositoryInterface::class);
        $paymentR->shouldNotReceive('repoLatestPaymentDateForInvquery');

        $service = $this->makeService($invR, $paymentR);

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
        $invR->shouldNotReceive('repoClientInvoiceCountAfterDatequery');

        $paymentR = m::mock(PaymentRepositoryInterface::class);
        $paymentR->shouldNotReceive('repoLatestPaymentDateForInvquery');

        $service = $this->makeService($invR, $paymentR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function paidInvoiceWithNoPaymentRecordIsNotEligible(): void
    {
        $lastPaid = m::mock(Inv::class);
        /** @var \Mockery\Expectation $e */
        $e = $lastPaid->shouldReceive('reqId');
        $e->once()->andReturn(42);

        $invR = m::mock(InvRepositoryInterface::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $invR->shouldReceive('repoClientInvoiceCountquery');
        $e2->once()->with(1)->andReturn(1);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $invR->shouldReceive('repoClientLatestPaidInvoicequery');
        $e3->once()->with(1)->andReturn($lastPaid);
        $invR->shouldNotReceive('repoClientInvoiceCountAfterDatequery');

        $paymentR = m::mock(PaymentRepositoryInterface::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $paymentR->shouldReceive('repoLatestPaymentDateForInvquery');
        $e4->once()->with(42)->andReturn(null);

        $invItemR = m::mock(InvItemRepositoryInterface::class);
        $invItemR->shouldNotReceive('repoInvItemIdquery');

        $service = $this->makeService($invR, $paymentR, $invItemR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function interimInvoiceSinceLastPaidDateIsNotEligible(): void
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
        /** @var \Mockery\Expectation $e4 */
        $e4 = $invR->shouldReceive('repoClientInvoiceCountAfterDatequery');
        $e4->once()->with(1, '2026-06-01')->andReturn(1);

        $paymentR = m::mock(PaymentRepositoryInterface::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $paymentR->shouldReceive('repoLatestPaymentDateForInvquery');
        $e5->once()->with(42)->andReturn('2026-06-01');

        $invItemR = m::mock(InvItemRepositoryInterface::class);
        $invItemR->shouldNotReceive('repoInvItemIdquery');

        $service = $this->makeService($invR, $paymentR, $invItemR);

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
        /** @var \Mockery\Expectation $e4 */
        $e4 = $invR->shouldReceive('repoClientInvoiceCountAfterDatequery');
        $e4->once()->with(1, '2026-06-01')->andReturn(0);

        $paymentR = m::mock(PaymentRepositoryInterface::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $paymentR->shouldReceive('repoLatestPaymentDateForInvquery');
        $e5->once()->with(42)->andReturn('2026-06-01');

        $item = m::mock(InvItem::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $item->shouldReceive('getProductId');
        $e6->once()->andReturn(7);

        $invItemR = m::mock(InvItemRepositoryInterface::class);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e7->once()->with(42)->andReturn([$item]);

        $product = m::mock(Product::class);
        /** @var \Mockery\Expectation $e8 */
        $e8 = $product->shouldReceive('getProductType');
        $e8->once()->andReturn('product');

        $productR = m::mock(ProductRepositoryInterface::class);
        /** @var \Mockery\Expectation $e9 */
        $e9 = $productR->shouldReceive('repoProductquery');
        $e9->once()->with(7)->andReturn($product);

        $service = $this->makeService($invR, $paymentR, $invItemR, $productR);

        Assert::null($service->findInvoiceToCopyIfEligible(1));
    }

    public function paidWithNoInterimInvoiceAndServiceItemIsEligibleAndReturnsLastPaidInvoice(): void
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
        /** @var \Mockery\Expectation $e4 */
        $e4 = $invR->shouldReceive('repoClientInvoiceCountAfterDatequery');
        $e4->once()->with(1, '2026-06-01')->andReturn(0);

        $paymentR = m::mock(PaymentRepositoryInterface::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $paymentR->shouldReceive('repoLatestPaymentDateForInvquery');
        $e5->once()->with(42)->andReturn('2026-06-01');

        $item = m::mock(InvItem::class);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $item->shouldReceive('getProductId');
        $e6->once()->andReturn(7);

        $invItemR = m::mock(InvItemRepositoryInterface::class);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $invItemR->shouldReceive('repoInvItemIdquery');
        $e7->once()->with(42)->andReturn([$item]);

        $product = m::mock(Product::class);
        /** @var \Mockery\Expectation $e8 */
        $e8 = $product->shouldReceive('getProductType');
        $e8->once()->andReturn('service');

        $productR = m::mock(ProductRepositoryInterface::class);
        /** @var \Mockery\Expectation $e9 */
        $e9 = $productR->shouldReceive('repoProductquery');
        $e9->once()->with(7)->andReturn($product);

        $service = $this->makeService($invR, $paymentR, $invItemR, $productR);

        Assert::same($lastPaid, $service->findInvoiceToCopyIfEligible(1));
    }
}
