<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAuditLog\InvAuditLog;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Inv\InvService;
use App\Invoice\InvAuditLog\InvAuditFieldDiffer;
use App\Invoice\InvAuditLog\InvAuditLogRepository;
use App\Invoice\Setting\SettingRepository;
use App\User\UserService;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Covers InvService's public API: transaction wrapping, invoice creation
 * (saveInv), invoice copying (copyInv), the VAT tax-point rule
 * (setTaxPoint), recurring-invoice materialisation (saveInvFromRecurring),
 * and the delete/restore repository pass-throughs.
 */
#[Test]
final class InvServiceTest
{
    private function makeService(
        InvRepository $repository,
        ?TranslatorInterface $translator = null,
        ?ClientRepository $cR = null,
        ?GroupRepository $gR = null,
        ?DatabaseManager $dbal = null,
        ?UserService $userService = null,
        ?InvAuditLogRepository $auditLogRepository = null,
    ): InvService {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = $translator ?? m::mock(TranslatorInterface::class);
        /** @var ClientRepository&m\MockInterface $cR */
        $cR = $cR ?? m::mock(ClientRepository::class);
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = $gR ?? m::mock(GroupRepository::class);
        /** @var DatabaseManager&m\MockInterface $dbal */
        $dbal = $dbal ?? m::mock(DatabaseManager::class);
        if (null === $userService) {
            // Every saveInv() call now resolves the acting user for its own
            // audit-log entry (see InvService::recordAuditLog()) -- default
            // to "no interactively signed-in user" so every pre-existing
            // test in this file, none of which care about audit behaviour,
            // keeps working unchanged.
            /** @var UserService&m\MockInterface $userService */
            $userService = m::mock(UserService::class);
            $userService->shouldReceive('getUser')->andReturnNull();
        }
        if (null === $auditLogRepository) {
            /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
            $auditLogRepository = m::mock(InvAuditLogRepository::class);
            $auditLogRepository->shouldReceive('save');
        }
        return new InvService(
            $repository,
            $translator,
            $cR,
            $gR,
            $dbal,
            $userService,
            $auditLogRepository,
            new InvAuditFieldDiffer(),
        );
    }

    /**
     * Simulates Cycle ORM's own real behaviour on a successful INSERT --
     * the generated primary key is hydrated back onto the same entity
     * instance -- for a mocked InvRepository::save() call. The bare mock
     * used throughout this file has no such side effect on its own, so
     * every saveInv() test on a brand new (never setId()'d) Inv needs this
     * wired onto its save() expectation: InvService now calls
     * $model->reqId() for its own audit-log entry (recordAuditLog()) right
     * after repository->save() returns, exactly like real callers (e.g.
     * Trait\Add::handleSaveForUser()) already do with the real return
     * value.
     */
    private static function assignIdOnSave(Inv $inv): void
    {
        $inv->setId(999);
    }

    /**
     * Builds a mocked SettingRepository answering the setting keys touched
     * by InvService (directly, and indirectly through Inv::setDateDue() and
     * DateHelper). $overrides replaces individual default values.
     *
     * @param array<string, string> $overrides
     */
    private function makeSettingRepo(array $overrides = []): SettingRepository
    {
        $settings = array_merge([
            'invoices_due_after' => '14',
            'stand_in_code' => '',
            'mark_invoices_sent_copy' => '0',
            'generate_invoice_number_for_draft' => '0',
            'invoice_default_payment_method' => '4',
            'date_format' => 'Y-m-d',
            'first_day_of_week' => 'monday',
            'time_zone' => 'Europe/London',
        ], $overrides);

        /** @var SettingRepository&m\MockInterface $s */
        $s = m::mock(SettingRepository::class);
        $e = $s->shouldReceive('loadSettings');
        $e->andReturnNull();
        $e2 = $s->shouldReceive('getSetting');
        $e2->with('invoices_due_after')->andReturn($settings['invoices_due_after']);
        $e3 = $s->shouldReceive('getSetting');
        $e3->with('stand_in_code')->andReturn($settings['stand_in_code']);
        $e4 = $s->shouldReceive('getSetting');
        $e4->with('mark_invoices_sent_copy')->andReturn($settings['mark_invoices_sent_copy']);
        $e5 = $s->shouldReceive('getSetting');
        $e5->with('generate_invoice_number_for_draft')->andReturn($settings['generate_invoice_number_for_draft']);
        $e6 = $s->shouldReceive('getSetting');
        $e6->with('invoice_default_payment_method')->andReturn($settings['invoice_default_payment_method']);
        $e7 = $s->shouldReceive('getSetting');
        $e7->with('date_format')->andReturn($settings['date_format']);
        $e8 = $s->shouldReceive('getSetting');
        $e8->with('first_day_of_week')->andReturn($settings['first_day_of_week']);
        $e9 = $s->shouldReceive('getSetting');
        $e9->with('time_zone')->andReturn($settings['time_zone']);

        return $s;
    }

