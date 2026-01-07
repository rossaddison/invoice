<?php

declare(strict_types=1);

namespace App\Invoice\UserCustom;

use App\Invoice\Entity\UserCustom;
use App\User\UserRepository as UR;

final readonly class UserCustomService
{
    public function __construct(
        private UserCustomRepository $repository,
        private UR $userR,
    ) {
    }

    private function persist(
        UserCustom $model,
        int $user_id
    ): void {
        $user = $this->userR->findById((string) $user_id);
        if ($user) {
            $model->setUser($user);
            $model->setUser_id((int) $user->getId());
        }
    }

    /**
     * @param UserCustom $model
     * @param UserCustomForm $form
     */
    public function saveUserCustom(
        UserCustom $model,
        UserCustomForm $form
    ): void {
        if (
            null !== $form->getUser_id()
            && null !== $form->getFieldid()
        ) {
            $this->persist($model, $form->getUser_id());
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
