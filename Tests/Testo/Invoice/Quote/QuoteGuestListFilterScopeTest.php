<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Quote;

use App\Invoice\Quote\QuoteRepository;
use Cycle\ORM\Select;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Translator\Translator;

/**
 * The guest quote list (client_quotes) shows only the quotes of the clients assigned to the signed-in user. Its two
 * filters (quote number, amount total) used to run as queries of their own over every quote, replacing that list, so a
 * guest could read any client's quotes, drafts included, by adding ?filterQuoteNumber=... to the address. They are now
 * added to the scoped query.
 */
#[Test]
final class QuoteGuestListFilterScopeTest
{
    /** @var list<list{string, array<array-key, mixed>}> every condition the query was given, in order */
    private array $calls = [];

    /** @param list<int> $clients */
    private function build(array $clients, string $number = '', string $total = '', int $status = 0): void
    {
        $this->calls = [];
        /** @var Select<\App\Infrastructure\Persistence\Quote\Quote>&m\MockInterface $select */
        $select = m::mock(Select::class);
        foreach (['where', 'andWhere', 'load'] as $method) {
            $select->shouldReceive($method)->andReturnUsing(
                function (mixed ...$args) use ($method, $select): Select {
                    $this->calls[] = [$method, $args];
                    return $select;
                },
            );
        }
        // EntityReader asks the query for its database driver when it is built
        $select->shouldReceive('getDriver')->andReturn(null);
        $repository = new QuoteRepository(
            $select,
            TypedMock::of(EntityWriter::class),
            new Translator(),
        );

        $repository->repoGuestClientsSentViewedApprovedRejectedCancelled($status, $clients, $number, $total);
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

    /** The columns a condition names: the keys of a where-array, or the text of a column-name argument. */
    private static function describe(mixed $first): string
    {
        if (is_array($first)) {
            return implode(',', array_keys($first));
        }
        return is_string($first) ? $first : '';
    }

    public function withNoFilterTheListIsScopedToTheGuestsClients(): void
    {
        $this->build([5, 6]);

        Assert::true(in_array('where client_id', $this->conditions(), true));
    }

    public function aQuoteNumberFilterIsAddedToTheScopedQueryNotRunOnItsOwn(): void
    {
        $this->build([5, 6], '1001');

        $conditions = $this->conditions();
        Assert::true(in_array('where client_id', $conditions, true), 'the client scope must still be there');
        Assert::true(in_array('andWhere number', $conditions, true));
        // nothing may start a second, unscoped condition for the number
        Assert::false(in_array('where number', $conditions, true));
    }

    public function anAmountTotalFilterIsAddedToTheScopedQueryToo(): void
    {
        $this->build([5, 6], '', '100.00');

        $conditions = $this->conditions();
        Assert::true(in_array('where client_id', $conditions, true));
        Assert::true(in_array('andWhere quoteAmount.total', $conditions, true));
        Assert::false(in_array('where quoteAmount.total', $conditions, true));
    }

    public function bothFiltersTogetherKeepTheScope(): void
    {
        $this->build([5, 6], '1001', '100.00');

        $conditions = $this->conditions();
        Assert::true(in_array('where client_id', $conditions, true));
        Assert::true(in_array('andWhere number', $conditions, true));
        Assert::true(in_array('andWhere quoteAmount.total', $conditions, true));
    }

    public function theStatusTabKeepsItsScopeWhenFilteredToo(): void
    {
        $this->build([5, 6], '1001', '', 2);

        $conditions = $this->conditions();
        Assert::true(in_array('andWhere client_id', $conditions, true));
        Assert::true(in_array('andWhere number', $conditions, true));
    }

    public function blankOrSpaceOnlyFiltersAddNothing(): void
    {
        $this->build([5, 6], '  ', '');

        Assert::false(in_array('andWhere number', $this->conditions(), true));
    }
}
