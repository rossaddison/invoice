<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Entity\Quote;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface;

final readonly class QuoteToolbar
{
    public function __construct(
        private SettingRepository $settingRepository,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {}

    public function render(Quote $quote, bool $quoteEdit, string $vat, float $quoteAmountTotal): string
    {
        $quoteId = $quote->getId();
        $buttons = $this->buildButtons($quote, $quoteEdit, $vat, $quoteAmountTotal);

        return $this->renderToolbar($buttons);
    }

    private function buildButtons(Quote $quote, bool $quoteEdit, string $vat, ?float $quoteAmountTotal): array
    {
        $quoteId = $quote->getId();
        $buttons = [];

        // View created invoice (if exists)
        if ($quote->getInv_id() !== '0' && !empty($quote->getInv_id())) {
            $buttons[] = $this->createLinkButton(
                'view-invoice',
                $this->urlGenerator->generate('inv/view', ['id' => $quote->getInv_id()]),
                'fa-file-text',
                'btn-outline-success',
                $this->translator->translate('view') . ' ' . $this->translator->translate('invoice'),
            );
        }

        // Edit button
        if ($quoteEdit) {
            $buttons[] = $this->createLinkButton(
                'edit-quote',
                $this->urlGenerator->generate('quote/edit', ['id' => $quoteId]),
                'fa-edit',
                'btn-outline-primary',
                $this->translator->translate('edit'),
            );
        }

        // Add Quote Tax button (only if VAT is disabled and editing allowed)
        if ($quoteEdit && $vat === '0') {
            $buttons[] = $this->createModalButton(
                'add-quote-tax',
                '#add-quote-tax',
                'fa-plus',
                'btn-outline-secondary',
                $this->translator->translate('add.quote.tax'),
            );
        }

        // PDF button
        $buttons[] = $this->createModalButton(
            'quote-to-pdf',
            '#quote-to-pdf',
            'fa-print',
            'btn-outline-info',
            $this->translator->translate('download.pdf'),
        );

        // Email button (only if editing allowed, quote is draft, and has amount)
        if (null !== $quoteAmountTotal) {
            if ($quoteEdit && ($quote->getStatus_id() === 1) && ($quoteAmountTotal > 0)) {
                $buttons[] = $this->createLinkButton(
                    'send-email',
                    $this->urlGenerator->generate('quote/email_stage_0', ['id' => $quoteId]),
                    'fa-send',
                    'btn-outline-success',
                    $this->translator->translate('send.email'),
                );
            }
        }

        // Quote to SO button - show enabled if approved, disabled if not approved
        if ($quoteEdit && $quote->getSo_id() === '0' && null !== $quoteAmountTotal && $quoteAmountTotal > 0) {
            if ($quote->getStatus_id() === 4) {
                // Quote is approved - show enabled button
                $buttons[] = $this->createModalButton(
                    'quote-to-so',
                    '#quote-to-so',
                    'fa-refresh',
                    'btn-outline-warning',
                    $this->translator->translate('quote.to.so'),
                );
            } else {
                // Quote not approved - show disabled button with indicator
                $buttons[] = $this->createDisabledButton(
                    'quote-to-so-disabled',
                    'fa-refresh',
                    'btn-outline-secondary',
                    $this->translator->translate('quote.to.so') . ' (' . $this->translator->translate('approval.required') . ')',
                    $this->translator->translate('quote.must.be.approved.first'),
                );
            }
        }

        // Quote to Invoice button - show enabled if approved, disabled if not approved (but don't show if already converted)
        if ($quoteEdit && $quote->getInv_id() === '0' && null !== $quoteAmountTotal && $quoteAmountTotal > 0) {
            if ($quote->getStatus_id() === 4) {
                // Quote is approved - show enabled button
                $buttons[] = $this->createModalButton(
                    'quote-to-invoice',
                    '#quote-to-invoice',
                    'fa-refresh',
                    'btn-outline-primary',
                    $this->translator->translate('quote.to.invoice'),
                );
            } else {
                // Quote not approved - show disabled button with indicator
                $buttons[] = $this->createDisabledButton(
                    'quote-to-invoice-disabled',
                    'fa-refresh',
                    'btn-outline-secondary',
                    $this->translator->translate('quote.to.invoice') . ' (' . $this->translator->translate('approval.required') . ')',
                    $this->translator->translate('quote.must.be.approved.first'),
                );
            }
        }

        // Copy Quote button
        if ($quoteEdit) {
            $buttons[] = $this->createModalButton(
                'quote-to-quote',
                '#quote-to-quote',
                'fa-copy',
                'btn-outline-secondary',
                $this->translator->translate('copy.quote'),
            );
        }

        // Delete Quote button
        if ($quoteEdit) {
            $buttons[] = $this->createModalButton(
                'delete-quote',
                '#delete-quote',
                'fa-trash',
                'btn-outline-danger',
                $this->translator->translate('delete.quote'),
            );
        }

        // Delete Items button
        if ($quoteEdit) {
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

    private function createLinkButton(string $id, string $href, string $icon, string $class, string $title): array
    {
        return [
            'type' => 'link',
            'id' => $id,
            'href' => $href,
            'icon' => $icon,
            'class' => $class,
            'title' => $title,
        ];
    }

    private function createModalButton(string $id, string $href, string $icon, string $class, string $title): array
    {
        return [
            'type' => 'modal',
            'id' => $id,
            'href' => $href,
            'icon' => $icon,
            'class' => $class,
            'title' => $title,
        ];
    }

    private function createDisabledButton(string $id, string $icon, string $class, string $title, string $tooltip): array
    {
        return [
            'type' => 'disabled',
            'id' => $id,
            'href' => '#',
            'icon' => $icon,
            'class' => $class,
            'title' => $title,
            'tooltip' => $tooltip,
        ];
    }

    private function renderToolbar(array $buttons): string
    {
        return Html::openTag('div', [
            'class' => 'quote-actions-toolbar d-flex flex-wrap gap-2 align-items-center',
            'style' => 'margin-bottom: 1rem;',
        ]) .
        $this->renderButtons($buttons) .
        Html::closeTag('div');
    }

    private function renderButtons(array $buttons): string
    {
        $html = '';

        /**
         * @var array $button
         */
        foreach ($buttons as $button) {
            $html .= $this->renderButton($button) . ' ';
        }

        return trim($html);
    }

    private function renderButton(array $button): string
    {
        $baseClasses = 'btn ' . (string) $button['class'];
        $iconHtml = Html::openTag('i', ['class' => 'fa ' . (string) $button['icon']]) . Html::closeTag('i');

        if ((string) $button['type'] === 'link') {
            return A::tag()
                ->href((string) $button['href'])
                ->addClass($baseClasses)
                ->id($this->getButtonId($button))
                ->content($iconHtml . ' ' . (string) $button['title'])
                ->encode(false)
                ->render();
        } elseif ((string) $button['type'] === 'disabled') {
            // Disabled button with tooltip
            return A::tag()
                ->href('#')
                ->addClass($baseClasses . ' disabled')
                ->id($this->getButtonId($button))
                ->attribute('disabled', 'disabled')
                ->attribute('title', (string) $button['tooltip'])
                ->attribute('data-bs-toggle', 'tooltip')
                ->attribute('style', 'text-decoration: none; cursor: not-allowed; opacity: 0.6;')
                ->attribute('onclick', 'event.preventDefault(); return false;')
                ->content($iconHtml . ' ' . (string) $button['title'])
                ->encode(false)
                ->render();
        } else {
            // Modal button
            return A::tag()
                ->href((string) $button['href'])
                ->addClass($baseClasses)
                ->id($this->getButtonId($button))
                ->attribute('data-bs-toggle', 'modal')
                ->attribute('style', 'text-decoration: none')
                ->content($iconHtml . ' ' . (string) $button['title'])
                ->encode(false)
                ->render();
        }
    }

    private function renderInlineStatusIndicators(Quote $quote): string
    {
        $badges = [];

        // Quote status indicator
        $statusClass = match ($quote->getStatus_id()) {
            1 => 'bg-secondary',    // Draft
            2 => 'bg-primary',      // Sent
            3 => 'bg-warning',      // Viewed
            4 => 'bg-success',      // Approved
            5 => 'bg-danger',       // Rejected
            default => 'bg-light',
        };

        $statusText = match ($quote->getStatus_id()) {
            1 => $this->translator->translate('draft'),
            2 => $this->translator->translate('sent'),
            3 => $this->translator->translate('viewed'),
            4 => $this->translator->translate('approved'),
            5 => $this->translator->translate('rejected'),
            default => $this->translator->translate('unknown'),
        };

        $badges[] = Span::tag()
            ->addClass('badge ' . $statusClass . ' me-2')
            ->content($statusText)
            ->render();

        // SO status indicator if quote has been converted
        if ($quote->getSo_id() !== '0' && !empty($quote->getSo_id())) {
            $badges[] = Span::tag()
                ->addClass('badge bg-info me-2')
                ->content($this->translator->translate('converted.to.so'))
                ->render();
        }

        // Invoice status indicator if quote has been converted
        if ($quote->getInv_id() !== '0' && !empty($quote->getInv_id())) {
            $badges[] = Span::tag()
                ->addClass('badge bg-success me-2')
                ->content($this->translator->translate('converted.to.invoice'))
                ->render();
        }

        return implode('', $badges);
    }

    public function renderWithStatus(Quote $quote, bool $quoteEdit, string $vat, ?float $quoteAmountTotal): string
    {
        $quoteId = $quote->getId();
        $buttons = $this->buildButtons($quote, $quoteEdit, $vat, $quoteAmountTotal);
        $statusBadges = $this->renderInlineStatusIndicators($quote);

        return Html::openTag('div', [
            'class' => 'quote-actions-toolbar d-flex flex-wrap justify-content-between align-items-left',
            'style' => 'margin-bottom: 1rem;',
        ]) .
        Html::openTag('div', ['class' => 'd-flex flex-wrap gap-2 align-items-right']) .
        $this->renderButtons($buttons) .
        Html::closeTag('div') .
        Html::openTag('div', ['class' => 'd-flex align-items-center']) .
        $statusBadges .
        Html::closeTag('div') .
        Html::closeTag('div');
    }

    /**
     * Safely extract button ID ensuring it's either non-empty-string or null
     *
     * @param array $button
     * @return non-empty-string|null
     */
    private function getButtonId(array $button): ?string
    {
        if (!isset($button['id'])) {
            return null;
        }

        $id = trim((string) $button['id']);
        return $id !== '' ? $id : null;
    }
}
