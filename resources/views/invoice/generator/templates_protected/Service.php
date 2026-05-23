<?php

declare(strict_types=1);

/**
 * @var App\Infrastructure\Persistence\Gentor\Gentor $generator
 * @var Cycle\Database\Table $orm_schema
 * @var array $relations
 * @var string $typecast
 */

echo "<?php\n";
$TYPE_DATE = 'date';
$TYPECAST_INT = '(int)';
$TYPECAST_STRING = '(string)';
$MODEL_SET = '$model->set';
?>

declare(strict_types=1);

namespace <?= $generator->getNamespacePath() . DIRECTORY_SEPARATOR . $generator->getCamelcaseCapitalName() . ";\n"; ?>

use App\Infrastructure\Persistence\<?= $generator->getCamelcaseCapitalName(); ?>\<?= $generator->getCamelcaseCapitalName(); ?>;



final class <?= $generator->getCamelcaseCapitalName(); ?>Service
{

    public function __construct(private <?= $generator->getCamelcaseCapitalName(); ?>Repository $repository)
    {
    }

    public function save<?= $generator->getCamelcaseCapitalName(); ?>(<?= $generator->getCamelcaseCapitalName(); ?> $model, array $array): void
    {
        <?php
            echo "\n";
/**
 * @var Cycle\Database\Schema\AbstractColumn $column
 */
foreach ($orm_schema->getColumns() as $column) {
    if ($column->getAbstractType() <> 'primary') {
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
                $typecast = $TYPECAST_INT;
                break;
                //Small/tiny integer, check your DBMS to check its size.
            case 'tinyInteger':
                $typecast = $TYPECAST_INT;
                break;
                //Big/long integer (usually 64 bits), check your DBMS to check its size.
            case 'bigInteger':
                $typecast = $TYPECAST_INT;
                break;
                //length:255] String with specified length, a perfect type for emails and usernames as it can be indexed.
            case 'string':
                $typecast = $TYPECAST_STRING;
                break;
                //Database specific type to store text data. Check DBMS to find size limitations.
            case 'text':
                $typecast = $TYPECAST_STRING;
                break;
                //Tiny text, same as "text" for most of the databases. Differs only in MySQL.
            case 'tinyText':
                $typecast = $TYPECAST_STRING;
                break;
                //Long text, same as "text" for most of the databases. Differs only in MySQL.
            case 'longText':
                $typecast = $TYPECAST_STRING;
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
            case $TYPE_DATE:
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
            default:
                break;
        }
        if ($column->getAbstractType() <> $TYPE_DATE) {
            echo '   isset($array[' . "'" . $column->getName() . "']) ? " . $MODEL_SET . ucfirst($column->getName()) . '(' . $typecast . '$array[' . "'" . $column->getName() . "']) : '';" . "\n";
        }
        if ($column->getAbstractType() === $TYPE_DATE) {
            echo '   isset($array[' . "'" . $column->getName() . "']) ? " . $MODEL_SET . ucfirst($column->getName()) . '(' . $typecast . '$array[' . "'" . $column->getName() . "']) : '';" . "\n";
            echo '$datetime = new \DateTime();';
            echo '/**';
            echo ' * @var string $array[' . "'" . $column->getName() . "'" . ']';
            echo ' */';
            echo '$date = $array' . "['" . $column->getName() . "'] ?? '';";
            echo $MODEL_SET . ucfirst($column->getName()) . '($datetime::createFromFormat(' . "'" . "Y-m-d" . "'," . '$date));';
        }
    }
}
?>
        $this->repository->save($model);
    }

    public function delete<?= $generator->getCamelcaseCapitalName(); ?>(<?= $generator->getCamelcaseCapitalName(); ?> $model): void
    {
        $this->repository->delete($model);
    }
}