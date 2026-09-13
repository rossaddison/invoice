<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvAuditLog;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAuditLog\InvAuditLog;
use App\Infrastructure\Persistence\User\User;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Plain accessor coverage for the InvAuditLog entity itself -- no
 * repository/DB involved, matching InvService's own writeAuditLog(), the
 * only real production caller of this constructor.
 */
#[Test]
final class InvAuditLogTest
{
    public function isNotPersistedUntilAnIdIsSet(): void
    {
        $log = new InvAuditLog(inv_id: 1, action: 'created');

        Assert::false($log->hasIdentity());
    }

    public function reqIdThrowsUntilPersisted(): void
    {
        $log = new InvAuditLog(inv_id: 1, action: 'created');

        try {
            $log->reqId();
            Assert::fail('Expected a LogicException.');
        } catch (\LogicException $e) {
            Assert::same('InvAuditLog not persisted', $e->getMessage());
        }
    }

    public function reqIdReturnsTheIdOnceSet(): void
    {
        $log = new InvAuditLog(inv_id: 1, action: 'created');
        $log->setId(42);

        Assert::true($log->hasIdentity());
        Assert::same(42, $log->reqId());
    }

    public function reqInvIdReturnsTheInvId(): void
    {
        $log = new InvAuditLog(inv_id: 7, action: 'created');

        Assert::same(7, $log->reqInvId());
    }

    public function userIdAndUserRelationDefaultToNull(): void
    {
        $log = new InvAuditLog(inv_id: 1, action: 'created');

        Assert::null($log->getUserId());
        Assert::null($log->getUser());
    }

    public function setUserIdAndSetUserAreIndependentlySettable(): void
    {
        $log = new InvAuditLog(inv_id: 1, user_id: 9, action: 'updated');
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $log->setUser($user);

        Assert::same(9, $log->getUserId());
        Assert::same($user, $log->getUser());

        $log->setUserId(10);
        Assert::same(10, $log->getUserId());
    }

    public function invRelationIsSettableAndReadableIndependentlyOfInvId(): void
    {
        $log = new InvAuditLog(inv_id: 3, action: 'created');
        Assert::null($log->getInv());

        $inv = new Inv();
        $log->setInv($inv);

        Assert::same($inv, $log->getInv());
        // Setting the relation object doesn't change the scalar inv_id --
        // they're independently maintained, matching every other
        // BelongsTo relation + scalar FK pair in this codebase.
        Assert::same(3, $log->reqInvId());
    }

    public function actionDefaultsToUpdated(): void
    {
        $log = new InvAuditLog(inv_id: 1);

        Assert::same('updated', $log->getAction());
    }

    public function actionReflectsWhateverWasConstructed(): void
    {
        foreach (['created', 'updated', 'deleted', 'restored'] as $action) {
            $log = new InvAuditLog(inv_id: 1, action: $action);
            Assert::same($action, $log->getAction());
        }
    }

    public function changedFieldsDefaultsToNull(): void
    {
        $log = new InvAuditLog(inv_id: 1, action: 'created');

        Assert::null($log->getChangedFields());
    }

    public function changedFieldsReflectsWhateverWasConstructed(): void
    {
        $json = '{"terms":{"old":"Net 30","new":"Net 45"}}';
        $log = new InvAuditLog(inv_id: 1, action: 'updated', changed_fields: $json);

        Assert::same($json, $log->getChangedFields());
    }

    public function changedAtIsSetAtConstructionTime(): void
    {
        $before = new \DateTimeImmutable('now');
        $log = new InvAuditLog(inv_id: 1, action: 'created');
        $after = new \DateTimeImmutable('now');

        Assert::true($log->getChangedAt() >= $before);
        Assert::true($log->getChangedAt() <= $after);
    }
}
