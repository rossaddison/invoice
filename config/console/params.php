<?php

declare(strict_types=1);

use App\Command\InstallCommand;
use App\Command\Invoice\AutoIncrementSetToOneAfterTruncate6Command;
use App\Command\Invoice\GeneratorTruncateCommand;
use App\Command\Invoice\InvTruncate1Command;
use App\Command\Invoice\ItemsCommand;
use App\Command\Invoice\NonUserRelatedTruncate4Command;
use App\Command\Invoice\QuoteTruncate2Command;
use App\Command\Invoice\SettingTruncateCommand;
use App\Command\Invoice\SalesOrderTruncate3Command;
use App\Command\Invoice\UserRelatedTruncate5Command;
use App\Command\Router\ListCommand;
use App\Command\Translation\TranslateCommand;
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
            'serve' => Serve::class,
            'install' => InstallCommand::class,
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
