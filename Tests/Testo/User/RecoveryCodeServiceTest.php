<?php

declare(strict_types=1);

namespace Tests\Testo\User;

use App\Infrastructure\Persistence\User\User;
use App\User\RecoveryCode;
use App\User\RecoveryCodeRepository;
use App\User\RecoveryCodeService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * Covers RecoveryCodeService: saving/deleting a single RecoveryCode,
 * bulk-removing a user's backup codes, checking whether a user has any
 * backup codes left, generating fresh plain-text backup codes, hashing and
 * persisting a batch of them, and validating + marking a submitted code as
 * used.
 */
#[Test]
final class RecoveryCodeServiceTest
{
    /** @param list<RecoveryCode> $items */
    private function readerYielding(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        /** @var \Mockery\Expectation $e */
        $e = $reader->shouldReceive('getIterator');
        $e->andReturn((static function () use ($items) {
            yield from $items;
        })());
        return $reader;
    }

    public function saveRecoveryCodeSetsFieldsAndSaves(): void
    {
        $user = m::mock(User::class);

        $model = m::mock(RecoveryCode::class);
        /** @var \Mockery\Expectation $e */
        $e = $model->shouldReceive('setUser');
        $e->once()->with($user);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $model->shouldReceive('setCodeHash');
        $e2->once()->with('hashed-value');
        /** @var \Mockery\Expectation $e3 */
        $e3 = $model->shouldReceive('setUsed');
        $e3->once()->with(true);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $repo->expects('save');
        $e4->once()->with($model);

        $service = new RecoveryCodeService($repo);
        $service->saveRecoveryCode($model, $user, 'hashed-value', true);
    }

    public function deleteRecoveryCodeCallsRepositoryDelete(): void
    {
        $model = m::mock(RecoveryCode::class);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('delete');
        $e->once()->with($model);

        $service = new RecoveryCodeService($repo);
        $service->deleteRecoveryCode($model);
    }

    public function removeBackupRecoveryCodesDeletesEachCodeReturnedForUser(): void
    {
        $user = m::mock(User::class);
        $code1 = m::mock(RecoveryCode::class);
        $code2 = m::mock(RecoveryCode::class);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('findByUser');
        $e->once()->with($user)->andReturn($this->readerYielding([$code1, $code2]));
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->expects('delete');
        $e2->once()->with($code1);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repo->expects('delete');
        $e3->once()->with($code2);

        $service = new RecoveryCodeService($repo);
        $service->removeBackupRecoveryCodes($user);
    }

    public function removeBackupRecoveryCodesDoesNothingWhenUserHasNoCodes(): void
    {
        $user = m::mock(User::class);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('findByUser');
        $e->once()->with($user)->andReturn($this->readerYielding([]));
        $repo->shouldNotReceive('delete');

        $service = new RecoveryCodeService($repo);
        $service->removeBackupRecoveryCodes($user);
    }

    public function userHasBackupCodesReturnsTrueWhenCountIsPositive(): void
    {
        $user = m::mock(User::class);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('findByUserCount');
        $e->once()->with($user)->andReturn(3);

        $service = new RecoveryCodeService($repo);

        Assert::true($service->userHasBackupCodes($user));
    }

    public function userHasBackupCodesReturnsFalseWhenCountIsZero(): void
    {
        $user = m::mock(User::class);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('findByUserCount');
        $e->once()->with($user)->andReturn(0);

        $service = new RecoveryCodeService($repo);

        Assert::false($service->userHasBackupCodes($user));
    }

    public function generateBackupCodesDefaultArgsProducesFiveEightCharHexCodes(): void
    {
        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);

        $service = new RecoveryCodeService($repo);
        /** @var list<string> $codes */
        $codes = $service->generateBackupCodes();

