<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Entity\Gentor $generator
 * @var Cycle\Database\Table $orm_schema
 * @var array $relations
 * @var string $typecast
 */

echo "<?php\n";
?>

declare(strict_types=1); 

namespace <?= $generator->getNamespace_path() .DIRECTORY_SEPARATOR. $generator->getCamelcase_capital_name().";\n"; ?>

use <?= $generator->getNamespace_path() .DIRECTORY_SEPARATOR.'Entity' .DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().";\n"; ?>


final class <?= $generator->getCamelcase_capital_name(); ?>Service
{

    private <?= $generator->getCamelcase_capital_name(); ?>Repository $repository;

    public function __construct(<?= $generator->getCamelcase_capital_name(); ?>Repository $repository)
    {
        $this->repository = $repository;
    }

    public function save<?= $generator->getCamelcase_capital_name(); ?>(<?= $generator->getCamelcase_capital_name(); ?> $model, array $array): void
    {
        <?php
            echo "\n";
/**
 * @var Cycle\Database\Schema\AbstractColumn $column
 */
foreach ($orm_schema->getColumns() as $column) {
    if (($column->getAbstractType() <> 'primary')) {
        switch ($column->getAbstractType()) {
            //case 'primary':
            //
            //    break;
            //Same as primary but uses bigInteger to store its values.
            //case 'bigPrimary':
            //
            //    break;
            //Boolean type, some databases store it as an integer (1/0).
            case 'boolean':
                $typecast = '(bool)';
                break;
                //Database specific integer (usually 32 bits).
            case 'integer':
                $typecast = '(int)';
                break;
                //Small/tiny integer, check your DBMS to check its size.
            case 'tinyInteger':
                $typecast = '(int)';
                break;
                //Big/long integer (usually 64 bits), check your DBMS to check its size.
            case 'bigInteger':
                $typecast = '(int)';
                break;
                //length:255] String with specified length, a perfect type for emails and usernames as it can be indexed.
            case 'string':
                $typecast = '(string)';
                break;
                //Database specific type to store text data. Check DBMS to find size limitations.
            case 'text':
                $typecast = '(string)';
                break;
                //Tiny text, same as "text" for most of the databases. Differs only in MySQL.
            case 'tinyText':
                $typecast = '(string)';
                break;
                //Long text, same as "text" for most of the databases. Differs only in MySQL.
            case 'longText':
                $typecast = '(string)';
                break;
                //[Double precision number.] (https://en.wikipedia.org/wiki/Double-precision_floating-point_format)
            case 'double':
                $typecast = '';
                break;
                //Single precision number, usually mapped into "real" type in the database.
            case 'float':
                $typecast = '(float)';
                break;
                //precision, [scale:0]	Number with specified precision and scale.
            case 'decimal':
                $typecast = '';
                break;
                //To store specific date and time, DBAL will automatically force UTC timezone for such columns.
            case 'datetime':
                $typecast = '';
                break;
                //To store date only, DBAL will automatically force UTC timezone for such columns.
            case 'date':
                $typecast = '';
                break;
                //To store time only.
            case 'time':
                $typecast = '';
                break;
                //Timestamp without a timezone, DBAL will automatically convert incoming values into UTC timezone.
                //Do not use such column in your objects to store time (use DateTime instead) as timestamps will behave very specific to select DBMS.
            case 'timestamp':
                $typecast = '';
                break;
                //To store binary data. Check specific DBMS to find size limitations.
            case 'binary':
                $typecast = '';
                break;
                //Tiny binary, same as "binary" for most of the databases. Differs only in MySQL.
            case 'tinyBinary':
                $typecast = '';
                break;
                //Long binary, same as "binary" for most of the databases. Differs only in MySQL.
            case 'longBinary':
                $typecast = '';
                break;
                //To store JSON structures, usually mapped to "text", only Postgres supports it natively.
            case 'json':
                $typecast = '';
                break;
            case 'enum':
                $typecast = '';
                break;
        }
        if ($column->getAbstractType() <> 'date') {
            echo '   isset($array['."'". $column->getName()."']) ? ". '$model->set'. ucfirst($column->getName()).'('.$typecast.'$array['."'".$column->getName()."']) : '';"."\n";
        }
        if ($column->getAbstractType() === 'date') {
            echo '   isset($array['."'". $column->getName()."']) ? ". '$model->set'. ucfirst($column->getName()).'('.$typecast.'$array['."'".$column->getName()."']) : '';"."\n";
            echo '$datetime = new \DateTime();';
            echo '/**';
            echo ' * @var string $array['. "'". $column->getName(). "'".']';
            echo ' */';
            echo '$date = $array'."['". $column->getName() ."'] ?? '';";
            echo '$model->set'.ucfirst($column->getName()).'($datetime::createFromFormat('."'". "Y-m-d" ."',". '$date));';
        }
    }
}
?> 
        $this->repository->save($model);
    }
    
    public function delete<?= $generator->getCamelcase_capital_name(); ?>(<?= $generator->getCamelcase_capital_name(); ?> $model): void
    {
        $this->repository->delete($model);
    }
}