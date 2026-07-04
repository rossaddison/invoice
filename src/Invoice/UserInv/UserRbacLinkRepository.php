<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Infrastructure\Persistence\UserRbacLink\UserRbacLink;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Rbac\AssignmentsStorageInterface;

/**
 * @template TEntity of UserRbacLink
 * @extends Select\Repository<TEntity>
 */
final class UserRbacLinkRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(
        Select $select,
        private readonly EntityWriter $entityWriter,
    ) {
        parent::__construct($select);
    }

    /**
     * @throws Throwable
     */
    public function save(UserRbacLink $entity): void
    {
        $this->entityWriter->write([$entity]);
    }

    /**
     * @throws Throwable
     */
    public function deleteByUserId(int $userId): void
    {
        $entity = $this->findByUserId($userId);
        if ($entity !== null) {
            $this->entityWriter->delete([$entity]);
        }
    }

    public function findByUserId(int $userId): ?UserRbacLink
    {
        return $this->select()
                    ->load('user_inv')
                    ->where(['user_inv.user_id' => $userId])
                    ->fetchOne();
    }

    /**
     * Insert or replace the bridge row for a user that has just been assigned an RBAC role.
     *
     * @throws Throwable
     */
    public function upsert(int $user_inv_id, int $userId): void
    {
        $this->deleteByUserId($userId);
        $this->save(new UserRbacLink(user_inv_id: $user_inv_id, rbac_user_id: (string) $userId));
    }

    public function count(): int
    {
        return $this->select()->count();
    }

    /**
     * Returns a map of every user_id that has a bridge row — O(1) lookup in views.
     *
     * @return array<int, true>
     */
    public function findLinkedUserIdMap(): array
    {
        $map = [];
        foreach ($this->select()->load('user_inv') as $link) {
            /** @var UserRbacLink $link */
            $uid = $link->getUserId();
            if ($uid !== null) {
                $map[$uid] = true;
            }
        }
        return $map;
    }

    /**
     * Backfill user_rbac_link from yii_rbac_assignment on first boot after table creation.
     * Uses AssignmentsStorageInterface from yiisoft/rbac-cycle-db — no raw SQL needed.
     * Called automatically from InvoiceController::index(); no console command required.
     *
     * @throws Throwable
     */
    public function syncIfEmpty(AssignmentsStorageInterface $storage, UserInvRepository $uiR): void
    {
        if ($this->count() > 0) {
            return;
        }
        foreach (array_keys($storage->getAll()) as $rbacUserId) {
            $userId = (int) $rbacUserId;
            $userInv = $uiR->repoUserInvUserIdquery($userId);
            if ($userId > 0 && ($userInv !== null)) {
                $this->upsert($userInv->reqId(), $userId);
            }
        }
    }
}
