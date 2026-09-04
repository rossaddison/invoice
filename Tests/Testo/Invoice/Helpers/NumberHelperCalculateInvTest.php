<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\Helpers\CalcInvDeps;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Payment\PaymentRepository as PYMR;
use App\Invoice\Setting\SettingRepository;
use Mockery as m;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Cycle\Reader\EntityReader;

/**
 * calculateInv() -- the orchestrator that ties together everything covered
 * in NumberHelperTaxCalculationsTest and NumberHelperDiscountAndItemTotalsTest,
 * plus its own genuinely untested logic: allowance/charge sign-flip (charge
 * adds, allowance subtracts, based on getAllowanceCharge()?->getIdentifier()),
 * the VAT-registration-disables-per-document-tax branching, and the private
 * helpers it calls (calculateAndSetBalance / saveInvAmountTotals /
 * setInvAmountFields / invBalanceZeroSetToReadOnlyIfFullyPaid) -- most
 * notably the read-only-on-fully-paid state transition, a real business
 * rule that was entirely untested before this.
 *
 * calculateQuote() is structurally near-identical (covered separately in
 * NumberHelperCalculateQuoteTest, which uses real QuoteItem instances
 * rather than mocks for the one piece that relies on PHP's default
 * *public-property-only* object iteration to read an item's `id`).
 * calculateSo() was deleted rather than tested -- see
 * NumberHelperDiscountAndItemTotalsTest's own docblock and
 * project_number_helper_tax_calculation_tests for why.
 */
