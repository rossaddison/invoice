<?php

declare(strict_types=1);

namespace App\Invoice\Qa;

use App\Invoice\Entity\Qa;

final readonly class QaService
{
    public function __construct(private QaRepository $repository)
    {
    }

    /**
     * @param Qa $model
     * @param array $array
     */
    public function saveQa(Qa $model, array $array): void
    {
        $model->setActive($array['active'] === '1' ? 1 : 0);
        isset($array['question']) ?
            $model->setQuestion((string) $array['question']) : '';
        isset($array['answer']) ?
            $model->setAnswer((string) $array['answer']) : '';
        $this->repository->save($model);
    }

    /**
     * @param array|Qa|null $model
     */
    public function deleteQa(array|Qa|null $model): void
    {
        $this->repository->delete($model);
    }
}
