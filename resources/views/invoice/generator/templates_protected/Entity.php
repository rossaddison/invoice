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
$MUT_FREE = '    /** @psalm-mutation-free */' . "\n";
// Column names are snake_case (DB convention); accessor method names should
// be PascalCase/camelCase (PHP convention) -- e.g. curr_code -> CurrCode, so
// getCurr_code()/setCurr_code() become getCurrCode()/setCurrCode(). The
// underlying property/parameter name is left as-is (it must match the real
// column name for Cycle's annotation mapping). Same helper already used by
// _view.php -- applied here too, and in Form.php/Service.php/_form.php, for
// consistency across every generated file for this entity.
$toPascalCase = static fn (string $name): string => str_replace('_', '', ucwords($name, '_'));
// Shared by the primary-key detection below and the relation-key
// resolution further down. Uses TableInterface::getPrimaryKeys() -- the
// actual DB-reflected primary key, independent of Cycle's "abstract type"
// classification -- NOT a column-walk checking getAbstractType() ===
// 'primary'/'bigPrimary'. That classification is reserved for
// auto-increment integer columns: confirmed directly (dumping every
// column's abstract type for fa_spike.currencies) that a plain,
// non-auto-increment string primary key like curr_abrev comes back as
// abstract type 'string', with no primary-key marker on the column at
// all, so the column-walk silently found nothing for tables like that.
// getPrimaryKeys() has no such blind spot; it's generic to any column
// type. Returns null only if the table genuinely has no primary key.
// Composite primary keys (rare in this schema) return multiple names;
// this generator doesn't support those yet, so only the first is used --
// callers needing to know about that should check count() themselves.
$findPrimaryColumnNames = static function (\Cycle\Database\TableInterface $table): array {
    return $table->getPrimaryKeys();
};
// Detect the entity's own primary key up front -- needed below both for
// the #[Column]/property emission further down AND for deciding whether
// the RequireId trait/import are even used (see $usesRequireId), which
// has to be known before the class's `use` statements are emitted.
$sourcePrimaryKeys = $findPrimaryColumnNames($orm_schema);
if (count($sourcePrimaryKeys) === 0) {
    throw new \RuntimeException(
        "Cannot generate entity '" . $generator->getCamelcaseCapitalName() . "': table '"
            . $generator->getPreEntityTable() . "' has no detectable primary key."
    );
}
if (count($sourcePrimaryKeys) > 1) {
    throw new \RuntimeException(
        "Cannot generate entity '" . $generator->getCamelcaseCapitalName() . "': table '"
            . $generator->getPreEntityTable() . "' has a composite primary key ("
            . implode(', ', $sourcePrimaryKeys) . "), which this generator doesn't support yet."
    );
}
$primaryColumnName = $sourcePrimaryKeys[0];
$primaryType = $TYPE_PRIMARY;
$primaryColumnIsAutoIncrementInt = false;
foreach ($orm_schema->getColumns() as $col) {
    if ($col->getName() !== $primaryColumnName) {
        continue;
    }
    if ($col->getAbstractType() === $TYPE_PRIMARY || $col->getAbstractType() === $TYPE_BIG_PRIMARY) {
        $primaryColumnIsAutoIncrementInt = true;
    }
    if ($col->getAbstractType() === $TYPE_BIG_PRIMARY) {
        $primaryType = 'bigPrimary';
    }
}
// A natural-key entity (e.g. currencies.curr_abrev) is known up front and
// never DB-assigned, so it gets no $id/reqId()/hasIdentity()/setId()
// machinery at all -- it's just a regular column below. RequireId's
// requireId() is only actually called from reqId() (auto-increment PK)
// and from req<Column>Id() helpers generated further down for int-typed
// "_id" FK columns, so skip emitting the trait/import entirely when
// neither applies, rather than leaving an always-unused import behind.
$usesRequireId = $primaryColumnIsAutoIncrementInt;
foreach ($orm_schema->getColumns() as $col) {
    if ($col->getName() === $primaryColumnName && $primaryColumnIsAutoIncrementInt) {
        continue;
    }
    if ($col->getType() === 'int' && substr($col->getName(), -3) === '_id') {
        $usesRequireId = true;
    }
}
// Resolve every relation's target ONCE, up front -- shared by the `use`
// import statement below AND the #[BelongsTo] key resolution further
// down in the class body, which used to redo this same lookup. Keeping
// it in one place also fixes a real bug: the import statement used to
// hardcode App\Infrastructure\Persistence as every relation target's
// namespace root, correct only when the target happens to live in the
// same namespace tree as the entity being generated -- wrong the moment
// it doesn't, found generating FrontAccounting's Supplier -> Currencie
// relation, which spans two different bounded-context namespaces
// (Purchasing and Currency). The target's real namespace now comes from
// its own Gentor record, same as everything else resolved here.
/**
 * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
 */
