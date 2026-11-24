<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvGuestFilter;
use App\Invoice\Inv\InvRepository;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Translator\Translator;

/**
 * inv/guest lists only the invoices of the clients assigned to the signed-in user (or allocated to their worker). Its
 * filters used to run as queries of their own over every invoice and replace that list, so adding
 * ?filterClient=<any client id> or ?filterInvNumber=... to the address listed any client's invoices. They are now
 * conditions added to the scoped query, so they can only narrow it.
 */
#[Test]
final class InvGuestListFilterScopeTest
{
    /** @var list<list{string, array<array-key, mixed>}> every condition the query was given, in order */
    private array $calls = [];

    private function repository(): InvRepository
    {
        /** @var Select<Inv>&m\MockInterface $select */
        $select = m::mock(Select::class);
        foreach (['where', 'andWhere', 'load'] as $method) {
            $select->shouldReceive($method)->andReturnUsing(
                function (mixed ...$args) use ($method, $select): Select {
                    $this->calls[] = [$method, $args];
                    return $select;
                },
            );
        }
        // EntityReader asks the query for its database driver when it is built; the credit-note lookup fetches
        $select->shouldReceive('getDriver')->andReturn(null);
        $select->shouldReceive('fetchAll')->andReturn([]);

        $repository = new InvRepository($select, TypedMock::of(EntityWriter::class), new Translator());
        // the constructor's own conditions (active clients only) are not what is being checked
        $this->calls = [];

        return $repository;
    }

    /** @param list<int> $clients */
    private function clientList(array $clients, InvGuestFilter $filter, int $status = 0): void
    {
        $this->calls = [];
        $this->repository()->repoGuestClientsPostDraft($status, $clients, $filter);
    }

    private function filter(
        string $number = '',
        string $client = '',
        string $total = '',
        string $paid = '',
        string $balance = '',
        string $credit = '',
    ): InvGuestFilter {
        $filter = new InvGuestFilter();
        $filter->filterInvNumber = $number;
        $filter->filterClient = $client;
        $filter->filterInvAmountTotal = $total;
        $filter->filterInvAmountPaid = $paid;
        $filter->filterInvAmountBalance = $balance;
        $filter->filterCreditInvNumber = $credit;

        return $filter;
    }

    /** The columns a condition names: the keys of a where-array, or the text of a column-name argument. */
    private static function describe(mixed $first): string
    {
        if (is_array($first)) {
            return implode(',', array_keys($first));
        }
        return is_string($first) ? $first : '';
    }

    /** The client ids of a client_id IN (...) condition, or null when the argument is not one. */
    private static function scopedClientIds(mixed $condition): ?array
    {
        if (!is_array($condition) || !is_array($condition['client_id'] ?? null)) {
            return null;
        }
        return self::idsOf($condition['client_id']['in'] ?? null);
    }

    private static function idsOf(mixed $in): ?array
    {
        return $in instanceof Parameter ? array_map('intval', (array) $in->getValue()) : null;
    }

    /** @return list<string> a readable line per condition, e.g. "where client_id" or "andWhere number" */
    private function conditions(): array
    {
        $lines = [];
        foreach ($this->calls as [$method, $args]) {
            $lines[] = $method . ' ' . self::describe($args[0] ?? null);
        }
        return $lines;
    }

    private function has(string $condition): bool
    {
        return in_array($condition, $this->conditions(), true);
    }

    public function withNoFilterTheListIsScopedToTheGuestsClients(): void
    {
        $this->clientList([5, 6], $this->filter());

        Assert::true($this->has('where client_id'));
        Assert::false($this->has('andWhere number'));
        Assert::false($this->has('andWhere client_id'));
    }

    public function theScopeIsTheGuestsOwnClientsAndNothingElse(): void
    {
        $this->clientList([5, 6], $this->filter(client: '99'));

        foreach ($this->calls as [$method, $args]) {
            $ids = $method === 'where' ? self::scopedClientIds($args[0] ?? null) : null;
            if (null !== $ids) {
                Assert::same($ids, [5, 6]);
                return;
            }
        }
        Assert::true(false, 'the client scope was not in the query');
    }

    public function aClientFilterOnAnotherClientIsAnExtraConditionOnTheScopedQueryNotARewrite(): void
    {
        // client 99 is not one of this guest's clients: the scope stays [5, 6] and client_id = 99 is ANDed on
        $this->clientList([5, 6], $this->filter(client: '99'));

        Assert::true($this->has('where client_id'));
        Assert::true($this->has('andWhere client_id'));
    }

    public function anInvoiceNumberFilterNarrowsTheScopedQuery(): void
    {
        $this->clientList([5, 6], $this->filter(number: ' INV-1001 '));

        Assert::true($this->has('where client_id'));
        Assert::true($this->has('andWhere number'));
        // nothing may start a second, unscoped condition for the number
        Assert::false($this->has('where number'));
    }

    public function theAmountFiltersNarrowTheScopedQueryToo(): void
    {
        $this->clientList([5, 6], $this->filter(total: '100', paid: '50', balance: '25'));

        Assert::true($this->has('where client_id'));
        Assert::true($this->has('andWhere invAmount.total'));
        Assert::true($this->has('andWhere invAmount.paid'));
        Assert::true($this->has('andWhere invAmount.balance'));
    }

    public function aCreditNoteFilterNarrowsTheScopedQueryToo(): void
    {
        $this->clientList([5, 6], $this->filter(credit: 'INV-1'));

        Assert::true($this->has('where client_id'));
        // no parent invoice matched in the stub, so nothing can be listed
        Assert::true($this->has('andWhere id'));
    }

    public function theStatusTabKeepsItsScopeWhenFilteredToo(): void
    {
        $this->clientList([5, 6], $this->filter(number: 'INV-1001'), 2);

        Assert::true($this->has('where client_id'));
        Assert::true($this->has('andWhere number'));
    }

    public function emptyAndZeroFiltersAddNothing(): void
    {
        $this->clientList([5, 6], $this->filter(number: '', client: '0', total: '0', paid: '', balance: '0'));

        Assert::false($this->has('andWhere number'));
        Assert::false($this->has('andWhere client_id'));
        Assert::false($this->has('andWhere invAmount.total'));
    }

    public function aWorkersListKeepsTheirAllocationScopeWhenFiltered(): void
    {
        $this->calls = [];
        $this->repository()->repoWorkerVisible(0, 7, false, $this->filter(number: 'INV-1001', client: '99'));

        Assert::true($this->has('where @or'));
        Assert::true($this->has('andWhere number'));
        Assert::true($this->has('andWhere client_id'));
    }
}
