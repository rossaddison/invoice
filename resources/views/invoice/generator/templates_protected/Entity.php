<?php

declare(strict_types=1);

/**
* Related logic: see GeneratorController function entity
* @var App\Infrastructure\Persistence\Gentor\Gentor $generator
* @var Cycle\Database\Table $orm_schema
* @var array $relations
* @var Cycle\Database\DatabaseManager|null $dbal
* @var App\Invoice\Generator\GeneratorRepository|null $gr
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
$EXT_MUT_FREE = '    /** @psalm-external-mutation-free */' . "\n";
// Column names are snake_case (DB convention); accessor method names should
// be PascalCase/camelCase (PHP convention) -- e.g. curr_code -> CurrCode, so
// getCurr_code()/setCurr_code() become getCurrCode()/setCurrCode(). The
// underlying property/parameter name is left as-is (it must match the real
// column name for Cycle's annotation mapping). Same helper already used by
// _view.php -- applied here too, and in Form.php/Service.php/_form.php, for
// consistency across every generated file for this entity.
$toPascalCase = static fn (string $name): string => str_replace('_', '', ucwords($name, '_'));
// Shared by the primary-key detection below and the relation-key
// resolution further down -- walks a table's columns for the one(s)
// marked primary/bigPrimary and returns its real name, or null if none.
$findPrimaryColumnName = static function (\Cycle\Database\TableInterface $table): ?string {
    $name = null;
    foreach ($table->getColumns() as $col) {
        if ($col->getAbstractType() === 'primary' || $col->getAbstractType() === 'bigPrimary') {
            $name = $col->getName();
        }
    }
    return $name;
};
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
    $needsDateTimeImmutable = false;
    foreach ($orm_schema->getColumns() as $column) {
        if ($column->getAbstractType() === $TYPE_DATE || $column->getAbstractType() === $TYPE_DATETIME) {
            $needsDateTimeImmutable = true;
        }
    }
    if ($needsDateTimeImmutable) {
        echo 'use \DateTimeImmutable;' . "\n";
    }
?>

