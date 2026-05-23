<?php

declare(strict_types=1);

/**
* Related logic: see GeneratorController function entity
* @var App\Infrastructure\Persistence\Gentor\Gentor $generator
* @var Cycle\Database\Table $orm_schema
* @var array $relations
*/

echo "<?php\n";
$TYPE_PRIMARY = 'primary';
$TYPE_BIG_PRIMARY = 'bigPrimary';
$TYPE_DATE = 'date';
$TYPE_DATETIME = 'datetime';
$TYPE_BOOLEAN = 'boolean';
$COL_SEP = "',column:'";
$SIZED_CLOSE = ")', nullable: ";
$PUB_GET = '    public function get';
$PUB_SET = '    public function set';
$FUNC_OPEN = '    {' . "\n";
$FUNC_CLOSE = '    }' . "\n";
$RETURN_THIS = '        return $this->';
$VOID_EOL = '): void';
$THIS_ARROW = '        $this->';
?>

declare(strict_types=1);

namespace App\Infrastructure\Persistence\<?= $generator->getCamelcaseCapitalName(); ?>;

<?php
/**
 * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
 */
foreach ($relations as $relation) {
    echo 'use App\\Infrastructure\\Persistence\\'
        . ($relation->getCamelcaseName() ?? '#') . '\\'
        . ($relation->getCamelcaseName() ?? '#') . ';' . "\n";
}
?>
use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
<?php
    /**
     * @var Cycle\Database\Schema\AbstractColumn $column
     */
    foreach ($orm_schema->getColumns() as $column) {
        if ($column->getAbstractType() === $TYPE_DATE) {
            echo 'use \DateTime;' . "\n";
        }
        if ($column->getAbstractType() === $TYPE_DATETIME) {
            echo 'use \DateTimeImmutable;' . "\n";
        }
    }
?>

<?php
    echo '#[Entity(repository: '
            . DIRECTORY_SEPARATOR
            . $generator->getNamespacePath()
            . DIRECTORY_SEPARATOR
            . $generator->getCamelcaseCapitalName()
            . DIRECTORY_SEPARATOR
            . $generator->getCamelcaseCapitalName()
            . 'Repository::class)]' . "\n";
