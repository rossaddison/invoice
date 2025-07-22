<?php

declare(strict_types=1);

namespace App\Invoice\AllowanceCharge;

use App\Invoice\Entity\AllowanceCharge;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @template TEntity of AllowanceCharge
 *
 * @extends Select\Repository<TEntity>
 */
final class AllowanceChargeRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter, private readonly TranslatorInterface $translator)
    {
        parent::__construct($select);
    }

    /**
     * Get allowanceCharges  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
            ->load('tax_rate');

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
     * @psalm-param TEntity $allowanceCharge
     *
     * @throws \Throwable
     */
    public function save(array|AllowanceCharge|null $allowanceCharge): void
    {
        $this->entityWriter->write([$allowanceCharge]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|AllowanceCharge|null $allowanceCharge): void
    {
        $this->entityWriter->delete([$allowanceCharge]);
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
    public function repoAllowanceChargequery(string $id): ?AllowanceCharge
    {
        $query = $this->select()
            ->load('tax_rate')
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoCount(string $id): int
    {
        $query = $this->select()
            ->where(['id' => $id]);

        return $query->count();
    }

    public function optionsDataAllowanceCharges(): array
    {
        $optionsDataAllowanceCharges = [];
        $allowanceCharges            = $this->findAllPreloaded();
        /**
         * @var AllowanceCharge $allowanceCharge
         */
        foreach ($allowanceCharges as $allowanceCharge) {
            $key = $allowanceCharge->getId();
            $key ? ($optionsDataAllowanceCharges[$key] = ($allowanceCharge->getIdentifier()
            ? $this->translator->translate('allowance.or.charge.charge')
            : $this->translator->translate('allowance.or.charge.allowance')).
            '  '.$allowanceCharge->getReasonCode().
            ' '.
            $allowanceCharge->getReason()) : '';
        }

        return $optionsDataAllowanceCharges;
    }
}
