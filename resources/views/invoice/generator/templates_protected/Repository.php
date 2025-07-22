<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Entity\Gentor $generator
 * @var array                     $relations
 */
echo "<?php\n";
?>

declare(strict_types=1); 

namespace <?php echo $generator->getNamespace_path().DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().';'."\n"; ?>

use <?php echo $generator->getNamespace_path().DIRECTORY_SEPARATOR.'Entity'.DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name().';'."\n"; ?>
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of <?php echo $generator->getCamelcase_capital_name()."\n"; ?>
 * @extends Select\Repository<TEntity>
 */
final class <?php echo $generator->getCamelcase_capital_name(); ?>Repository extends Select\Repository
{
private EntityWriter $entityWriter;
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter)
    {
        $this->entityWriter = $entityWriter;
        parent::__construct($select);
    }

    /**
     * Get <?php echo $generator->getSmall_singular_name(); ?>s  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        <?php if (!empty($relations)) {
            $echo = '$query = $this->select()';
            /**
             * @var App\Invoice\Entity\GentorRelation $relation
             */
            foreach ($relations as $relation) {
                $echo .= "->load('".($relation->getLowercase_name() ?? '#')."')";
            }
            echo $echo.';';
        } else {
            echo '$query = $this->select();';
        }
?>
        return $this->prepareDataReader($query);
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }
    
    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }    
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|<?php echo $generator->getCamelcase_capital_name(); ?>|null $<?php echo $generator->getSmall_singular_name()."\n"; ?>
     * @psalm-param TEntity $<?php echo $generator->getSmall_singular_name()."\n"; ?>
     * @throws Throwable 
     * @return void
     */
    public function save(array|<?php echo $generator->getCamelcase_capital_name(); ?>|null $<?php echo $generator->getSmall_singular_name(); ?>): void
    {
        $this->entityWriter->write([$<?php echo $generator->getSmall_singular_name(); ?>]);
    }
    
    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|<?php echo $generator->getCamelcase_capital_name(); ?>|null $<?php echo $generator->getSmall_singular_name()."\n"; ?>  
     * @throws Throwable 
     * @return void
     */
    public function delete(array|<?php echo $generator->getCamelcase_capital_name(); ?>|null $<?php echo $generator->getSmall_singular_name(); ?>): void
    {
        $this->entityWriter->delete([$<?php echo $generator->getSmall_singular_name(); ?>]);
    }
    
    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc'])
        );
    }    
    
    /**
     * @param string $id
     * @psalm-return TEntity|null
     * @return <?php echo $generator->getCamelcase_capital_name(); ?>|null
     */
    public function repo<?php echo $generator->getCamelcase_capital_name(); ?><?php echo !empty($relations) ? 'Loaded' : 'Unloaded'; ?>query(string $id): <?php echo $generator->getCamelcase_capital_name(); ?>|null
    {
        <?php if (!empty($relations)) {
            echo '$query = $this->select()';
            /**
             * @var App\Invoice\Entity\GentorRelation $relation
             */
            foreach ($relations as $relation) {
                echo "->load('".($relation->getLowercase_name() ?? '#')."')\n";
            }
            echo "->where(['id' =>".'$id]);';
        } else {
            echo '$query = $this->select()'."\n";
            echo "->where(['id' =>".'$id]);';
        }
?>
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * @param string $id
     * @return int
     */
    public function repoCount(string $id) : int {
        $query = $this->select()
                      ->where(['id' => $id]);
        return $query->count();
    }   
}