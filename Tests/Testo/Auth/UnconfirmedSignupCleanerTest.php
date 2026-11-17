<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\UnconfirmedSignupCleaner;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use DateTimeImmutable;
use Testo\Assert;
use Testo\Test;

/**
 * Runs the real queries against an in-memory SQLite copy of just the tables the cleaner touches.
 * Each test builds one small world; "old" means a token issued 3 days before the 1-day cutoff.
 *
 * @see UnconfirmedSignupCleaner
 */
#[Test]
final class UnconfirmedSignupCleanerTest
{
    private const string OLD = '2026-11-01 10:00:00';
    private const string RECENT = '2026-11-14 23:30:00';
    private const string CUTOFF = '2026-11-14 12:00:00';

    private ?DatabaseManager $manager = null;

    private function database(): DatabaseInterface
    {
        $this->manager = $databases = new DatabaseManager(new DatabaseConfig([
            'default' => 'default',
            'databases' => ['default' => ['connection' => 'sqlite']],
            'connections' => ['sqlite' => new SQLiteDriverConfig(connection: new MemoryConnectionConfig())],
        ]));
        $db = $databases->database();
        foreach ([
            'CREATE TABLE user (id INTEGER PRIMARY KEY, login TEXT, created_at TEXT)',
            'CREATE TABLE identity (id INTEGER PRIMARY KEY, user_id INTEGER)',
            'CREATE TABLE token (id INTEGER PRIMARY KEY, identity_id INTEGER, token TEXT, type TEXT, created_at TEXT)',
            'CREATE TABLE user_inv (id INTEGER PRIMARY KEY, user_id INTEGER, active INTEGER)',
            'CREATE TABLE user_rbac_link (id INTEGER PRIMARY KEY, user_inv_id INTEGER)',
            'CREATE TABLE yii_rbac_assignment (item_name TEXT, user_id TEXT)',
            'CREATE TABLE home_care_pending_signup (id INTEGER PRIMARY KEY, user_id INTEGER)',
        ] as $sql) {
            $db->execute($sql);
        }
        return $db;
    }

    /** The cleaner over the manager that database() built: the same in-memory database. */
    private function cleaner(): UnconfirmedSignupCleaner
    {
        return new UnconfirmedSignupCleaner($this->manager ?? throw new \LogicException("database() first"));
    }

    /**
     * One signup as the forms leave it: user, identity, an inactive user_inv, an rbac link and role,
     * and a verification token (plus the pending row when $homecare).
     */
    private function signup(
        DatabaseInterface $db,
        int $id,
        string $tokenCreatedAt = self::OLD,
        string $token = 'abcdefghijklmnopqrstuvwxyz012345',
        string $type = 'email-verification',
        bool $active = false,
        ?string $role = 'observer',
        bool $homecare = false,
    ): void {
        $db->insert('user')->values(['id' => $id, 'login' => 'virtual' . $id, 'created_at' => $tokenCreatedAt])->run();
        $db->insert('identity')->values(['id' => $id + 100, 'user_id' => $id])->run();
        $db->insert('token')->values([
            'identity_id' => $id + 100,
            'token' => $token,
            'type' => $type,
            'created_at' => $tokenCreatedAt,
        ])->run();
        $db->insert('user_inv')->values(['id' => $id + 200, 'user_id' => $id, 'active' => $active ? 1 : 0])->run();
        $db->insert('user_rbac_link')->values(['user_inv_id' => $id + 200])->run();
        if (null !== $role) {
            $db->insert('yii_rbac_assignment')->values(['item_name' => $role, 'user_id' => (string) $id])->run();
        }
        if ($homecare) {
            $db->insert('home_care_pending_signup')->values(['user_id' => $id])->run();
        }
    }

    /** @return list<int> */
    private function candidateIds(): array
    {
        $ids = array_map(
            static fn (array $c): int => $c['userId'],
            $this->cleaner()->findCandidates(new DateTimeImmutable(self::CUTOFF)),
        );
        sort($ids);
        return $ids;
    }

    private function count(DatabaseInterface $db, string $table): int
    {
        return $db->select()->from($table)->count();
    }