    // ── withTransaction ──────────────────────────────────────────────────

    public function withTransactionInvokesDatabaseTransactionWithGivenCallback(): void
    {
        $callback = static function (): void {
        };

        /** @var DatabaseInterface&m\MockInterface $database */
        $database = m::mock(DatabaseInterface::class);
        $e = $database->shouldReceive('transaction');
        $e->once()->with($callback)->andReturn(true);

        /** @var DatabaseManager&m\MockInterface $dbal */
        $dbal = m::mock(DatabaseManager::class);
        $e2 = $dbal->shouldReceive('database');
        $e2->once()->andReturn($database);

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $service = $this->makeService($repo, null, null, null, $dbal);

        $service->withTransaction($callback);
    }

    // ── deleteInv / restoreInv ───────────────────────────────────────────

    public function deleteInvCallsRepositoryDelete(): void
    {
        // deleteInv()/restoreInv() only ever make sense for an already-
        // persisted invoice (soft-deleting or un-soft-deleting something
        // with no id is meaningless), so -- unlike the brand-new-Inv()
        // saveInv()/copyInv() tests elsewhere in this file -- setId() here
        // reflects a real precondition, not a mock-simulation workaround.
        $inv = new Inv();
        $inv->setId(55);

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e = $repo->shouldReceive('delete');
        $e->once()->with($inv);

        /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
        $auditLogRepository = m::mock(InvAuditLogRepository::class);
        $e2 = $auditLogRepository->shouldReceive('save');
        $e2->once()->with(m::on(static fn (InvAuditLog $log): bool => $log->getAction() === 'deleted'
            && $log->reqInvId() === 55
            && $log->getChangedFields() === null));

        $service = $this->makeService($repo, null, null, null, null, null, $auditLogRepository);
        $service->deleteInv($inv);
    }

    public function restoreInvClearsDeletedStatusAndSaves(): void
    {
        $inv = new Inv();
        $inv->setId(56);

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e = $repo->shouldReceive('save');
        $e->once()->with($inv);

        /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
        $auditLogRepository = m::mock(InvAuditLogRepository::class);
        $e2 = $auditLogRepository->shouldReceive('save');
        $e2->once()->with(m::on(static fn (InvAuditLog $log): bool => $log->getAction() === 'restored'
            && $log->reqInvId() === 56
            && $log->getChangedFields() === null));

        $service = $this->makeService($repo, null, null, null, null, null, $auditLogRepository);
        $service->restoreInv($inv);

        Assert::false($inv->isDeleted());
    }

    // ── setTaxPoint ──────────────────────────────────────────────────────

    public function setTaxPointReturnsDateCreatedWhenClientAttachedAndCreatedIsWithinFourteenDaysAfterSupplied(): void
    {
        $inv = new Inv();
        $inv->setClient(new Client());

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $service = $this->makeService($repo);

        $supplied = new DateTimeImmutable('2026-01-01');
        $created = new DateTimeImmutable('2026-01-10');

        Assert::same($created, $service->setTaxPoint($inv, $supplied, $created));
    }

    public function setTaxPointReturnsDateSuppliedWhenClientAttachedAndCreatedIsMoreThanFourteenDaysAfterSupplied(): void
    {
        $inv = new Inv();
        $inv->setClient(new Client());

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $service = $this->makeService($repo);

        $supplied = new DateTimeImmutable('2026-01-01');
        $created = new DateTimeImmutable('2026-02-01');

        Assert::same($supplied, $service->setTaxPoint($inv, $supplied, $created));
    }

    public function setTaxPointReturnsDateCreatedWhenClientAttachedAndCreatedIsBeforeSupplied(): void
    {
        $inv = new Inv();
        $inv->setClient(new Client());

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $service = $this->makeService($repo);

        $created = new DateTimeImmutable('2026-01-01');
        $supplied = new DateTimeImmutable('2026-01-10');

        Assert::same($created, $service->setTaxPoint($inv, $supplied, $created));
    }

    public function setTaxPointReturnsNullWhenClientAttachedAndDatesHaveEqualValueButDifferentInstances(): void
    {
        $inv = new Inv();
        $inv->setClient(new Client());

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $service = $this->makeService($repo);

        $created = new DateTimeImmutable('2026-01-01');
        $supplied = new DateTimeImmutable('2026-01-01');

        Assert::null($service->setTaxPoint($inv, $supplied, $created));
    }

    public function setTaxPointReturnsDateSuppliedWhenNoClientAttached(): void
    {
        $inv = new Inv();

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $service = $this->makeService($repo);

        $created = new DateTimeImmutable('2026-01-01');
        $supplied = new DateTimeImmutable('2026-01-05');

        Assert::same($supplied, $service->setTaxPoint($inv, $supplied, $created));
    }

    // ── saveInv ──────────────────────────────────────────────────────────