$relationTargets = [];
foreach ($relations as $relation) {
    $relationLowercaseName = $relation->getLowercaseName() ?? '';
    $relationCamelcaseName = $relation->getCamelcaseName() ?? '';
    if ($dbal === null || $gr === null) {
        throw new \RuntimeException(
            "Cannot generate relation '$relationLowercaseName': this template needs \$dbal/\$gr to resolve " .
            "the target's real namespace, table and FK column (see GeneratorCodeController::entity())."
        );
    }
    $targetGentor = $gr->findByCamelcaseCapitalName($relationCamelcaseName);
    if ($targetGentor === null) {
        throw new \RuntimeException(
            "Cannot generate relation '$relationLowercaseName': no Gentor record found for target entity " .
            "'$relationCamelcaseName'. Create one first -- it's how this generator discovers the target's " .
            'real namespace/table/primary key column.'
        );
    }
    $targetNamespacePath = $targetGentor->getNamespacePath();
    if ($targetNamespacePath === '') {
        throw new \RuntimeException(
            "Cannot generate relation '$relationLowercaseName': the Gentor record for '$relationCamelcaseName' " .
            'has no namespace_path set.'
        );
    }
    $targetTable = $targetGentor->getPreEntityTable();
    if ($targetTable === '') {
        throw new \RuntimeException(
            "Cannot generate relation '$relationLowercaseName': the Gentor record for '$relationCamelcaseName' " .
            'has no pre_entity_table set.'
        );
    }
    $targetSchema = $dbal->database('default')->table($targetTable);
    $targetPrimaryKeys = $findPrimaryColumnNames($targetSchema);
    if (count($targetPrimaryKeys) === 0) {
        throw new \RuntimeException(
            "Cannot generate relation '$relationLowercaseName': target table '$targetTable' has no detectable " .
            'primary key.'
        );
    }
    if (count($targetPrimaryKeys) > 1) {
        throw new \RuntimeException(
            "Cannot generate relation '$relationLowercaseName': target table '$targetTable' has a composite " .
            'primary key (' . implode(', ', $targetPrimaryKeys) . '), which this generator doesn\'t support yet.'
        );
    }
    $outerKey = $targetPrimaryKeys[0];
    $outerKeyIsAutoIncrementInt = false;
    foreach ($targetSchema->getColumns() as $col) {
        if ($col->getName() === $outerKey
                && ($col->getAbstractType() === $TYPE_PRIMARY || $col->getAbstractType() === $TYPE_BIG_PRIMARY)) {
            $outerKeyIsAutoIncrementInt = true;
        }
    }
    $relationTargets[$relationLowercaseName] = [
        'camelcaseName' => $relationCamelcaseName,
        'namespacePath' => $targetNamespacePath,
        'outerKey' => $outerKey,
        'outerKeyIsAutoIncrementInt' => $outerKeyIsAutoIncrementInt,
    ];
}
?>

declare(strict_types=1);

namespace App\Infrastructure\Persistence\<?= $generator->getCamelcaseCapitalName(); ?>;

<?php
foreach ($relations as $relation) {
    $t = $relationTargets[$relation->getLowercaseName() ?? ''];
    echo 'use ' . $t['namespacePath'] . '\\'
        . $t['camelcaseName'] . '\\'
        . $t['camelcaseName'] . ';' . "\n";
}
?>
<?php if ($usesRequireId) : ?>
use App\Infrastructure\Persistence\Trait\RequireId;
<?php endif; ?>
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
<?php if (!$primaryColumnIsAutoIncrementInt) : ?>
use Cycle\Annotated\Annotation\Table\PrimaryKey;
<?php endif; ?>
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
    // A natural/string PK (e.g. currencies.curr_abrev) has no abstract
    // 'primary'/'bigPrimary' column type for Cycle to detect on its own --
    // that classification is reserved for auto-increment surrogate keys
    // (see $findPrimaryColumnNames's own comment above). Without this
    // explicit #[PrimaryKey] index annotation, Cycle\Annotated\MergeIndexes
    // (part of the real schema-compile pipeline, confirmed via
    // yii-cycle's MetadataSchemaConveyor) never registers a primary key
    // for this entity at all, and any relation targeting it fails to
    // compile with "Entity `<role>` must have defined primary key" --
    // confirmed by generating this exact failure against currencies
    // before adding this.
    if (!$primaryColumnIsAutoIncrementInt) {
        echo "#[PrimaryKey(columns: ['" . $primaryColumnName . "'])]" . "\n";
    }
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
<?php if ($usesRequireId) : ?>
    use RequireId;

