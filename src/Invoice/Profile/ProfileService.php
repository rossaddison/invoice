<?php

declare(strict_types=1);

namespace App\Invoice\Profile;

use App\Invoice\Entity\Profile;

final readonly class ProfileService
{
    public function __construct(private ProfileRepository $repository)
    {
    }

    public function saveProfile(Profile $model, array $array): void
    {
        isset($array['company_id']) ? $model->setCompany_id((int) $array['company_id']) : '';
        $model->setCurrent('1' === $array['current'] ? 1 : 0);
        isset($array['mobile']) ? $model->setMobile((string) $array['mobile']) : '';
        isset($array['email']) ? $model->setEmail((string) $array['email']) : '';
        isset($array['description']) ? $model->setDescription((string) $array['description']) : '';

        $this->repository->save($model);
    }

    public function deleteProfile(Profile $model): void
    {
        $this->repository->delete($model);
    }
}
