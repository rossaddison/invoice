<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Domain;

use App\Bookkeeping\Domain\AccountRole;
use App\Bookkeeping\Domain\BookkeepingLine;
use App\Bookkeeping\Domain\DebitCredit;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class BookkeepingLineTest
{
    public function isDebitIsTrueOnlyForADebitLine(): void
    {
        $line = new BookkeepingLine(AccountRole::Bank, DebitCredit::Debit, 10.00);

        Assert::true($line->isDebit());
        Assert::false($line->isCredit());
    }

    public function isCreditIsTrueOnlyForACreditLine(): void
    {
        $line = new BookkeepingLine(AccountRole::Bank, DebitCredit::Credit, 10.00);

        Assert::false($line->isDebit());
        Assert::true($line->isCredit());
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsAZeroAmount(): void
    {
        new BookkeepingLine(AccountRole::Bank, DebitCredit::Debit, 0.00); // NOSONAR php:S1848 — the constructor throwing is the assertion
    }

    #[ExpectException(\InvalidArgumentException::class)]
    public function rejectsANegativeAmount(): void
    {
        new BookkeepingLine(AccountRole::Bank, DebitCredit::Debit, -5.00); // NOSONAR php:S1848 — the constructor throwing is the assertion
    }

    public function carriesOptionalTaxCodeAndDescriptionAsGiven(): void
    {
        $line = new BookkeepingLine(
            AccountRole::Vat,
            DebitCredit::Credit,
            20.00,
            'GB_STANDARD',
            'VAT on INV-1024',
        );

        Assert::same('GB_STANDARD', $line->taxCode);
        Assert::same('VAT on INV-1024', $line->description);
    }

    public function taxCodeAndDescriptionDefaultToNull(): void
    {
        $line = new BookkeepingLine(AccountRole::Sales, DebitCredit::Credit, 100.00);

        Assert::null($line->taxCode);
        Assert::null($line->description);
    }
}
