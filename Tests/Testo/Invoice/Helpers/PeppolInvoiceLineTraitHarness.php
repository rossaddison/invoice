<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAmount\InvAmount;
use App\Infrastructure\Persistence\InvItemAmount\InvItemAmount;
use App\Invoice\Helpers\Peppol\Trait\PeppolHelperInvoiceLineTrait;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Translator\TranslatorInterface as Translator;

/**
 * Test-only host for PeppolHelperInvoiceLineTrait. The real host, PeppolHelper, builds a dozen collaborators
 * and carries five other traits, none of which the invoice line builders use. This supplies only what the
 * trait reads from its host: the settings and translator, the invoice amount, the document currency, two
 * constants and the three helper methods it calls.
 */
final class PeppolInvoiceLineTraitHarness
{
    use PeppolHelperInvoiceLineTrait;

    public const string SETTING_PEPPOL_DOCUMENT_CURRENCY = 'peppol_document_currency';
    public const string TAX_CATEGORY_VAT = 'VAT';

    private string $documentCurrency = 'GBP';

    public function __construct(
        public readonly SettingRepository $s,
        private readonly Translator $t,
        private readonly InvAmount $inv_amount,
        private readonly bool $creditNote = false,
    ) {
    }

    private function isCreditNote(Inv $_invoice): bool
    {
        return $this->creditNote;
    }

    private function invoiceReferenceId(Inv $_invoice): string
    {
        return 'INV-REF';
    }

    public function getInvItemAmount(int $item_id, IIAR $iiaR): ?InvItemAmount
    {
        return $iiaR->repoInvItemAmountquery($item_id);
    }
}
