<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Setting;

use App\Infrastructure\Persistence\Company\Company;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\CompanyPrivate\CompanyPrivateRepository;
use App\Invoice\Setting\SettingRepository;
use Cycle\ORM\Select;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Covers SettingRepository::rememberMeDurationDays() -- the guarded-default
 * pattern matching the existing (untested, like every other query-building
 * method here -- see InvAuditLogRepositoryTest's own docblock for this
 * codebase's established convention) positiveListLimit(). $settingsArray is
 * a public property specifically so loadSettings()'s own guard
 * (`if ($this->settingsArray !== []) return;`) can be pre-satisfied here,
 * bypassing the real Cycle Select/EntityReader query path entirely rather
 * than mocking it -- these tests are about rememberMeDurationDays()'s own
 * fallback arithmetic, not about the query-building this class otherwise
 * never gets unit-tested for.
 */
#[Test]
final class SettingRepositoryTest
{
    private function makeRepository(?CompanyRepository $compR = null): SettingRepository
    {
        /** @var Select<\App\Infrastructure\Persistence\Setting\Setting>&m\MockInterface $select */
        $select = m::mock(Select::class);
        /** @var EntityWriter&m\MockInterface $entityWriter */
        $entityWriter = m::mock(EntityWriter::class);
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        /** @var CompanyRepository&m\MockInterface $compR */
        $compR = $compR ?? m::mock(CompanyRepository::class);
        /** @var CompanyPrivateRepository&m\MockInterface $compPR */
        $compPR = m::mock(CompanyPrivateRepository::class);

        return new SettingRepository($select, $entityWriter, $translator, $compR, $compPR);
    }

    public function rememberMeDurationDaysReturnsTheConfiguredValueWhenPositive(): void
    {
        $repository = $this->makeRepository();
        $repository->settingsArray = ['remember_me_days' => '45'];

        Assert::same(45, $repository->rememberMeDurationDays());
    }

    public function rememberMeDurationDaysFallsBackToThirtyWhenUnset(): void
    {
        $repository = $this->makeRepository();
        // loadSettings()'s own guard treats an empty array as "not loaded
        // yet" and would try a real query -- a single unrelated key keeps
        // it non-empty (already "loaded") without exercising that path,
        // the same way an install with no remember_me_days row yet (every
        // already-existing DB before this feature shipped) behaves.
        $repository->settingsArray = ['default_list_limit' => '120'];

        Assert::same(30, $repository->rememberMeDurationDays());
    }

    public function rememberMeDurationDaysFallsBackToThirtyWhenZero(): void
    {
        $repository = $this->makeRepository();
        $repository->settingsArray = ['remember_me_days' => '0'];

        Assert::same(30, $repository->rememberMeDurationDays());
    }

    public function rememberMeDurationDaysFallsBackToThirtyWhenNegative(): void
    {
        $repository = $this->makeRepository();
        $repository->settingsArray = ['remember_me_days' => '-5'];

        Assert::same(30, $repository->rememberMeDurationDays());
    }

    public function rememberMeDurationDaysFallsBackToThirtyWhenNonNumeric(): void
    {
        $repository = $this->makeRepository();
        $repository->settingsArray = ['remember_me_days' => 'not-a-number'];

        Assert::same(30, $repository->rememberMeDurationDays());
    }

    // ─── SettingTooltipTrait's remember_me_days entry ──────────────────

    public function infoIconRendersATooltipForRememberMeDaysInDebugMode(): void
    {
        $repository = $this->makeRepository();

        // $debug_mode: true forces the debug-mode-only branch on directly,
        // rather than depending on the real $_ENV['YII_DEBUG'] this test
        // process happens to run under.
        $html = $repository->infoIcon('remember_me_days', true);

        Assert::true(str_contains($html, 'data-bs-toggle="tooltip"'));
        Assert::true(str_contains($html, 'Remember Me'));
    }

    public function infoIconIsEmptyForRememberMeDaysOutsideDebugMode(): void
    {
        $repository = $this->makeRepository();

        Assert::same('', $repository->infoIcon('remember_me_days', false));
    }

    // ─── SettingConfigTrait::getActiveCompany() ────────────────────────

    public function getActiveCompanyReturnsWhateverCompanyRepositoryFindsActive(): void
    {
        /** @var Company&m\MockInterface $company */
        $company = m::mock(Company::class);

        /** @var CompanyRepository&m\MockInterface $compR */
        $compR = m::mock(CompanyRepository::class);
        $e = $compR->shouldReceive('repoCompanyActivequery');
        $e->once()->andReturn($company);

        $repository = $this->makeRepository($compR);

        Assert::same($company, $repository->getActiveCompany());
    }

    public function getActiveCompanyReturnsNullWhenNoCompanyIsActive(): void
    {
        /** @var CompanyRepository&m\MockInterface $compR */
        $compR = m::mock(CompanyRepository::class);
        $e = $compR->shouldReceive('repoCompanyActivequery');
        $e->once()->andReturn(null);

        $repository = $this->makeRepository($compR);

        Assert::null($repository->getActiveCompany());
    }
}
