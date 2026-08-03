<?php

declare(strict_types=1);

use App\Command\CacheClearCommand;
use App\Command\InstallCommand;
use App\Command\Invoice\AutoIncrementSetToOneAfterTruncate6Command;
use App\Command\Invoice\GeneratorTruncateCommand;
use App\Command\Invoice\InvTruncate1Command;
use App\Command\Invoice\ItemsCommand;
use App\Command\Invoice\NonUserRelatedTruncate4Command;
use App\Command\Invoice\QuoteTruncate2Command;
use App\Command\Invoice\RenderInvoicePdfCommand;
use App\Command\Invoice\SettingTruncateCommand;
use App\Command\Invoice\SalesOrderTruncate3Command;
use App\Command\Invoice\UserRelatedTruncate5Command;
use App\Command\Router\ListCommand;
use App\Command\Translation\TranslateCommand;
use App\Invoice\As4\Console\As4MonitorCommand;
use App\Invoice\As4\Console\As4ResendCommand;
use App\Invoice\As4\Console\As4RetryCommand;
use App\Invoice\As4\Console\As4StatusCommand;
use App\Invoice\As4\Console\As4TestSendCommand;
use App\Invoice\InvRecurring\Console\ProcessRecurringInvoicesCommand;
use App\Invoice\Peppol\Console\RetryFailedCommand;
use App\Invoice\Setting\Console\BackupDatabaseCommand;
use App\Invoice\System\Console\CheckPhpVersionCommand;
use App\User\Console\CreateCommand;
use App\User\Console\AssignRoleCommand;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Console\Command\Serve;

return [
    'yiisoft/yii-console' => [
        'name' => Application::NAME,
        'version' => Application::VERSION,
        'autoExit' => false,
        'commands' => [
            'cache/clear' => CacheClearCommand::class,
            'serve' => Serve::class,
            'install' => InstallCommand::class,
            'as4/retry'           => As4RetryCommand::class,
            'as4/status'          => As4StatusCommand::class,
            'as4/resend'          => As4ResendCommand::class,
            'as4/monitor'         => As4MonitorCommand::class,
            'as4/test-send'       => As4TestSendCommand::class,
            'peppol/retry-failed' => RetryFailedCommand::class,
            'system/check-php-version' => CheckPhpVersionCommand::class,
            'invrecurring/process' => ProcessRecurringInvoicesCommand::class,
            'setting/backup-database' => BackupDatabaseCommand::class,
            'user/create' => CreateCommand::class,
            'user/assignRole' => AssignRoleCommand::class,
            'router/list' => ListCommand::class,
            'translator/translate' => TranslateCommand::class,
            /**
             * Build a randomly generated items list for an invoice with a summary table
             * using two specific item taxes for the item list and
             * using two specific invoice taxes generated in the summary table
             */
            'invoice/items' => ItemsCommand::class,
            'invoice/render-pdf' => RenderInvoicePdfCommand::class,
            'invoice/setting/truncate' => SettingTruncateCommand::class,
            'invoice/generator/truncate' => GeneratorTruncateCommand::class,
            'invoice/inv/truncate1' => InvTruncate1Command::class,
            'invoice/quote/truncate2' => QuoteTruncate2Command::class,
            'invoice/salesorder/truncate3' => SalesOrderTruncate3Command::class,
            'invoice/nonuserrelated/truncate4' => NonUserRelatedTruncate4Command::class,
            'invoice/userrelated/truncate5' => UserRelatedTruncate5Command::class,
            'invoice/autoincrementsettooneafter/truncate6' => AutoIncrementSetToOneAfterTruncate6Command::class,
        ],
    ],
];
