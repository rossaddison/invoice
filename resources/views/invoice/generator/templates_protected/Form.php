<?php

declare(strict_types=1);

/**
 * Related logic: see interal type e.g. appearing in mysql
 * Related logic: see abstract type e.g. doctrine/cycle appearing IN annotation
 * Related logic: see type e.g. doctrine/cycle appearing BELOW annotation.
 *
 * @var App\Invoice\Entity\Gentor $generator
 * @var Cycle\Database\Table      $orm_schema
 * @var array                     $relations
 */
echo "<?php\n";
?>

declare(strict_types=1);

namespace <?php echo $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>;

use App\Invoice\Entity\<?php echo $generator->getCamelcase_capital_name(); ?>;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
<?php
foreach ($orm_schema->getColumns() as $column) {
    if ('date' === $column->getAbstractType() || 'datetime' === $column->getAbstractType() || 'time' === $column->getAbstractType()) {
        echo 'use DateTime;'."\n";
        echo 'use DateTimeImmutable;'."\n";
        break;
    }
}
?>

final class <?php echo $generator->getCamelcase_capital_name(); ?>Form extends FormModel
{    
    <?php
    echo "\n";
foreach ($orm_schema->getColumns() as $column) {
    $init = '';
    switch ($column->getType()) {
        case 'string':
            // Display as two single quotes i.e. ''
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
                $init = $column->getDefaultValue();
                if (1 === $init) {
                    $init = 'true';
                }
                if (0 === $init) {
                    $init = 'false';
                }
            } else {
                $init = 'false';
            }
            break;
    }
    // Ignore the id field
    if ('primary' != $column->getAbstractType()) {
        if (('date' === $column->getAbstractType()) || ('datetime' === $column->getAbstractType())) {
            // mixed => null, or string, or DateTimeImmutable
            echo '    private mixed $'.$column->getName().' = '.(string) $init.';'."\n";
        } else {
            echo '    private ?'.$column->getType().' $'.$column->getName().' = '.(string) $init.';'."\n";
        }
    }
}
?>

    public function __construct(<?php echo $generator->getCamelcase_capital_name(); ?> $<?php echo $generator->getSmall_singular_name(); ?>) 
    {
    <?php
    echo "\n";
$bo = '';
/**
 * @var Cycle\Database\Schema\AbstractColumn $column
 */
foreach ($orm_schema->getColumns() as $column) {
    // Ignore the id field
    if ('primary' != $column->getAbstractType()) {
        $bo .= '        $this->'.$column->getName().' = $'.$generator->getSmall_singular_name().'->get'.ucfirst($column->getName())."();\n";
    }
}
echo rtrim($bo, ",\n")."\n";
?>
    }
    
    <?php
/**
 * @var Cycle\Database\Schema\AbstractColumn $column
 */
foreach ($orm_schema->getColumns() as $column) {
    if (('primary' != $column->getAbstractType()) && ('date' != $column->getAbstractType()) && ('time' != $column->getAbstractType())) {
        echo "\n";
        echo '    public function get'.ucfirst($column->getName()).'() : '.$column->getType().'|null'."\n";
        echo '    {'."\n";
        echo '      return $this->'.$column->getName().';'."\n";
        echo '    }'."\n";
    }
    if (('date' === $column->getAbstractType()) || ('datetime' === $column->getAbstractType())) {
        echo "\n";
        echo '    public function get'.ucfirst($column->getName()).'() : '.($column->isNullable() ? 'null|' : '').'string|DateTimeImmutable'."\n";
        echo '    {'."\n";
        echo '           **'."\n";
        echo '           * @var string|'.($column->isNullable() ? 'null|' : '').'DateTimeImmutable $this->'.$column->getName().''."\n";
        echo '           */'."\n";
        echo '          return $this->'.$column->getName().';'."\n";
        echo '    }'."\n";
    }
    if ('time' === $column->getAbstractType()) {
        echo "\n";
        echo '    public function get'.ucfirst($column->getName()).'() : ?\DateTime'."\n";
        echo '    {'."\n";
        echo '      return $this->'.$column->getName().'=new DateTime(date('."'".'H:i:s'."'".'));'."\n";
        echo '    }'."\n";
    }
}
echo "\n";
echo '    /**'."\n";
echo '     * @return string'."\n";
echo "     * @psalm-return ''\n";
echo '     */';
echo '#[\Override]';
echo "\n";
echo '    public function getFormName(): string'."\n";
echo '    {'."\n";
echo '      return '."''".';'."\n";
echo '    }'."\n";
echo "\n";
echo '}'."\n";
?>