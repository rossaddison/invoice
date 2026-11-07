<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareDemo;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Infrastructure\Persistence\Family\Family;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\Setting\Setting;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\AppConstants;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Enum\PostponeReason;
use Cycle\Database\Query\SelectQuery;
use DateTimeImmutable;
use Yiisoft\Security\Random;

/**
 * TEST-SUPPORT TOOL -- not for production use.
 *
 * A small, end-to-end HomeCare world for manual testing of the per-side crew
 * allocation: a lead worker and two crew workers (each with a login), a
 * customer login, a street of six houses (clients with a Dwelling each) and
 * six DRAFT invoices in different allocation states -- so every screen and
 * every rule (chips, settle buttons, send gate, run sheet) has something to
 * act on without hours of manual set-up.
 *
 * Everything it owns is recognisable: logins start with "hc-" and every
 * street/client name starts with "[HC DEMO]". seed() always begins with
 * cleanup(), so running it again gives a fresh copy; cleanup() removes
 * everything it created (and anything hung off it while testing -- sent logs,
 * audit rows, payments) using the database's own foreign-key metadata.
 */
final readonly class HomeCareDemoSeeder
{
    public const string MARKER = '[HC DEMO]';
    public const string LOGIN_PREFIX = 'hc-';
    public const string PASSWORD = 'HomeCare-Demo-2026!';

    private const int GROUP_ID = 1;
    private const int TAX_RATE_ID = 1;
    private const int ADMIN_USER_ID = 1;
    private const int HOUSES = 6;

    public function __construct(private HomeCareSeedDeps $d)
    {
    }

    /**
     * @return list<array{login: string, role: string, note: string}> the logins created
     */
    public function seed(): array
    {
        $this->cleanup();
        $lead = $this->worker('hc-lead', 'Lee', 'Lead');
        $dave = $this->worker('hc-dave', 'Dave', 'Smith');
        $sam = $this->worker('hc-sam', 'Sam', 'Jones');
        $customerUser = $this->user('hc-customer', 'Customer');
        $this->role($customerUser, AppConstants::ROLE_OBSERVER);

        $family = new Family(family_name: self::MARKER . ' Demo Street');
        $family->setStreetSortOrder(1);
        $this->d->fR->save($family);

        $clients = [];
        for ($n = 1; $n <= self::HOUSES; $n++) {
            $clients[$n] = $this->house($family, $n);
        }
        $this->d->ucR->save(new UserClient(user_id: $customerUser->reqId(), client_id: $clients[1]->reqId()));

        $this->invoice($clients[1], 1, 12.0);
        $this->invoice($clients[2], 2, 12.0);
        $this->wholeHouse($this->invoice($clients[3], 3, 15.0), $dave);
        $this->split($this->invoice($clients[4], 4, 18.0), $dave, $sam);
        $this->completedWholeHouse($this->invoice($clients[5], 5, 15.0), $sam, $lead);
        $this->frontsDoneBackPostponed($this->invoice($clients[6], 6, 20.0), $dave, $sam, $lead);

        $this->configure($lead, [$lead, $dave, $sam]);

        return [
            ['login' => 'hc-lead', 'role' => 'worker (the lead)', 'note' => 'sees every HomeCare house (any invoice whose client has a Dwelling), plus the crew form, chips and allocate/settle controls on inv/guest'],
            ['login' => 'hc-dave', 'role' => 'worker', 'note' => 'sees houses 3, 4 and 6 (his sides); can complete/postpone/cancel his own'],
            ['login' => 'hc-sam', 'role' => 'worker', 'note' => 'sees houses 4, 5 and 6 (his sides)'],
            ['login' => 'hc-customer', 'role' => 'customer (observer)', 'note' => 'linked to House 1 only; must never see drafts or any allocation controls'],
        ];
    }

    /**
     * Removes everything seed() created, and anything hung off it since.
     *
     * @return array<string, int> rows removed per table
     */
    public function cleanup(): array
    {
        $db = $this->d->databases->database();
        $userIds = $this->ids($db->select('id')->from('user')->where('login', 'LIKE', self::LOGIN_PREFIX . '%'));
        $clientIds = $this->ids($db->select('id')->from('client')->where('client_name', 'LIKE', self::MARKER . '%'));
        $familyIds = $this->ids($db->select('id')->from('family')->where('family_name', 'LIKE', self::MARKER . '%'));
        $workerIds = [] === $userIds ? [] : $this->ids($db->select('id')->from('worker')->where('user_id', 'IN', $userIds));
        $invIds = [] === $clientIds ? [] : $this->ids($db->select('id')->from('inv')->where('client_id', 'IN', $clientIds));
        $itemIds = [] === $invIds ? [] : $this->ids($db->select('id')->from('inv_item')->where('inv_id', 'IN', $invIds));
        $dwellingIds = [] === $familyIds ? [] : $this->ids($db->select('id')->from('dwelling')->where('family_id', 'IN', $familyIds));
        $userInvIds = [] === $userIds ? [] : $this->ids($db->select('id')->from('user_inv')->where('user_id', 'IN', $userIds));

        $removed = [];
        $db->execute('SET FOREIGN_KEY_CHECKS=0');
        try {
            $removed += $this->deleteIn('inv_worker_allocation', 'inv_id', $invIds);
            $removed += $this->deleteIn('inv_item_amount', 'inv_item_id', $itemIds);
            $removed += $this->deleteReferencing('inv', $invIds);
            $removed += $this->deleteIn('inv', 'id', $invIds);
            $removed += $this->deleteReferencing('client', $clientIds);
            $removed += $this->deleteIn('client', 'id', $clientIds);
            $removed += $this->deleteIn('dwelling', 'id', $dwellingIds);
            $removed += $this->deleteIn('family', 'id', $familyIds);
            $removed += $this->deleteReferencing('worker', $workerIds);
            $removed += $this->deleteIn('worker', 'id', $workerIds);
            $removed += $this->deleteIn('user_rbac_link', 'user_inv_id', $userInvIds);
            $removed += $this->deleteIn('user_inv', 'id', $userInvIds);
            $removed += $this->deleteIn('yii_rbac_assignment', 'user_id', array_map('strval', $userIds));
            $removed += $this->deleteReferencing('user', $userIds);
            $removed += $this->deleteIn('user', 'id', $userIds);
        } finally {
            $db->execute('SET FOREIGN_KEY_CHECKS=1');
        }
        $this->resetSettingsPointingAt($workerIds);
        return array_filter($removed);
    }

    private function user(string $login, string $name): User
    {
        $user = new User($login, $login . '@homecare-demo.test', self::PASSWORD);
        $this->d->uR->save($user);
        $userInv = new UserInv();
        $userInv->setUserId($user->reqId());
        $userInv->setType(1);
        $userInv->setActive(true);
        $userInv->setName($name);
        $this->d->uiR->save($userInv);
        return $user;
    }

    private function role(User $user, string $role): void
    {
        $this->d->manager->assign($role, (string) $user->reqId());
        $userInv = $this->d->uiR->repoUserInvUserIdquery($user->reqId());
        null !== $userInv && $this->d->urlR->upsert($userInv->reqId(), $user->reqId());
    }

    private function worker(string $login, string $first, string $last): Worker
    {
        $user = $this->user($login, $first . ' ' . $last);
        $this->role($user, AppConstants::ROLE_WORKER);
        $worker = new Worker($first, $last);
        $worker->setUser($user);
        $this->d->wR->save($worker);
        return $worker;
    }

    private function house(Family $family, int $number): Client
    {
        $dwelling = new Dwelling(
            family_id: $family->reqId(),
            house_number_numeric: $number,
            postcode: 'DM1' . $number . 'AA',
            source: 'hc_demo',
        );
        $this->d->dwR->save($dwelling);
        $client = new Client(client_name: self::MARKER . ' House ' . $number);
        $client->setClientActive(true);
        $client->setClientEmail('house' . $number . '@homecare-demo.test');
        $client->setClientBuildingNumber((string) $number);
        $client->setClientCity('Demoville');
        $client->setClientZip('DM1 ' . $number . 'AA');
        $client->setDwellingId($dwelling->reqId());
        $this->d->cR->save($client);
        return $client;
    }

    /**
     * A draft invoice with one window-cleaning line, for one house.
     */
    private function invoice(Client $client, int $number, float $price): Inv
    {
        $inv = new Inv(
            client_id: $client->reqId(),
            user_id: self::ADMIN_USER_ID,
            group_id: self::GROUP_ID,
            so_id: 0,
            status_id: 1,
            number: 'HCD-' . $number,
            // url_key is unique per invoice (its default '' collides on the second one)
            url_key: Random::string(32),
            payment_method: 6,
        );
        // Inv's client/group/user are BelongsTo relations Cycle persists from;
        // the scalar ids alone write no row (see SeedPeppolHappyPathFixtureCommand).
        $inv->setClient($client);
        $inv->setGroup($this->d->gR->repoGroupquery(self::GROUP_ID));
        $inv->setUser($this->d->uR->findById(self::ADMIN_USER_ID));
        $this->d->iR->save($inv);

        $item = new InvItem(
            name: 'Window cleaning',
            description: self::MARKER . ' window cleaning, house ' . $number,
            quantity: 1.0,
            price: $price,
            discount_amount: 0.0,
            inv_id: $inv->reqId(),
            tax_rate_id: self::TAX_RATE_ID,
        );
        $item->setInv($inv);
        $this->d->iiR->save($item);
        $inv->getItems()->add($item);
        $this->d->iiaR->save(new InvItemAmount(
            inv_item_id: $item->reqId(),
            subtotal: $price,
            tax_total: 0.0,
            total: $price,
        ));
        $amount = $inv->getInvAmount();
        $amount->setInv($inv);
        $amount->setInvId($inv->reqId());
        $amount->setItemSubtotal($price);
        $amount->setItemTaxTotal(0.0);
        $amount->setTotal($price);
        $amount->setBalance($price);
        $this->d->iaR->save($amount);
        return $inv;
    }

    private function wholeHouse(Inv $inv, Worker $worker): void
    {
        $this->d->allocations->allocate($inv->reqId(), AllocationSide::Both, $worker->reqId());
        $this->refresh($inv, $worker);
    }

    private function split(Inv $inv, Worker $front, Worker $back): void
    {
        $this->d->allocations->allocate($inv->reqId(), AllocationSide::Front, $front->reqId());
        $this->d->allocations->allocate($inv->reqId(), AllocationSide::Back, $back->reqId());
        $this->refresh($inv, null);
    }

    private function completedWholeHouse(Inv $inv, Worker $worker, Worker $lead): void
    {
        $this->d->allocations->allocate($inv->reqId(), AllocationSide::Both, $worker->reqId());
        foreach ($this->d->allocations->forInv($inv->reqId()) as $allocation) {
            $this->d->allocations->decide($allocation, AllocationStatus::Completed, $worker->reqId(), $lead->reqId());
        }
        $this->refresh($inv, $worker);
    }

    private function frontsDoneBackPostponed(Inv $inv, Worker $front, Worker $back, Worker $lead): void
    {
        $this->d->allocations->allocate($inv->reqId(), AllocationSide::Front, $front->reqId());
        $this->d->allocations->allocate($inv->reqId(), AllocationSide::Back, $back->reqId());
        foreach ($this->d->allocations->forInv($inv->reqId()) as $allocation) {
            AllocationSide::Front === $allocation->getSide()
                ? $this->d->allocations->decide($allocation, AllocationStatus::Completed, $front->reqId(), $lead->reqId())
                : $this->d->allocations->decide($allocation, AllocationStatus::Postponed, $back->reqId(), $lead->reqId(), PostponeReason::GateLocked);
        }
        $this->refresh($inv, null);
    }

    /**
     * Keeps the legacy single worker (whole-house only) and the send gate in
     * step with the allocations, as the allocate/settle actions do.
     */
    private function refresh(Inv $inv, ?Worker $legacyWorker): void
    {
        $inv->setWorker($legacyWorker);
        $inv->setWorkerAllocatedAt($this->d->allocations->hasAllocations($inv->reqId()) ? new DateTimeImmutable() : null);
        $this->d->gate->apply($inv);
        $this->d->iR->save($inv);
    }

    /**
     * The lead, today's crew (everyone, whole-house mode) and HomeCare mode.
     *
     * @param list<Worker> $crew
     */
    private function configure(Worker $lead, array $crew): void
    {
        $this->upsertSetting('homecare_lead_worker_id', (string) $lead->reqId());
        $this->d->crew->save(
            array_map(static fn (Worker $w): int => $w->reqId(), $crew),
            false,
            array_map(static fn (Worker $w): int => $w->reqId(), $crew),
        );
    }

    private function upsertSetting(string $key, string $value): void
    {
        $setting = $this->d->sR->withKey($key) ?? new Setting($key, $value);
        $setting->setSettingValue($value);
        $this->d->sR->save($setting);
        $this->d->sR->setSetting($key, $value);
    }

    /**
     * Clears the lead / crew settings if they still point at a deleted demo worker.
     *
     * @param list<int> $workerIds
     */
    private function resetSettingsPointingAt(array $workerIds): void
    {
        if ([] === $workerIds) {
            return;
        }
        if (in_array((int) $this->d->sR->getSetting('homecare_lead_worker_id'), $workerIds, true)) {
            $this->upsertSetting('homecare_lead_worker_id', '0');
        }
        if ([] !== array_intersect($this->d->crew->workerIds(), $workerIds)) {
            $this->d->crew->save([], false, []);
        }
    }

    /**
     * The `id` column of every row a select query returns.
     *
     * @return list<int>
     */
    private function ids(SelectQuery $query): array
    {
        /** @var list<array{id: int|string}> $rows */
        $rows = $query->fetchAll();
        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    /**
     * @param list<int|string> $values
     * @return array<string, int>
     */
    private function deleteIn(string $table, string $column, array $values): array
    {
        if ([] === $values) {
            return [];
        }
        $count = $this->d->databases->database()->delete($table, [$column => ['IN' => $values]])->run();
        return [$table => $count];
    }

    /**
     * Deletes the rows of every table with a foreign key to $table's primary
     * key whose value is among $ids (sent logs, audit rows, payments... that
     * testing may have created).
     *
     * @param list<int> $ids
     * @return array<string, int>
     */
    private function deleteReferencing(string $table, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }
        $db = $this->d->databases->database();
        $removed = [];
        /** @var list<array{t: string, c: string}> $refs */
        $refs = $db->query(
            'SELECT table_name AS t, column_name AS c FROM information_schema.key_column_usage '
            . 'WHERE table_schema = DATABASE() AND referenced_table_name = ?',
            [$table]
        )->fetchAll();
        foreach ($refs as $ref) {
            $removed += $this->deleteIn($ref['t'], $ref['c'], $ids);
        }
        return $removed;
    }
}
