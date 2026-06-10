<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Infrastructure\Persistence\{
    SalesOrder\SalesOrder,
    SalesOrderAmount\SalesOrderAmount,
    SalesOrderCustom\SalesOrderCustom,
    SalesOrderItem\SalesOrderItem,
};
use App\Invoice\Helpers\{
    CustomValuesHelper as CVH,
    MpdfHelper,
    PdfCreateContext,
};
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class SalesOrderPdfService
{
    public function __construct(
        private SR $s,
        private SessionInterface $session,
        private TranslatorInterface $translator,
        private WebViewRenderer $webViewRenderer,
        private SalesOrderPdfCoreDeps $coreDeps,
        private SalesOrderPdfDocDeps $docDeps,
        private SalesOrderPdfItemDeps $itemDeps,
    ) {
    }

    public function generate(int $soId, bool $stream, bool $custom): string
    {
        $soAmount = $this->coreDeps->soaR->repoSalesOrderAmountCount($soId) > 0
            ? $this->coreDeps->soaR->repoSalesOrderquery($soId)
            : null;
        if (null === $soAmount) {
            return '';
        }
        $so = $this->coreDeps->soR->repoSalesOrderUnloadedquery($soId);
        if (null === $so) {
            return '';
        }
        return $this->createPdf($soId, $so->reqUserId(), $stream, $custom, $soAmount, $this->customValues($soId));
    }

    public function findSalesOrder(int $soId): ?SalesOrder
    {
        return $this->coreDeps->soR->repoSalesOrderUnloadedquery($soId);
    }

    private function createPdf(
        int $soId,
        int $userId,
        bool $stream,
        bool $custom,
        SalesOrderAmount $soAmount,
        array $soCustomValues,
    ): string {
        $so = $this->coreDeps->soR->repoCount($soId) > 0
            ? $this->coreDeps->soR->repoSalesOrderLoadedquery($soId)
            : null;
        if (null === $so) {
            return '';
        }
        $html = $this->renderHtml($soId, $userId, $custom, $soAmount, $soCustomValues, $so);
        if ($this->s->getSetting('pdf_html_salesorder') === '1') {
            return $html;
        }
        $this->session->set('print_language', '');
        $mpdf = new MpdfHelper($this->translator);
        $filename = $this->translator->translate('salesorder')
            . '_'
            . str_replace(['\\', '/'], '_', $so->getNumber() ?? (string) random_int(0, 10));
        return $mpdf->pdfCreate(
            $html, $filename, $stream, $this->s,
            new PdfCreateContext($so->getPassword(), null, null, false, false, [], $so),
        );
    }

    private function renderHtml(
        int $soId,
        int $userId,
        bool $custom,
        SalesOrderAmount $soAmount,
        array $soCustomValues,
        SalesOrder $so,
    ): string {
        $userinv = $this->coreDeps->uiR->repoUserInvcount($userId) > 0
            ? $this->coreDeps->uiR->repoUserInvquery($userId)
            : null;
        $soTemplate = !empty($this->s->getSetting('pdf_salesorder_template'))
            ? $this->s->getSetting('pdf_salesorder_template')
            : 'salesorder';
        $items = $this->itemDeps->soiR->repoCount($soId) > 0
            ? $this->itemDeps->soiR->repoSalesOrderItemIdquery($soId)
            : null;
        $clientNumber = (string) $so->getClient()?->getClientNumber();
        $showItemDiscounts = false;
        if (null !== $items) {
            /** @var SalesOrderItem $item */
            foreach ($items as $item) {
                if ($item->getDiscountAmount() !== 0.00) {
                    $showItemDiscounts = true;
                }
            }
        }
        $cfTable = $this->docDeps->cfR->repoTablequery('salesorder_custom');
        $cvH = new CVH($this->s, $this->docDeps->cvR);
        $data = [
            'salesorder' => $so,
            'salesorder_tax_rates' => $this->itemDeps->sotrR->repoCount($soId) > 0
                ? $this->itemDeps->sotrR->repoSalesOrderquery($soId)
                : null,
            'items' => $items,
            'soiaR' => $this->itemDeps->soiaR,
            'acsoiR' => $this->itemDeps->acsoiR,
            'output_type' => 'pdf',
            'show_item_discounts' => $showItemDiscounts,
            'show_custom_fields' => $custom,
            'custom_fields' => $cfTable,
            'custom_values' => $this->docDeps->cvR->fixCfValueToCf($cfTable),
            'cvH' => $cvH,
            'cvR' => $this->docDeps->cvR,
            'salesorder_custom_values' => $soCustomValues,
            'top_custom_fields' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/template/salesorder/pdf/top_custom_fields',
                ['custom_fields' => $cfTable, 'cvR' => $this->docDeps->cvR,
                 'salesorder_custom_values' => $soCustomValues, 'cvH' => $cvH],
            ),
            'view_custom_fields' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/template/salesorder/pdf/view_custom_fields',
                ['custom_fields' => $cfTable, 'cvR' => $this->docDeps->cvR,
                 'salesorder_custom_values' => $soCustomValues, 'cvH' => $cvH],
            ),
            'company_logo_and_address' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/setting/company_logo_and_address.php',
                [
                    'company' => $this->s->getConfigCompanyDetails(),
                    'document_number' => $so->getNumber(),
                    'client_number' => $clientNumber,
                    'isInvoice' => false,
                    'isQuote' => false,
                    'isSalesOrder' => true,
                ],
            ),
            'userInv' => $userinv,
            'client' => $this->docDeps->cR->repoClientqueryOrig(
                $so->getClient()?->reqId() ?? 0,
            ),
            'so_amount' => $soAmount,
            'cldr' => array_search(
                $this->printLanguage($so),
                $this->s->localeLanguageArray(),
            ),
        ];
        return $this->webViewRenderer->renderPartialAsString(
            '//invoice/template/salesorder/pdf/' . $soTemplate, $data,
        );
    }

    private function customValues(int $soId): array
    {
        $values = [];
        if ($soId > 0 && $this->coreDeps->socR->repoSalesOrderCount($soId) > 0) {
            /** @var SalesOrderCustom $soCustom */
            foreach ($this->coreDeps->socR->repoFields($soId) as $soCustom) {
                $values[] = $soCustom;
            }
        }
        return $values;
    }

    private function printLanguage(SalesOrder $so): mixed
    {
        return $so->getClient()?->getClientLanguage() ?? $this->localeToLanguage();
    }

    private function localeToLanguage(): ?string
    {
        $dropdown = (string) $this->session->get('_language');
        /** @var array<string, string> $sessionList */
        $sessionList = $this->s->localeLanguageArray();
        return $sessionList[$dropdown] ?? null;
    }
}
