<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\Table;
use Yiisoft\Html\Tag\Tr;
use Yiisoft\Html\Tag\H3;

?>

<?php
    echo H3::tag()
         ->content('Yii Console Commands  (FAQ\'s - {root}/resources/views/layout/invoice.php //FAQ)');
echo Br::tag();
echo Table::tag()
->attributes([ 'class' => 'table table-info table-striped table-bordered'])
->rows(
    Tr::tag()
    ->headerStrings([
        'Purpose',
        'Replaces',
        'Console',
        'Source Code File Path'
    ]),
    Tr::tag()
    ->dataStrings([
        'Clear the current database schema',
        'Manually deleting the schema in {root}/runtime/schema.php',
        'yii cycle/schema/clear',
        '{root}\vendor\yiisoft\yii-cycle\src\Command\Schema\SchemaClearCommand.php'
    ]),
    Tr::tag()
    ->dataStrings([
        'Rebuild the current database schema',
        'Alternating config/common/params MODE_WRITE_ONLY and MODE_READ_AND_WRITE and .env BUILD_DATABASE = true',
        'yii cycle/schema/rebuild',
        '{root}\vendor\yiisoft\yii-cycle\src\Command\Schema\SchemaRebuildCommand.php'
    ]),
    Tr::tag()
    ->dataStrings([
        'Creates a list of random invoice items with Item tax and with a Summary Table with two Invoice specific Taxes of 15% and 20% respectively.',
        'Online testing of creating invoices.',
        'yii invoice/items',
        '{root}\src\Command\Invoice\SettingTruncateCommand.php and config\console\commands.php'
    ]),    
    Tr::tag()
    ->dataStrings([
        'Removes all the settings in the Setting Table. An array in future can be passed to the InvoiceController which can be tweaked from within the config/common/params.',
        'Online deleting of settings.',
        'yii invoice/setting/truncate',
        '{root}\src\Command\Invoice\SettingTruncateCommand.php and config\console\commands.php'
    ]),
    Tr::tag()
    ->dataStrings([
        'Removes all the records in the gentor and gentor relation tables. Reuse the generator to build CRUD for another.',
        'Online deleting of records during Development.',
        'yii invoice/generator/truncate',
        '{root}\src\Command\Invoice\GeneratorTruncateCommand.php and config\console\commands.php'
    ]),
    Tr::tag()
    ->dataStrings([
        'Removes all invoices and invoice related tables.',
        'Online deleting of records during Development.',
        'yii invoice/inv/truncate1',
        '{root}\src\Command\Invoice\InvTruncate1Command.php and config\console\commands.php'
    ]),
    Tr::tag()
    ->dataStrings([
        'Removes all quotes and quote related tables.',
        'Online deleting of records during Development.',
        'yii invoice/quote/truncate2',
        '{root}\src\Command\Invoice\QuoteTruncate2Command.php and config\console\commands.php'
    ]),
    Tr::tag()
    ->dataStrings([
        'Removes all salesorders and salesorder related tables.',
        'Online deleting of records during Development.',
        'yii invoice/salesorder/truncate3',
        '{root}\src\Command\Invoice\SalesOrderTruncate3Command.php and config\console\commands.php'
    ]),
    Tr::tag()
    ->dataStrings([
        'Removes all subsequent tables besides tables responsible for logging in.',
        'Online deleting of records during Development.',
        'yii invoice/nonuserrelated/truncate4',
        '{root}\src\Command\Invoice\NonUserRelatedTruncate4Command.php and config\console\commands.php'
    ]), 
)
->render();
?>