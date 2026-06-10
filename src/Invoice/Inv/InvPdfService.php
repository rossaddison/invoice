<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\{
    Inv\Inv,
    InvAllowanceCharge\InvAllowanceCharge,
    InvAmount\InvAmount,
    InvCustom\InvCustom,
    SalesOrder\SalesOrder,
};
use App\Invoice\Helpers\{
    CustomValuesHelper as CVH,
    DateHelper,
    MpdfHelper,
    PdfCreateContext,
    ZugFerdHelper,
};
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\UserClient\UserClientRepository as UCR;
use App\Invoice\UserInv\UserInvRepository as UIR;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class InvPdfService
{
    public function __construct(
        private SR $s,
        private SessionInterface $session,
        private TranslatorInterface $translator,
        private WebViewRenderer $webViewRenderer,
        private InvPdfCoreDeps $coreDeps,
        private InvPdfDocDeps $docDeps,
        private InvPdfItemDeps $itemDeps,
    ) {
    }

    public function generate(int $invId, bool $stream, bool $custom): string
    {
        $invAmount = $this->coreDeps->iaR->repoInvAmountCount($invId) > 0
            ? $this->coreDeps->iaR->repoInvquery($invId)
            : null;
        if (null === $invAmount) {
            return '';
        }
        $invCustomValues = $this->customValues($invId);
        if ($this->s->getSetting('mark_invoices_sent_pdf') == 1) {
            $this->markSentIfApplicable($invId);
            $this->s->invoiceMarkSent($invId, $this->coreDeps->iR);
        }
        $inv = $this->coreDeps->iR->repoInvUnloadedquery($invId);
        if (null === $inv) {
            return '';
        }
        $soId = $inv->getSoId();
        $so = ($soId !== null && $soId > 0)
            ? $this->coreDeps->soR->repoSalesOrderUnloadedquery($soId)
            : null;
        return $this->createPdf($invId, $stream, $custom, $so, $invAmount, $invCustomValues);
    }

    public function loadGuestInv(string $urlKey): ?Inv
    {
        return $this->coreDeps->iR->repoUrlKeyGuestLoaded($urlKey);
    }

    public function ucR(): UCR
    {
        return $this->coreDeps->ucR;
    }

    public function uiR(): UIR
    {
        return $this->coreDeps->uiR;
    }

    public function findInv(int $invId): ?Inv
    {
        return $this->coreDeps->iR->repoInvUnloadedquery($invId);
    }

    public function generateHtml(int $invId, bool $custom): string
    {
        $invAmount = $this->coreDeps->iaR->repoInvAmountCount($invId) > 0
            ? $this->coreDeps->iaR->repoInvquery($invId)
            : null;
        if (null === $invAmount) {
            return '';
        }
        $invCustomValues = $this->customValues($invId);
        $invUnloaded = $this->coreDeps->iR->repoInvUnloadedquery($invId);
        if (null === $invUnloaded) {
            return '';
        }
        $soId = $invUnloaded->getSoId();
        $so = ($soId !== null && $soId > 0)
            ? $this->coreDeps->soR->repoSalesOrderUnloadedquery($soId)
            : null;
        $inv = $this->coreDeps->iR->repoCount($invId) > 0
            ? $this->coreDeps->iR->repoInvLoadedquery($invId)
            : null;
        if (null === $inv) {
            return '';
        }
        return $this->renderHtml($invId, $inv->reqUserId(), $custom, $so, $invAmount, $invCustomValues, $inv);
    }

    private function createPdf(
        int $invId,
        bool $stream,
        bool $custom,
        ?SalesOrder $so,
        ?InvAmount $invAmount,
        array $invCustomValues,
    ): string {
        $inv = $this->coreDeps->iR->repoCount($invId) > 0
            ? $this->coreDeps->iR->repoInvLoadedquery($invId)
            : null;
        if (null === $inv) {
            return '';
        }
        $html = $this->renderHtml(
            $invId, $inv->reqUserId(), $custom, $so, $invAmount, $invCustomValues, $inv,
        );
        $this->session->set('print_language', '');
        $mpdf = new MpdfHelper($this->translator);
        $includeZugferd = $this->s->getSetting('include_zugferd') !== '0';
        if ($includeZugferd && null !== $invAmount) {
            $z = new ZugFerdHelper($this->s, $this->itemDeps->iiaR, $invAmount, $this->translator);
            $associatedFiles = [[
                'name' => 'ZUGFeRD-invoice.xml',
                'description' => 'ZUGFeRD Invoice',
                'AFRelationship' => 'Alternative',
                'mime' => 'text/xml',
                'path' => $z->generateInvoiceZugferdXmlTempFile($inv),
            ]];
        } else {
            $associatedFiles = [];
        }
        $filename = $this->translator->translate('invoice')
            . '_'
            . str_replace(['\\', '/'], '_', $inv->getNumber() ?? (string) random_int(0, 10));
        return $mpdf->pdfCreate(
            $html, $filename, $stream, $this->s,
            new PdfCreateContext(
                $inv->getPassword(), $this->itemDeps->iiaR, $invAmount,
                true, $includeZugferd, $associatedFiles, $inv,
            ),
        );
    }

    private function renderHtml(
        int $invId,
        int $userId,
        bool $custom,
        ?SalesOrder $so,
        ?InvAmount $invAmount,
        array $invCustomValues,
        Inv $inv,
    ): string {
        $userinv = $this->coreDeps->uiR->repoUserInvcount($userId) > 0
            ? $this->coreDeps->uiR->repoUserInvquery($userId)
            : null;
        $invTemplate = $this->resolveTemplate($inv->reqStatusId() ?: 1);
        $items = $this->itemDeps->iiR->repoCount($invId) > 0
            ? $this->itemDeps->iiR->repoInvItemIdquery($invId)
            : null;
        $clientPoNumber = $so ? $so->getClientPoNumber() : '';
        $dateHelper = new DateHelper($this->s);
        /** @var string|null $language */
        $language = $this->session->get('_language');
        $showItemDiscounts = false;
        $vat = $this->s->getSetting('enable_vat_registration');
        if (null !== $items) {
            /** @var \App\Infrastructure\Persistence\InvItem\InvItem $item */
            foreach ($items as $item) {
                if ($item->getDiscountAmount() !== 0.00) {
                    $showItemDiscounts = true;
                }
            }
        }
        $cfTable = $this->docDeps->cfR->repoTablequery('inv_custom');
        $cvH = new CVH($this->s, $this->docDeps->cvR);
        $data = [
            'aciiR' => $this->itemDeps->aciiR,
            'inv' => $inv,
            'inv_tax_rates' => $this->itemDeps->itrR->repoCount($invId) > 0
                ? $this->itemDeps->itrR->repoInvquery($invId)
                : [],
            'items' => $items,
            'iiaR' => $this->itemDeps->iiaR,
            'output_type' => 'pdf',
            'show_item_discounts' => $showItemDiscounts,
            'show_custom_fields' => $custom,
            'custom_fields' => $cfTable,
            'custom_values' => $this->docDeps->cvR->fixCfValueToCf($cfTable),
            'cvH' => $cvH,
            'inv_custom_values' => $invCustomValues,
            'top_custom_fields' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/template/invoice/pdf/top_custom_fields',
                ['custom_fields' => $cfTable, 'cvR' => $this->docDeps->cvR,
                 'inv_custom_values' => $invCustomValues, 'cvH' => $cvH],
            ),
            'view_custom_fields' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/template/invoice/pdf/view_custom_fields',
                ['custom_fields' => $cfTable, 'cvR' => $this->docDeps->cvR,
                 'inv_custom_values' => $invCustomValues, 'cvH' => $cvH],
            ),
            'userinv' => $userinv,
            'company_logo_and_address' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/company_logo_and_address.php',
                [
                    'company' => $this->s->getPrivateCompanyDetails() !== []
                        ? $this->s->getPrivateCompanyDetails()
                        : $this->s->getConfigCompanyDetails(),
                    'document_number' => $inv->getNumber(),
                    'client_purchase_order_number' => $clientPoNumber,
                    'date_tax_point' => $dateHelper->dateFromMysql($inv->getDateTaxPoint()),
                    '_language' => $language,
                    'inv_id' => $invId,
                    'isInvoice' => true,
                    'isQuote' => false,
                    'isSalesOrder' => false,
                ],
            ),
            'inv_allowance_charges' => $this->renderAllowanceCharges($invId, $vat),
            'delivery_location' => $this->renderDeliveryLocation(
                (string) $language,
                $inv->getDeliveryLocationId() ?? 0,
            ),
            'client' => $this->docDeps->cR->repoClientqueryOrig(
                $inv->getClient()?->reqId() ?? 0,
            ),
            'inv_amount' => $invAmount,
            'cldr' => array_search(
                $this->printLanguage($inv),
                $this->s->localeLanguageArray(),
            ),
        ];
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/template/invoice/pdf/' . $invTemplate, $data,
        );
    }

    private function resolveTemplate(int $statusId): string
    {
        return match (true) {
            $statusId == 4 && !empty($this->s->getSetting('pdf_invoice_template_paid')) =>
                $this->s->getSetting('pdf_invoice_template_paid'),
            $statusId == 4 && empty($this->s->getSetting('pdf_invoice_template_paid')) =>
                'paid',
            $statusId == 5 && !empty($this->s->getSetting('pdf_invoice_template_overdue')) =>
                $this->s->getSetting('pdf_invoice_template_overdue'),
            $statusId == 5 && empty($this->s->getSetting('pdf_invoice_template_overdue')) =>
                'overdue',
            default => strlen($this->s->getSetting('pdf_invoice_template')) > 0
                ? $this->s->getSetting('pdf_invoice_template')
                : 'invoice',
        };
    }

    private function renderAllowanceCharges(int $invId, string $vat): string
    {
        if ($invId === 0) {
            return '';
        }
        $charges = $this->itemDeps->aciR->repoACIquery($invId);
        $aOrC = 'allowance.or.charge.';
        $identifier = 0;
        $print = '';
        /** @var InvAllowanceCharge $charge */
        foreach ($charges as $charge) {
            $allowanceCharge = $charge->getAllowanceCharge();
            $allowanceOrCharge = '';
            if ($allowanceCharge) {
                $identifier = $allowanceCharge->getIdentifier();
                $allowanceOrCharge = $identifier
                    ? $this->translator->translate($aOrC . 'allowance')
                    : $this->translator->translate($aOrC . 'charge');
            }
            $amount = $charge->getAmount();
            $vatOrTax = $charge->getVatOrTax();
            $amountTitle = $this->translator->translate($aOrC . 'amount');
            $key = match (true) {
                $identifier && $vat   => $aOrC . 'allowance.vat',
                $identifier           => $aOrC . 'tax',
                !$identifier && $vat  => $aOrC . 'charge.vat',
                default               => $aOrC . 'charge.tax',
            };
            $vatOrHeadingTitle = $this->translator->translate($key);
            $print .= "{$allowanceOrCharge}: {$amountTitle} {$amount}, {$vatOrHeadingTitle}: {$vatOrTax}<br>";
        }
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/inv/partial_inv_allowance_charges',
            ['title' => $this->translator->translate($aOrC . 'inv'), 'inv_allowance_charges' => $print],
        );
    }

    private function renderDeliveryLocation(string $language, int $deliveryLocationId): string
    {
        if ($deliveryLocationId <= 0) {
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

    private function customValues(int $invId): array
    {
        $values = [];
        if ($invId > 0 && $this->docDeps->icR->repoInvCount($invId) > 0) {
            /** @var InvCustom $invCustom */
            foreach ($this->docDeps->icR->repoFields($invId) as $invCustom) {
                $values[] = $invCustom;
            }
        }
        return $values;
    }

    private function markSentIfApplicable(int $invId): void
    {
        if ($invId <= 0) {
            return;
        }
        $inv = $this->coreDeps->iR->repoInvUnloadedquery($invId);
        if (null === $inv) {
            return;
        }
        if ($this->coreDeps->iR->repoCount($invId) > 0
            && $inv->reqStatusId() === 1
            && $inv->getNumber() === ''
        ) {
            $inv->setNumber((string) $this->generateNumber($inv->reqGroupId()));
            $this->coreDeps->iR->save($inv);
        }
    }

    private function generateNumber(int $groupId): mixed
    {
        if ($this->s->getSetting('generate_invoice_number_for_draft') == '0') {
            return $this->coreDeps->iR->getInvNumber($groupId, $this->coreDeps->gR);
        }
        return '';
    }

    private function printLanguage(Inv $inv): mixed
    {
        $locale = $this->localeToLanguage();
        return $inv->getClient()?->getClientLanguage() ?? $locale;
    }

    private function localeToLanguage(): ?string
    {
        $dropdown = (string) $this->session->get('_language');
        /** @var array<string, string> $sessionList */
        $sessionList = $this->s->localeLanguageArray();
        return $sessionList[$dropdown] ?? null;
    }
}
