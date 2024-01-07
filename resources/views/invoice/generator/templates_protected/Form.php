<?php         
   //interal type = eg. appearing in mysql
   //abstract type = eg. doctrine/cycle appearing IN annotation
   //type = eg. doctrine/cycle appearing BELOW annotation
   echo "<?php\n";             
?>

declare(strict_types=1);

namespace <?= $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(); ?>;

use App\Invoice\Entity\<?= $generator->getCamelcase_capital_name();?>;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;
<?php
foreach ($orm_schema->getColumns() as $column) {
    if ($column->getAbstractType() === 'date' || $column->getAbstractType() === 'datetime' || $column->getAbstractType() === 'time' ) {
        echo 'use DateTime;'."\n";
        echo 'use DateTimeImmutable;'."\n";
        break;
    }
}         
?>

final class <?= $generator->getCamelcase_capital_name();?>Form extends FormModel
{    
    <?php
    echo "\n";
    foreach ($orm_schema->getColumns() as $column) {
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
                {
                   if ($column->hasDefaultValue()) {
                      $init  = $column->getDefaultValue();
                      if ($init === 1) {$init = 'true';}
                      if ($init === 0) {$init = 'false';}
                      break;
                   }
                   else {
                       $init = 'false';
                       break;
                   }
                }
        }
        if ($column->getAbstractType() <> 'primary') {
           echo '    private ?'.$column->getType()." $".$column->getName(). '='.$init.';'."\n";
        }
    }
    ?>

    public function __construct(<?= $generator->getCamelcase_capital_name();?> $<?= $generator->getSmall_singular_name();?>) 
    {
    <?php
        echo "\n";
        $bo = '';
        foreach ($orm_schema->getColumns() as $column) {
            $bo .= '        $this->'.$column->getName()." = $".$generator->getSmall_singular_name()."->get".ucfirst($column->getName())."();\n";
        }
        echo rtrim($bo,",\n")."\n";        
    ?>
    }
    
    <?php
    foreach ($orm_schema->getColumns() as $column) {
      if (($column->getAbstractType() <> 'primary') && ($column->getAbstractType() <> 'date') && ($column->getAbstractType() <> 'time')) {
        echo "\n";
        echo '    public function get'.ucfirst($column->getName()).'() : '.$column->getType().'|null'."\n";
        echo '    {'."\n";
        echo '      return $this->'.$column->getName().';'."\n";
        echo '    }'."\n";
      }
      if ($column->getAbstractType() === 'date') {
        echo "\n";
        echo '    public function get'.ucfirst($column->getName()).'() : '.($column->isNullable() ? 'null|' : ''). 'string|DateTimeImmutable'."\n";
        echo '    {'."\n";
        echo '           **'."\n";
        echo '           * @var string|'.($column->isNullable() ? 'null|' : '').'DateTimeImmutable $this->'. $column->getName(). ''."\n";
        echo '           */'."\n";
        echo '          return $this->'.$column->getName().';'."\n"; 
        echo '    }'."\n";
      }
      if ($column->getAbstractType() === 'time') {
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
    echo "     * @psalm-return ''"."\n";
    echo '     */';
    echo "\n";
    echo '    public function getFormName(): string'."\n";
    echo '    {'."\n";
    echo '      return '."''".';'."\n";
    echo '    }'."\n";
    echo "\n";
    echo '}'."\n";
 ?>
