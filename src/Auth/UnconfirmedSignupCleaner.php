<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Controller\HomeCareSignupController;
use App\Auth\Controller\SignupController;
use App\Invoice\AppConstants;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use DateTimeImmutable;

/**
 * Removes public signups whose confirmation link was never clicked.
 *
 * Both signup forms create the user, identity, token and (inactive) user_inv row before the email is
 * confirmed, so a bot wave leaves a pile of rows that also keep the login and email address taken.
 *
 * An account is only a candidate when ALL of these hold:
 *  - it has an email-verification token (either signup flow) that was never clicked: a clicked token
 *    is renamed to "already_used_token_<time>", so it no longer matches;
 *  - that token was issued before the cutoff, which must be later than the link's lifetime;
 *  - it has no active user_inv row;
 *  - it is not user 1 and holds no admin role.
 *
 * Accounts an administrator created or deactivated carry no unclicked verification token, so they are
 * never candidates. An account whose link was clicked after it had expired is not removed either:
 * its token is burned, so it cannot be told apart from a deactivated real user.
 */
final readonly class UnconfirmedSignupCleaner
{
    /** The confirmation link lives one hour; nothing younger than this may ever be removed. */
    public const int LINK_LIFETIME_SECONDS = 3600;

    private const string USED_TOKEN_PREFIX = 'already_used_token_';
    private const int FIRST_USER_ID = 1;
    private const int CHUNK = 500;
    private const string DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private DatabaseManager $databases)
    {
    }

    /**
     * @return list<array{userId: int, login: string, createdAt: string}>
     */
    public function findCandidates(DateTimeImmutable $cutoff): array
    {
        $db = $this->databases->database();

        /** @var list<array<string, mixed>> $tokenRows */
        $tokenRows = $db->select('identity_id')
            ->from('token')
            ->where('type', 'IN', [SignupController::EMAIL_VERIFICATION_TOKEN, HomeCareSignupController::EMAIL_VERIFICATION_TOKEN])
            ->where('created_at', '<', $cutoff->format(self::DATETIME_FORMAT))
            ->where('token', 'NOT LIKE', self::USED_TOKEN_PREFIX . '%')
            ->fetchAll();
        $identityIds = $this->column($tokenRows, 'identity_id');
        $userIds = $this->idsIn($db, 'identity', 'user_id', 'id', $identityIds);
        $userIds = array_diff($userIds, [self::FIRST_USER_ID]);
        $userIds = array_diff(
            $userIds,
            $this->idsIn($db, 'user_inv', 'user_id', 'user_id', $userIds, ['active' => 1]),
            $this->idsIn($db, 'yii_rbac_assignment', 'user_id', 'user_id', $userIds, ['item_name' => AppConstants::ROLE_ADMIN]),
        );

        $candidates = [];
        foreach (array_chunk(array_values($userIds), self::CHUNK) as $chunk) {
            /** @var list<array{id: int|string, login: string, created_at: string}> $rows */
            $rows = $db->select(['id', 'login', 'created_at'])->from('user')->where('id', 'IN', $chunk)->fetchAll();
            foreach ($rows as $row) {
                $candidates[] = ['userId' => (int) $row['id'], 'login' => $row['login'], 'createdAt' => $row['created_at']];
            }
        }
        return $candidates;
    }

    /**
     * Deletes one account and everything the signup created for it, in a single transaction.
     * Nothing is half removed: on any failure the account stays as it was and the exception is thrown.
     *
     * @return array<string, int> rows removed per table
     */
    public function remove(int $userId): array
    {
        if ($userId === self::FIRST_USER_ID) {
            throw new \LogicException('The first user is never removed.');
        }
        $db = $this->databases->database();

        /** @var array<string, int> $removed */
        $removed = [];
        $db->transaction(function () use ($db, $userId, &$removed): void {
            $identityIds = $this->idsIn($db, 'identity', 'id', 'user_id', [$userId]);
            $userInvIds = $this->idsIn($db, 'user_inv', 'id', 'user_id', [$userId]);

            $removed['home_care_pending_signup'] = $this->delete($db, 'home_care_pending_signup', 'user_id', [$userId]);
            $removed['user_rbac_link'] = $this->delete($db, 'user_rbac_link', 'user_inv_id', $userInvIds);
            $removed['user_inv'] = $this->delete($db, 'user_inv', 'user_id', [$userId]);
            $removed['yii_rbac_assignment'] = $this->delete($db, 'yii_rbac_assignment', 'user_id', [(string) $userId]);
            $removed['token'] = $this->delete($db, 'token', 'identity_id', $identityIds);
            $removed['identity'] = $this->delete($db, 'identity', 'user_id', [$userId]);
            $removed['user'] = $this->delete($db, 'user', 'id', [$userId]);
        });
        return array_filter($removed);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<int>
     */
    private function column(array $rows, string $column): array
    {
        return array_values(array_unique(array_map(static fn (array $row): int => (int) $row[$column], $rows)));
    }

    /**
     * @param array<array-key, int|string> $values
     * @param array<string, int|string> $also extra equality conditions
     * @return list<int>
     */
    private function idsIn(
        DatabaseInterface $db,
        string $table,
        string $select,
        string $in,
        array $values,
        array $also = [],
    ): array {
        $found = [];
        foreach (array_chunk(array_values($values), self::CHUNK) as $chunk) {
            $query = $db->select($select)->from($table)->where($in, 'IN', $chunk);
            foreach ($also as $field => $value) {
                $query->where($field, $value);
            }
            /** @var list<array<string, mixed>> $rows */
            $rows = $query->fetchAll();
            $found = [...$found, ...$this->column($rows, $select)];
        }
        return array_values(array_unique($found));
    }

    /** @param list<int|string> $values */
    private function delete(DatabaseInterface $db, string $table, string $column, array $values): int
    {
        return [] === $values ? 0 : $db->delete($table, [$column => ['IN' => $values]])->run();
    }
}
