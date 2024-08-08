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
    ) 
    ->render();