    public function anUnclickedGenericSignupOlderThanTheCutoffIsACandidate(): void
    {
        $db = $this->database();
        $this->signup($db, 5);

        Assert::same($this->candidateIds(), [5]);
    }

    public function anUnclickedHomeCareSignupIsACandidateToo(): void
    {
        $db = $this->database();
        $this->signup($db, 6, type: 'homecare-email-verification', homecare: true);

        Assert::same($this->candidateIds(), [6]);
    }

    public function aSignupYoungerThanTheCutoffIsLeftAlone(): void
    {
        $db = $this->database();
        $this->signup($db, 5, tokenCreatedAt: self::RECENT);

        Assert::same($this->candidateIds(), []);
    }

    public function aClickedLinkMeansNotACandidate(): void
    {
        $db = $this->database();
        $this->signup($db, 5, token: 'already_used_token_1793000000');

        Assert::same($this->candidateIds(), []);
    }

    public function anActiveAccountIsNeverACandidate(): void
    {
        $db = $this->database();
        $this->signup($db, 5, active: true);

        Assert::same($this->candidateIds(), []);
    }

    public function anAdminIsNeverACandidate(): void
    {
        $db = $this->database();
        $this->signup($db, 5, role: 'admin');

        Assert::same($this->candidateIds(), []);
    }

    public function theFirstUserIsNeverACandidate(): void
    {
        $db = $this->database();
        $this->signup($db, 1);

        Assert::same($this->candidateIds(), []);
    }

    public function anAccountWithoutAVerificationTokenIsNeverACandidate(): void
    {
        // an administrator-created or deliberately deactivated user: inactive, but no unclicked link
        $db = $this->database();
        $this->signup($db, 5, type: 'password-reset');

        Assert::same($this->candidateIds(), []);
    }

    public function onlyTheEligibleAccountsAmongSeveralAreReturned(): void
    {
        $db = $this->database();
        $this->signup($db, 5);
        $this->signup($db, 6, tokenCreatedAt: self::RECENT);
        $this->signup($db, 7, active: true);
        $this->signup($db, 8, type: 'homecare-email-verification', homecare: true);

        Assert::same($this->candidateIds(), [5, 8]);
    }

    public function removeDeletesEverythingTheSignupCreatedAndNothingElse(): void
    {
        $db = $this->database();
        $this->signup($db, 5, homecare: true);
        $this->signup($db, 6, homecare: true);
        $cleaner = $this->cleaner();

        $removed = $cleaner->remove(5);

        Assert::same($removed, [
            'home_care_pending_signup' => 1,
            'user_rbac_link' => 1,
            'user_inv' => 1,
            'yii_rbac_assignment' => 1,
            'token' => 1,
            'identity' => 1,
            'user' => 1,
        ]);
        foreach (['user', 'identity', 'token', 'user_inv', 'user_rbac_link', 'yii_rbac_assignment', 'home_care_pending_signup'] as $table) {
            Assert::same($this->count($db, $table), 1);
        }
        /** @var list<array{id: int|string}> $users */
        $users = $db->select('id')->from('user')->fetchAll();
        Assert::same((int) $users[0]['id'], 6);
    }

    public function aFailureHalfWayLeavesTheAccountExactlyAsItWas(): void
    {
        $db = $this->database();
        $this->signup($db, 5, homecare: true);
        // the last table to be touched is missing, so the earlier deletes must be rolled back
        $db->execute('DROP TABLE user');
        $threw = false;

        try {
            $this->cleaner()->remove(5);
        } catch (\Throwable) {
            $threw = true;
        }

        Assert::true($threw);
        foreach (['identity', 'token', 'user_inv', 'user_rbac_link', 'yii_rbac_assignment', 'home_care_pending_signup'] as $table) {
            Assert::same($this->count($db, $table), 1);
        }
    }

    public function theFirstUserCannotBeRemovedEvenIfAskedTo(): void
    {
        $db = $this->database();
        $this->signup($db, 1);
        $threw = false;

        try {
            $this->cleaner()->remove(1);
        } catch (\LogicException) {
            $threw = true;
        }

        Assert::true($threw);
        Assert::same($this->count($db, 'user'), 1);
    }
}
