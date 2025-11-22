<?php

declare(strict_types=1);

namespace App\Invoice\UserCustom;

use App\Invoice\Entity\UserCustom;

final readonly class UserCustomService
{
    public function __construct(private UserCustomRepository $repository)
    {
    }

    /**
     * @param UserCustom $model
     * @param UserCustomForm $form
     */
    public function saveUserCustom(UserCustom $model, UserCustomForm $form): void
    {
        if (null !== $form->getUser_id() && null !== $form->getFieldid()) {
            $model->setUser_id($form->getUser_id());
            $model->setFieldid($form->getFieldid());
            $model->setFieldvalue($form->getFieldvalue() ?? '');
            $this->repository->save($model);
        }
    }

    /**
     * @param UserCustom $model
     */
    public function deleteUserCustom(UserCustom $model): void
    {
        $this->repository->delete($model);
    }
}
