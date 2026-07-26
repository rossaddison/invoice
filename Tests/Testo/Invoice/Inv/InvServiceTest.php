<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Inv\InvRepository;
use App\Invoice\Inv\InvService;
use App\Invoice\Setting\SettingRepository;
use App\User\UserRepository;
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
        ?UserRepository $uR = null,
        ?DatabaseManager $dbal = null,
    ): InvService {
        return new InvService(
            $repository,
            $translator ?? m::mock(TranslatorInterface::class),
            $cR ?? m::mock(ClientRepository::class),
            $gR ?? m::mock(GroupRepository::class),
            $uR ?? m::mock(UserRepository::class),
            $dbal ?? m::mock(DatabaseManager::class),
        );
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
        /** @var \Mockery\Expectation $e */
        $e = $s->shouldReceive('loadSettings');
        $e->andReturnNull();
        /** @var \Mockery\Expectation $e2 */
        $e2 = $s->shouldReceive('getSetting');
        $e2->with('invoices_due_after')->andReturn($settings['invoices_due_after']);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $s->shouldReceive('getSetting');
        $e3->with('stand_in_code')->andReturn($settings['stand_in_code']);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $s->shouldReceive('getSetting');
        $e4->with('mark_invoices_sent_copy')->andReturn($settings['mark_invoices_sent_copy']);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $s->shouldReceive('getSetting');
        $e5->with('generate_invoice_number_for_draft')->andReturn($settings['generate_invoice_number_for_draft']);
        /** @var \Mockery\Expectation $e6 */
        $e6 = $s->shouldReceive('getSetting');
        $e6->with('invoice_default_payment_method')->andReturn($settings['invoice_default_payment_method']);
        /** @var \Mockery\Expectation $e7 */
        $e7 = $s->shouldReceive('getSetting');
        $e7->with('date_format')->andReturn($settings['date_format']);
        /** @var \Mockery\Expectation $e8 */
        $e8 = $s->shouldReceive('getSetting');
        $e8->with('first_day_of_week')->andReturn($settings['first_day_of_week']);
        /** @var \Mockery\Expectation $e9 */
        $e9 = $s->shouldReceive('getSetting');
        $e9->with('time_zone')->andReturn($settings['time_zone']);

        return $s;
    }

    // ── withTransaction ──────────────────────────────────────────────────

    public function withTransactionInvokesDatabaseTransactionWithGivenCallback(): void
    {
        $callback = static function (): void {
        };

        $database = m::mock(DatabaseInterface::class);
        /** @var \Mockery\Expectation $e */
        $e = $database->shouldReceive('transaction');
        $e->once()->with($callback)->andReturn(true);

        $dbal = m::mock(DatabaseManager::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $dbal->shouldReceive('database');
        $e2->once()->andReturn($database);

        $repo = m::mock(InvRepository::class);
        $service = $this->makeService($repo, null, null, null, null, $dbal);

        $service->withTransaction($callback);
    }

    // ── deleteInv / restoreInv ───────────────────────────────────────────

    public function deleteInvCallsRepositoryDelete(): void
    {
        $inv = new Inv();

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->shouldReceive('delete');
        $e->once()->with($inv);

        $service = $this->makeService($repo);
        $service->deleteInv($inv);
    }

    public function restoreInvClearsDeletedStatusAndSaves(): void
    {
        $inv = new Inv();

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->shouldReceive('save');
        $e->once()->with($inv);

        $service = $this->makeService($repo);
        $service->restoreInv($inv);

        Assert::false($inv->isDeleted());
    }

    // ── setTaxPoint ──────────────────────────────────────────────────────

    public function setTaxPointReturnsDateCreatedWhenClientAttachedAndCreatedIsWithinFourteenDaysAfterSupplied(): void
    {
        $inv = new Inv();
        $inv->setClient(new Client());

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

        $repo = m::mock(InvRepository::class);
        $service = $this->makeService($repo);

        $created = new DateTimeImmutable('2026-01-01');
        $supplied = new DateTimeImmutable('2026-01-01');

        Assert::null($service->setTaxPoint($inv, $supplied, $created));
    }

    public function setTaxPointReturnsDateSuppliedWhenNoClientAttached(): void
    {
        $inv = new Inv();

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
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('reqId');
        $e->andReturn(9);

        $s = $this->makeSettingRepo();
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');
        $gR->shouldNotReceive('repoGroupquery');

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model);

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

    public function saveInvOnNewInvoiceMarksSentAndReadOnlyWhenSettingEnabled(): void
    {
        $model = new Inv(status_id: 9, is_read_only: false);
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo(['mark_invoices_sent_copy' => '1']);
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repo, null, null, $gR);
        $service->saveInv($user, $model, ['terms' => 'Net 30'], $s, $gR);

        Assert::same(2, $model->reqStatusId());
        Assert::true($model->getIsReadOnly());
    }

    public function saveInvGeneratesNumberForDraftWhenSettingEnabled(): void
    {
        $model = new Inv();
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo(['generate_invoice_number_for_draft' => '1']);
        $group = new Group();

        $gR = m::mock(GroupRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $gR->shouldReceive('generateNumber');
        $e2->once()->with(5, true)->andReturn('DRAFT-777');
        /** @var \Mockery\Expectation $e3 */
        $e3 = $gR->shouldReceive('repoGroupquery');
        $e3->once()->with(5)->andReturn($group);

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $repo->shouldReceive('save');
        $e4->once()->with($model);

        $service = $this->makeService($repo, null, null, $gR);
        $service->saveInv($user, $model, ['group_id' => 5, 'terms' => 'Net 30'], $s, $gR);

        Assert::same('DRAFT-777', $model->getNumber());
        Assert::same($group, $model->getGroup());
    }

    public function saveInvAssignsNumberWhenExistingSentInvoiceHasNoNumber(): void
    {
        $model = new Inv();
        $model->setId(42);
        $user = m::mock(User::class);
        $user->shouldNotReceive('reqId');

        $s = $this->makeSettingRepo();
        $group = new Group();

        $gR = m::mock(GroupRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $gR->shouldReceive('generateNumber');
        $e->once()->with(3, true)->andReturn('INV-0100');
        /** @var \Mockery\Expectation $e2 */
        $e2 = $gR->shouldReceive('repoGroupquery');
        $e2->once()->with(3)->andReturn($group);

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e3 */
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
        $user = m::mock(User::class);
        $user->shouldNotReceive('reqId');

        $s = $this->makeSettingRepo();
        $group = new Group();

        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');
        /** @var \Mockery\Expectation $e */
        $e = $gR->shouldReceive('repoGroupquery');
        $e->once()->with(3)->andReturn($group);

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repo, null, null, $gR);
        $service->saveInv($user, $model, ['status_id' => 2, 'group_id' => 3, 'terms' => 'Net 30'], $s, $gR);

        Assert::same('INV-EXISTING', $model->getNumber());
    }

    public function saveInvLeavesTermsUnchangedWhenArrayOmitsTermsDespiteCallingTranslator(): void
    {
        $model = new Inv();
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo();
        $gR = m::mock(GroupRepository::class);
        $gR->shouldNotReceive('generateNumber');

        $translator = m::mock(TranslatorInterface::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $translator->shouldReceive('translate');
        $e2->once()->with('payment.term.general')->andReturn('Net 30 (translated)');

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repo->shouldReceive('save');
        $e3->once()->with($model);

        $service = $this->makeService($repo, $translator, null, $gR);
        $service->saveInv($user, $model, [], $s, $gR);

        // The translated string is computed but never assigned back onto the
        // model (see POSSIBLE ISSUE in the final report) - terms stays at
        // its untouched constructor default.
        Assert::same('', $model->getTerms());
    }

    public function saveInvPersistsClientGroupAndUserWhenIdsProvidedInArray(): void
    {
        $model = new Inv();
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('reqId');
        $e->andReturn(20);

        $client = new Client();
        $group = new Group();
        $fetchedUser = m::mock(User::class);

        $cR = m::mock(ClientRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $cR->shouldReceive('repoClientquery');
        $e2->once()->with(3)->andReturn($client);

        $gR = m::mock(GroupRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $gR->shouldReceive('repoGroupquery');
        $e3->once()->with(5)->andReturn($group);
        $gR->shouldNotReceive('generateNumber');

        $uR = m::mock(UserRepository::class);
        /** @var \Mockery\Expectation $e4 */
        $e4 = $uR->shouldReceive('findById');
        $e4->once()->with(9)->andReturn($fetchedUser);

        $s = $this->makeSettingRepo();

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e5 */
        $e5 = $repo->shouldReceive('save');
        $e5->once()->with($model);

        $service = $this->makeService($repo, null, $cR, $gR, $uR);
        $service->saveInv(
            $user,
            $model,
            ['client_id' => 3, 'group_id' => 5, 'user_id' => 9, 'terms' => 'Net 30'],
            $s,
            $gR,
        );

        Assert::same($client, $model->getClient());
        Assert::same($group, $model->getGroup());
        Assert::same($fetchedUser, $model->getUser());
        Assert::same(20, $model->reqUserId());
    }

    // ── copyInv ──────────────────────────────────────────────────────────

    public function copyInvMapsProvidedArrayFieldsOntoModelAndSaves(): void
    {
        $model = new Inv();
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('reqId');
        $e->andReturn(3);

        $s = $this->makeSettingRepo();

        $translator = m::mock(TranslatorInterface::class);
        /** @var \Mockery\Expectation $e2 */
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

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repo->shouldReceive('save');
        $e3->once()->with($model);

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
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('reqId');
        $e->andReturn(1);

        $s = $this->makeSettingRepo();

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

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $repo->shouldReceive('save');
        $e2->once()->with($model);

        $service = $this->makeService($repo, $translator);
        $service->copyInv($user, $model, $array, $s);

        Assert::same($supplied, $model->getDateSupplied());
        Assert::same($taxPoint, $model->getDateTaxPoint());
        Assert::same(5, $model->getPaymentMethod());
        Assert::same('PO1', $model->getClientPoNumber());
        Assert::same('Jane', $model->getClientPoPerson());
        Assert::same('Net 15', $model->getTerms());
    }

    // ── saveInvFromRecurring ─────────────────────────────────────────────

    public function saveInvFromRecurringOnExistingInvoiceUpdatesFieldsAndSaves(): void
    {
        $model = new Inv();
        $model->setId(77);

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

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e */
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

        $repo = m::mock(InvRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repo->shouldReceive('save');
        $e->once()->with($model);

        $service = $this->makeService($repo);
        $service->saveInvFromRecurring($user, $model, $details, $s);

        Assert::same(1, $model->reqStatusId());
        Assert::same('REC-001', $model->getNumber());
        Assert::same($user, $model->getUser());
        Assert::same(32, strlen($model->getUrlKey()));
        Assert::same(0, $model->getPaymentMethod());
        Assert::same(0.00, $model->getDiscountAmount());
        Assert::same(date('Y-m-d'), $model->getDateCreated()->format('Y-m-d'));
        Assert::same(date('Y-m-d'), $model->getDateSupplied()->format('Y-m-d'));
        Assert::same(date('Y-m-d'), $model->getDateTaxPoint()->format('Y-m-d'));
    }
}
