<?php

declare(strict_types=1);

/**
 * @see GeneratorController function quick_view_schema
 * @var Cycle\Database\TableInterface[] $tables
 * @var bool $isGuest
 * @var string $alerts
 */

?>
<?php

$alerts;

if (!$isGuest) {
    /**
     * @var Cycle\Database\TableInterface $table
     */
    foreach ($tables as $table) {
        echo '<div>';
        echo '<br>';
        echo '<h1>'.$table->getName().'</h1>';
        echo '<table class="table">';
        echo '<thead>';
        echo '<tr><th scope="row">Name</th><th scope="row">Internal Type<th scope="row">Abstract Type</th><th scope="row">Type</th><th scope="row">Has Def Val</th><th scope="row">Def Value</th><th scope="row">Size</th><th scope="row">Precision</th><th scope="row">Scale</th><th scope="row">Nullable</th><th scope="row">Enums</th><th scope="row">Constraints</th></tr>';
        echo '</thead>';
        echo '<tbody>';
        /**
         * @var Cycle\Database\Schema\AbstractColumn $column
         */
        foreach ($table->getColumns() as $column) {
            echo '<tr>';
            echo "<td>{$column->getName()}</td>";
            echo "<td>{$column->getInternalType()}</td>";
            echo "<td>{$column->getAbstractType()}</td>";
            echo "<td>{$column->getType()}</td>";          // PHP type: int, float, string, bool
            echo "<td>{$column->hasDefaultValue()}</td>";
            echo "<td>{$column->getDefaultValue()}</td>";
            echo "<td>{$column->getSize()}</td>";
            echo "<td>{$column->getPrecision()}</td>";     // Decimals only
            echo "<td>{$column->getScale()}</td><td>{$column->isNullable()}</td>";
            $temp = '';

            /**
             * @var array $enumValues
             * @var string $enum
             */
            foreach (($enumValues = $column->getEnumValues()) as $enum) {
                $temp = $enum;
                $temp .= " ".$temp;
            }
            echo '<td>'.$temp.'</td>';
            $var = '';

            /**
             * @var array $columnConstraints
             * @var string $constraint
             */
            foreach (($columnConstraints = $column->getConstraints()) as $constraint) {
                $var = $constraint;
                $var .= " ".$var;
            }
            echo '<td>'.$temp.'</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }
}
