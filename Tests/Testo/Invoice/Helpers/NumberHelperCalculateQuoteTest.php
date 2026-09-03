<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\QuoteAllowanceCharge\QuoteAllowanceCharge;
use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Infrastructure\Persistence\QuoteItemAmount\QuoteItemAmount;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * calculateQuote() -- structurally near-identical to calculateInv() (see
 * NumberHelperCalculateInvTest), covering the same allowance/charge
 * sign-flip and VAT-registration branching, but for the Quote side, plus
 * QuoteCalcTrait's own previously-untested saveQuoteAmountTotals() /
 * setQuoteAmountFields() (the update-in-place / zero-out-stale-row /
 * create-new-row branches).
 *
 * The one real wrinkle: quoteCalculateTotalsofItemTotals() reads each
 * item's id via PHP's default *public-property-only* object iteration
 * (`foreach ($item as $key => $value)`), which only works because
 * QuoteItem::$id is deliberately the sole public, constructor-promoted
 * property on the class -- a Mockery mock built without running the real
 * constructor won't reliably expose that, so real `new QuoteItem(id: ...)`
 * instances are used here instead of mocks for that one piece.
 */
#[Test]
final class NumberHelperCalculateQuoteTest
{
    /**
     * @param array<array-key, object> $items
     */
    private function entityReaderOf(array $items): EntityReader
    {
        /** @var EntityReader&m\MockInterface $reader */
        $reader = m::mock(EntityReader::class);
        $reader->shouldReceive('getIterator')->andReturnUsing(
            static function () use ($items) {
                yield from $items;
            },
        );
        return $reader;
    }