if ($generator->isCreatedInclude()
        || $generator->isUpdatedInclude()
                || $generator->isModifiedInclude()) {
    echo $generator->isCreatedInclude() ?
            '#[Behavior\CreatedAt(field: '
            . "'"
            . 'date_created'
            . $COL_SEP
            . 'date_created'
            . ')]' : '';
    echo $generator->isUpdatedInclude() ? '#[Behavior\UpdatedAt(field: ' . "'"
            . 'date_updated' . $COL_SEP . 'date_updated' . ')]' : '';
    echo $generator->isModifiedInclude() ? '#[Behavior\ModifiedAt(field: ' . "'"
            . 'date_modified' . $COL_SEP . 'date_modified' . ')]' : '';
}
?>
class <?= $generator->getCamelcaseCapitalName() . "\n"; ?>
{
    use RequireId;

<?php
    /**
     * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
     */
    foreach ($relations as $relation) {
        echo '    #[BelongsTo(target:' . ($relation->getCamelcaseName() ?? '')
                . "::class, nullable: false, fkAction:" . "'NO ACTION'" . ")]"
                . "\n";
        echo '    private ?' . ($relation->getCamelcaseName() ?? '') . " $"
                . ($relation->getLowercaseName() ?? '') . ' = null;' . "\n";
        echo "\n";
    }
    // Detect primary key type
    $primaryType = $TYPE_PRIMARY;
    foreach ($orm_schema->getColumns() as $col) {
        if ($col->getAbstractType() === $TYPE_BIG_PRIMARY) {
            $primaryType = 'bigPrimary';
            break;
        }
    }
    echo "    #[Column(type: '" . $primaryType . "')]" . "\n";
    echo '    private ?int $id = null;' . "\n";
    echo "\n";
?>
    public function __construct(
<?php
/**
 * @var Cycle\Database\Schema\AbstractColumn $column
 */
foreach ($orm_schema->getColumns() as $column) {
    // id is declared separately above
    if ($column->getAbstractType() === $TYPE_PRIMARY || $column->getAbstractType() === $TYPE_BIG_PRIMARY) {
        continue;
    }

    $nullable = $column->isNullable() ? 'true' : 'false';
    $questionmark = $column->isNullable() ? '?' : '';

    switch ($column->getType()) {
        case 'string':
            $init = "''";
            break;
        case 'float':
            $init = 'null';
            break;
        case 'int':
            $init = 'null';
            break;
        case 'bool':
            if ($column->hasDefaultValue()) {
                /** @var mixed $dv */
                $dv = $column->getDefaultValue();
                $init = ($dv === 1) ? 'false' : 'true';
            } else {
                $init = 'false';
            }
            break;
        default:
            $init = 'null';
    }
    if ($init === 'null') {
        $questionmark = '?';
    }

    // Column annotation for the constructor parameter
    switch ($column->getAbstractType()) {
        case $TYPE_BOOLEAN:
            $ab = "        #[Column(type: 'bool'"
                  . ($column->isNullable() ? ', nullable: true' : ', nullable: false, default: false')
                  . ')]' . "\n";
            break;
        case 'integer':
            $ab = "        #[Column(type: 'integer(" . $column->getSize() . $SIZED_CLOSE . $nullable . ')]' . "\n";
            break;
        case 'tinyInteger':
            $ab = "        #[Column(type: 'tinyInteger(" . $column->getSize() . $SIZED_CLOSE . $nullable . ')]' . "\n";
            break;
        case 'bigInteger':
            $ab = "        #[Column(type: 'bigInteger(" . $column->getSize() . $SIZED_CLOSE . $nullable . ')]' . "\n";
            break;
        case 'string':
            $ab = "        #[Column(type: 'string(" . $column->getSize() . $SIZED_CLOSE . $nullable . ')]' . "\n";
            break;
        case 'text':
            $ab = "        #[Column(type: 'text', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'tinyText':
            $ab = "        #[Column(type: 'tinyText', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'longText':
            $ab = "        #[Column(type: 'longText', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'double':
            $ab = "        #[Column(type: 'double', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'float':
            $ab = "        #[Column(type: 'float', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'decimal':
            $ab = "        #[Column(type: 'decimal(" . $column->getPrecision() . ',' . $column->getScale() . $SIZED_CLOSE . $nullable . ')]' . "\n";
            break;
        case $TYPE_DATETIME:
            $ab = "        #[Column(type: 'datetime', nullable: " . $nullable . ')]' . "\n";
            break;
        case $TYPE_DATE:
            $ab = "        #[Column(type: 'date', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'time':
            $ab = "        #[Column(type: 'time', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'timestamp':
            $ab = "        #[Column(type: 'timestamp', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'binary':
            $ab = "        #[Column(type: 'binary', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'tinyBinary':
            $ab = "        #[Column(type: 'tinyBinary', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'longBinary':
            $ab = "        #[Column(type: 'longBinary', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'json':
            $ab = "        #[Column(type: 'json', nullable: " . $nullable . ')]' . "\n";
            break;
        case 'enum':
            $ab = "        #[Column(type: 'enum(-1,1)', nullable: " . $nullable . ')]' . "\n";
            break;
        default:
            $ab = "        #[Column(type: '" . $column->getAbstractType() . "', nullable: " . $nullable . ')]' . "\n";
    }

    echo $ab;

    if ($column->getAbstractType() === $TYPE_BOOLEAN) {
        echo '        private ' . $questionmark . 'bool $' . $column->getName() . ' = ' . $init . ',' . "\n";
    } elseif ($column->getAbstractType() === $TYPE_DATETIME) {
        echo '        private ?\\DateTimeImmutable $' . $column->getName() . ' = null,' . "\n";
    } elseif ($column->getAbstractType() === $TYPE_DATE) {
        echo '        private mixed $' . $column->getName() . ' = null,' . "\n";
    } else {
        echo '        private ' . $questionmark . $column->getType() . ' $' . $column->getName() . ' = ' . $init . ',' . "\n";
    }
    echo "\n";
}
?>
    ) {}

