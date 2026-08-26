<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\StockMovement;

use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\Setting\SettingRepository as sR;
use App\Invoice\StockMovement\LowStockNotifier;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Testo\Assert;
use Testo\Test;

/**
 * Covers every guard notifyIfCrossed() checks before it would construct a
 * real TelegramHelper and attempt a network call — see that method's own
 * docblock. The actual "message sent" path is deliberately not exercised
 * here: TelegramHelper is constructed directly inside sendAlert() (same
 * inline-construction convention TelegramController itself already uses
 * throughout this app), and this codebase has no existing precedent for
 * intercepting that real Phptg\BotApi\TelegramBotApi HTTP call in a unit
 * test — TelegramController itself has none either, for the same reason.
 * The plan's own live-verification step (a real paid invoice driving stock
 * across a configured threshold, confirmed against an actual Telegram chat)
 * is what covers that last step instead.
 *
 * @see LowStockNotifier
 */
#[Test]
final class LowStockNotifierTest
{
    private function trackedProduct(float $stockQuantity, ?float $reorderThreshold): Product
    {
        $product = new Product(stock_quantity: $stockQuantity, reorder_threshold: $reorderThreshold);
        $product->setId(9);
        $product->setTrackStock(true);
        return $product;
    }

    private function settingRepositoryNeverConsulted(): sR
    {
        /** @var sR&m\MockInterface $s */
        $s = m::mock(sR::class);
        $s->shouldNotReceive('getSetting');
        return $s;
    }

    private function logger(): LoggerInterface
    {
        /** @var LoggerInterface&m\MockInterface $logger */
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')->byDefault();
        return $logger;
    }

    public function doesNothingWhenStockIsNotTracked(): void
    {
        $product = $this->trackedProduct(2.00, 5.00);
        $product->setTrackStock(false);

        $notifier = new LowStockNotifier($this->settingRepositoryNeverConsulted(), $this->logger());
        // stockBefore (10) > threshold (5) and current stock (2) <= 5 —
        // would otherwise be a real crossing, if only track_stock were true.
        $notifier->notifyIfCrossed($product, 10.00);
    }

    public function doesNothingWhenNoReorderThresholdIsConfigured(): void
    {
        $product = $this->trackedProduct(2.00, null);

        $notifier = new LowStockNotifier($this->settingRepositoryNeverConsulted(), $this->logger());
        $notifier->notifyIfCrossed($product, 10.00);
    }

    public function doesNothingWhenStockWasAlreadyAtOrBelowTheThreshold(): void
    {
        // stockBefore (5) was already at the threshold (5) -- not a fresh
        // crossing, even though current stock (4) is also <= 5. Avoids
        // paging staff again for every sale after the one that mattered.
        $product = $this->trackedProduct(4.00, 5.00);

        $notifier = new LowStockNotifier($this->settingRepositoryNeverConsulted(), $this->logger());
        $notifier->notifyIfCrossed($product, 5.00);
    }

    public function doesNothingWhenTelegramIsNotEnabled(): void
    {
        $product = $this->trackedProduct(4.00, 5.00);

        /** @var sR&m\MockInterface $s */
        $s = m::mock(sR::class);
        $s->shouldReceive('getSetting')->once()->with('enable_telegram')->andReturn('0');
        $s->shouldNotReceive('getSetting')->with('telegram_token');

        $notifier = new LowStockNotifier($s, $this->logger());
        $notifier->notifyIfCrossed($product, 10.00);
    }

    public function doesNothingWhenNoTelegramTokenIsSet(): void
    {
        $product = $this->trackedProduct(4.00, 5.00);

        /** @var sR&m\MockInterface $s */
        $s = m::mock(sR::class);
        $s->shouldReceive('getSetting')->once()->with('enable_telegram')->andReturn('1');
        $s->shouldReceive('getSetting')->once()->with('telegram_token')->andReturn('');
        $s->shouldReceive('getSetting')->once()->with('telegram_chat_id')->andReturn('12345');

        $notifier = new LowStockNotifier($s, $this->logger());
        $notifier->notifyIfCrossed($product, 10.00);
    }

    public function doesNothingWhenNoTelegramChatIdIsSet(): void
    {
        $product = $this->trackedProduct(4.00, 5.00);

        /** @var sR&m\MockInterface $s */
        $s = m::mock(sR::class);
        $s->shouldReceive('getSetting')->once()->with('enable_telegram')->andReturn('1');
        $s->shouldReceive('getSetting')->once()->with('telegram_token')->andReturn('valid-token');
        $s->shouldReceive('getSetting')->once()->with('telegram_chat_id')->andReturn('');

        $notifier = new LowStockNotifier($s, $this->logger());
        $notifier->notifyIfCrossed($product, 10.00);
    }
}
