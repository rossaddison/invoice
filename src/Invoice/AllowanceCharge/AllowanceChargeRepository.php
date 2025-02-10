<?php

declare(strict_types=1);

namespace App\Invoice\AllowanceCharge;

use App\Invoice\Entity\AllowanceCharge;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of AllowanceCharge
 * @extends Select\Repository<TEntity>
 */
final class AllowanceChargeRepository extends Select\Repository
{
    private EntityWriter $entityWriter;
    private TranslatorInterface $translator;

    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, EntityWriter $entityWriter, TranslatorInterface $translator)
    {
        $this->entityWriter = $entityWriter;
        $this->translator = $translator;
        parent::__construct($select);
    }

    /**
     * Get allowanceCharges  without filter
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

    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param AllowanceCharge|array|null $allowanceCharge
     * @psalm-param TEntity $allowanceCharge
     * @throws Throwable
     */
    public function save(array|AllowanceCharge|null $allowanceCharge): void
    {
        $this->entityWriter->write([$allowanceCharge]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param AllowanceCharge|array|null $allowanceCharge

     * @throws Throwable
     */
    public function delete(array|AllowanceCharge|null $allowanceCharge): void
    {
        $this->entityWriter->delete([$allowanceCharge]);
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
     * @return AllowanceCharge|null
     */
    public function repoAllowanceChargequery(string $id): AllowanceCharge|null
    {
        $query = $this->select()
                      ->load('tax_rate')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $id
     * @return int
     */
    public function repoCount(string $id): int
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return $query->count();
    }

    public function optionsDataAllowanceCharges(): array
    {
        $optionsDataAllowanceCharges = [];
        $allowanceCharges = $this->findAllPreloaded();
        /**
         * @var AllowanceCharge $allowanceCharge
         */
        foreach ($allowanceCharges as $allowanceCharge) {
            $key = $allowanceCharge->getId();
            $key ? ($optionsDataAllowanceCharges[$key] = ($allowanceCharge->getIdentifier()
            ? $this->translator->translate('invoice.invoice.allowance.or.charge.charge')
            : $this->translator->translate('invoice.invoice.allowance.or.charge.allowance')) .
            '  ' . $allowanceCharge->getReasonCode() .
            ' ' .
            $allowanceCharge->getReason()) : '';
        }
        return $optionsDataAllowanceCharges;
    }
}
