<?php

declare(strict_types=1);

use Yiisoft\Yii\Console\Command\Serve;

return [
    'serve' => Serve::class,
    'user/create' => App\User\Console\CreateCommand::class,
    'user/assignRole' => App\User\Console\AssignRoleCommand::class,
    'router/list' => App\Command\Router\ListCommand::class,
    'translator/translate' => App\Command\Translation\TranslateCommand::class,
    /** 
     * Build a randomly generated items list for an invoice with a summary table
     * using two specific item taxes for the item list and 
     * using two specific invoice taxes generated in the summary table
     */
    'invoice/items' => App\Command\Invoice\ItemsCommand::class,
    'invoice/setting/truncate' => App\Command\Invoice\SettingTruncateCommand::class,
    'invoice/generator/truncate' => App\Command\Invoice\GeneratorTruncateCommand::class,
    'invoice/inv/truncate1' => App\Command\Invoice\InvTruncate1Command::class,
    'invoice/quote/truncate2' => App\Command\Invoice\QuoteTruncate2Command::class,
    'invoice/salesorder/truncate3' => App\Command\Invoice\SalesOrderTruncate3Command::class,
    'invoice/nonuserrelated/truncate4' => App\Command\Invoice\NonUserRelatedTruncate4Command::class
];