<?php
    // table: is required, not cosmetic -- Cycle otherwise guesses the table
    // name by pluralizing the entity's role (lowercased class name), which
    // only coincidentally matches the real table for some names (TaxType ->
    // tax_types) and silently targets the wrong/a nonexistent table for most
    // others (e.g. FiscalYear -> Cycle's default guess is fiscal_years,
    // grn_batch -> GrnBatch would guess grn_batches). Always pin it to the
    // real table this entity was actually generated from.
    echo '#[Entity(repository: '
            . '\\'
            . $generator->getNamespacePath()
            . '\\'
            . $generator->getCamelcaseCapitalName()
            . '\\'
            . $generator->getCamelcaseCapitalName()
            . 'Repository::class, table: '
            . "'" . $generator->getPreEntityTable() . "'"
            . ')]' . "\n";
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
// Do NOT mark this class final: Cycle ORM generates a lazy-loading proxy
// subclass of every entity at runtime (visible as "<Entity> Cycle ORM
// Proxy" if you var_dump a hydrated instance) -- a final class can't be
// subclassed, which breaks Cycle's own proxy mechanism, not just some
// hypothetical external extender. Left as an accepted, visible
// ClassMustBeFinal finding rather than suppressed.
/** @psalm-external-mutation-free */
class <?= $generator->getCamelcaseCapitalName() . "\n"; ?>
{
    use RequireId;

<?php
    /**
     * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
     */
    foreach ($relations as $relation) {
        $relationLowercaseName = $relation->getLowercaseName() ?? '';
        $relationCamelcaseName = $relation->getCamelcaseName() ?? '';

        // Resolve the target's real table + primary key column instead of
        // trusting Cycle's default {relation}_{outerKey} relation-key
        // convention (see cycle/schema-builder's BelongsTo.php): that
        // convention assumes the target's PK FIELD is always named "id"
        // (true here, by this generator's own design) AND that the
        // source table's FK column is exactly "{relation}_id" -- false
        // for FrontAccounting's many string-keyed FKs (curr_code,
        // loc_code, stock_id, ...), and fragile in general since it
        // depends on lowercasename being typed in to exactly match
        // whatever the real column happens to be called. Fail loudly at
        // generation time instead of ever emitting a guessed key.
        if ($dbal === null || $gr === null) {
            throw new \RuntimeException(
                "Cannot generate relation '$relationLowercaseName': this template needs \$dbal/\$gr to resolve " .
                "the real target table and FK column (see GeneratorCodeController::entity())."
            );
        }
        $targetGentor = $gr->findByCamelcaseCapitalName($relationCamelcaseName);
        if ($targetGentor === null) {
            throw new \RuntimeException(
                "Cannot generate relation '$relationLowercaseName': no Gentor record found for target entity " .
                "'$relationCamelcaseName'. Create one first -- it's how this generator discovers the target's " .
                'real table/primary key column.'
            );
        }
        $targetTable = $targetGentor->getPreEntityTable();
        if ($targetTable === null || $targetTable === '') {
            throw new \RuntimeException(
                "Cannot generate relation '$relationLowercaseName': the Gentor record for '$relationCamelcaseName' " .
                'has no pre_entity_table set.'
            );
        }
        $targetSchema = $dbal->database('default')->table($targetTable);
        $outerKey = $findPrimaryColumnName($targetSchema);
        if ($outerKey === null) {
            throw new \RuntimeException(
                "Cannot generate relation '$relationLowercaseName': target table '$targetTable' has no detectable " .
                'primary key.'
            );
        }

        $innerKey = $relationLowercaseName . '_id';
        if (!$orm_schema->hasColumn($innerKey)) {
            throw new \RuntimeException(
                "Cannot generate relation '$relationLowercaseName': expected a '$innerKey' column on " .
                "'" . $generator->getPreEntityTable() . "' (the conventional {relation}_id foreign key) but it " .
                "doesn't exist. This generator can't yet infer a differently-named or non-integer foreign key " .
                "column automatically -- rename the relation's lowercasename to match the real column, or wire " .
                'this one relation up by hand.'
            );
        }

        echo '    #[BelongsTo(target:' . $relationCamelcaseName
                . "::class, innerKey: '" . $innerKey . "', outerKey: '" . $outerKey . "'"
                . ', nullable: false, fkAction:' . "'NO ACTION'" . ')]'
                . "\n";
        echo '    private ?' . $relationCamelcaseName . ' $'
                . $relationLowercaseName . ' = null;' . "\n";
        echo "\n";
    }
    // Detect primary key type and its real column name -- the PHP property
    // is always named $id for consistency with RequireId/reqId()/
    // hasIdentity() below, but the real column is very often NOT literally
    // called "id" (e.g. suppliers.supplier_id, currencies.curr_abrev) and
    // Cycle's Column attribute defaults the DB column name to the PHP
    // property name when none is given, so this must be explicit.
    $primaryType = $TYPE_PRIMARY;
    $primaryColumnName = 'id';
    foreach ($orm_schema->getColumns() as $col) {
        if ($col->getAbstractType() === $TYPE_PRIMARY || $col->getAbstractType() === $TYPE_BIG_PRIMARY) {
            $primaryColumnName = $col->getName();
        }
        if ($col->getAbstractType() === $TYPE_BIG_PRIMARY) {
            $primaryType = 'bigPrimary';
        }
    }
    echo "    #[Column(type: '" . $primaryType . "', name: '" . $primaryColumnName . "')]" . "\n";
    echo '    private ?int $id = null;' . "\n";
    echo "\n";
?>
    /** @psalm-mutation-free */
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
            $init = $column->isNullable() ? 'null' : '0.0';
            break;
        case 'int':
            $init = $column->isNullable() ? 'null' : '0';
            break;
        case 'bool':
            if ($column->hasDefaultValue()) {
                /** @var mixed $dv */
                $dv = $column->getDefaultValue();
                // MySQL reports a tinyint(1) default as int 1/0 or string '1'/'0'
                // depending on driver version -- check both forms.
                $init = ($dv === 1 || $dv === '1' || $dv === true) ? 'true' : 'false';
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
            $ab = "        #[Column(type: 'bool', typecast: 'bool'"
                  . ($column->isNullable() ? ', nullable: true' : ', nullable: false, default: ' . $init)
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
        echo '        private \\DateTimeImmutable|string|null $' . $column->getName() . ' = null,' . "\n";
    } else {
        echo '        private ' . $questionmark . $column->getType() . ' $' . $column->getName() . ' = ' . $init . ',' . "\n";
    }
    echo "\n";
}
?>
    ) {}

    /** @psalm-mutation-free */
    public function reqId(): int
    {
        return $this->requireId($this->id, '<?= $generator->getCamelcaseCapitalName(); ?>');
    }

    /** @psalm-mutation-free */
    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    /** @psalm-external-mutation-free */
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
    echo $EXT_MUT_FREE;
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
        echo '    public function req' . ucfirst($toPascalCase($column->getName())) . '(): int' . "\n";
        echo $FUNC_OPEN;
        echo '        return $this->requireId($this->' . $column->getName()
                . ", '" . $generator->getCamelcaseCapitalName() . ' ' . $column->getName() . "');" . "\n";
        echo $FUNC_CLOSE;
        echo "\n";
        echo $PUB_GET . ucfirst($toPascalCase($column->getName())) . '(): ?int' . "\n";
        echo $FUNC_OPEN;
        echo $RETURN_THIS . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        echo "\n";
        echo $EXT_MUT_FREE;
        echo $PUB_SET . ucfirst($toPascalCase($column->getName())) . '(int $' . $column->getName() . $VOID_EOL . "\n";
        echo $FUNC_OPEN;
        echo $THIS_ARROW . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        continue;
    }

    // date fields
    if ($column->getAbstractType() === $TYPE_DATE) {
        echo $PUB_GET . ucfirst($toPascalCase($column->getName()))
                . '(): \\DateTimeImmutable|string|null' . "\n";
        echo $FUNC_OPEN;
        echo $RETURN_THIS . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        echo "\n";
        echo $EXT_MUT_FREE;
        echo $PUB_SET . ucfirst($toPascalCase($column->getName()))
                . '(\\DateTimeImmutable|string|null $' . $column->getName() . $VOID_EOL . "\n";
        echo $FUNC_OPEN;
        echo $THIS_ARROW . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        continue;
    }

    // datetime fields
    if ($column->getAbstractType() === $TYPE_DATETIME) {
        echo $PUB_GET . ucfirst($toPascalCase($column->getName()))
                . '(): ?\\DateTimeImmutable' . "\n";
        echo $FUNC_OPEN;
        echo $RETURN_THIS . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        echo "\n";
        echo $EXT_MUT_FREE;
        echo $PUB_SET . ucfirst($toPascalCase($column->getName()))
                . '(\\DateTimeImmutable $' . $column->getName() . $VOID_EOL . "\n";
        echo $FUNC_OPEN;
        echo $THIS_ARROW . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        continue;
    }

    // boolean fields
    if ($column->getAbstractType() === $TYPE_BOOLEAN) {
        echo $PUB_GET . ucfirst($toPascalCase($column->getName()))
                . '(): ' . $questionmark . 'bool' . "\n";
        echo $FUNC_OPEN;
        echo $RETURN_THIS . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        echo "\n";
        echo $EXT_MUT_FREE;
        echo $PUB_SET . ucfirst($toPascalCase($column->getName()))
                . '(' . $questionmark . 'bool $' . $column->getName() . $VOID_EOL . "\n";
        echo $FUNC_OPEN;
        echo $THIS_ARROW . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
        echo $FUNC_CLOSE;
        continue;
    }

    // all other fields
    echo $PUB_GET . ucfirst($toPascalCase($column->getName()))
            . '(): ' . $questionmark . $column->getType() . "\n";
    echo $FUNC_OPEN;
    echo $RETURN_THIS . $column->getName() . ';' . "\n";
    echo $FUNC_CLOSE;
    echo "\n";
    echo $EXT_MUT_FREE;
    echo $PUB_SET . ucfirst($toPascalCase($column->getName()))
            . '(' . $column->getType() . ' $' . $column->getName() . $VOID_EOL . "\n";
    echo $FUNC_OPEN;
    echo $THIS_ARROW . $column->getName() . ' = $' . $column->getName() . ';' . "\n";
    echo $FUNC_CLOSE;
}
?>
}