    public function saveInvOnNewInvoiceInitializesDefaultsAndSaves(): void
    {
        $model = new Inv(status_id: 9, is_read_only: true, number: 'OLD-1');
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $e = $user->shouldReceive('reqId');
        $e->andReturn(9);

        $s = $this->makeSettingRepo();
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');
        $gR->shouldNotReceive('repoGroupquery');

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        $service = $this->makeService($repo, null, null, $gR);
        $result = $service->saveInv($user, $model, ['terms' => 'Net 30'], $s, $gR);

        Assert::same($model, $result);
        Assert::same(1, $model->reqStatusId());
        Assert::false($model->getIsReadOnly());
        Assert::same('', $model->getNumber());
        Assert::same(9, $model->reqUserId());
        Assert::same(0.00, $model->getDiscountAmount());
        Assert::same(4, $model->getPaymentMethod());
    }

    public function saveInvRecomputesDateDueWhenNoDirectDebitDateScheduled(): void
    {
        $model = new Inv();
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo();
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        $service = $this->makeService($repo, null, null, $gR);
        $service->saveInv($user, $model, ['terms' => 'Net 30'], $s, $gR);

        // invoices_due_after is 14 in makeSettingRepo(); date_due should no
        // longer be the constructor's 2024-01-01 placeholder.
        Assert::same(
            $model->getDateCreated()->add(new \DateInterval('P14D'))->format('Y-m-d'),
            $model->getDateDue()->format('Y-m-d'),
        );
    }

    public function saveInvLeavesDateDueUnchangedWhenDirectDebitDateAlreadyScheduled(): void
    {
        $model = new Inv();
        $model->setDirectDebitDate(new DateTimeImmutable('2026-08-07'));
        $originalDateDue = $model->getDateDue();

        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo();
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        $service = $this->makeService($repo, null, null, $gR);
        $service->saveInv($user, $model, ['terms' => 'Net 30'], $s, $gR);

        Assert::same($originalDateDue->format('Y-m-d'), $model->getDateDue()->format('Y-m-d'));
    }

    public function saveInvOnNewInvoiceMarksSentAndReadOnlyWhenSettingEnabled(): void
    {
        $model = new Inv(status_id: 9, is_read_only: false);
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo(['mark_invoices_sent_copy' => '1']);
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        $service = $this->makeService($repo, null, null, $gR);
        $service->saveInv($user, $model, ['terms' => 'Net 30'], $s, $gR);

        Assert::same(2, $model->reqStatusId());
        Assert::true($model->getIsReadOnly());
    }

    public function saveInvGeneratesNumberForDraftWhenSettingEnabled(): void
    {
        $model = new Inv();
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo(['generate_invoice_number_for_draft' => '1']);
        $group = new Group();

        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $e2 = $gR->shouldReceive('generateNumber');
        $e2->once()->with(5, true)->andReturn('DRAFT-777');
        $e3 = $gR->shouldReceive('repoGroupquery');
        $e3->once()->with(5)->andReturn($group);

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e4 = $repo->shouldReceive('save');
        $e4->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        $service = $this->makeService($repo, null, null, $gR);
        $service->saveInv($user, $model, ['group_id' => 5, 'terms' => 'Net 30'], $s, $gR);

        Assert::same('DRAFT-777', $model->getNumber());
        Assert::same($group, $model->getGroup());
    }

    public function saveInvAssignsNumberWhenExistingSentInvoiceHasNoNumber(): void
    {
        $model = new Inv();
        $model->setId(42);
        // A real DB-loaded invoice always has these (nullable: false
        // columns) -- InvService::snapshotAuditFields()'s own "before"
        // snapshot now requires them via reqClientId()/reqGroupId()/
        // reqStatusId() the moment hasIdentity() is true.
        $model->setClientId(1);
        $model->setGroupId(1);
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $user->shouldNotReceive('reqId');

        $s = $this->makeSettingRepo();
        $group = new Group();

        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $e = $gR->shouldReceive('generateNumber');
        $e->once()->with(3, true)->andReturn('INV-0100');
        $e2 = $gR->shouldReceive('repoGroupquery');
        $e2->once()->with(3)->andReturn($group);

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e3 = $repo->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repo, null, null, $gR);
        $service->saveInv($user, $model, ['status_id' => 2, 'group_id' => 3, 'terms' => 'Net 30'], $s, $gR);

