<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Entity\Quote;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

final readonly class QuoteToolbar
{
        public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    private function buildButtons(Quote $quote, bool $quoteEdit, string $vat, ?float $quoteAmountTotal): array
    {
        $quoteId = $quote->getId();
        $buttons = [];

        // View created invoice (if exists)
        if ($quote->getInvId() !== '0' && !empty($quote->getInvId())) {
            $buttons[] = $this->createLinkButton(
                'view-invoice',
                $this->urlGenerator->generate('inv/view', ['id' => $quote->getInvId()]),
                'bi-file-text',
                'btn-outline-success',
                $this->translator->translate('view') . ' ' . $this->translator->translate('invoice'),
            );
        }

        // Edit button
        if ($quoteEdit) {
            $buttons[] = $this->createLinkButton(
                'edit-quote',
                $this->urlGenerator->generate('quote/edit', ['id' => $quoteId]),
                'bi-pencil-square',
                'btn-outline-primary',
                $this->translator->translate('edit'),
            );
        }

        // Add Quote Tax button (only if VAT is disabled and editing allowed)
        if ($quoteEdit && $vat === '0') {
            $buttons[] = $this->createModalButton(
                'add-quote-tax',
                '#add-quote-tax',
                'bi-plus-circle',
                'btn-outline-secondary',
                $this->translator->translate('add.quote.tax'),
            );
        }

        // PDF button
        $buttons[] = $this->createModalButton(
            'quote-to-pdf',
            '#quote-to-pdf',
            'bi-printer',
            'btn-outline-info',
            $this->translator->translate('download.pdf'),
        );

        // Email button (only if editing allowed, quote is draft, and has amount)
        if (null !== $quoteAmountTotal) {
            if ($quoteEdit && ($quote->getStatusId() === 1) && ($quoteAmountTotal > 0)) {
                $buttons[] = $this->createLinkButton(
                    'send-email',
                    $this->urlGenerator->generate('quote/emailStage0', ['id' => $quoteId]),
                    'bi-send',
                    'btn-outline-success',
                    $this->translator->translate('send.email'),
                );
            }
        }

        // Quote to SO button - show enabled if approved, disabled if not approved
        if ($quoteEdit && $quote->getSoId() === '0' && null !== $quoteAmountTotal && $quoteAmountTotal > 0) {
            if ($quote->getStatusId() === 4) {
                // Quote is approved - show enabled button
                $buttons[] = $this->createModalButton(
                    'quote-to-so',
                    '#quote-to-so',
                    'bi-arrow-repeat',
                    'btn-outline-warning',
                    $this->translator->translate('quote.to.so'),
                );
            } else {
                // Quote not approved - show disabled button with indicator
                $buttons[] = $this->createDisabledButton(
                    'quote-to-so-disabled',
                    'bi-arrow-repeat',
                    'btn-outline-secondary',
                    $this->translator->translate('quote.to.so') . ' (' . $this->translator->translate('approval.required') . ')',
                    $this->translator->translate('quote.must.be.approved.first'),
                );
            }
        }

        // Quote to Invoice button - show enabled if approved, disabled if not approved (but don't show if already converted)
        if ($quoteEdit && $quote->getInvId() === '0' && null !== $quoteAmountTotal && $quoteAmountTotal > 0) {
            if ($quote->getStatusId() === 4) {
                // Quote is approved - show enabled button
                $buttons[] = [
                    'type' => 'modal',
                    'id' => 'quote-to-invoice',
                    'href' => '#quote-to-invoice',
                    'icon' => 'bi-arrow-repeat',
                    'class' => '',
                    'title' => $this->translator->translate('quote.to.invoice'),
                    'style' => 'background-color: #ffffff !important; border: 2px solid #b19cd9 !important; color: #b19cd9 !important; font-weight: 500;'
                ];
            } else {
                // Quote not approved - show disabled button with indicator
                $buttons[] = $this->createDisabledButton(
                    'quote-to-invoice-disabled',
                    'bi-arrow-repeat',
                    'btn-outline-secondary',
                    $this->translator->translate('quote.to.invoice') . ' (' . $this->translator->translate('approval.required') . ')',
                    $this->translator->translate('quote.must.be.approved.first'),
                );
            }
        }

        if ($quoteEdit) {
            $buttons[] = $this->createModalButton(
                'allowance-charge',
                '#add-quote-allowance-charge',
                'bi-plus-circle',
                'btn-outline-secondary',
                $this->translator->translate('allowance.or.charge.quote.add'),
            );
        }

        // Copy Quote button
        if ($quoteEdit) {
            $buttons[] = $this->createModalButton(
                'quote-to-quote',
                '#quote-to-quote',
                'bi-copy',
                'btn-outline-secondary',
                $this->translator->translate('copy.quote'),
            );
        }

        // Delete Quote button
        if ($quoteEdit) {
            $buttons[] = $this->createModalButton(
                'delete-quote',
                '#delete-quote',
                'bi-trash',
                'btn-outline-danger',
                $this->translator->translate('delete.quote'),
            );
        }

        // Delete Items button
        if ($quoteEdit) {
            $buttons[] = $this->createModalButton(
                'delete-items',
                '#delete-items',
                'bi-trash',
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
        $iconHtml = Html::openTag('i', ['class' => 'bi ' . (string) $button['icon']]) . Html::closeTag('i');

        if ((string) $button['type'] === 'link') {
            return  new A()
                ->href((string) $button['href'])
                ->addClass($baseClasses)
                ->id($this->getButtonId($button))
                ->content($iconHtml . ' ' . (string) $button['title'])
                ->encode(false)
                ->render();
        } elseif ((string) $button['type'] === 'disabled') {
            // Disabled button with tooltip
            return  new A()
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
            $styleAttr = isset($button['style'])
                ? 'text-decoration: none; ' . (string) $button['style']
                : 'text-decoration: none';

            return  new A()
                ->href((string) $button['href'])
                ->addClass($baseClasses)
                ->id($this->getButtonId($button))
                ->attribute('data-bs-toggle', 'modal')
                ->attribute('style', $styleAttr)
                ->content($iconHtml . ' ' . (string) $button['title'])
                ->encode(false)
                ->render();
        }
    }

    private function renderInlineStatusIndicators(Quote $quote): string
    {
        $badges = [];

        // Quote status indicator
        $statusClass = match ($quote->getStatusId()) {
            1 => 'bg-secondary',    // Draft
            2 => 'bg-primary',      // Sent
            3 => 'bg-warning',      // Viewed
            4 => 'bg-success',      // Approved
            5 => 'bg-danger',       // Rejected
            default => 'bg-light',
        };

        $statusText = match ($quote->getStatusId()) {
            1 => $this->translator->translate('draft'),
            2 => $this->translator->translate('sent'),
            3 => $this->translator->translate('viewed'),
            4 => $this->translator->translate('approved'),
            5 => $this->translator->translate('rejected'),
            default => $this->translator->translate('unknown'),
        };

        $badges[] =  new Span()
            ->addClass('badge ' . $statusClass . ' me-2')
            ->content($statusText)
            ->render();

        // SO status indicator if quote has been converted
        if ($quote->getSoId() !== '0' && !empty($quote->getSoId())) {
            $badges[] =  new Span()
                ->addClass('badge bg-info me-2')
                ->content($this->translator->translate('converted.to.so'))
                ->render();
        }

        // Invoice status indicator if quote has been converted
        if ($quote->getInvId() !== '0' && !empty($quote->getInvId())) {
            $badges[] =  new Span()
                ->addClass('badge bg-success me-2')
                ->content($this->translator->translate('converted.to.invoice'))
                ->render();
        }

        return implode('', $badges);
    }

    public function renderWithStatus(Quote $quote, bool $quoteEdit, string $vat, ?float $quoteAmountTotal): string
    {
        $buttons = $this->buildButtons($quote, $quoteEdit, $vat, $quoteAmountTotal);
        $statusBadges = $this->renderInlineStatusIndicators($quote);

        return Html::openTag('div', [
            'class' => 'quote-actions-toolbar d-flex flex-wrap justify-content-between align-items-left',
            'style' => 'margin-bottom: 1rem;',
        ])
        . Html::openTag('div', ['class' => 'd-flex flex-wrap gap-2 align-items-right'])
        . $this->renderButtons($buttons)
        . Html::closeTag('div')
        . Html::openTag('div', ['class' => 'd-flex align-items-center'])
        . $statusBadges
        . Html::closeTag('div')
        . Html::closeTag('div');
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
