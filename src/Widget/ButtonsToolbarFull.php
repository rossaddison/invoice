<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\Sumex;
use App\Invoice\InvAmount\InvAmountRepository;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * ButtonsToolbarFull widget provides a comprehensive horizontal toolbar
 * with all available invoice actions organized into primary and advanced sections
 */
final readonly class ButtonsToolbarFull
{
    public function __construct(
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private SettingRepository $settingRepository,
    ) {}

    /**
     * Generate comprehensive invoice actions toolbar with all features
     */
    public function render(
        Inv $inv,
        InvAmountRepository $iaR,
        ?Sumex $sumex = null,
        bool $invEdit = true,
        bool $paymentView = true,
        bool $read_only = false,
        array $enabledGateways = [],
        string $vat = '0',
        bool $isRecurring = false,
        bool $paymentCfExist = false,
    ): string {
        $invId = $inv->getId();
        $primaryButtons = [];
        $advancedButtons = [];

        // View originating quote (if exists)
        if (!empty($inv->getQuote_id()) && $inv->getQuote_id() !== '0') {
            $primaryButtons[] = $this->createButton(
                'view-quote',
                $this->urlGenerator->generate('quote/view', ['id' => $inv->getQuote_id()]),
                'fa-file-text-o',
                'btn-info',
                $this->translator->translate('view') . ' ' . $this->translator->translate('quote'),
            );
        }

        // Core primary actions
        if ($invEdit) {
            $primaryButtons[] = $this->createButton(
                'edit',
                $this->urlGenerator->generate('inv/edit', ['id' => $invId]),
                'fa-edit',
                'btn-primary',
                $this->translator->translate('edit'),
            );
        }

        // PDF Generation
        $pdfTitle = $this->settingRepository->getSetting('sumex') === '1'
            ? $this->translator->translate('generate.sumex')
            : $this->translator->translate('download.pdf');

        $primaryButtons[] = $this->createModalButton(
            'pdf',
            '#inv-to-pdf',
            'fa-file-pdf-o',
            'btn-success',
            $pdfTitle,
        );

        // Email functionality
        if ($invEdit) {
            $primaryButtons[] = $this->createButton(
                'email',
                $this->urlGenerator->generate('inv/email_stage_0', ['id' => $invId]),
                'fa-envelope',
                'btn-info',
                $this->translator->translate('send.email'),
            );
        }

        // Copy Invoice
        if ($invEdit) {
            $primaryButtons[] = $this->createModalButton(
                'copy',
                '#inv-to-inv',
                'fa-copy',
                'btn-secondary',
                $this->translator->translate('copy.invoice'),
            );
        }

        // Payment Entry
        $invAmount = $iaR->repoInvAmountcount((int) $invId) > 0 ? $iaR->repoInvquery((int) $invId) : null;
        if ($invAmount && $invAmount->getBalance() >= 0.00 && $inv->getStatus_id() !== 1 && $invEdit) {
            $primaryButtons[] = $this->createButton(
                'payment',
                $this->urlGenerator->generate('payment/add'),
                'fa-credit-card',
                'btn-warning',
                $this->translator->translate('enter.payment'),
                [
                    'class' => 'invoice-add-payment',
                    'data-invoice-id' => Html::encode($invId),
                    'data-invoice-balance' => Html::encode($invAmount->getBalance()),
                    'data-invoice-payment-method' => Html::encode($inv->getPayment_method()),
                    'data-payment-cf-exist' => Html::encode($paymentCfExist),
                ],
            );
        }

        // Credit Invoice Creation
        if (($read_only === true || $inv->getStatus_id() === 4) && $invEdit && !(int) $inv->getCreditinvoice_parent_id() > 0) {
            $primaryButtons[] = $this->createModalButton(
                'credit',
                '#create-credit-inv',
                'fa-minus',
                'btn-danger',
                $this->translator->translate('create.credit.invoice'),
                ['data-invoice-id' => $invId],
            );
        }

        // Recurring Invoice
        if ($invEdit) {
            $primaryButtons[] = $this->createButton(
                'recurring',
                $this->urlGenerator->generate('invrecurring/add', ['inv_id' => $invId]),
                'fa-refresh',
                'btn-secondary',
                $this->translator->translate('create.recurring'),
            );
        }

        // Get advanced buttons
        $advancedButtons = $this->getAdvancedButtons($inv, $sumex, $invEdit, $enabledGateways, $vat);

        return $this->renderFullToolbar($primaryButtons, $advancedButtons, $inv);
    }

    private function getAdvancedButtons(
        Inv $inv,
        ?Sumex $sumex,
        bool $invEdit,
        array $enabledGateways,
        string $vat,
    ): array {
        $invId = $inv->getId();
        $buttons = [];

        // Tax and Allowance/Charge buttons
        if ($invEdit && $vat === '0') {
            $buttons[] = $this->createModalButton(
                'add-tax',
                '#add-inv-tax',
                'fa-plus',
                'btn-outline-secondary',
                $this->translator->translate('add.invoice.tax'),
            );
        }

        if ($invEdit) {
            $buttons[] = $this->createModalButton(
                'allowance-charge',
                '#add-inv-allowance-charge',
                'fa-plus',
                'btn-outline-secondary',
                $this->translator->translate('allowance.or.charge.inv.add'),
            );
        }

        // PEPPOL features
        if ($invEdit && $inv->getSo_id()) {
            $buttons[] = $this->createWindowButton(
                'peppol',
                $this->urlGenerator->generate('inv/peppol', ['id' => $invId]),
                'fa-window-restore',
                'btn-outline-info',
                $this->translator->translate('peppol'),
            );

            // Delivery location
            $buttons[] = $this->createButton(
                'delivery-location',
                $this->urlGenerator->generate('del/add', ['client_id' => $inv->getClient_id()]),
                'fa-plus',
                'btn-outline-info',
                $this->translator->translate('delivery.location.add'),
            );

            // PEPPOL toggle
            $peppolStreamToggle = $this->settingRepository->getSetting('peppol_stream_toggle') === '1';
            $buttons[] = $this->createButton(
                'peppol-toggle',
                $this->urlGenerator->generate('inv/peppol_stream_toggle', ['id' => $invId]),
                $peppolStreamToggle ? 'fa-toggle-on' : 'fa-toggle-off',
                'btn-outline-info',
                $this->translator->translate('peppol.stream.toggle'),
            );

            // External validators
            $buttons[] = $this->createWindowButton(
                'ecosio-validator',
                'https://ecosio.com/en/peppol-and-xml-document-validator-button/?pk_abe=EN_Peppol_XML_Validator_Page&pk_abv=With_CTA',
                'fa-check',
                'btn-outline-success',
                $this->translator->translate('peppol.ecosio.validator'),
            );

            $buttons[] = $this->createWindowButton(
                'storecove',
                $this->urlGenerator->generate('inv/storecove', ['id' => $invId]),
                'fa-eye',
                'btn-outline-info',
                $this->translator->translate('storecove.invoice.json.encoded'),
            );
        }

        // Payment gateways - REMOVED
        // Pay-now buttons should only appear in the options dropdown menu, not in the toolbar

        // Modal PDF (if enabled)
        if ($this->settingRepository->getSetting('pdf_stream_inv') === '1') {
            $buttons[] = $this->createModalButton(
                'modal-pdf',
                '#inv-to-modal-pdf',
                'fa-desktop',
                'btn-outline-success',
                $this->translator->translate('pdf.modal') . ' ✅',
            );
        } else {
            $buttons[] = $this->createButton(
                'modal-pdf-settings',
                $this->urlGenerator->generate('setting/tab_index', [], ['active' => 'invoices'], 'settings[pdf_stream_inv]'),
                'fa-desktop',
                'btn-outline-secondary',
                $this->translator->translate('pdf.modal') . ' ❌',
            );
        }

        // HTML Preview
        $htmlTitle = $this->settingRepository->getSetting('sumex') === '1'
            ? $this->translator->translate('html.sumex.yes')
            : $this->translator->translate('html.sumex.no');

        $buttons[] = $this->createModalButton(
            'html-preview',
            '#inv-to-html',
            'fa-code',
            'btn-outline-secondary',
            $htmlTitle,
        );

        // Sumex editing
        if ($sumex && (null !== $sumex->getInvoice())) {
            $buttons[] = $this->createButton(
                'sumex-edit',
                $this->urlGenerator->generate('sumex/edit', ['id' => $invId]),
                'fa-edit',
                'btn-outline-info',
                $this->translator->translate('sumex.edit'),
            );
        }

        // Delete buttons (if allowed)
        if ($this->canDeleteInvoice($inv, $invEdit)) {
            $buttons[] = $this->createModalButton(
                'delete-invoice',
                '#delete-inv',
                'fa-trash',
                'btn-outline-danger',
                $this->translator->translate('delete'),
            );

            $buttons[] = $this->createModalButton(
                'delete-items',
                '#delete-items',
                'fa-trash',
                'btn-outline-danger',
                $this->translator->translate('delete') . ' ' . $this->translator->translate('item'),
            );
        }

        return $buttons;
    }

    private function canDeleteInvoice(Inv $inv, bool $invEdit): bool
    {
        return ($inv->getStatus_id() === 1 ||
                ($this->settingRepository->getSetting('enable_invoice_deletion') === '1' &&
                 $inv->getIs_read_only() === false)) &&
               !$inv->getSo_id() &&
               $invEdit;
    }

    private function createButton(
        string $id,
        string $href,
        string $icon,
        string $btnClass,
        string $title,
        array $attributes = [],
    ): array {
        return [
            'type' => 'link',
            'id' => $id,
            'href' => $href,
            'icon' => $icon,
            'class' => $btnClass,
            'title' => $title,
            'attributes' => $attributes,
        ];
    }

    private function createModalButton(
        string $id,
        string $target,
        string $icon,
        string $btnClass,
        string $title,
        array $attributes = [],
    ): array {
        $attributes['data-bs-toggle'] = 'modal';
        return [
            'type' => 'modal',
            'id' => $id,
            'href' => $target,
            'icon' => $icon,
            'class' => $btnClass,
            'title' => $title,
            'attributes' => $attributes,
        ];
    }

    private function createWindowButton(
        string $id,
        string $href,
        string $icon,
        string $btnClass,
        string $title,
    ): array {
        return [
            'type' => 'window',
            'id' => $id,
            'href' => $href,
            'icon' => $icon,
            'class' => $btnClass,
            'title' => $title,
        ];
    }

    private function renderFullToolbar(array $primaryButtons, array $advancedButtons, Inv $inv): string
    {
        $string = Html::openTag('div', [
            'class' => 'invoice-actions-toolbar-full',
            'style' => 'margin: 8px 0; padding: 8px 12px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 1px solid #dee2e6; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; flex-wrap: wrap;',
        ]);

        /**
         * @var array $button
         */
        foreach ($primaryButtons as $button) {
            $string .= $this->renderButton($button);
        }

        // Status indicators inline
        $string .= $this->renderInlineStatusIndicators($inv);

        // Divider
        if (!empty($advancedButtons)) {
            $string .= Html::openTag('div', [
                'style' => 'height: 20px; width: 1px; background: #dee2e6; margin: 0 4px;',
            ]);
            $string .= Html::closeTag('div');
        }

        /**
         * @var array $button
         */
        foreach ($advancedButtons as $button) {
            $string .= $this->renderButton($button);
        }

        $string .= Html::closeTag('div'); // main toolbar
        return $string;
    }

    private function renderButton(array $button): string
    {
        $link = A::tag()
            ->id('toolbar-full-' . (string) $button['id'])
            ->addClass('btn', (string) $button['class'], 'btn-sm')
            ->attribute('title', (string) $button['title'])
            ->content('<i class="fa ' . Html::encode((string) $button['icon']) . '"></i> ' . Html::encode($button['title']));

        // Add additional attributes
        if (isset($button['attributes'])) {
            /**
             * @var string $key
             * @var string $value
             */
            foreach ($button['attributes'] as $key => $value) {
                $link = $link->attribute($key, $value);
            }
        }

        // Handle different button types
        switch ($button['type']) {
            case 'modal':
                $link = $link->href((string) $button['href']);
                break;
            case 'window':
                $link = $link->href('#')
                           ->attribute('onclick', "window.open('" . Html::encode((string) $button['href']) . "')");
                break;
            case 'link':
            default:
                $link = $link->href((string) $button['href']);
                break;
        }

        return $link->encode(false)->render(); // Allow HTML content for icon
    }

    private function renderStatusIndicators(Inv $inv): string
    {
        $string = Html::openTag('div', [
            'class' => 'invoice-status-indicators',
            'style' => 'margin: 6px 0; display: flex; gap: 6px; align-items: center; flex-wrap: wrap;',
        ]);

        if ($inv->getIs_read_only() === true) {
            $string .= Span::tag()
                ->addClass('badge bg-danger')
                ->attribute('style', 'font-size: 0.75rem; padding: 4px 8px; border-radius: 12px;')
                ->content('🔒 ' . $this->translator->translate('read.only'))
                ->render();
        }

        $statusClass = match ($inv->getStatus_id()) {
            1 => 'bg-secondary',
            2 => 'bg-info',
            3 => 'bg-warning',
            4 => 'bg-success',
            5 => 'bg-danger',
            default => 'bg-light',
        };

        $statusText = match ($inv->getStatus_id()) {
            1 => '📝 ' . $this->translator->translate('draft'),
            2 => '📤 ' . $this->translator->translate('sent'),
            3 => '👁 ' . $this->translator->translate('viewed'),
            4 => '✅ ' . $this->translator->translate('paid'),
            5 => '⚠️ ' . $this->translator->translate('overdue'),
            default => '❓ ' . $this->translator->translate('unknown'),
        };

        $string .= Span::tag()
            ->addClass('badge ' . $statusClass)
            ->attribute('style', 'font-size: 0.75rem; padding: 4px 8px; border-radius: 12px;')
            ->content($statusText)
            ->render();

        $string .= Html::closeTag('div');
        return $string;
    }

    private function renderInlineStatusIndicators(Inv $inv): string
    {
        $string = '';

        if ($inv->getIs_read_only() === true) {
            $string .= Span::tag()
                ->addClass('badge bg-danger')
                ->attribute('style', 'font-size: 0.7rem; padding: 3px 6px; border-radius: 10px; margin-right: 6px;')
                ->content('🔒 ' . $this->translator->translate('read.only'))
                ->render();
        }

        $statusClass = match ($inv->getStatus_id()) {
            1 => 'bg-secondary',
            2 => 'bg-info',
            3 => 'bg-warning',
            4 => 'bg-success',
            5 => 'bg-danger',
            default => 'bg-light',
        };

        $statusText = match ($inv->getStatus_id()) {
            1 => '📝 ' . $this->translator->translate('draft'),
            2 => '📤 ' . $this->translator->translate('sent'),
            3 => '👁 ' . $this->translator->translate('viewed'),
            4 => '✅ ' . $this->translator->translate('paid'),
            5 => '⚠️ ' . $this->translator->translate('overdue'),
            default => '❓ ' . $this->translator->translate('unknown'),
        };

        $string .= Span::tag()
            ->addClass('badge ' . $statusClass)
            ->attribute('style', 'font-size: 0.7rem; padding: 3px 6px; border-radius: 10px;')
            ->content($statusText)
            ->render();

        return $string;
    }
}
