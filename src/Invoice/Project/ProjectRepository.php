<?php

declare(strict_types=1);

namespace App\Invoice\Project;

use App\Invoice\Entity\Project;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of Project
 *
 * @extends Select\Repository<TEntity>
 */
final class ProjectRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get projects  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('client');

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

    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function save(array|Project|null $project): void
    {
        $this->entityWriter->write([$project]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|Project|null $project): void
    {
        $this->entityWriter->delete([$project]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoProjectquery(string $id): ?Project
    {
        $query = $this->select()->load('client')->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function count(string $project_id): int
    {
        return $this->select()
            ->where(['id' => $project_id])
            ->count();
    }

    public function optionsDataProjects(): array
    {
        $optionsDataProjects = [];
        /**
         * @var Project $project
         */
        foreach ($this->findAllPreloaded() as $project) {
            $optionsDataProjects[$project->getId()] = $project->getName();
        }

        return $optionsDataProjects;
    }
}
