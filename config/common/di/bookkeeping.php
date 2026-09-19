<?php

declare(strict_types=1);

use App\Bookkeeping\Application\BookkeepingDocumentSourceInterface;
use App\Bookkeeping\Application\BookkeepingGatewayInterface;
use App\Bookkeeping\Application\BookkeepingTransactionRepositoryInterface;
use App\Bookkeeping\Infrastructure\FrontAccounting\FrontAccountingGateway;
use App\Bookkeeping\Infrastructure\Persistence\CycleBookkeepingTransactionRepository;
use App\Bookkeeping\Infrastructure\QuickBooks\QuickBooksGateway;
use App\Invoice\Inv\InvBookkeepingDocumentSource;
use App\Invoice\Setting\SettingRepository;
use Psr\Container\ContainerInterface;

/**
 * Which provider BookkeepingService exports to is chosen by the
 * `bookkeeping_provider` setting ('quickbooks', the default, or
 * 'frontaccounting'), so switching is a settings change, not a code change.
 *
 * No Intuit::class binding lives here deliberately -- yiisoft/config's
 * Merger throws a hard error on a duplicate top-level key across the
 * files that make up one group (confirmed live: defining Intuit::class
 * both here and in config/web/di/yii-auth-client.php broke the whole
 * 'di-web' group with an ErrorException, it does not silently "last one
 * wins"). QuickBooksGateway instead constructs its own Intuit instance
 * directly in its constructor default (same `new HttpClient()`-as-
 * default-param pattern already used by every other gateway class in
 * this app), so it works from console context without needing any
 * shared container binding at all.
 */
return [
    BookkeepingTransactionRepositoryInterface::class => CycleBookkeepingTransactionRepository::class,
    BookkeepingDocumentSourceInterface::class => InvBookkeepingDocumentSource::class,
    BookkeepingGatewayInterface::class => static function (ContainerInterface $container): BookkeepingGatewayInterface {
        /** @var SettingRepository $settings */
        $settings = $container->get(SettingRepository::class);
        /** @var BookkeepingGatewayInterface $gateway */
        $gateway = $container->get(
            $settings->getSetting('bookkeeping_provider') === 'frontaccounting'
                ? FrontAccountingGateway::class
                : QuickBooksGateway::class,
        );

        return $gateway;
    },
];
