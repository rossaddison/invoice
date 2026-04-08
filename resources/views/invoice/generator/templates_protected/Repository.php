<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Entity\Gentor $generator
 * @var array $relations
 */

echo "<?php\n";
?>

declare(strict_types=1);

namespace <?= $generator->getNamespacePath() . DIRECTORY_SEPARATOR . $generator->getCamelcaseCapitalName() . ';' . "\n"; ?>

use <?= $generator->getNamespacePath() . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR . $generator->getCamelcaseCapitalName() . ';' . "\n"; ?>
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of <?php echo $generator->getCamelcaseCapitalName() . "\n"; ?>
 * @extends Select\Repository<TEntity>
 */
final class <?= $generator->getCamelcaseCapitalName(); ?>Repository extends Select\Repository
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
     * Get <?= $generator->getSmallSingularName(); ?>s  without filter
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
                $echo .= "->load('" . ($relation->getLowercaseName() ?? '#') . "')";
            }
            echo $echo . ";";
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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|<?= $generator->getCamelcaseCapitalName() ?>|null $<?php echo $generator->getSmallSingularName() . "\n" ?>
     * @psalm-param TEntity $<?php echo $generator->getSmallSingularName() . "\n" ?>
     * @throws Throwable
     * @return void
     */
    public function save(array|<?= $generator->getCamelcaseCapitalName() ?>|null $<?= $generator->getSmallSingularName(); ?>): void
    {
        $this->entityWriter->write([$<?= $generator->getSmallSingularName(); ?>]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|<?= $generator->getCamelcaseCapitalName(); ?>|null $<?= $generator->getSmallSingularName() . "\n" ?>
     * @throws Throwable
     * @return void
     */
    public function delete(array|<?= $generator->getCamelcaseCapitalName(); ?>|null $<?= $generator->getSmallSingularName(); ?>): void
    {
        $this->entityWriter->delete([$<?= $generator->getSmallSingularName(); ?>]);
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
     * @return <?= $generator->getCamelcaseCapitalName(); ?>|null
     */
    public function repo<?= $generator->getCamelcaseCapitalName(); ?><?= !empty($relations) ? 'Loaded' : 'Unloaded' ?>query(string $id): <?= $generator->getCamelcaseCapitalName(); ?>|null
    {
        <?php if (!empty($relations)) {
            echo '$query = $this->select()';
            /**
             * @var App\Invoice\Entity\GentorRelation $relation
             */
            foreach ($relations as $relation) {
                echo "->load('" . ($relation->getLowercaseName() ?? '#') . "')" . "\n";
            }
            echo "->where(['id' =>" . '$id]);';
        } else {
            echo '$query = $this->select()' . "\n";
            echo "->where(['id' =>" . '$id]);';
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