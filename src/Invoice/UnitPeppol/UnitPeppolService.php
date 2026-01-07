<?php

declare(strict_types=1);

namespace App\Invoice\UnitPeppol;

use App\Invoice\Entity\UnitPeppol;
use App\Invoice\Unit\UnitRepository as UR;

final readonly class UnitPeppolService
{
    public function __construct(
        private UnitPeppolRepository $repository,
        private UR $uR,
    ) {
    }

    private function persist(UnitPeppol $model, array $array): void
    {
        $unit = $this->uR->repoUnitquery(
            (string) $array['unit_id']
        );
        if ($unit) {
            $model->setUnit($unit);
            $model->setUnit_id((int) $unit->getUnit_id());
        }
    }

    public function saveUnitPeppol(
        UnitPeppol $model,
        array $array
    ): void {
        isset($array['id'])
            ? $model->setId((int) $array['id'])
            : '';
        $this->persist($model, $array);
        isset($array['code'])
            ? $model->setCode((string) $array['code'])
            : '';
        isset($array['name'])
            ? $model->setName((string) $array['name'])
            : '';
        isset($array['description'])
            ? $model->setDescription((string) $array['description'])
            : '';
        $this->repository->save($model);
    }

    public function deleteUnitPeppol(UnitPeppol $model): void
    {
        $this->repository->delete($model);
    }
}