<?php endif; ?>
<?php
    /**
     * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
     */
    foreach ($relations as $relation) {
        $relationLowercaseName = $relation->getLowercaseName() ?? '';
        $t = $relationTargets[$relationLowercaseName];
        $outerKey = $t['outerKey'];

        // The conventional '{relation}_id' guess (Cycle's own default --
        // see cycle/schema-builder's BelongsTo.php) only ever made sense
        // for an auto-increment int target (this generator's own id-
        // always-named-"id" convention). For a natural/string target key
        // (e.g. currencies.curr_abrev), the source FK column name doesn't
        // follow any derivable convention in this schema (it's curr_code,
        // not curr_abrev, not currencie_id), so it must be stated
        // explicitly via GentorRelation::foreign_key_column. Fail loudly
        // if it's needed but wasn't set, rather than ever guessing wrong.
        $explicitForeignKeyColumn = $relation->getForeignKeyColumn();
        if ($explicitForeignKeyColumn !== null && $explicitForeignKeyColumn !== '') {
            $innerKey = $explicitForeignKeyColumn;
        } elseif ($t['outerKeyIsAutoIncrementInt']) {
            $innerKey = $relationLowercaseName . '_id';
        } else {
            throw new \RuntimeException(
                "Cannot generate relation '$relationLowercaseName': target's primary key ('$outerKey') is not " .
                "an auto-increment integer, so the conventional '{relation}_id' naming doesn't apply. Set this " .
                "GentorRelation's foreign_key_column to the real source FK column name on " .
                "'" . $generator->getPreEntityTable() . "' for this relation."
            );
        }
        if (!$orm_schema->hasColumn($innerKey)) {
            throw new \RuntimeException(
                "Cannot generate relation '$relationLowercaseName': expected a '$innerKey' column on " .
                "'" . $generator->getPreEntityTable() . "' but it doesn't exist. " .
                ($explicitForeignKeyColumn !== null && $explicitForeignKeyColumn !== ''
                    ? 'Check foreign_key_column on this GentorRelation.'
                    : "This generator can't yet infer a differently-named foreign key column automatically -- " .
                        "rename the relation's lowercasename to match the real column, set foreign_key_column " .
                        'explicitly, or wire this one relation up by hand.')
            );
        }

        echo '    #[BelongsTo(target:' . $t['camelcaseName']
                . "::class, innerKey: '" . $innerKey . "', outerKey: '" . $outerKey . "'"
                . ', nullable: false, fkAction:' . "'NO ACTION'" . ')]'
                . "\n";
        echo '    private ?' . $t['camelcaseName'] . ' $'
                . $relationLowercaseName . ' = null;' . "\n";
        echo "\n";
    }
    // $primaryColumnName/$primaryType/$primaryColumnIsAutoIncrementInt were
    // already resolved up top (needed there to decide $usesRequireId before
    // the class's `use` statements). Auto-increment int PKs get the
    // dedicated $id property + reqId()/hasIdentity()/setId() shape below,
    // since the real value is unknown until Cycle assigns it post-insert.
    // A natural/string PK (e.g.
    // currencies.curr_abrev) is known up front and never DB-assigned, so
    // it's deliberately NOT special-cased here -- it flows through the
    // same per-column loop as every other field below (real column name
    // as the property name, ordinary getter/setter), emitting nothing in
    // this block at all.
    if ($primaryColumnIsAutoIncrementInt) {
        echo "    #[Column(type: '" . $primaryType . "', name: '" . $primaryColumnName . "')]" . "\n";
        echo '    private ?int $id = null;' . "\n";
        echo "\n";
    }
?>
    /** @psalm-mutation-free */
    public function __construct(
<?php
/**
 * @var Cycle\Database\Schema\AbstractColumn $column
 */
foreach ($orm_schema->getColumns() as $column) {
    // The auto-increment PK's $id is declared separately above and
    // skipped here; a natural-key PK is NOT skipped -- it's generated
    // below like any other column, using its real column name.
    if ($column->getName() === $primaryColumnName && $primaryColumnIsAutoIncrementInt) {
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
<?php if ($primaryColumnIsAutoIncrementInt) : ?>

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
<?php endif; ?>

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
            . ($relation->getLowercaseName() ?? '#') . $VOID_EOL . "\n";
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
    // auto-increment PK already handled by reqId/hasIdentity/setId;
    // a natural-key PK falls through and gets an ordinary getter/setter
    // like any other column, via the branches below.
    if ($column->getName() === $primaryColumnName && $primaryColumnIsAutoIncrementInt) {
        continue;
    }

    $questionmark = $column->isNullable() ? '?' : '';

    echo "\n";

    // FK _id fields: req + get + set. Guarded on real int type, not just
    // the "_id" name suffix -- FrontAccounting has at least one natural
    // string key that happens to be named that way (stock_master.stock_id
    // is a varchar product code, not an auto-increment int), which would
    // otherwise get a wrong int-typed requireId()-based helper here.
    if ($column->getType() === 'int' && substr($column->getName(), -3) === '_id') {
        echo $MUT_FREE;
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