    public function reqId(): int
    {
        return $this->requireId($this->id, '<?= $generator->getCamelcaseCapitalName(); ?>');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

<?php
/**
 * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
 */
foreach ($relations as $relation) {
    echo "\n";
    echo $PUB_GET . ($relation->getCamelcaseName() ?? '#')
            . '(): ?' . ($relation->getCamelcaseName() ?? '#') . "\n";
    echo $FUNC_OPEN;
    echo $RETURN_THIS . ($relation->getLowercaseName() ?? '#') . ';' . "\n";
    echo $FUNC_CLOSE;
    echo "\n";
    echo $PUB_SET . ($relation->getCamelcaseName() ?? '#')
            . '(?' . ($relation->getCamelcaseName() ?? '#') . ' $'
            . ($relation->getCamelcaseName() ?? '#') . $VOID_EOL . "\n";
    echo $FUNC_OPEN;
    echo $THIS_ARROW . ($relation->getLowercaseName() ?? '#')
            . ' = $' . ($relation->getLowercaseName() ?? '#') . ';' . "\n";
    echo $FUNC_CLOSE;
}
?>

<?php
/**
 * @var Cycle\Database\Schema\AbstractColumn $column
 */
foreach ($orm_schema->getColumns() as $column) {
    // primary key already handled by reqId/hasIdentity/setId
    if ($column->getAbstractType() === $TYPE_PRIMARY || $column->getAbstractType() === $TYPE_BIG_PRIMARY) {
        continue;
    }

    $questionmark = $column->isNullable() ? '?' : '';

    echo "\n";

    // FK _id fields: req + get + set
    if (substr($column->getName(), -3) === '_id') {
        echo '    public function req' . ucfirst($column->getName()) . '(): int' . "\n";
        echo $FUNC_OPEN;
        echo '        return $this->requireId($this->' . $column->getName()
                . ", '" . $generator->getCamelcaseCapitalName() . ' ' . $column->getName() . "');" . "\n";
        echo $FUNC_CLOSE;
        echo "\n";
        echo $PUB_GET . ucfirst($column->getName()) . '(): ?int' . "\n";
        echo $FUNC_OPEN;
        echo $RETURN_THIS . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        echo "\n";
        echo $PUB_SET . ucfirst($column->getName()) . '(int $' . $column->getName() . $VOID_EOL . "\n";
        echo $FUNC_OPEN;
        echo $THIS_ARROW . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        continue;
    }

    // date fields
    if ($column->getAbstractType() === $TYPE_DATE) {
        echo $PUB_GET . ucfirst($column->getName())
                . '(): \\DateTimeImmutable|string|null' . "\n";
        echo $FUNC_OPEN;
        echo $RETURN_THIS . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        echo "\n";
        echo $PUB_SET . ucfirst($column->getName())
                . '(\\DateTimeImmutable|string|null $' . $column->getName() . $VOID_EOL . "\n";
        echo $FUNC_OPEN;
        echo $THIS_ARROW . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        continue;
    }

    // datetime fields
    if ($column->getAbstractType() === $TYPE_DATETIME) {
        echo $PUB_GET . ucfirst($column->getName())
                . '(): ?\\DateTimeImmutable' . "\n";
        echo $FUNC_OPEN;
        echo $RETURN_THIS . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        echo "\n";
        echo $PUB_SET . ucfirst($column->getName())
                . '(\\DateTimeImmutable $' . $column->getName() . $VOID_EOL . "\n";
        echo $FUNC_OPEN;
        echo $THIS_ARROW . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        continue;
    }

    // boolean fields
    if ($column->getAbstractType() === $TYPE_BOOLEAN) {
        echo $PUB_GET . ucfirst($column->getName())
                . '(): ?bool' . "\n";
        echo $FUNC_OPEN;
        echo $RETURN_THIS . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        echo "\n";
        echo $PUB_SET . ucfirst($column->getName())
                . '(?bool $' . $column->getName() . $VOID_EOL . "\n";
        echo $FUNC_OPEN;
        echo $THIS_ARROW . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        continue;
    }

    // all other fields
    echo $PUB_GET . ucfirst($column->getName())
            . '(): ' . $questionmark . $column->getType() . "\n";
    echo $FUNC_OPEN;
    echo $RETURN_THIS . $column->getName() . ';' . "\n";
    echo $FUNC_CLOSE;
    echo "\n";
    echo $PUB_SET . ucfirst($column->getName())
            . '(' . $column->getType() . ' $' . $column->getName() . $VOID_EOL . "\n";
    echo $FUNC_OPEN;
    echo $THIS_ARROW . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
    echo $FUNC_CLOSE;
}
?>
}
