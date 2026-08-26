<?php

declare(strict_types=1);

namespace App\Invoice\StockMovement;

use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\Helpers\Telegram\TelegramHelper;
use App\Invoice\Setting\SettingRepository;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Alerts staff over Telegram the moment a product's stock crosses at or
 * below its Product::$reorder_threshold — early enough that the reserved
 * buffer (see that property's own docblock) still exists to fulfil orders
 * from while restocking, not just at the point of actually running out.
 *
 * Called from InvPaymentSettlementService::recordStockMovementsForSale(),
 * the single choke point every "stock decreased due to a sale" event
 * already flows through, webshop or staff-invoiced alike.
 */
final readonly class LowStockNotifier
{
    public function __construct(
        private SettingRepository $settingRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * $stockBeforeSale is the product's own stock_quantity read *before*
     * this sale's delta was applied — the caller already has it on hand
     * (it's reading the same Product it's about to mutate), so no extra
     * query is needed here to detect the crossing.
     *
     * Never throws — a Telegram HTTP failure must never roll back a
     * confirmed payment; this runs inside that same DB transaction.
     */
    public function notifyIfCrossed(Product $product, float $stockBeforeSale): void
    {
        if (!$this->crossedThreshold($product, $stockBeforeSale)) {
            return;
        }
        $config = $this->resolveTelegramConfig();
        if ($config === null) {
            return;
        }

        /** @var float $threshold Guaranteed non-null by crossedThreshold() above. */
        $threshold = $product->getReorderThreshold();
        $this->sendAlert($product, $product->getStockQuantity(), $threshold, $config['token'], $config['chatId']);
    }

    /**
     * Only true the first time a sale pushes stock at/below the threshold,
     * not on every subsequent sale while already below it — otherwise
     * every single order once a product is low would page staff again,
     * drowning out the one alert that actually mattered.
     */
    private function crossedThreshold(Product $product, float $stockBeforeSale): bool
    {
        $threshold = $product->getReorderThreshold();
        if (!$product->isTrackStock() || $threshold === null) {
            return false;
        }
        return $stockBeforeSale > $threshold && $product->getStockQuantity() <= $threshold;
    }

    /** @return array{token: string, chatId: string}|null */
    private function resolveTelegramConfig(): ?array
    {
        if ($this->settingRepository->getSetting('enable_telegram') !== '1') {
            return null;
        }
        $token = $this->settingRepository->getSetting('telegram_token');
        $chatId = $this->settingRepository->getSetting('telegram_chat_id');
        if (strlen($token) < 2 || strlen($chatId) < 1) {
            return null;
        }
        return ['token' => $token, 'chatId' => $chatId];
    }

    private function sendAlert(
        Product $product,
        float $stockAfterSale,
        float $threshold,
        string $token,
        string $chatId,
    ): void {
        $productName = $product->getProductName();
        $name = ($productName !== null && $productName !== '') ? $productName : ('Product #' . $product->reqId());
        $text = 'Low stock: ' . $name . ' is down to ' . $stockAfterSale
            . ' (reorder threshold ' . $threshold . '). Time to reorder.';

        try {
            $telegramHelper = new TelegramHelper($token, $this->logger);
            $telegramHelper->sendMessage($chatId, $text);
        } catch (Throwable $e) {
            $this->logger->warning('LowStockNotifier: Telegram sendMessage failed', [
                'productId' => $product->reqId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
