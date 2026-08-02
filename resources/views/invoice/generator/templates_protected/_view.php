<?php

declare(strict_types=1);

/**
 * Related logic: see GeneratorController function form
 * @var App\Infrastructure\Persistence\Gentor\Gentor $generator
 * @var Cycle\Database\Table $orm_schema
 * @var array $relations
 */

echo "<?php\n";
?>

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\<?= $generator->getCamelcaseCapitalName(); ?>\<?= $generator->getCamelcaseCapitalName(); ?>Form $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string, list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $<?= $generator->getSmallSingularName(); ?>
 */

<?php
    echo "?>\n";
echo "\n";
echo "// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.\n";
echo '$selectLabel = static function (array $optionsData, int|string|null $value): string {' . "\n";
echo "    if (\$value === null || \$value === '') {\n";
echo "        return '';\n";
echo "    }\n";
echo '    $key = (string) $value;' . "\n";
echo '    /** @var string|array|null $label */' . "\n";
echo '    $label = $optionsData[$key] ?? null;' . "\n";
echo '    return is_string($label) ? $label : $key;' . "\n";
echo "};\n";
echo "?>\n";
echo "\n";
echo "<?= Html::openTag('div',['class'=>'container-fluid py-3']); ?>" . "\n";
echo "<?= Html::openTag('div',['class'=>'row justify-content-center']); ?>" . "\n";
echo "<?= Html::openTag('div',['class'=>'col-12 col-lg-10 col-xl-10']); ?>" . "\n";
echo "<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>";
echo "<?= Html::openTag('div',['class'=>'card-body']); ?>" . "\n";

echo "<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>" . "\n";
echo '    <?= Html::encode($title); ?>' . "\n";
echo "<?= Html::closeTag('h1'); ?>" . "\n";
echo '<?= $button::back(); ?>' . "\n";
echo "<?= Html::openTag('div'); ?>" . "\n";

/**
 * @var App\Infrastructure\Persistence\GentorRelation\GentorRelation $relation
 */
foreach ($relations as $relation) {
    $relationName = $relation->getLowercaseName() ?? '#';
    echo '    <?php ReadOnlyField::render(' . "\n";
    echo '        $translator->translate(\'' . $relationName . '\'),' . "\n";
    echo '        $selectLabel($' . $relationName . 's, $form->get' . ucfirst($relationName) . '_id()),' . "\n";
    echo '    ); ?>' . "\n";
}

// exclude relations or fields ending in '_id'
foreach ($orm_schema->getColumns() as $column) {
    /**
     * If the column is not a relation column ending in _id
     * Related logic: see src/Invoice/Entity/Product
     * Related logic: see #[Column(type: 'integer(11)', nullable: true)]
     * Related logic: see private ?int $family_id = null;
     */
    if (substr($column->getName(), -3) <> '_id') {
        /**
         * Related logic: see src/Invoice/Entity/Client
         * Related logic: see #[Column(type: 'bool', default: false)]
         * Related logic: see private bool $client_active = false;
         */
        if (($column->getType() === 'bool') && ($column->getAbstractType() === 'bool')) {
            echo '    <?php ReadOnlyField::render(' . "\n";
            echo '        $translator->translate(\'' . $column->getName() . '\'),' . "\n";
            echo '        $translator->translate($form->get' . ucfirst($column->getName()) . '() === true ? \'yes\' : \'no\'),' . "\n";
            echo '    ); ?>' . "\n";
        }
        /**
         * Related logic: see src/Invoice/Entity/ClientNote
         * Related logic: see #[Column(type: 'date', nullable: false)]
         * Related logic: see private mixed $date_note;
         */
        if (($column->getType() === 'mixed') && (($column->getAbstractType() === 'date'))) {
            echo '    <?php ReadOnlyField::render(' . "\n";
            echo '        $translator->translate(\'' . $column->getName() . '\'),' . "\n";
            echo '        $form->get' . ucfirst($column->getName()) . '() instanceof \DateTimeImmutable ? ($form->get' . ucfirst($column->getName()) . '())->format(\'Y-m-d\') : \'\',' . "\n";
            echo '    ); ?>' . "\n";
        }
        /**
         * Related logic: see src/Invoice/Entity/Product
         * Related logic: see #[Column(type: 'decimal(20,2)', nullable: true)]
         * Related logic: see private ?float $purchase_price = null;
         */
        if (($column->getType() === 'float') && ($column->getAbstractType() === 'decimal')) {
            echo '    <?php ReadOnlyField::render(' . "\n";
            echo '        $translator->translate(\'' . $column->getName() . '\'),' . "\n";
            echo '        $s->formatAmount((float) ($form->get' . ucfirst($column->getName()) . '() ?? 0.00)),' . "\n";
            echo '    ); ?>' . "\n";
        }
        /**
         * Related logic: see src/Invoice/Entity/ClientNote
         * Related logic: see #[Column(type: 'longText', nullable:false)]
         * Related logic: see private string $note =  '';
         */
        if (($column->getType() === 'string') && ($column->getAbstractType() <> 'date')) {
            echo '    <?php ReadOnlyField::render(' . "\n";
            echo '        $translator->translate(\'' . $column->getName() . '\'),' . "\n";
            echo '        $form->get' . ucfirst($column->getName()) . '(),' . "\n";
            echo '    ); ?>' . "\n";
        }

        if (($column->getType() === 'int') && ($column->getAbstractType() <> 'date') && ($column->getAbstractType() <> 'primary')) {
            echo '    <?php ReadOnlyField::render(' . "\n";
            echo '        $translator->translate(\'' . $column->getName() . '\'),' . "\n";
            echo '        $form->get' . ucfirst($column->getName()) . '() !== null ? (string) $form->get' . ucfirst($column->getName()) . '() : \'\',' . "\n";
            echo '    ); ?>' . "\n";
        }
    } // if substr
} // foreach columns

echo "<?= Html::closeTag('div'); ?>" . "\n";

echo '<?= Html::closeTag(\'div\'); ?>' . "\n";
echo '<?= Html::closeTag(\'div\'); ?>' . "\n";
echo '<?= Html::closeTag(\'div\'); ?>' . "\n";
echo '<?= Html::closeTag(\'div\'); ?>' . "\n";
echo '<?= Html::closeTag(\'div\'); ?>' . "\n";
