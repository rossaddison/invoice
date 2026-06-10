<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Infrastructure\Persistence\{
    Quote\Quote,
    QuoteAmount\QuoteAmount,
    QuoteCustom\QuoteCustom,
    QuoteItem\QuoteItem,
};
use App\Invoice\Helpers\{
    CustomValuesHelper as CVH,
    MpdfHelper,
    PdfCreateContext,
};
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class QuotePdfService
{
    public function __construct(
        private SR $s,
        private SessionInterface $session,
        private TranslatorInterface $translator,
        private WebViewRenderer $webViewRenderer,
        private QuotePdfCoreDeps $coreDeps,
        private QuotePdfDocDeps $docDeps,
        private QuotePdfItemDeps $itemDeps,
    ) {
    }

    public function generate(int $quoteId, bool $stream, bool $custom): string
    {
        $quoteAmount = $this->coreDeps->qaR->repoQuoteAmountCount($quoteId) > 0
            ? $this->coreDeps->qaR->repoQuotequery($quoteId)
            : null;
        if (null === $quoteAmount) {
            return '';
        }
        $quoteCustomValues = $this->customValues($quoteId);
        if ($this->s->getSetting('mark_quotes_sent_pdf') == 1) {
            $this->markSentIfApplicable($quoteId);
            $this->s->quoteMarkSent($quoteId, $this->coreDeps->qR);
        }
        $quote = $this->coreDeps->qR->repoQuoteUnLoadedquery($quoteId);
        if (null === $quote) {
            return '';
        }
        return $this->createPdf($quoteId, $quote->reqUserId(), $stream, $custom, $quoteAmount, $quoteCustomValues);
    }

    public function findQuote(int $quoteId): ?Quote
    {
        return $this->coreDeps->qR->repoQuoteUnLoadedquery($quoteId);
    }

    public function uiR(): UIR
    {
        return $this->coreDeps->uiR;
    }

    private function createPdf(
        int $quoteId,
        int $userId,
        bool $stream,
        bool $custom,
        QuoteAmount $quoteAmount,
        array $quoteCustomValues,
    ): string {
        $quote = $this->coreDeps->qR->repoCount($quoteId) > 0
            ? $this->coreDeps->qR->repoQuoteLoadedquery($quoteId)
            : null;
        if (null === $quote) {
            return '';
        }
        $html = $this->renderHtml($quoteId, $userId, $custom, $quoteAmount, $quoteCustomValues, $quote);
        if ($this->s->getSetting('pdf_html_quote') === '1') {
            return $html;
        }
        $this->session->set('print_language', '');
        $mpdf = new MpdfHelper($this->translator);
        $filename = $this->translator->translate('quote')
            . '_'
            . str_replace(['\\', '/'], '_', $quote->getNumber() ?? (string) random_int(0, 10));
        return $mpdf->pdfCreate(
            $html, $filename, $stream, $this->s,
            new PdfCreateContext($quote->getPassword(), null, null, false, false, [], $quote),
        );
    }

    private function renderHtml(
        int $quoteId,
        int $userId,
        bool $custom,
        QuoteAmount $quoteAmount,
        array $quoteCustomValues,
        Quote $quote,
    ): string {
        $userinv = $this->coreDeps->uiR->repoUserInvcount($userId) > 0
            ? $this->coreDeps->uiR->repoUserInvquery($userId)
            : null;
        $quoteTemplate = !empty($this->s->getSetting('pdf_quote_template'))
            ? $this->s->getSetting('pdf_quote_template')
            : 'quote';
        /** @var string|null $language */
        $language = $this->session->get('_language');
        $items = $this->itemDeps->qiR->repoCount($quoteId) > 0
            ? $this->itemDeps->qiR->repoQuoteItemIdquery($quoteId)
            : null;
        $clientNumber = (string) $quote->getClient()?->getClientNumber();
        $showItemDiscounts = false;
        if (null !== $items) {
            /** @var QuoteItem $item */
            foreach ($items as $item) {
                if ($item->getDiscountAmount() !== 0.00) {
                    $showItemDiscounts = true;
                }
            }
        }
        $cfTable = $this->docDeps->cfR->repoTablequery('quote_custom');
        $cvH = new CVH($this->s, $this->docDeps->cvR);
        $data = [
            'quote' => $quote,
            'quote_tax_rates' => $this->itemDeps->qtrR->repoCount($quoteId) > 0
                ? $this->itemDeps->qtrR->repoQuotequery($quoteId)
                : null,
            'items' => $items,
            'qiaR' => $this->itemDeps->qiaR,
            'acqiR' => $this->itemDeps->acqiR,
            'output_type' => 'pdf',
            'show_item_discounts' => $showItemDiscounts,
            'show_custom_fields' => $custom,
            'custom_fields' => $cfTable,
            'custom_values' => $this->docDeps->cvR->fixCfValueToCf($cfTable),
            'cvH' => $cvH,
            'cvR' => $this->docDeps->cvR,
            'quote_custom_values' => $quoteCustomValues,
            'top_custom_fields' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/template/quote/pdf/top_custom_fields',
                ['custom_fields' => $cfTable, 'cvR' => $this->docDeps->cvR,
                 'quote_custom_values' => $quoteCustomValues, 'cvH' => $cvH],
            ),
            'view_custom_fields' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/template/quote/pdf/view_custom_fields',
                ['custom_fields' => $cfTable, 'cvR' => $this->docDeps->cvR,
                 'quote_custom_values' => $quoteCustomValues, 'cvH' => $cvH],
            ),
            'company_logo_and_address' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/company_logo_and_address.php',
                [
                    'company' => $this->s->getPrivateCompanyDetails() !== []
                        ? $this->s->getPrivateCompanyDetails()
                        : $this->s->getConfigCompanyDetails(),
                    'document_number' => $quote->getNumber(),
                    'client_number' => $clientNumber,
                    'isInvoice' => false,
                    'isQuote' => true,
                    'isSalesOrder' => false,
                ],
            ),
            'delivery_location' => $this->renderDeliveryLocation(
                (string) $language,
                $quote->getDeliveryLocationId(),
            ),
            'userInv' => $userinv,
            'client' => $this->docDeps->cR->repoClientqueryOrig(
                $quote->getClient()?->reqId() ?? 0,
            ),
            'quote_amount' => $quoteAmount,
            'cldr' => array_search(
                $this->printLanguage($quote),
                $this->s->localeLanguageArray(),
            ),
        ];
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/template/quote/pdf/' . $quoteTemplate, $data,
        );
    }

    private function renderDeliveryLocation(string $language, ?int $deliveryLocationId): string
    {
        if ($deliveryLocationId === null || $deliveryLocationId <= 0) {
            return '';
        }
        $del = $this->docDeps->dlR->repoDeliveryLocationquery($deliveryLocationId);
        if (null === $del) {
            return '';
        }
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/partial_inv_delivery_location',
            [
                'actionName' => 'del/view',
                'actionArguments' => ['_language' => $language, 'id' => $deliveryLocationId],
                'title' => $this->translator->translate('delivery.location'),
                'building_number' => $del->getBuildingNumber(),
                'address_1' => $del->getAddress1(),
                'address_2' => $del->getAddress2(),
                'city' => $del->getCity(),
                'state' => $del->getZip(),
                'country' => $del->getCountry(),
                'global_location_number' => $del->getGlobalLocationNumber(),
            ],
        );
    }

    private function customValues(int $quoteId): array
    {
        $values = [];
        if ($quoteId > 0 && $this->coreDeps->qcR->repoQuoteCount($quoteId) > 0) {
            /** @var QuoteCustom $quoteCustom */
            foreach ($this->coreDeps->qcR->repoFields($quoteId) as $quoteCustom) {
                $values[] = $quoteCustom;
            }
        }
        return $values;
    }

    private function markSentIfApplicable(int $quoteId): void
    {
        if ($quoteId <= 0) {
            return;
        }
        $quote = $this->coreDeps->qR->repoQuoteUnLoadedquery($quoteId);
        if (null === $quote) {
            return;
        }
        if ($this->coreDeps->qR->repoCount($quoteId) > 0
            && $quote->reqStatusId() === 1
            && ($quote->getNumber() ?? '') === ''
        ) {
            $quote->setNumber((string) $this->generateNumber($quote->reqGroupId()));
            $this->coreDeps->qR->save($quote);
        }
    }

    private function generateNumber(int $groupId): mixed
    {
        if ($this->s->getSetting('generate_quote_number_for_draft') == '0') {
            return $this->coreDeps->qR->getQuoteNumber($groupId, $this->coreDeps->gR);
        }
        return '';
    }

    private function printLanguage(Quote $quote): mixed
    {
        $locale = $this->localeToLanguage();
        return $quote->getClient()?->getClientLanguage() ?? $locale;
    }

    private function localeToLanguage(): ?string
    {
        $dropdown = (string) $this->session->get('_language');
        /** @var array<string, string> $sessionList */
        $sessionList = $this->s->localeLanguageArray();
        return $sessionList[$dropdown] ?? null;
    }
}