        Assert::same('INV-0100', $model->getNumber());
    }

    public function saveInvDoesNotAssignNumberWhenExistingInvoiceAlreadyHasANumber(): void
    {
        $model = new Inv(number: 'INV-EXISTING');
        $model->setId(42);
        // See saveInvAssignsNumberWhenExistingSentInvoiceHasNoNumber()'s
        // own comment on these two lines.
        $model->setClientId(1);
        $model->setGroupId(1);
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $user->shouldNotReceive('reqId');

        $s = $this->makeSettingRepo();
        $group = new Group();

        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');
        $e = $gR->shouldReceive('repoGroupquery');
        $e->once()->with(3)->andReturn($group);

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repo, null, null, $gR);
        $service->saveInv($user, $model, ['status_id' => 2, 'group_id' => 3, 'terms' => 'Net 30'], $s, $gR);

        Assert::same('INV-EXISTING', $model->getNumber());
    }

    public function saveInvLeavesTermsUnchangedWhenArrayOmitsTermsDespiteCallingTranslator(): void
    {
        $model = new Inv();
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo();
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $e2 = $translator->shouldReceive('translate');
        $e2->once()->with('payment.term.general')->andReturn('Net 30 (translated)');

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e3 = $repo->shouldReceive('save');
        $e3->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        $service = $this->makeService($repo, $translator, null, $gR);
        $service->saveInv($user, $model, [], $s, $gR);

        // The translated string is computed but never assigned back onto the
        // model (see PR #998 "Possible issues found") - terms stays at
        // its untouched constructor default.
        Assert::same('', $model->getTerms());
    }

    public function saveInvPersistsClientAndGroupFromArrayAndUserFromParameter(): void
    {
        $model = new Inv();
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $e = $user->shouldReceive('reqId');
        $e->andReturn(20);

        $client = new Client();
        $group = new Group();

        /** @var ClientRepository&m\MockInterface $cR */
        $cR = m::mock(ClientRepository::class);
        $e2 = $cR->shouldReceive('repoClientquery');
        $e2->once()->with(3)->andReturn($client);

        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $e3 = $gR->shouldReceive('repoGroupquery');
        $e3->once()->with(5)->andReturn($group);
        $gR->shouldNotReceive('generateNumber');

        $s = $this->makeSettingRepo();

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e5 = $repo->shouldReceive('save');
        $e5->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        $service = $this->makeService($repo, null, $cR, $gR);
        $service->saveInv(
            $user,
            $model,
            ['client_id' => 3, 'group_id' => 5, 'terms' => 'Net 30'],
            $s,
            $gR,
        );

        Assert::same($client, $model->getClient());
        Assert::same($group, $model->getGroup());
        // The related User object always comes from saveInv()'s own $user
        // parameter (see InvService::persist()) -- not looked up again via
        // an array key, so it's the same instance passed in above.
        Assert::same($user, $model->getUser());
        Assert::same(20, $model->reqUserId());
    }

    // ── saveInv: audit log ──────────────────────────────────────────────
    //
    // saveInv()'s $user parameter is the invoice's assigned client-portal
    // owner (see InvController::activeUser()'s own docblock), NOT the
    // actual signed-in staff member performing this save -- the audit log
    // deliberately resolves that second, different identity from
    // UserService::getUser() instead. Every test below uses a distinct
    // mock for each so a passing test genuinely proves the two are never
    // confused.

    public function saveInvOnNewInvoiceWritesACreatedAuditLogEntryAttributedToTheActingUser(): void
    {
        $model = new Inv();
        /** @var User&m\MockInterface $invoiceOwner */
        $invoiceOwner = m::mock(User::class);
        $e = $invoiceOwner->shouldReceive('reqId');
        $e->andReturn(1);

        /** @var User&m\MockInterface $actingStaffUser */
        $actingStaffUser = m::mock(User::class);
        $e2 = $actingStaffUser->shouldReceive('reqId');
        $e2->andReturn(77);

        /** @var UserService&m\MockInterface $userService */
        $userService = m::mock(UserService::class);
        $e3 = $userService->shouldReceive('getUser');
        $e3->once()->andReturn($actingStaffUser);

        $s = $this->makeSettingRepo();
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e4 = $repo->shouldReceive('save');
        $e4->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
        $auditLogRepository = m::mock(InvAuditLogRepository::class);
        $e5 = $auditLogRepository->shouldReceive('save');
        $e5->once()->with(m::on(static function (InvAuditLog $log) use ($actingStaffUser): bool {
            Assert::same('created', $log->getAction());
            Assert::null($log->getChangedFields());
            Assert::same(999, $log->reqInvId());
            Assert::same(77, $log->getUserId());
            Assert::same($actingStaffUser, $log->getUser());
            return true;
        }));

        $service = $this->makeService($repo, null, null, $gR, null, $userService, $auditLogRepository);
        $service->saveInv($invoiceOwner, $model, ['terms' => 'Net 30'], $s, $gR);
    }

    public function saveInvOnNewInvoiceRecordsANullUserIdWhenNoInteractiveUserIsSignedIn(): void
    {
        // Covers the recurring-invoice cron and any other non-interactive
        // caller: UserService::getUser() genuinely has no session identity
        // to resolve, and the audit row should say so honestly rather than
        // fall back to saveInv()'s own $user parameter (the client-portal
        // owner, a different concept entirely -- see this section's own
        // docblock above).
        $model = new Inv();
        /** @var User&m\MockInterface $invoiceOwner */
        $invoiceOwner = m::mock(User::class);
        $e = $invoiceOwner->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo();
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
        $auditLogRepository = m::mock(InvAuditLogRepository::class);
        $e3 = $auditLogRepository->shouldReceive('save');
        $e3->once()->with(m::on(static function (InvAuditLog $log): bool {
            Assert::null($log->getUserId());
            Assert::null($log->getUser());
            return true;
        }));

        // Default makeService() UserService mock already answers getUser()
        // with null -- passed explicitly here only so the intent reads
        // clearly at the call site, not because the default differs.
        $service = $this->makeService($repo, null, null, $gR, null, null, $auditLogRepository);
        $service->saveInv($invoiceOwner, $model, ['terms' => 'Net 30'], $s, $gR);
    }

    public function saveInvOnExistingInvoiceWritesAnUpdatedAuditLogEntryForTheFieldThatActuallyChanged(): void
    {
        $model = new Inv(client_id: 1, group_id: 1, terms: 'Net 30');
        $model->setId(42);
        // Freezes date_due -- saveInv() skips recomputing it whenever a
        // direct debit collection date is already scheduled (see its own
        // comment on that line) -- and every other date field below is
        // pinned to the exact value already on the model via the $array
        // passed to saveInv(), so the only real difference this save
        // introduces is 'terms'.
        $model->setDirectDebitDate(new DateTimeImmutable('2026-07-01'));
        $model->setDateCreated('2026-06-15');
        $model->setDateSupplied(new DateTimeImmutable('2026-06-15'));
        $model->setDateTaxPoint(new DateTimeImmutable('2026-06-15'));

        /** @var User&m\MockInterface $invoiceOwner */
        $invoiceOwner = m::mock(User::class);
        $invoiceOwner->shouldNotReceive('reqId');

        $s = $this->makeSettingRepo();
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e = $repo->shouldReceive('save');
        $e->once()->with($model);

        /** @var InvAuditLog|null $captured */
        $captured = null;
        /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
        $auditLogRepository = m::mock(InvAuditLogRepository::class);
        $e2 = $auditLogRepository->shouldReceive('save');
        $e2->once()->with(m::type(InvAuditLog::class));
        $e2->andReturnUsing(static function (InvAuditLog $log) use (&$captured): void {
            $captured = $log;
        });

        $service = $this->makeService($repo, null, null, $gR, null, null, $auditLogRepository);
        $service->saveInv(
            $invoiceOwner,
            $model,
            [
                'terms' => 'Net 45',
                'date_created' => '2026-06-15',
                'date_supplied' => '2026-06-15',
                'date_tax_point' => '2026-06-15',
            ],
            $s,
            $gR,
        );

        Assert::notNull($captured);
        Assert::same('updated', $captured->getAction());
        Assert::same(
            ['terms' => ['old' => 'Net 30', 'new' => 'Net 45']],
            json_decode((string) $captured->getChangedFields(), true),
        );
    }

    public function saveInvWritesNoAuditLogEntryWhenNothingAuditWorthyChangesOnAnExistingInvoice(): void
    {
        $model = new Inv(client_id: 1, group_id: 1, number: 'INV-1');
        $model->setId(42);
        $model->setDirectDebitDate(new DateTimeImmutable('2026-07-01'));
        $model->setDateCreated('2026-06-15');
        $model->setDateSupplied(new DateTimeImmutable('2026-06-15'));
        $model->setDateTaxPoint(new DateTimeImmutable('2026-06-15'));

        /** @var User&m\MockInterface $invoiceOwner */
        $invoiceOwner = m::mock(User::class);
        $invoiceOwner->shouldNotReceive('reqId');

        $s = $this->makeSettingRepo();

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $e = $translator->shouldReceive('translate');
        $e->with('payment.term.general')->andReturn('ignored -- see saveInvLeavesTermsUnchangedWhenArrayOmitsTermsDespiteCallingTranslator');

        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model);

        /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
        $auditLogRepository = m::mock(InvAuditLogRepository::class);
        $auditLogRepository->shouldNotReceive('save');

        $service = $this->makeService($repo, $translator, null, $gR, null, null, $auditLogRepository);
        $service->saveInv(
            $invoiceOwner,
            $model,
            [
                'date_created' => '2026-06-15',
                'date_supplied' => '2026-06-15',
                'date_tax_point' => '2026-06-15',
            ],
            $s,
            $gR,
        );
    }

    public function saveInvRedactsThePasswordValueInTheAuditLogWhenItChanges(): void
    {
        $model = new Inv(client_id: 1, group_id: 1, password: 'old-secret');
        $model->setId(42);
        $model->setDirectDebitDate(new DateTimeImmutable('2026-07-01'));
        $model->setDateCreated('2026-06-15');
        $model->setDateSupplied(new DateTimeImmutable('2026-06-15'));
        $model->setDateTaxPoint(new DateTimeImmutable('2026-06-15'));

        /** @var User&m\MockInterface $invoiceOwner */
        $invoiceOwner = m::mock(User::class);
        $invoiceOwner->shouldNotReceive('reqId');

        $s = $this->makeSettingRepo();
        /** @var GroupRepository&m\MockInterface $gR */
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $e = $translator->shouldReceive('translate');
        $e->with('payment.term.general')->andReturn('ignored -- terms not part of this test');

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model);

        /** @var InvAuditLog|null $captured */
        $captured = null;
        /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
        $auditLogRepository = m::mock(InvAuditLogRepository::class);
        $e3 = $auditLogRepository->shouldReceive('save');
        $e3->once()->with(m::type(InvAuditLog::class));
        $e3->andReturnUsing(static function (InvAuditLog $log) use (&$captured): void {
            $captured = $log;
        });

        $service = $this->makeService($repo, $translator, null, $gR, null, null, $auditLogRepository);
        $service->saveInv(
            $invoiceOwner,
            $model,
            [
                'password' => 'new-secret',
                'date_created' => '2026-06-15',
                'date_supplied' => '2026-06-15',
                'date_tax_point' => '2026-06-15',
            ],
            $s,
            $gR,
        );

        Assert::notNull($captured);
        $changedFields = (string) $captured->getChangedFields();
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($changedFields, true);
        // Neither the real old nor the real new password value ever
        // appears here -- only that a change happened -- so a shareable
        // public-invoice-link password can't leak into a log other staff
        // can read.
        Assert::same(['old' => '(redacted)', 'new' => '(redacted)'], $decoded['password'] ?? null);
        Assert::false(str_contains($changedFields, 'old-secret'));
        Assert::false(str_contains($changedFields, 'new-secret'));
    }

    // ── copyInv ──────────────────────────────────────────────────────────

    public function copyInvMapsProvidedArrayFieldsOntoModelAndSaves(): void
    {
        $model = new Inv();
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $e = $user->shouldReceive('reqId');
        $e->andReturn(3);

        $s = $this->makeSettingRepo();

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $e2 = $translator->shouldReceive('translate');
        $e2->once()->with('payment.term.general')->andReturn('Net 30');

        $array = [
            'client_id' => 4,
            'group_id' => 6,
            'so_id' => 7,
            'quote_id' => 8,
            'status_id' => 2,
            'is_read_only' => true,
            'password' => 'secret',
            'date_supplied' => '2026-06-05',
            'date_tax_point' => '2026-06-06',
            'time_created' => '10:00:00',
            'stand_in_code' => 'AB',
            'number' => 'INV-9',
            'discount_amount' => 5.5,
            'terms' => '',
            'note' => 'a note',
            'document_description' => 'desc',
            'url_key' => 'key123',
            'payment_method' => 0,
            'creditinvoice_parent_id' => 0,
            'delivery_id' => 1,
            'delivery_location_id' => 2,
            'postal_address_id' => 3,
            'contract_id' => 9,
        ];

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e3 = $repo->shouldReceive('save');
        $e3->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        $service = $this->makeService($repo, $translator);
        $result = $service->copyInv($user, $model, $array, $s);

        Assert::same($model, $result);
        Assert::same(4, $model->reqClientId());
        Assert::same(6, $model->reqGroupId());
        Assert::same(7, $model->getSoId());
        Assert::same(8, $model->getQuoteId());
        Assert::same(3, $model->reqUserId());
        Assert::same(2, $model->reqStatusId());
        Assert::true($model->getIsReadOnly());
        Assert::same('secret', $model->getPassword());
        Assert::same('AB', $model->getStandInCode());
        Assert::same('INV-9', $model->getNumber());
        Assert::same(5.5, $model->getDiscountAmount());
        Assert::same('Net 30', $model->getTerms());
        Assert::same('a note', $model->getNote());
        Assert::same('desc', $model->getDocumentDescription());
        Assert::same('key123', $model->getUrlKey());
        Assert::same(4, $model->getPaymentMethod());
        Assert::same(0, $model->getCreditinvoiceParentId());
        Assert::same(1, $model->getDeliveryId());
        Assert::same(2, $model->getDeliveryLocationId());
        Assert::same(3, $model->getPostalAddressId());
        Assert::same(9, $model->getContractId());
        Assert::same('', $model->getClientPoNumber());
        Assert::same('', $model->getClientPoPerson());
    }

    public function copyInvUsesProvidedDateTimeImmutableInstancesDirectlyAndSkipsTranslatorWhenTermsGiven(): void
    {
        $model = new Inv();
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo();

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldNotReceive('translate');

        $supplied = new DateTimeImmutable('2026-03-01');
        $taxPoint = new DateTimeImmutable('2026-03-02');

        $array = [
            'client_id' => 1, 'group_id' => 1, 'so_id' => 1, 'quote_id' => 1,
            'status_id' => 1, 'is_read_only' => false, 'password' => '',
            'date_supplied' => $supplied, 'date_tax_point' => $taxPoint,
            'time_created' => '09:00:00', 'stand_in_code' => '',
            'number' => 'N1', 'discount_amount' => 0.0, 'terms' => 'Net 15',
            'note' => '', 'document_description' => '', 'url_key' => 'k',
            'payment_method' => 5, 'creditinvoice_parent_id' => 0,
            'delivery_id' => 0, 'delivery_location_id' => 0,
            'postal_address_id' => 0, 'contract_id' => 0,
            'client_po_number' => 'PO1', 'client_po_person' => 'Jane',
        ];

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        $service = $this->makeService($repo, $translator);
        $service->copyInv($user, $model, $array, $s);

        Assert::same($supplied, $model->getDateSupplied());
        Assert::same($taxPoint, $model->getDateTaxPoint());
        Assert::same(5, $model->getPaymentMethod());
        Assert::same('PO1', $model->getClientPoNumber());
        Assert::same('Jane', $model->getClientPoPerson());
        Assert::same('Net 15', $model->getTerms());
    }

    public function copyInvWritesACreatedAuditLogEntry(): void
    {
        // copyInv()'s only real caller always passes a brand new Inv() --
        // see InvService::copyInv()'s own comment -- so this is always
        // 'created', never a diff.
        $model = new Inv();
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo();

        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $e2 = $translator->shouldReceive('translate');
        $e2->with('payment.term.general')->andReturn('Net 30');

        $array = [
            'client_id' => 1, 'group_id' => 1, 'so_id' => 1, 'quote_id' => 1,
            'status_id' => 1, 'is_read_only' => false, 'password' => '',
            'date_supplied' => '2026-06-05', 'date_tax_point' => '2026-06-06',
            'time_created' => '10:00:00', 'stand_in_code' => 'AB',
            'number' => 'INV-9', 'discount_amount' => 5.5, 'terms' => '',
            'note' => 'a note', 'document_description' => 'desc',
            'url_key' => 'key123', 'payment_method' => 0,
            'creditinvoice_parent_id' => 0, 'delivery_id' => 1,
            'delivery_location_id' => 2, 'postal_address_id' => 3,
            'contract_id' => 9,
        ];

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e3 = $repo->shouldReceive('save');
        $e3->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
        $auditLogRepository = m::mock(InvAuditLogRepository::class);
        $e4 = $auditLogRepository->shouldReceive('save');
        $e4->once()->with(m::on(static fn (InvAuditLog $log): bool => $log->getAction() === 'created'
            && $log->reqInvId() === 999
            && $log->getChangedFields() === null));

        $service = $this->makeService($repo, $translator, null, null, null, null, $auditLogRepository);
        $service->copyInv($user, $model, $array, $s);
    }

    // ── saveInvFromRecurring ─────────────────────────────────────────────

    public function saveInvFromRecurringOnExistingInvoiceUpdatesFieldsAndSaves(): void
    {
        $model = new Inv();
        $model->setId(77);
        // A real DB-loaded invoice always has these (nullable: false
        // columns) -- see saveInvAssignsNumberWhenExistingSentInvoiceHasNoNumber()'s
        // own identical comment; snapshotAuditFields()'s "before" snapshot
        // now requires them the moment hasIdentity() is true.
        $model->setClientId(1);
        $model->setGroupId(1);

        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $user->shouldNotReceive('reqId');

        $s = $this->makeSettingRepo();

        $details = [
            'client_id' => 2,
            'group_id' => 3,
            'status_id' => 4,
            'discount_amount' => 12.5,
            'url_key' => 'existing-key',
            'password' => 'pw',
            'payment_method' => 6,
            'terms' => 'Net 60',
            'creditinvoice_parent_id' => 0,
            'delivery_id' => 0,
            'delivery_location_id' => 0,
            'postal_address_id' => 0,
            'contract_id' => 0,
            'date_created' => '2026-05-01',
            'date_supplied' => '2026-05-02',
            'date_tax_point' => '2026-05-03',
        ];

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e = $repo->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repo);
        $service->saveInvFromRecurring($user, $model, $details, $s);

        Assert::same(2, $model->reqClientId());
        Assert::same(3, $model->reqGroupId());
        Assert::same(4, $model->reqStatusId());
        Assert::same(12.5, $model->getDiscountAmount());
        Assert::same('existing-key', $model->getUrlKey());
        Assert::same('pw', $model->getPassword());
        Assert::same(6, $model->getPaymentMethod());
        Assert::same('Net 60', $model->getTerms());
        Assert::same('2026-05-01', $model->getDateCreated()->format('Y-m-d'));
        Assert::same('2026-05-02', $model->getDateSupplied()->format('Y-m-d'));
        Assert::same('2026-05-03', $model->getDateTaxPoint()->format('Y-m-d'));
    }

    public function saveInvFromRecurringOnNewInvoiceResetsCoreFieldsToFreshDefaultsAndSaves(): void
    {
        $model = new Inv();
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);

        $s = $this->makeSettingRepo();

        $details = [
            'client_id' => 5,
            'group_id' => 6,
            'status_id' => 3,
            'discount_amount' => 20.0,
            'url_key' => 'ignored-key',
            'password' => 'pw2',
            'payment_method' => 6,
            'terms' => 'Net 90',
            'creditinvoice_parent_id' => 0,
            'delivery_id' => 0,
            'delivery_location_id' => 0,
            'postal_address_id' => 0,
            'contract_id' => 0,
            'number' => 'REC-001',
            'date_created' => '2026-05-01',
            'date_supplied' => '2026-05-02',
            'date_tax_point' => '2026-05-03',
        ];

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e = $repo->shouldReceive('save');
        $e->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        $before = date('Y-m-d');
        $service = $this->makeService($repo);
        $service->saveInvFromRecurring($user, $model, $details, $s);
        $after = date('Y-m-d');

        Assert::same(1, $model->reqStatusId());
        Assert::same('REC-001', $model->getNumber());
        Assert::same($user, $model->getUser());
        Assert::same(32, strlen($model->getUrlKey()));
        Assert::same(0, $model->getPaymentMethod());
        Assert::same(0.00, $model->getDiscountAmount());
        Assert::true(in_array($model->getDateCreated()->format('Y-m-d'), [$before, $after], true));
        Assert::true(in_array($model->getDateSupplied()->format('Y-m-d'), [$before, $after], true));
        Assert::true(in_array($model->getDateTaxPoint()->format('Y-m-d'), [$before, $after], true));
    }

    public function saveInvFromRecurringOnExistingInvoiceWritesAnUpdatedAuditLogEntryForTheFieldThatChanged(): void
    {
        $model = new Inv(client_id: 1, group_id: 1, status_id: 1, terms: 'Net 30');
        $model->setId(88);

        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        $user->shouldNotReceive('reqId');

        $s = $this->makeSettingRepo();

        $details = [
            'client_id' => 1,
            'group_id' => 1,
            'status_id' => 1,
            'discount_amount' => 0.0,
            'url_key' => 'k',
            'password' => '',
            'payment_method' => 0,
            'terms' => 'Net 45',
            'creditinvoice_parent_id' => 0,
            'delivery_id' => 0,
            'delivery_location_id' => 0,
            'postal_address_id' => 0,
            'contract_id' => 0,
            'date_created' => (new DateTimeImmutable())->format('Y-m-d'),
            'date_supplied' => (new DateTimeImmutable())->format('Y-m-d'),
            'date_tax_point' => (new DateTimeImmutable())->format('Y-m-d'),
        ];

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e = $repo->shouldReceive('save');
        $e->once()->with($model);

        /** @var InvAuditLog|null $captured */
        $captured = null;
        /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
        $auditLogRepository = m::mock(InvAuditLogRepository::class);
        $e2 = $auditLogRepository->shouldReceive('save');
        $e2->once()->with(m::type(InvAuditLog::class));
        $e2->andReturnUsing(static function (InvAuditLog $log) use (&$captured): void {
            $captured = $log;
        });

        $service = $this->makeService($repo, null, null, null, null, null, $auditLogRepository);
        $service->saveInvFromRecurring($user, $model, $details, $s);

        Assert::notNull($captured);
        Assert::same('updated', $captured->getAction());
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $captured->getChangedFields(), true);
        Assert::same(['old' => 'Net 30', 'new' => 'Net 45'], $decoded['terms'] ?? null);
    }

    public function saveInvFromRecurringOnNewInvoiceWritesACreatedAuditLogEntry(): void
    {
        $model = new Inv();
        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);

        $s = $this->makeSettingRepo();

        $details = [
            'client_id' => 5, 'group_id' => 6, 'status_id' => 3,
            'discount_amount' => 20.0, 'url_key' => 'ignored-key',
            'password' => 'pw2', 'payment_method' => 6, 'terms' => 'Net 90',
            'creditinvoice_parent_id' => 0, 'delivery_id' => 0,
            'delivery_location_id' => 0, 'postal_address_id' => 0,
            'contract_id' => 0, 'number' => 'REC-002',
            'date_created' => '2026-05-01', 'date_supplied' => '2026-05-02',
            'date_tax_point' => '2026-05-03',
        ];

        /** @var InvRepository&m\MockInterface $repo */
        $repo = m::mock(InvRepository::class);
        $e = $repo->shouldReceive('save');
        $e->once()->with($model)->andReturnUsing(self::assignIdOnSave(...));

        /** @var InvAuditLogRepository&m\MockInterface $auditLogRepository */
        $auditLogRepository = m::mock(InvAuditLogRepository::class);
        $e2 = $auditLogRepository->shouldReceive('save');
        $e2->once()->with(m::on(static fn (InvAuditLog $log): bool => $log->getAction() === 'created'
            && $log->reqInvId() === 999
            && $log->getChangedFields() === null));

        $service = $this->makeService($repo, null, null, null, null, null, $auditLogRepository);
        $service->saveInvFromRecurring($user, $model, $details, $s);
    }
}