#[Test]
final class NumberHelperCalculateInvTest
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

    private function makeInvoiceItem(): InvItem
    {
        /** @var InvItem&m\MockInterface $item */
        $item = m::mock(InvItem::class);
        $item->shouldReceive('reqId')->andReturn(201);
        return $item;
    }

    private function makeInvoiceItemAmount(): InvItemAmount
    {
        /** @var InvItemAmount&m\MockInterface $amount */
        $amount = m::mock(InvItemAmount::class);
        $amount->shouldReceive('getSubtotal')->andReturn(100.0);
        $amount->shouldReceive('getTaxTotal')->andReturn(20.0);
        $amount->shouldReceive('getDiscount')->andReturn(10.0);
        $amount->shouldReceive('getCharge')->andReturn(0.0);
        $amount->shouldReceive('getAllowance')->andReturn(0.0);
        $amount->shouldReceive('getTotal')->andReturn(110.0);
        return $amount;
    }

    /**
     * Builds the CalcInvDeps + expectations shared by every scenario below:
     * one InvItem/InvItemAmount (subtotal 100, tax 20, discount 10 -- so
     * item_subtotal_discount = 90, +tax = 110), a $6 customer discount on
     * the invoice itself, zero tax rates (calculateInvTaxes's own logic is
     * already covered directly in NumberHelperTaxCalculationsTest -- giving
     * it nothing to do here keeps this test focused on calculateInv()'s own
     * branching), and an existing InvAmount row (the "update in place"
     * branch of saveInvAmountTotals()).
     */
    private function makeDeps(
        InvAllowanceCharge ...$allowanceCharges,
    ): CalcInvDeps {
        /** @var ACIR&m\MockInterface $aciR */
        $aciR = m::mock(ACIR::class);
        $aciR->shouldReceive('repoACIquery')->once()->with(7)
            ->andReturn($this->entityReaderOf($allowanceCharges));

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(7)
            ->andReturn($this->entityReaderOf([$this->makeInvoiceItem()]));
        $iiR->shouldReceive('repoCount')->once()->with(7)->andReturn(1);

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(201)
            ->andReturn($this->makeInvoiceItemAmount());

        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        $itrR->shouldReceive('repoInvquery')->with(7)->andReturn($this->entityReaderOf([]));
        $itrR->shouldReceive('repoCount')->with(7)->andReturn(0);

        return new CalcInvDeps(
            $aciR,
            $iiR,
            $iiaR,
            $itrR,
            $this->makeIar(),
            $this->makeIrForDiscount(),
            $this->makePymR(fullyPaid: true),
        );
    }

    private function makeIar(): IAR
    {
        /** @var InvAmount&m\MockInterface $invAmount */
        $invAmount = m::mock(InvAmount::class);
        $invAmount->shouldReceive('setInvId')->once()->with(7);
        $invAmount->shouldReceive('setItemSubtotal')->once()->with(90.0);
        $invAmount->shouldReceive('setItemTaxTotal')->once()->with(20.0);
        $invAmount->shouldReceive('setPackhandleshipTotal')->once()->with(5.0);
        $invAmount->shouldReceive('setPackhandleshipTax')->once()->with(1.0);
        $invAmount->shouldReceive('setTaxTotal')->once()->with(0.00);
        $invAmount->shouldReceive('setTotal')->once()->with(110.0);
        $invAmount->shouldReceive('setPaid')->once()->with(110.0);
        $invAmount->shouldReceive('setBalance')->once()->with(0.0);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('repoInvAmountCount')->once()->with(7)->andReturn(1);
        $iaR->shouldReceive('repoInvquery')->once()->with(7)->andReturn($invAmount);
        $iaR->shouldReceive('save')->once()->with($invAmount);
        return $iaR;
    }

    private function makeIrForDiscount(bool $expectReadOnlyFlip = true): IR
    {
        // One Inv mock, not two: invIncludeCustomerDiscountRequest() calls
        // $iR->repoInvUnloadedquery() (lowercase 'l') and
        // calculateAndSetBalance() separately calls
        // $deps->iR->repoInvUnLoadedquery() (capital 'L') -- PHP method
        // dispatch is case-insensitive, so these are the *same* declared
        // method on the real InvRepository (confirmed: only one such
        // method actually exists). Registering two shouldReceive() calls
        // under the two different-cased strings does NOT model that
        // correctly -- Mockery ends up handing the wrong mock's return
        // value to one of the two real call sites. One registration, using
        // the class's real declared casing, correctly serves both.
        /** @var Inv&m\MockInterface $inv */
        $inv = m::mock(Inv::class);
        $inv->shouldReceive('getDiscountAmount')->andReturn(6.0);

        // getInvAmount()/setIsReadOnly()/setStatusId() are only ever
        // reached inside invBalanceZeroSetToReadOnlyIfFullyPaid() once
        // every earlier &&-guard (toggle==4, invoice not null, balance==0)
        // has already passed -- i.e. in exactly the same condition as the
        // flip itself, never independently.
        if ($expectReadOnlyFlip) {
            /** @var InvAmount&m\MockInterface $freshAmount */
            $freshAmount = m::mock(InvAmount::class);
            $freshAmount->shouldReceive('getPaid')->andReturn(110.0);
            $freshAmount->shouldReceive('getTotal')->andReturn(110.0);

            // Called twice in the real source -- once for ->getPaid(), once
            // for ->getTotal() -- neither call caches the other's result.
            $inv->shouldReceive('getInvAmount')->twice()->andReturn($freshAmount);
            $inv->shouldReceive('setIsReadOnly')->once()->with(true);
            $inv->shouldReceive('setStatusId')->once()->with(4);
        } else {
            $inv->shouldNotReceive('getInvAmount');
            $inv->shouldNotReceive('setIsReadOnly');
            $inv->shouldNotReceive('setStatusId');
        }

        /** @var IR&m\MockInterface $iR */
        $iR = m::mock(IR::class);
        // repoInvUnLoadedquery() is reached unconditionally by
        // calculateAndSetBalance() (before its own inner if-guard) on
        // every single calculateInv() call, on top of
        // invIncludeCustomerDiscountRequest()'s own call earlier in the
        // same flow -- no ->once()/->twice() count constraint here since
        // exactly how many times depends on the scenario, and the return
        // value is identical either way.
        $iR->shouldReceive('repoInvUnLoadedquery')->with(7)->andReturn($inv);

        if ($expectReadOnlyFlip) {
            $iR->shouldReceive('save')->once()->with($inv);
        } else {
            $iR->shouldNotReceive('save');
        }

        return $iR;
    }

    private function makePymR(bool $fullyPaid): PYMR
    {
        /** @var PYMR&m\MockInterface $pymR */
        $pymR = m::mock(PYMR::class);
        if ($fullyPaid) {
            /** @var Payment&m\MockInterface $payment */
            $payment = m::mock(Payment::class);
            $payment->shouldReceive('getAmount')->andReturn(110.0);
            $pymR->shouldReceive('repoCount')->once()->with(7)->andReturn(1);
            $pymR->shouldReceive('repoInvquery')->once()->with(7)->andReturn($this->entityReaderOf([$payment]));
        } else {
            $pymR->shouldReceive('repoCount')->once()->with(7)->andReturn(0);
            $pymR->shouldNotReceive('repoInvquery');
        }
        return $pymR;
    }

    private function makeCharge(bool $isCharge, float $amount, float $vatOrTax): InvAllowanceCharge
    {
        /** @var AllowanceCharge&m\MockInterface $allowanceCharge */
        $allowanceCharge = m::mock(AllowanceCharge::class);
        $allowanceCharge->shouldReceive('getIdentifier')->andReturn($isCharge);

        /** @var InvAllowanceCharge&m\MockInterface $iac */
        $iac = m::mock(InvAllowanceCharge::class);
        $iac->shouldReceive('getAllowanceCharge')->andReturn($allowanceCharge);
        $iac->shouldReceive('getAmount')->andReturn($amount);
        $iac->shouldReceive('getVatOrTax')->andReturn($vatOrTax);
        return $iac;
    }

    public function calculateInvHappyPathWithAChargeAndFullPayment(): void
    {
        // 90 (item subtotal-discount) + 20 (item tax) + 0 (no invoice-level
        // tax rates) + 5 (charge amount) + 1 (charge vat/tax) = 116, minus
        // the invoice's own 6.00 customer discount = 110.00 -- fully paid
        // by a single 110.00 payment, so the balance hits exactly zero and
        // read_only_toggle=4 flips the invoice read-only/paid.
        $charge = $this->makeCharge(isCharge: true, amount: 5.0, vatOrTax: 1.0);
        $deps = $this->makeDeps($charge);

        $helper = $this->makeHelper([
            'enable_vat_registration' => '0',
            'read_only_toggle' => '4',
        ]);

        $helper->calculateInv(7, $deps);
    }

    public function calculateInvSubtractsAnAllowanceInsteadOfAddingIt(): void
    {
        // Same shape, but identifier=false (an allowance, not a charge) --
        // both the amount and vat/tax must be *subtracted*, not added.
        // Rebalanced so the final total still comes out to 110.00: item
        // total 110, minus a 5.00/1.00 allowance = 104, minus the 6.00
        // customer discount = 98.00 this time (not fully paid -- payment
        // total intentionally left unmatched to keep this test focused on
        // the sign, not the balance/read-only branch).
        $allowance = $this->makeCharge(isCharge: false, amount: 5.0, vatOrTax: 1.0);

        /** @var ACIR&m\MockInterface $aciR */
        $aciR = m::mock(ACIR::class);
        $aciR->shouldReceive('repoACIquery')->once()->with(7)
            ->andReturn($this->entityReaderOf([$allowance]));

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(7)
            ->andReturn($this->entityReaderOf([$this->makeInvoiceItem()]));
        $iiR->shouldReceive('repoCount')->once()->with(7)->andReturn(1);

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(201)
            ->andReturn($this->makeInvoiceItemAmount());

        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        $itrR->shouldReceive('repoInvquery')->with(7)->andReturn($this->entityReaderOf([]));
        $itrR->shouldReceive('repoCount')->with(7)->andReturn(0);

        /** @var InvAmount&m\MockInterface $invAmount */
        $invAmount = m::mock(InvAmount::class);
        $invAmount->shouldReceive('setInvId')->once()->with(7);
        $invAmount->shouldReceive('setItemSubtotal')->once()->with(90.0);
        $invAmount->shouldReceive('setItemTaxTotal')->once()->with(20.0);
        $invAmount->shouldReceive('setPackhandleshipTotal')->once()->with(-5.0);
        $invAmount->shouldReceive('setPackhandleshipTax')->once()->with(-1.0);
        $invAmount->shouldReceive('setTaxTotal')->once()->with(0.00);
        $invAmount->shouldReceive('setTotal')->once()->with(98.0);
        $invAmount->shouldReceive('setPaid')->once()->with(0.00);
        $invAmount->shouldReceive('setBalance')->once()->with(98.0);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('repoInvAmountCount')->once()->with(7)->andReturn(1);
        $iaR->shouldReceive('repoInvquery')->once()->with(7)->andReturn($invAmount);
        $iaR->shouldReceive('save')->once()->with($invAmount);

        $deps = new CalcInvDeps(
            $aciR,
            $iiR,
            $iiaR,
            $itrR,
            $iaR,
            $this->makeIrForDiscount(expectReadOnlyFlip: false),
            $this->makePymR(fullyPaid: false),
        );

        $helper = $this->makeHelper([
            'enable_vat_registration' => '0',
            'read_only_toggle' => '4',
        ]);

        $helper->calculateInv(7, $deps);
    }

    public function calculateInvTreatsAMissingAllowanceChargeRelationAsAnAllowance(): void
    {
        // getAllowanceCharge() returns null -> the nullsafe chain makes
        // $isCharge null, and `if (null)` takes the same subtract path as
        // an explicit allowance (false), not a crash and not the add path.
        /** @var InvAllowanceCharge&m\MockInterface $iac */
        $iac = m::mock(InvAllowanceCharge::class);
        $iac->shouldReceive('getAllowanceCharge')->andReturn(null);
        $iac->shouldReceive('getAmount')->andReturn(5.0);
        $iac->shouldReceive('getVatOrTax')->andReturn(1.0);

        /** @var ACIR&m\MockInterface $aciR */
        $aciR = m::mock(ACIR::class);
        $aciR->shouldReceive('repoACIquery')->once()->with(7)
            ->andReturn($this->entityReaderOf([$iac]));

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(7)
            ->andReturn($this->entityReaderOf([$this->makeInvoiceItem()]));
        $iiR->shouldReceive('repoCount')->once()->with(7)->andReturn(1);

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(201)
            ->andReturn($this->makeInvoiceItemAmount());

        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        $itrR->shouldReceive('repoInvquery')->with(7)->andReturn($this->entityReaderOf([]));
        $itrR->shouldReceive('repoCount')->with(7)->andReturn(0);

        /** @var InvAmount&m\MockInterface $invAmount */
        $invAmount = m::mock(InvAmount::class);
        $invAmount->shouldReceive('setInvId');
        $invAmount->shouldReceive('setItemSubtotal');
        $invAmount->shouldReceive('setItemTaxTotal');
        // The only assertion that actually matters for this test.
        $invAmount->shouldReceive('setPackhandleshipTotal')->once()->with(-5.0);
        $invAmount->shouldReceive('setPackhandleshipTax')->once()->with(-1.0);
        $invAmount->shouldReceive('setTaxTotal');
        $invAmount->shouldReceive('setTotal');
        $invAmount->shouldReceive('setPaid');
        $invAmount->shouldReceive('setBalance');

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('repoInvAmountCount')->once()->with(7)->andReturn(1);
        $iaR->shouldReceive('repoInvquery')->once()->with(7)->andReturn($invAmount);
        $iaR->shouldReceive('save')->once()->with($invAmount);

        $deps = new CalcInvDeps(
            $aciR,
            $iiR,
            $iiaR,
            $itrR,
            $iaR,
            $this->makeIrForDiscount(expectReadOnlyFlip: false),
            $this->makePymR(fullyPaid: false),
        );

        $helper = $this->makeHelper([
            'enable_vat_registration' => '0',
            'read_only_toggle' => '4',
        ]);

        $helper->calculateInv(7, $deps);
    }

    public function calculateInvSkipsInvoiceTaxesEntirelyUnderVatRegistration(): void
    {
        // enable_vat_registration !== '0' -> calculateInvTaxes() must never
        // be reached at all, not even to compute a zero -- the ITRR mock
        // below has zero expectations set up and would fail loudly on any
        // unexpected call.
        /** @var ACIR&m\MockInterface $aciR */
        $aciR = m::mock(ACIR::class);
        $aciR->shouldReceive('repoACIquery')->once()->with(7)->andReturn($this->entityReaderOf([]));

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(7)
            ->andReturn($this->entityReaderOf([$this->makeInvoiceItem()]));
        $iiR->shouldReceive('repoCount')->once()->with(7)->andReturn(1);

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(201)
            ->andReturn($this->makeInvoiceItemAmount());

        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        $itrR->shouldNotReceive('repoInvquery');
        $itrR->shouldNotReceive('repoCount');

        /** @var InvAmount&m\MockInterface $invAmount */
        $invAmount = m::mock(InvAmount::class);
        $invAmount->shouldReceive('setInvId');
        $invAmount->shouldReceive('setItemSubtotal');
        $invAmount->shouldReceive('setItemTaxTotal');
        $invAmount->shouldReceive('setPackhandleshipTotal')->once()->with(0.00);
        $invAmount->shouldReceive('setPackhandleshipTax')->once()->with(0.00);
        // The only assertion that actually matters for this test: taxes
        // stay exactly zero under the VAT regime, regardless of ITRR.
        $invAmount->shouldReceive('setTaxTotal')->once()->with(0.00);
        $invAmount->shouldReceive('setTotal');
        $invAmount->shouldReceive('setPaid');
        $invAmount->shouldReceive('setBalance');

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('repoInvAmountCount')->once()->with(7)->andReturn(1);
        $iaR->shouldReceive('repoInvquery')->once()->with(7)->andReturn($invAmount);
        $iaR->shouldReceive('save')->once()->with($invAmount);

        $deps = new CalcInvDeps(
            $aciR,
            $iiR,
            $iiaR,
            $itrR,
            $iaR,
            $this->makeIrForDiscount(expectReadOnlyFlip: false),
            $this->makePymR(fullyPaid: false),
        );

        $helper = $this->makeHelper([
            'enable_vat_registration' => '1',
            'read_only_toggle' => '4',
        ]);

        $helper->calculateInv(7, $deps);
    }

    public function calculateInvDoesNotFlipReadOnlyWhenTheBalanceIsNotZero(): void
    {
        // Fully-paid path's InvAmount/IAR expectations, but the payment is
        // deliberately short of the total -- balance stays 10.00, so
        // invBalanceZeroSetToReadOnlyIfFullyPaid()'s `$balance == 0.00`
        // guard must prevent the read-only flip even though
        // read_only_toggle is 4 and both paid/total are > 0.
        $charge = $this->makeCharge(isCharge: true, amount: 5.0, vatOrTax: 1.0);

        /** @var ACIR&m\MockInterface $aciR */
        $aciR = m::mock(ACIR::class);
        $aciR->shouldReceive('repoACIquery')->once()->with(7)
            ->andReturn($this->entityReaderOf([$charge]));

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(7)
            ->andReturn($this->entityReaderOf([$this->makeInvoiceItem()]));
        $iiR->shouldReceive('repoCount')->once()->with(7)->andReturn(1);

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(201)
            ->andReturn($this->makeInvoiceItemAmount());

        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        $itrR->shouldReceive('repoInvquery')->with(7)->andReturn($this->entityReaderOf([]));
        $itrR->shouldReceive('repoCount')->with(7)->andReturn(0);

        /** @var InvAmount&m\MockInterface $invAmount */
        $invAmount = m::mock(InvAmount::class);
        $invAmount->shouldReceive('setInvId');
        $invAmount->shouldReceive('setItemSubtotal');
        $invAmount->shouldReceive('setItemTaxTotal');
        $invAmount->shouldReceive('setPackhandleshipTotal');
        $invAmount->shouldReceive('setPackhandleshipTax');
        $invAmount->shouldReceive('setTaxTotal');
        $invAmount->shouldReceive('setTotal')->once()->with(110.0);
        $invAmount->shouldReceive('setPaid')->once()->with(100.0);
        $invAmount->shouldReceive('setBalance')->once()->with(10.0);

        /** @var IAR&m\MockInterface $iaR */
        $iaR = m::mock(IAR::class);
        $iaR->shouldReceive('repoInvAmountCount')->once()->with(7)->andReturn(1);
        $iaR->shouldReceive('repoInvquery')->once()->with(7)->andReturn($invAmount);
        $iaR->shouldReceive('save')->once()->with($invAmount);

        /** @var Payment&m\MockInterface $payment */
        $payment = m::mock(Payment::class);
        $payment->shouldReceive('getAmount')->andReturn(100.0);

        /** @var PYMR&m\MockInterface $pymR */
        $pymR = m::mock(PYMR::class);
        $pymR->shouldReceive('repoCount')->once()->with(7)->andReturn(1);
        $pymR->shouldReceive('repoInvquery')->once()->with(7)->andReturn($this->entityReaderOf([$payment]));

        $deps = new CalcInvDeps(
            $aciR,
            $iiR,
            $iiaR,
            $itrR,
            $iaR,
            $this->makeIrForDiscount(expectReadOnlyFlip: false),
            $pymR,
        );

        $helper = $this->makeHelper([
            'enable_vat_registration' => '0',
            'read_only_toggle' => '4',
        ]);

        $helper->calculateInv(7, $deps);
    }

    public function calculateInvDoesNotFlipReadOnlyWhenTheToggleSettingIsOff(): void
    {
        // Balance hits exactly zero (fully paid), but read_only_toggle
        // isn't '4' -- the flip must not happen regardless. Built inline
        // rather than via makeDeps() -- that helper's own makeIar()/
        // makeIrForDiscount()/makePymR() calls would otherwise create and
        // discard a second, never-invoked set of mocks with their own
        // pending ->once() expectations, which Mockery's per-test teardown
        // (MockeryPlugin, testo.php) would fail on as unfulfilled.
        $charge = $this->makeCharge(isCharge: true, amount: 5.0, vatOrTax: 1.0);

        /** @var ACIR&m\MockInterface $aciR */
        $aciR = m::mock(ACIR::class);
        $aciR->shouldReceive('repoACIquery')->once()->with(7)
            ->andReturn($this->entityReaderOf([$charge]));

        /** @var IIR&m\MockInterface $iiR */
        $iiR = m::mock(IIR::class);
        $iiR->shouldReceive('repoInvItemIdquery')->once()->with(7)
            ->andReturn($this->entityReaderOf([$this->makeInvoiceItem()]));
        $iiR->shouldReceive('repoCount')->once()->with(7)->andReturn(1);

        /** @var IIAR&m\MockInterface $iiaR */
        $iiaR = m::mock(IIAR::class);
        $iiaR->shouldReceive('repoInvItemAmountquery')->once()->with(201)
            ->andReturn($this->makeInvoiceItemAmount());

        /** @var ITRR&m\MockInterface $itrR */
        $itrR = m::mock(ITRR::class);
        $itrR->shouldReceive('repoInvquery')->with(7)->andReturn($this->entityReaderOf([]));
        $itrR->shouldReceive('repoCount')->with(7)->andReturn(0);

        $deps = new CalcInvDeps(
            $aciR,
            $iiR,
            $iiaR,
            $itrR,
            $this->makeIar(),
            $this->makeIrForDiscount(expectReadOnlyFlip: false),
            $this->makePymR(fullyPaid: true),
        );

        $helper = $this->makeHelper([
            'enable_vat_registration' => '0',
            'read_only_toggle' => '0',
        ]);

        $helper->calculateInv(7, $deps);
    }
}
