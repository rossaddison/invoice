<?php

declare(strict_types=1);

/**
* Related logic: see GeneratorController function entity
* @var App\Invoice\Entity\Gentor $generator
* @var Cycle\Database\Table $orm_schema
* @var array $relations
* @var string $questionmark
*/

echo "<?php\n";
?>

declare(strict_types=1); 

namespace <?= $generator->getNamespace_path() . '\Entity'; ?>;

<?php

/**
 * Related logic: see The namespace path normally begins with 'App' so alphabetically first
 * @var App\Invoice\Entity\GentorRelation $relation
 */
foreach ($relations as $relation) {
    echo 'use ' . $generator->getNamespace_path() . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR . ($relation->getCamelcase_name() ?? '#') . ';' . "\n";
} ?>
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
<?php
    /**
     * @var Cycle\Database\Schema\AbstractColumn $column
     */
    foreach ($orm_schema->getColumns() as $column) {
        if ($column->getAbstractType() === 'date') {
            echo 'use \DateTime;' . "\n";
        }
        if ($column->getAbstractType() === 'datetime') {
            echo 'use \DateTimeImmutable;' . "\n";
        }
    }
?>

 <?php
    echo '#[Entity(repository: ' . DIRECTORY_SEPARATOR . $generator->getNamespace_path() . DIRECTORY_SEPARATOR . $generator->getCamelcase_capital_name() . DIRECTORY_SEPARATOR . $generator->getCamelcase_capital_name() . 'Repository::class)]' . "\n";
if ($generator->isCreated_include() || $generator->isUpdated_include() || $generator->isModified_include()) {
    echo($generator->isCreated_include() ? '#[Behavior\CreatedAt(field: ' . "'" . 'date_created' . "',column:'" . 'date_created' . ')]' : '');
    echo($generator->isUpdated_include() ? '#[Behavior\UpdatedAt(field: ' . "'" . 'date_updated' . "',column:'" . 'date_updated' . ')]' : '');
    echo($generator->isModified_include() ? '#[Behavior\ModifiedAt(field: ' . "'" . 'date_modified' . "',column:'" . 'date_modified' . ')]' : '');
}
?>
 
class <?= $generator->getCamelcase_capital_name() . "\n"; ?>
{
    <?php
      /**
       * @var App\Invoice\Entity\GentorRelation $relation
       */
       foreach ($relations as $relation) {
           echo '    #[BelongsTo(target:' . ($relation->getCamelcase_name() ?? '') . "::class, nullable: false, fkAction:" . "'NO ACTION'" . ")]" . "\n";
           echo '    private ?' . ($relation->getCamelcase_name() ?? '') . " $" . ($relation->getLowercase_name() ?? '') . ' = null;' . "\n";
           echo '    ' . "\n";
       } ?>
    