        Assert::same(5, count($codes));
        foreach ($codes as $code) {
            Assert::same(8, strlen($code));
            Assert::same(1, preg_match('/^[0-9A-F]+$/', $code));
        }
        Assert::same(5, count(array_unique($codes)));
    }

    public function generateBackupCodesWithZeroCountReturnsEmptyArray(): void
    {
        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);

        $service = new RecoveryCodeService($repo);
        $codes = $service->generateBackupCodes(0, 8);

        Assert::same([], $codes);
    }

    public function generateBackupCodesRoundsUpOddLengthToNextEvenLength(): void
    {
        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);

        $service = new RecoveryCodeService($repo);
        /** @var list<string> $codes */
        $codes = $service->generateBackupCodes(3, 5);

        Assert::same(3, count($codes));
        foreach ($codes as $code) {
            Assert::same(6, strlen($code));
        }
    }

    public function generateBackupCodesClampsTooShortLengthToTwo(): void
    {
        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);

        $service = new RecoveryCodeService($repo);
        /** @var list<string> $codes */
        $codes = $service->generateBackupCodes(2, 0);

        Assert::same(2, count($codes));
        foreach ($codes as $code) {
            Assert::same(2, strlen($code));
        }
    }

    public function persistBackupCodesHashesAndSavesEachPlainCode(): void
    {
        $user = m::mock(User::class);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('save');
        $e->once()->with(m::on(
            static fn (mixed $rc): bool => $rc instanceof RecoveryCode
                && $rc->getUser() === $user
                && $rc->isUsed() === false
                && password_verify('AAAA1111', $rc->getCodeHash())
        ));
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->expects('save');
        $e2->once()->with(m::on(
            static fn (mixed $rc): bool => $rc instanceof RecoveryCode
                && $rc->getUser() === $user
                && $rc->isUsed() === false
                && password_verify('BBBB2222', $rc->getCodeHash())
        ));

        $service = new RecoveryCodeService($repo);
        $service->persistBackupCodes($user, ['AAAA1111', 'BBBB2222']);
    }

    public function persistBackupCodesWithEmptyArraySavesNothing(): void
    {
        $user = m::mock(User::class);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        $repo->shouldNotReceive('save');

        $service = new RecoveryCodeService($repo);
        $service->persistBackupCodes($user, []);
    }

    public function validateAndMarkCodeAsUsedMarksMatchingUnusedCodeAsUsedAndReturnsTrue(): void
    {
        $user = m::mock(User::class);
        $wrong = new RecoveryCode($user, password_hash('other-code', PASSWORD_DEFAULT), false);
        $match = new RecoveryCode($user, password_hash('secret-code', PASSWORD_DEFAULT), false);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('findByUser');
        $e->once()->with($user)->andReturn($this->readerYielding([$wrong, $match]));
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->expects('save');
        $e2->once()->with($match);

        $service = new RecoveryCodeService($repo);

        Assert::true($service->validateAndMarkCodeAsUsed($user, 'secret-code'));
        Assert::true($match->isUsed());
        Assert::false($wrong->isUsed());
    }

    public function validateAndMarkCodeAsUsedReturnsFalseWhenNoCodeMatches(): void
    {
        $user = m::mock(User::class);
        $codeA = new RecoveryCode($user, password_hash('code-a', PASSWORD_DEFAULT), false);
        $codeB = new RecoveryCode($user, password_hash('code-b', PASSWORD_DEFAULT), false);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('findByUser');
        $e->once()->with($user)->andReturn($this->readerYielding([$codeA, $codeB]));
        $repo->shouldNotReceive('save');

        $service = new RecoveryCodeService($repo);

        Assert::false($service->validateAndMarkCodeAsUsed($user, 'not-a-real-code'));
    }

    public function validateAndMarkCodeAsUsedIgnoresAlreadyUsedCodeEvenIfItMatches(): void
    {
        $user = m::mock(User::class);
        $usedMatch = new RecoveryCode($user, password_hash('secret-code', PASSWORD_DEFAULT), true);

        /** @var RecoveryCodeRepository&m\MockInterface $repo */
        $repo = m::mock(RecoveryCodeRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->expects('findByUser');
        $e->once()->with($user)->andReturn($this->readerYielding([$usedMatch]));
        $repo->shouldNotReceive('save');

        $service = new RecoveryCodeService($repo);

        Assert::false($service->validateAndMarkCodeAsUsed($user, 'secret-code'));
    }
}
