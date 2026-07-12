<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

/**
 * The Peppol document profile an invoice is generated/validated against.
 *
 * Each case supplies the `CustomizationID`, `ProfileID`, and tax scheme ID
 * ('VAT', 'GST', etc.) that vary between core BIS Billing 3.0 and each
 * country-specific PINT specialization. See docs/PEPPOL_PINT_AUNZ_ADAPTATION_PLAN.md
 * and docs/PEPPOL_PINT_UK_SPECULATIVE_PLAN.md.
 *
 * `taxCategories()` returning an empty array means "unrestricted" — the set
 * of UNCL5305 codes is governed entirely by admin-configured TaxRate records
 * (`TaxRate::getPeppolTaxRateCode()`), matching this repo's existing,
 * unconstrained core-bis3 behaviour exactly. A non-empty list is informational
 * only in this pass — it documents which codes a profile actually defines,
 * it does not (yet) enforce that invoice lines are restricted to them.
 */
enum PeppolProfile: string
{
    case CoreBis3 = 'core-bis3';
    case PintAuNz = 'pint-aunz';
    case PintSg = 'pint-sg';
    case PintUk = 'pint-uk';

    public function customizationId(): string
    {
        return match ($this) {
            self::CoreBis3 => 'urn:cen.eu:en16931:2017#compliant#urn:'
                . 'fdc:peppol.eu:2017:poacc:billing:3.0',
            self::PintAuNz => 'urn:peppol:pint:billing-1@aunz-1',
            self::PintSg => 'urn:peppol:pint:billing-1@sg-1',
            // Placeholder — no official PINT-UK spec exists yet. See
            // docs/PEPPOL_PINT_UK_SPECULATIVE_PLAN.md. Revise once
            // OpenPeppol/HMRC publish the real identifier.
            self::PintUk => 'urn:peppol:pint:billing-1@gb-1',
        };
    }

    /**
     * Human-readable label for settings-tab dropdowns and similar UI —
     * always ends with the tax scheme term so VAT vs GST is visible directly
     * in the dropdown, not just after selecting it.
     */
    public function label(): string
    {
        $base = match ($this) {
            self::CoreBis3 => 'Core BIS Billing 3.0',
            self::PintAuNz => 'PINT A-NZ — Australia / New Zealand',
            self::PintSg => 'PINT SG — Singapore',
            self::PintUk => 'PINT UK — United Kingdom (speculative)',
        };
        return $base . ' (' . $this->taxSchemeId() . ')';
    }

    public function profileId(): string
    {
        return match ($this) {
            self::CoreBis3 => 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            self::PintAuNz, self::PintSg, self::PintUk => 'urn:peppol:bis:billing',
        };
    }

    /** The `cbc:ID` value under `cac:TaxScheme` — 'VAT', 'GST', etc. */
    public function taxSchemeId(): string
    {
        return match ($this) {
            // UK VAT never needed relabelling — core BIS 3.0 already emits
            // 'VAT', unlike the AU/NZ and SG GST relabel.
            self::CoreBis3, self::PintUk => 'VAT',
            self::PintAuNz, self::PintSg => 'GST',
        };
    }

    /** @return list<TaxCategorySpec> */
    public function taxCategories(): array
    {
        return match ($this) {
            self::CoreBis3 => [],
            self::PintUk => [
                new TaxCategorySpec('S', 20.0, 'Standard rate'),
                new TaxCategorySpec('AA', 5.0, 'Reduced rate (e.g. domestic fuel)'),
                new TaxCategorySpec('Z', 0.0, 'Zero rated'),
                new TaxCategorySpec('E', null, 'Exempt'),
                new TaxCategorySpec('O', null, 'Outside the scope of VAT'),
                new TaxCategorySpec('AE', null, 'Domestic reverse charge (e.g. construction industry)'),
            ],
            self::PintAuNz => [
                new TaxCategorySpec('S', null, 'Standard rate (AU 10% / NZ 15%)'),
                new TaxCategorySpec('E', null, 'Exempt'),
                new TaxCategorySpec('Z', 0.0, 'Zero rated'),
                new TaxCategorySpec('G', 0.0, 'Free export'),
                new TaxCategorySpec('O', null, 'Outside scope'),
            ],
            self::PintSg => [
                new TaxCategorySpec('SR', 9.0, 'Standard rated supply'),
                new TaxCategorySpec('ZR', 0.0, 'Zero rated (exports/international services)'),
                new TaxCategorySpec('ES33', null, 'Exempt supplies under regulation 33'),
                new TaxCategorySpec('ESN33', null, 'Exempt supplies other than regulation 33'),
                new TaxCategorySpec('DS', 9.0, 'Deemed supplies'),
                new TaxCategorySpec('OS', null, 'Out-of-scope supplies'),
                new TaxCategorySpec('NG', null, 'Non-GST registered company supplies'),
                new TaxCategorySpec('NA', null, 'Taxable supplies where GST need not be charged'),
                new TaxCategorySpec('SRCA-S', null, 'Customer accounting supply by supplier'),
                new TaxCategorySpec('SRCA-C', 9.0, 'Customer accounting supply by customer'),
                new TaxCategorySpec('SRRC', 9.0, 'Reverse charge regime (B2B imported services)'),
                new TaxCategorySpec('SROVR-RS', 9.0, 'Remote services under Overseas Vendor Registration'),
                new TaxCategorySpec('SROVR-LVG', 9.0, 'Low-value goods under Overseas Vendor Registration'),
                new TaxCategorySpec('SRLVG', 9.0, 'Own supply of low-value goods'),
            ],
        };
    }

    /**
     * Every published `CustomizationID`, for validators that must recognise
     * a document generated under any known profile rather than just the one
     * currently selected in settings.
     *
     * @return list<string>
     */
    public static function knownCustomizationIds(): array
    {
        return array_map(
            static fn(self $profile): string => $profile->customizationId(),
            self::cases(),
        );
    }

    /**
     * Resolves the active profile from the `peppol_profile` setting, falling
     * back to {@see self::CoreBis3} for an empty or unrecognised value —
     * preserves existing behaviour for installs that have never set it.
     */
    public static function fromSetting(string $value): self
    {
        return self::tryFrom($value) ?? self::CoreBis3;
    }
}