    <?php
           $construct = '';
/**
 * @var Cycle\Database\Schema\AbstractColumn $column
 */
foreach ($orm_schema->getColumns() as $column) {
    $result = '';
    if ($column->IsNullable()) {
        $nullable = 'true';
        $questionmark = '?';
    } else {
        $nullable = 'false';
        $questionmark = '';
    }
    $init = '';
    switch ($column->getType()) {
        case 'string':
            // ''
            $init = '\'\'';
            break;
        case 'float':
            $init = 'null';
            break;
        case 'int':
            $init = 'null';
            break;
        case 'bool':
            if ($column->hasDefaultValue()) {
                /**
                 * @var mixed $init
                 */
                $init  = $column->getDefaultValue();
                if ($init === 1) {
                    $init = 'false';
                } elseif ($init === 0) {
                    $init = 'true';
                }
            } else {
                $init = 'false';
            }
            break;
    }
    $ab = '';
    $default = '';
    switch ($column->getAbstractType()) {
        //Special column type, usually mapped as integer + auto-incrementing flag
        //and added as table primary index column.
        //You can define only one primary column in your table
        //(you can still create a compound primary key, see below).
        case 'primary':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . "')]" . "\n";
            $ate_or_lic = 'private ';
            break;
            //Same as primary but uses bigInteger to store its values.
        case 'bigPrimary':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . "')]" . "\n";
            $ate_or_lic = 'private ';
            break;
            //Boolean type, some databases store it as an integer (1/0).
        case 'boolean':
            $ab = '    #[Column(type:' .
                  "'bool'" .
                  ',default:false' .
                  ($column->isNullable() ? ',nullable: false' : ',nullable: false') .
                  ')]' .
                  "\n";
            $ate_or_lic = 'private ';
            break;
            //Database specific integer (usually 32 bits).
        case 'integer':
            $result = (string) $column->getSize();
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . '(' . $result . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //Small/tiny integer, check your DBMS to check its size.
        case 'tinyInteger':
            $result = (string) $column->getSize();
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . '(' . $result . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            break;
            //Big/long integer (usually 64 bits), check your DBMS to check its size.
        case 'bigInteger':
            $result = (string) $column->getSize();
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . '(' . $result . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //length:255] String with specified length, a perfect type for emails and usernames as it can be indexed.
        case 'string':
            $result = (string) $column->getSize();
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . '(' . $result . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //Database specific type to store text data. Check DBMS to find size limitations.
        case 'text':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //Tiny text, same as "text" for most of the databases. Differs only in MySQL.
        case 'tinyText':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //Long text, same as "text" for most of the databases. Differs only in MySQL.
        case 'longText':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //[Double precision number.] (https://en.wikipedia.org/wiki/Double-precision_floating-point_format)
        case 'double':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //Single precision number, usually mapped into "real" type in the database.
        case 'float':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //precision, [scale:0]	Number with specified precision and scale.
        case 'decimal':
            $result = (string) $column->getPrecision() . ',' . (string) $column->getScale();
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . '(' . $result . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //To store specific date and time, DBAL will automatically force UTC timezone for such columns.
        case 'datetime':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . "', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //To store date only, DBAL will automatically force UTC timezone for such columns.
        case 'date':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //To store time only.
        case 'time':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . '"' . (string) $column->getDefaultvalue() . '"' . ")]" : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //Timestamp without a timezone, DBAL will automatically convert incoming values into UTC timezone.
            //Do not use such column in your objects to store time (use DateTime instead) as timestamps will behave very specific to select DBMS.
        case 'timestamp':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //To store binary data. Check specific DBMS to find size limitations.
        case 'binary':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //Tiny binary, same as "binary" for most of the databases. Differs only in MySQL.
        case 'tinyBinary':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //Long binary, same as "binary" for most of the databases. Differs only in MySQL.
        case 'longBinary':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ((string) $column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
            //To store JSON structures, usually mapped to "text", only Postgres supports it natively.
        case 'json':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . ")', nullable: " . $nullable . ($column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
        case 'enum':
            $ab = '    #[Column(type:' . "'" . $column->getAbstractType() . "(-1,1)', nullable: " . $nullable . ($column->hasDefaultvalue() ? ',default: ' . (string) $column->getDefaultvalue() . ')]' : ')]') . "\n";
            $ate_or_lic = 'private ';
            break;
    }
    echo $ab;
    if ($init === 'null') {
        $questionmark = '?';
    }
    if ($column->getAbstractType() === 'boolean') {
        echo '    ' . ($ate_or_lic ?? '') . $questionmark . "bool" . " $" . $column->getName() . ' =  ' . (string) $init . ';' . "\n";
        $construct .= "    bool" . " $" . $column->getName() . ' = ' . (string) $init . ',' . "\n    ";
    }

    if ($column->getAbstractType() === 'datetime') {
        echo '     ' . ($ate_or_lic ?? '') . 'DateTimeImmutable' . " $" . $column->getName() . ';' . "\n";
        $construct .= "      $" . $column->getName() . ' = ' . (string) $init . ',' . "\n    ";
    }

    if ($column->getAbstractType() === 'date') {
        echo '    ' . ($ate_or_lic ?? '') . " mixed $" . $column->getName() . ';' . "\n";
        $construct .= "     $" . $column->getName() . ' = ' . (string) $init . ',' . "\n    ";
    }

    if (($column->getAbstractType() <> 'date') && ($column->getAbstractType() <> 'datetime') && ($column->getAbstractType() <> 'boolean')) {
        echo '    ' . ($ate_or_lic ?? '') . $questionmark . $column->getType() . " $" . $column->getName() . ' =  ' . (string) $init . ';' . "\n";
        $construct .= "    " . $column->getType() . " $" . $column->getName() . ' = ' . (string) $init . ',' . "\n    ";
    }

    echo '    ' . "\n";
}
echo '    public function __construct(' . "\n";
echo '    ' . rtrim($construct, ",\n    ") . "\n";
echo '    )' . "\n";
echo '    {' . "\n";
foreach ($orm_schema->getColumns() as $column) {
    if (($column->getAbstractType() <> 'date') && ($column->getAbstractType() <> 'datetime')) {
        echo '       $this->' . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
    }
    if ($column->getAbstractType() == 'datetime') {
        echo '       $this->' . $column->getName() . " = new DateTimeImmutable('now');" . "\n";
    }
}
echo '    }' . "\n";

$nullify_relation_string = '';
/**
 * @var App\Invoice\Entity\GentorRelation $relation
 */
foreach ($relations as $relation) {
    echo '   ' . "\n";
    echo '   public function get' . ($relation->getCamelcase_name() ?? '#') . '() : ?' . ($relation->getCamelcase_name() ?? '#') . "\n";
    echo '   {' . "\n";
    echo '     return $this->' . ($relation->getLowercase_name() ?? '#') . ';' . "\n";
    echo '   }' . "\n";
    echo '   ' . "\n";
    echo '   public function set' . ($relation->getCamelcase_name() ?? '#') . '(?' . ($relation->getCamelcase_name() ?? '#') . ' $' . ($relation->getCamelcase_name() ?? '#') . '): void' . "\n";
    echo '   {' . "\n";
    echo '     $this->' . ($relation->getLowercase_name() ?? '#') . ' = $' . ($relation->getLowercase_name() ?? '#') . ';' . "\n";
    echo '   }' . "\n";
    $nullify_relation_string .= 'int $' . ($relation->getLowercase_name() ?? '#') . '_id, ';
}

//remove the last comma and space in the string
$final_string = substr($nullify_relation_string, 0, -2);

/**
 * @var Cycle\Database\Schema\AbstractColumn $column
 */
foreach ($orm_schema->getColumns() as $column) {
    echo '   ' . "\n";
    if (substr($column->getName(), -2) === 'id') {
        echo '   public function get' . ucfirst($column->getName()) . '(): ' . ($column->isNullable() ? $questionmark : '') . 'string' . "\n";
        echo '   {' . "\n";
        echo '    return (string)$this->' . $column->getName() . ';' . "\n";
    } else {
        /**
         * Related logic: see Entity/Client
         * mySql 'date' interpreted as DateTimeImmutable or string or null i.e. mixed with Cycle
         * Preferable to specifically use 'DateTimeImmutable|string|null' instead of 'mixed'
         * so as to define what variables make up the 'mixed'
         */

        if ($column->getAbstractType() === 'date') {
            echo '   public function get' . ucfirst($column->getName()) . '(): ' . ($column->isNullable() ? $questionmark : '') . 'DateTimeImmutable|string' . "\n";
        }
        if ($column->getAbstractType() === 'datetime') {
            echo '   public function get' . ucfirst($column->getName()) . '(): ' . ($column->isNullable() ? $questionmark : '') . 'DateTimeImmutable' . "\n";
        }
        if (($column->getAbstractType() <> 'date') && ($column->getAbstractType() <> 'datetime')) {
            echo '   public function get' . ucfirst($column->getName()) . '(): ' . ($column->isNullable() ? $questionmark : '') . $column->getType() . "\n";
        }
        echo '   {' . "\n";
        echo '      return $this->' . $column->getName() . ';' . "\n";
    }
    echo '   }' . "\n";
    echo '   ' . "\n";

    if ($column->getAbstractType() === 'date') {
        echo '   public function set' . ucfirst($column->getName()) . '(DateTimeImmutable|string|null $' . $column->getName() . ') : void' . "\n";
    }
    if ($column->getAbstractType() === 'datetime') {
        echo '   public function set' . ucfirst($column->getName()) . '(DateTimeImmutable $' . $column->getName() . ') : void' . "\n";
    }
    if (($column->getAbstractType() <> 'date') && ($column->getAbstractType() <> 'datetime')) {
        echo '   public function set' . ucfirst($column->getName()) . '(' . $column->getType() . ' $' . $column->getName() . ') : void' . "\n";
    }
    echo '   {' . "\n";
    echo '     $this->' . $column->getName() . ' =  $' . $column->getName() . ';' . "\n";
    echo '   }' . "\n";
}
?>
    
    /**
     * Make sure the sequence of parameters is correct
     * Related logic: see https://github.com/yiisoft/demo/issues/462 
     * Related logic: see e.g. Entity\Product.php which has 3 relations tax_rate, unit, and family
     */
    public function nullifyRelationOnChange(<?= $final_string; ?>) : void 
    {
        <?php
    /**
     * @var App\Invoice\Entity\GentorRelation $relation
     */
    foreach ($relations as $relation) {
        $lcn = $relation->getLowercase_name();
        echo 'if ($this->' . ($lcn ?? '#') . '_id <> $' . ($lcn ?? '#') . '_id) {' . "\n";
        echo '           $this->' . ($lcn ?? '#') . ' = null;' . "\n";
        echo '       }' . "\n";
    } ?>
    }
}