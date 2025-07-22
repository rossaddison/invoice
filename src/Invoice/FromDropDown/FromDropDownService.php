<?php

declare(strict_types=1);

namespace App\Invoice\FromDropDown;

use App\Invoice\Entity\FromDropDown;

final readonly class FromDropDownService
{
    public function __construct(private FromDropDownRepository $repository)
    {
    }

    public function saveFromDropDown(FromDropDown $model, array $array): void
    {
        isset($array['email']) ? $model->setEmail((string) $array['email']) : '';
        $model->setInclude('1' === $array['include'] ? true : false);
        $model->setDefault_email('1' === $array['default_email'] ? true : false);
        $this->repository->save($model);
    }

    public function deleteFromDropDown(FromDropDown $model): void
    {
        $this->repository->delete($model);
    }
}