    /**
     * @param array<string, string> $settings
     */
    private function makeHelper(array $settings): NumberHelper
    {
        $sRepo = (new ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor(); // NOSONAR: php:S3011
        $sRepo->settingsArray = $settings;
        return new NumberHelper($sRepo);
    }

    private function makeQuoteItemAmount(): QuoteItemAmount
    {
        /** @var QuoteItemAmount&m\MockInterface $amount */
        $amount = m::mock(QuoteItemAmount::class);
        $amount->shouldReceive('getSubTotal')->andReturn(100.0);
        $amount->shouldReceive('getTaxTotal')->andReturn(20.0);
        $amount->shouldReceive('getDiscount')->andReturn(10.0);
        $amount->shouldReceive('getCharge')->andReturn(0.0);
        $amount->shouldReceive('getAllowance')->andReturn(0.0);
        $amount->shouldReceive('getTotal')->andReturn(110.0);
        return $amount;
    }

    private function makeCharge(bool $isCharge, float $amount, float $vatOrTax): QuoteAllowanceCharge
    {
        /** @var AllowanceCharge&m\MockInterface $allowanceCharge */
        $allowanceCharge = m::mock(AllowanceCharge::class);
        $allowanceCharge->shouldReceive('getIdentifier')->andReturn($isCharge);

        /** @var QuoteAllowanceCharge&m\MockInterface $qac */
        $qac = m::mock(QuoteAllowanceCharge::class);
        $qac->shouldReceive('getAllowanceCharge')->andReturn($allowanceCharge);
        $qac->shouldReceive('getAmount')->andReturn($amount);
        $qac->shouldReceive('getVatOrTax')->andReturn($vatOrTax);
        return $qac;
    }

    private function makeQrForDiscount(float $discountAmount = 6.0): QR
    {
        /** @var Quote&m\MockInterface $quote */
        $quote = m::mock(Quote::class);
        $quote->shouldReceive('getDiscountAmount')->andReturn($discountAmount);

        /** @var QR&m\MockInterface $qR */
        $qR = m::mock(QR::class);
        $qR->shouldReceive('repoQuoteUnLoadedquery')->once()->with(9)->andReturn($quote);
        return $qR;
    }

    /**
     * No quote-level tax rates -- calculateQuoteTaxes()'s own logic is
     * already covered directly in NumberHelperTaxCalculationsTest, so
     * giving it nothing to do here keeps these tests focused on
     * calculateQuote()'s own branching.
     */
    private function makeQtrRWithNoTaxRates(): QTRR
    {
        /** @var QTRR&m\MockInterface $qtrR */
        $qtrR = m::mock(QTRR::class);
        $qtrR->shouldReceive('repoQuotequery')->with(9)->andReturn($this->entityReaderOf([]));
        $qtrR->shouldReceive('repoCount')->with(9)->andReturn(0);
        return $qtrR;
    }

    public function calculateQuoteHappyPathWithAChargeUpdatesAnExistingAmountRow(): void
    {
        // 90 (item subtotal-discount) + 20 (item tax) + 0 (no quote-level
        // tax rates) + 5 (charge amount) + 1 (charge vat/tax) = 116, minus
        // the quote's own 6.00 customer discount = 110.00. An item exists
        // (count=1) and an QuoteAmount row already exists (count=1) -- the
        // update-in-place branch of saveQuoteAmountTotals().
        $charge = $this->makeCharge(isCharge: true, amount: 5.0, vatOrTax: 1.0);

        /** @var ACQR&m\MockInterface $acqR */
        $acqR = m::mock(ACQR::class);
        $acqR->shouldReceive('repoACQquery')->once()->with(9)->andReturn($this->entityReaderOf([$charge]));

        /** @var QIR&m\MockInterface $qiR */
        $qiR = m::mock(QIR::class);
        $qiR->shouldReceive('repoQuoteItemIdquery')->once()->with(9)
            ->andReturn($this->entityReaderOf([new QuoteItem(id: 301)]));
        $qiR->shouldReceive('repoCount')->once()->with(9)->andReturn(1);

        /** @var QIAR&m\MockInterface $qiaR */
        $qiaR = m::mock(QIAR::class);
        $qiaR->shouldReceive('repoQuoteItemAmountquery')->once()->with(301)
            ->andReturn($this->makeQuoteItemAmount());

        /** @var QuoteAmount&m\MockInterface $quoteAmount */
        $quoteAmount = m::mock(QuoteAmount::class);
        $quoteAmount->shouldReceive('setQuoteId')->once()->with(9);
        $quoteAmount->shouldReceive('setItemSubtotal')->once()->with(90.0);
        $quoteAmount->shouldReceive('setItemTaxTotal')->once()->with(20.0);
        $quoteAmount->shouldReceive('setPackhandleshipTotal')->once()->with(5.0);
        $quoteAmount->shouldReceive('setPackhandleshipTax')->once()->with(1.0);
        $quoteAmount->shouldReceive('setTaxTotal')->once()->with(0.00);
        $quoteAmount->shouldReceive('setTotal')->once()->with(110.0);

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with(9)->andReturn(1);
        $qaR->shouldReceive('repoQuotequery')->once()->with(9)->andReturn($quoteAmount);
        $qaR->shouldReceive('save')->once()->with($quoteAmount);

        $helper = $this->makeHelper(['enable_vat_registration' => '0']);

        $helper->calculateQuote(9, $acqR, $qiR, $qiaR, $this->makeQtrRWithNoTaxRates(), $qaR, $this->makeQrForDiscount());
    }

    public function calculateQuoteSubtractsAnAllowanceInsteadOfAddingIt(): void
    {
        // Same shape, but identifier=false (an allowance, not a charge) --
        // both the amount and vat/tax must be *subtracted*, not added.
        $allowance = $this->makeCharge(isCharge: false, amount: 5.0, vatOrTax: 1.0);

        /** @var ACQR&m\MockInterface $acqR */
        $acqR = m::mock(ACQR::class);
        $acqR->shouldReceive('repoACQquery')->once()->with(9)->andReturn($this->entityReaderOf([$allowance]));

        /** @var QIR&m\MockInterface $qiR */
        $qiR = m::mock(QIR::class);
        $qiR->shouldReceive('repoQuoteItemIdquery')->once()->with(9)
            ->andReturn($this->entityReaderOf([new QuoteItem(id: 301)]));
        $qiR->shouldReceive('repoCount')->once()->with(9)->andReturn(1);

        /** @var QIAR&m\MockInterface $qiaR */
        $qiaR = m::mock(QIAR::class);
        $qiaR->shouldReceive('repoQuoteItemAmountquery')->once()->with(301)
            ->andReturn($this->makeQuoteItemAmount());

        /** @var QuoteAmount&m\MockInterface $quoteAmount */
        $quoteAmount = m::mock(QuoteAmount::class);
        $quoteAmount->shouldReceive('setQuoteId')->once()->with(9);
        $quoteAmount->shouldReceive('setItemSubtotal')->once()->with(90.0);
        $quoteAmount->shouldReceive('setItemTaxTotal')->once()->with(20.0);
        $quoteAmount->shouldReceive('setPackhandleshipTotal')->once()->with(-5.0);
        $quoteAmount->shouldReceive('setPackhandleshipTax')->once()->with(-1.0);
        $quoteAmount->shouldReceive('setTaxTotal')->once()->with(0.00);
        // 110 - 5 - 1 = 104, minus the 6.00 customer discount = 98.00.
        $quoteAmount->shouldReceive('setTotal')->once()->with(98.0);

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with(9)->andReturn(1);
        $qaR->shouldReceive('repoQuotequery')->once()->with(9)->andReturn($quoteAmount);
        $qaR->shouldReceive('save')->once()->with($quoteAmount);

        $helper = $this->makeHelper(['enable_vat_registration' => '0']);

        $helper->calculateQuote(9, $acqR, $qiR, $qiaR, $this->makeQtrRWithNoTaxRates(), $qaR, $this->makeQrForDiscount());
    }

    public function calculateQuoteTreatsAMissingAllowanceChargeRelationAsAnAllowance(): void
    {
        // getAllowanceCharge() returns null -> the nullsafe chain makes
        // $isCharge null, and `if (null)` takes the same subtract path as
        // an explicit allowance (false), not a crash and not the add path.
        /** @var QuoteAllowanceCharge&m\MockInterface $qac */
        $qac = m::mock(QuoteAllowanceCharge::class);
        $qac->shouldReceive('getAllowanceCharge')->andReturn(null);
        $qac->shouldReceive('getAmount')->andReturn(5.0);
        $qac->shouldReceive('getVatOrTax')->andReturn(1.0);

        /** @var ACQR&m\MockInterface $acqR */
        $acqR = m::mock(ACQR::class);
        $acqR->shouldReceive('repoACQquery')->once()->with(9)->andReturn($this->entityReaderOf([$qac]));

        /** @var QIR&m\MockInterface $qiR */
        $qiR = m::mock(QIR::class);
        $qiR->shouldReceive('repoQuoteItemIdquery')->once()->with(9)
            ->andReturn($this->entityReaderOf([new QuoteItem(id: 301)]));
        $qiR->shouldReceive('repoCount')->once()->with(9)->andReturn(1);

        /** @var QIAR&m\MockInterface $qiaR */
        $qiaR = m::mock(QIAR::class);
        $qiaR->shouldReceive('repoQuoteItemAmountquery')->once()->with(301)
            ->andReturn($this->makeQuoteItemAmount());

        /** @var QuoteAmount&m\MockInterface $quoteAmount */
        $quoteAmount = m::mock(QuoteAmount::class);
        $quoteAmount->shouldReceive('setQuoteId');
        $quoteAmount->shouldReceive('setItemSubtotal');
        $quoteAmount->shouldReceive('setItemTaxTotal');
        // The only assertion that actually matters for this test.
        $quoteAmount->shouldReceive('setPackhandleshipTotal')->once()->with(-5.0);
        $quoteAmount->shouldReceive('setPackhandleshipTax')->once()->with(-1.0);
        $quoteAmount->shouldReceive('setTaxTotal');
        $quoteAmount->shouldReceive('setTotal');

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with(9)->andReturn(1);
        $qaR->shouldReceive('repoQuotequery')->once()->with(9)->andReturn($quoteAmount);
        $qaR->shouldReceive('save')->once()->with($quoteAmount);

        $helper = $this->makeHelper(['enable_vat_registration' => '0']);

        $helper->calculateQuote(9, $acqR, $qiR, $qiaR, $this->makeQtrRWithNoTaxRates(), $qaR, $this->makeQrForDiscount());
    }

    public function calculateQuoteSkipsQuoteTaxesEntirelyUnderVatRegistration(): void
    {
        // enable_vat_registration !== '0' -> calculateQuoteTaxes() must
        // never be reached at all, not even to compute a zero -- the QTRR
        // mock below has zero expectations set up and would fail loudly on
        // any unexpected call.
        /** @var ACQR&m\MockInterface $acqR */
        $acqR = m::mock(ACQR::class);
        $acqR->shouldReceive('repoACQquery')->once()->with(9)->andReturn($this->entityReaderOf([]));

        /** @var QIR&m\MockInterface $qiR */
        $qiR = m::mock(QIR::class);
        $qiR->shouldReceive('repoQuoteItemIdquery')->once()->with(9)
            ->andReturn($this->entityReaderOf([new QuoteItem(id: 301)]));
        $qiR->shouldReceive('repoCount')->once()->with(9)->andReturn(1);

        /** @var QIAR&m\MockInterface $qiaR */
        $qiaR = m::mock(QIAR::class);
        $qiaR->shouldReceive('repoQuoteItemAmountquery')->once()->with(301)
            ->andReturn($this->makeQuoteItemAmount());

        /** @var QTRR&m\MockInterface $qtrR */
        $qtrR = m::mock(QTRR::class);
        $qtrR->shouldNotReceive('repoQuotequery');
        $qtrR->shouldNotReceive('repoCount');

        /** @var QuoteAmount&m\MockInterface $quoteAmount */
        $quoteAmount = m::mock(QuoteAmount::class);
        $quoteAmount->shouldReceive('setQuoteId');
        $quoteAmount->shouldReceive('setItemSubtotal');
        $quoteAmount->shouldReceive('setItemTaxTotal');
        $quoteAmount->shouldReceive('setPackhandleshipTotal')->once()->with(0.00);
        $quoteAmount->shouldReceive('setPackhandleshipTax')->once()->with(0.00);
        // The only assertion that actually matters for this test: taxes
        // stay exactly zero under the VAT regime, regardless of QTRR.
        $quoteAmount->shouldReceive('setTaxTotal')->once()->with(0.00);
        $quoteAmount->shouldReceive('setTotal');

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with(9)->andReturn(1);
        $qaR->shouldReceive('repoQuotequery')->once()->with(9)->andReturn($quoteAmount);
        $qaR->shouldReceive('save')->once()->with($quoteAmount);

        $helper = $this->makeHelper(['enable_vat_registration' => '1']);

        $helper->calculateQuote(9, $acqR, $qiR, $qiaR, $qtrR, $qaR, $this->makeQrForDiscount());
    }

    public function calculateQuoteZeroesOutAStaleAmountRowWhenNoItemsRemain(): void
    {
        // count=0 (no items left on the quote), but a QuoteAmount row from
        // before still exists (countQuoteAmount=1) -- saveQuoteAmountTotals()
        // must zero every field out on that existing row rather than
        // leaving stale totals in place or creating a second row.
        /** @var ACQR&m\MockInterface $acqR */
        $acqR = m::mock(ACQR::class);
        $acqR->shouldReceive('repoACQquery')->once()->with(9)->andReturn($this->entityReaderOf([]));

        /** @var QIR&m\MockInterface $qiR */
        $qiR = m::mock(QIR::class);
        $qiR->shouldReceive('repoQuoteItemIdquery')->once()->with(9)->andReturn($this->entityReaderOf([]));
        $qiR->shouldReceive('repoCount')->once()->with(9)->andReturn(0);

        /** @var QIAR&m\MockInterface $qiaR */
        $qiaR = m::mock(QIAR::class);
        $qiaR->shouldNotReceive('repoQuoteItemAmountquery');

        /** @var QuoteAmount&m\MockInterface $quoteAmount */
        $quoteAmount = m::mock(QuoteAmount::class);
        $quoteAmount->shouldReceive('setQuoteId')->once()->with(9);
        $quoteAmount->shouldReceive('setItemSubtotal')->once()->with(0.00);
        $quoteAmount->shouldReceive('setItemTaxTotal')->once()->with(0.00);
        $quoteAmount->shouldReceive('setTaxTotal')->once()->with(0.00);
        $quoteAmount->shouldReceive('setTotal')->once()->with(0.00);
        // The zero-out branch does not touch packhandleship fields.
        $quoteAmount->shouldNotReceive('setPackhandleshipTotal');
        $quoteAmount->shouldNotReceive('setPackhandleshipTax');

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with(9)->andReturn(1);
        $qaR->shouldReceive('repoQuotequery')->once()->with(9)->andReturn($quoteAmount);
        $qaR->shouldReceive('save')->once()->with($quoteAmount);

        $helper = $this->makeHelper(['enable_vat_registration' => '0']);

        $helper->calculateQuote(9, $acqR, $qiR, $qiaR, $this->makeQtrRWithNoTaxRates(), $qaR, $this->makeQrForDiscount(discountAmount: 0.0));
    }

    public function calculateQuoteCreatesANewAmountRowWhenNeitherItemsNorAnExistingRowExist(): void
    {
        // count=0 and countQuoteAmount=0 -- a brand new zeroed QuoteAmount
        // row must be created and saved, not fetched via repoQuotequery().
        /** @var ACQR&m\MockInterface $acqR */
        $acqR = m::mock(ACQR::class);
        $acqR->shouldReceive('repoACQquery')->once()->with(9)->andReturn($this->entityReaderOf([]));

        /** @var QIR&m\MockInterface $qiR */
        $qiR = m::mock(QIR::class);
        $qiR->shouldReceive('repoQuoteItemIdquery')->once()->with(9)->andReturn($this->entityReaderOf([]));
        $qiR->shouldReceive('repoCount')->once()->with(9)->andReturn(0);

        /** @var QIAR&m\MockInterface $qiaR */
        $qiaR = m::mock(QIAR::class);
        $qiaR->shouldNotReceive('repoQuoteItemAmountquery');

        /** @var QAR&m\MockInterface $qaR */
        $qaR = m::mock(QAR::class);
        $qaR->shouldReceive('repoQuoteAmountCount')->once()->with(9)->andReturn(0);
        $qaR->shouldNotReceive('repoQuotequery');
        $qaR->shouldReceive('save')->once()->with(m::on(static function (QuoteAmount $savedQuoteAmount): bool {
            Assert::same($savedQuoteAmount->getItemSubtotal(), 0.00);
            Assert::same($savedQuoteAmount->getItemTaxTotal(), 0.00);
            Assert::same($savedQuoteAmount->getTaxTotal(), 0.00);
            Assert::same($savedQuoteAmount->getTotal(), 0.00);
            return true;
        }));

        $helper = $this->makeHelper(['enable_vat_registration' => '0']);

        $helper->calculateQuote(9, $acqR, $qiR, $qiaR, $this->makeQtrRWithNoTaxRates(), $qaR, $this->makeQrForDiscount(discountAmount: 0.0));
    }
}
