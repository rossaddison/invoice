<?php

declare(strict_types=1);

namespace App\Invoice\Task;

use App\Invoice\Entity\Task;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * @template TEntity of Task
 *
 * @extends Select\Repository<TEntity>
 */
final class TaskRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter, private readonly Translator $translator)
    {
        parent::__construct($select);
    }

    /**
     * Get tasks  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()->load('tax_rate');

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
    public function save(array|Task|null $task): void
    {
        $this->entityWriter->write([$task]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|Task|null $task): void
    {
        $this->entityWriter->delete([$task]);
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
    public function repoTaskquery(string $id): ?Task
    {
        $query = $this->select()->load('tax_rate')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    /**
     * Get tasks  with filter.
     *
     * @psalm-return EntityReader
     */
    public function repoTaskStatusquery(int $status): EntityReader
    {
        $query = $this->select()->load('tax_rate')
            ->where(['status' => $status]);

        return $this->prepareDataReader($query);
    }

    public function findinTasks(array $task_ids): EntityReader
    {
        $query = $this->select()
            ->where(['id' => ['in' => new Parameter($task_ids)]]);

        return $this->prepareDataReader($query);
    }

    public function repoCount(string $task_id): int
    {
        return $this->select()
            ->where(['id' => $task_id])
            ->count();
    }

    public function getTaskStatuses(Translator $translator): array
    {
        return [
            '1' => [
                'label' => $translator->translate('not.started'),
                'class' => 'draft',
            ],
            '2' => [
                'label' => $translator->translate('in.progress'),
                'class' => 'viewed',
            ],
            '3' => [
                'label' => $translator->translate('complete'),
                'class' => 'sent',
            ],
            '4' => [
                'label' => $translator->translate('invoiced'),
                'class' => 'paid',
            ],
        ];
    }

    public function getSpecificStatusArrayLabel(string $key): string
    {
        $statuses_array = $this->getTaskStatuses($this->translator);

        /*
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['label']
         */
        return $statuses_array[$key]['label'];
    }

    public function getSpecificStatusArrayClass(int $status): string
    {
        $statuses_array = $this->getTaskStatuses($this->translator);

        /*
         * @var array $statuses_array[$status]
         * @var string $statuses_array[$status]['class']
         */
        return $statuses_array[$status]['class'];
    }
}
