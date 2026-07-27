<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use App\Infrastructure\Persistence\EmailTemplate\EmailTemplate;
use App\Infrastructure\Persistence\FromDropDown\FromDropDown;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button as HtmlButton;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Select;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Toolbar HTML builder extracted from InvsListWidget to stay within S1448 limit.
 */
final class InvsToolbar
{
    private const string ROUTE_INDEX = 'inv/index';

    public static function build(InvsToolbarParams $p): string
    {
        $toolbarReset = new A()
            ->addAttributes(['type' => 'reset'])
            ->addClass('btn btn-primary me-1 ajax-loader')
            ->content(new I()->addClass('bi bi-bootstrap-reboot'))
            ->href($p->urlGenerator->generate($p->currentRoute->getName() ?? self::ROUTE_INDEX))
            ->id('btn-reset')
            ->render();

        $allVisible = new A()
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                'title' => $p->translator->translate('hide.or.unhide.columns')])
            ->addClass('btn btn-warning me-1 ajax-loader')
            ->content('↔️')
            ->href($p->urlGenerator->generate('setting/visible', ['origin' => 'inv']))
            ->id('btn-all-visible')
            ->render();

        $copyMultiple = new A()
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'modal',
                'title' => Html::encode($p->translator->translate('copy.invoice'))])
            ->addClass('btn btn-success')
            ->href('#modal-copy-inv-multiple')
            ->content('☑️' . $p->translator->translate('copy.invoice'))
            ->id('btn-modal-copy-inv-multipe')
            ->render();

        $copyAllToDate = new A()
            ->addAttributes(['type' => 'button', 'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-copy-all-to-date',
                'title' => Html::encode($p->translator->translate('copy.all.to.date')),
                'style' => 'text-decoration:none'])
            ->addClass('btn btn-success')
            ->content('📅 ' . $p->translator->translate('copy.all.to.date'))
            ->id('btn-copy-all-to-date')
            ->render();

        $markAsSent = new A()
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                'title' => Html::encode($p->translator->translate('sent'))])
            ->addClass('btn btn-success')
            ->content('☑️' . $p->translator->translate('sent') . $p->iR->getSpecificStatusArrayEmoji(2))
            ->id('btn-mark-as-sent')
            ->render();

        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $t     = $p->translator;

        $bulkQuickPay = new A()
            ->addAttributes(['type' => 'button', 'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-bulk-quick-pay',
                'title' => Html::encode($t->translate('quick.pay')),
                'style' => 'text-decoration:none'])
            ->addClass('btn btn-success')
            ->content('☑️💰 ' . $t->translate('quick.pay'))
            ->id('btn-bulk-quick-pay')
            ->render();

        $bulkQuickPayModal  = self::buildBulkQuickPayModal($t, $today);
        $copyAllToDateModal = self::buildCopyAllToDateModal($t, $today);
        $batchEmailBtn      = self::buildBatchEmailButton($t);
        $batchEmailModal    = self::buildBatchEmailModal($p, $t);
        $markSentAsDraft    = self::buildMarkSentAsDraft($p);

        $markRecurring = new A()
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'modal'])
            ->addClass('btn btn-info')
            ->href('#create-recurring-multiple')
            ->content('☑️' . $p->translator->translate('recurring') . '♻️')
            ->render();

        $addBtn = self::buildAddBtn($p);
        $groupBySelect = self::buildGroupBySelect($p);
        $collapseExpand = self::buildCollapseExpand($p);

        return new Form()
                ->post($p->urlGenerator->generate(self::ROUTE_INDEX))
                ->csrf($p->csrf)
                ->open()
            . new Div()->addClass('float-start')->content(
                new H4()
                    ->addClass('me-3 d-inline-block')
                    ->content($p->translator->translate('invoice'))
                . Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
                . $allVisible
                . $toolbarReset
                . $copyMultiple
                . $copyAllToDate
                . $markAsSent
                . $markSentAsDraft
                . $bulkQuickPay
                . $batchEmailBtn
                . $markRecurring
                . $addBtn
                . Html::closeTag('div')
                . $groupBySelect
                . $collapseExpand
            )->encode(false)->render()
            . new Form()->close()
            . $bulkQuickPayModal
            . $copyAllToDateModal
            . $batchEmailModal;
    }

    private static function buildBulkQuickPayModal(TranslatorInterface $t, string $today): string
    {
        return Html::openTag('div', ['class' => 'modal fade', 'id' => 'modal-bulk-quick-pay',
                'tabindex' => '-1', 'aria-hidden' => 'true'])
            . Html::openTag('div', ['class' => 'modal-dialog'])
            . Html::openTag('div', ['class' => 'modal-content'])
            . Html::openTag('div', ['class' => 'modal-header'])
            . Html::tag('h5', '💰 ' . Html::encode($t->translate('quick.pay')),
                ['class' => 'modal-title'])
            . Html::tag('button', '', ['type' => 'button', 'class' => 'btn-close',
                'data-bs-dismiss' => 'modal', 'aria-label' => 'Close'])
            . Html::closeTag('div')
            . Html::openTag('div', ['class' => 'modal-body'])
            . Html::openTag('div', ['class' => 'mb-3'])
            . Html::tag('label', Html::encode($t->translate('payment.date')),
                ['class' => 'form-label', 'for' => 'bulk-quick-pay-date'])
            . Html::tag('input', '', ['type' => 'date', 'id' => 'bulk-quick-pay-date',
                'class' => 'form-control', 'value' => $today, 'required' => true,
                'data-action' => 'show-picker'])
            . Html::closeTag('div')
            . Html::openTag('div', ['class' => 'mb-3'])
            . Html::tag('label', Html::encode($t->translate('bank.ref')),
                ['class' => 'form-label', 'for' => 'bulk-quick-pay-note'])
            . Html::tag('input', '', ['type' => 'text', 'id' => 'bulk-quick-pay-note',
                'class' => 'form-control', 'placeholder' => Html::encode($t->translate('bank.ref'))])
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::openTag('div', ['class' => 'modal-footer'])
            . Html::tag('button', Html::encode($t->translate('cancel')),
                ['type' => 'button', 'class' => 'btn btn-secondary',
                    'data-bs-dismiss' => 'modal'])
            . Html::tag('button', '💰 ' . Html::encode($t->translate('quick.pay')),
                ['type' => 'button', 'class' => 'btn btn-success',
                    'id' => 'bulk-quick-pay-confirm'])
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::closeTag('div');
    }

    /**
     * Unlike buildBulkQuickPayModal() above, this has no checkbox selection
     * step at all — it acts on every invoice currently matching the grid's
     * active filters (see MultipleCopy::copyAllToDate()), so the date field
     * is the only input; the JS-side confirm() dialog is the sole guard
     * before a potentially large bulk-copy runs.
     */
    private static function buildCopyAllToDateModal(TranslatorInterface $t, string $today): string
    {
        return Html::openTag('div', ['class' => 'modal fade', 'id' => 'modal-copy-all-to-date',
                'tabindex' => '-1', 'aria-hidden' => 'true'])
            . Html::openTag('div', ['class' => 'modal-dialog'])
            . Html::openTag('div', ['class' => 'modal-content'])
            . Html::openTag('div', ['class' => 'modal-header'])
            . Html::tag('h5', '📅 ' . Html::encode($t->translate('copy.all.to.date')),
                ['class' => 'modal-title'])
            . Html::tag('button', '', ['type' => 'button', 'class' => 'btn-close',
                'data-bs-dismiss' => 'modal', 'aria-label' => 'Close'])
            . Html::closeTag('div')
            . Html::openTag('div', ['class' => 'modal-body'])
            . Html::tag('p', Html::encode($t->translate('copy.all.to.date.warning')),
                ['class' => 'text-danger'])
            . Html::openTag('div', ['class' => 'mb-3'])
            . Html::tag('label', Html::encode($t->translate('copy.all.to.date.new.date')),
                ['class' => 'form-label', 'for' => 'copy-all-to-date-date'])
            . Html::tag('input', '', ['type' => 'date', 'id' => 'copy-all-to-date-date',
                'class' => 'form-control', 'value' => $today, 'required' => true,
                'data-action' => 'show-picker'])
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::openTag('div', ['class' => 'modal-footer'])
            . Html::tag('button', Html::encode($t->translate('cancel')),
                ['type' => 'button', 'class' => 'btn btn-secondary',
                    'data-bs-dismiss' => 'modal'])
            . Html::tag('button', '📅 ' . Html::encode($t->translate('copy.all.to.date')),
                ['type' => 'button', 'class' => 'btn btn-success',
                    'id' => 'copy-all-to-date-confirm'])
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::closeTag('div');
    }

    private static function buildMarkSentAsDraft(InvsToolbarParams $p): string
    {
        return $p->sR->getSetting('disable_read_only') === '0'
            ? new A()
                ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                    'title' => Html::encode(
                        $p->translator->translate('security.disable.read.only.info')),
                    'disabled' => 'disabled', 'style' => 'text-decoration:none'])
                ->addClass('btn btn-success')
                ->content('☑️' . $p->translator->translate('draft') . $p->iR->getSpecificStatusArrayEmoji(1))
                ->id('btn-mark-sent-as-draft')
                ->render()
            : new A()
                ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                    'title' => Html::encode($p->translator->translate('draft')),
                    'style' => 'text-decoration:none'])
                ->addClass('btn btn-success')
                ->content('☑️' . $p->translator->translate('draft') . $p->iR->getSpecificStatusArrayEmoji(1))
                ->id('btn-mark-sent-as-draft')
                ->render();
    }

    private static function buildAddBtn(InvsToolbarParams $p): string
    {
        return $p->clientCount > 0
            ? new A()
                ->addAttributes(['class' => 'btn btn-info', 'data-bs-toggle' => 'modal',
                    'style' => 'text-decoration:none'])
                ->content('➕')
                ->href('#modal-add-inv')
                ->id('btn-enabled-invoice-add-button')
                ->render()
            : new A()
                ->addAttributes(['class' => 'btn btn-info', 'data-bs-toggle' => 'tooltip',
                    'title' => $p->translator->translate('add.client'),
                    'disabled' => 'disabled', 'style' => 'text-decoration:none'])
                ->content('➕')
                ->href('#modal-add-inv')
                ->id('btn-disabled-invoice-add-button')
                ->render();
    }

    private static function buildGroupBySelect(InvsToolbarParams $p): string
    {
        return new Div()
            ->addClass('btn-group ms-3')
            ->addAttributes(['role' => 'group'])
            ->content(
                new Label()
                    ->addClass('btn btn-outline-secondary active bi bi-collection me-1')
                    ->content(' ' . $p->translator->translate('group.by') . ':')
                . new Select()
                    ->addClass('form-select group-by-select')
                    ->addAttributes([
                        'style'         => 'max-width: 150px;',
                        'data-base-url' => $p->urlGenerator->generate(self::ROUTE_INDEX),
                    ])
                    ->optionsData([
                        'none'            => $p->translator->translate('grouping.none'),
                        'status'          => $p->translator->translate('status'),
                        'client'          => $p->translator->translate('client'),
                        'client_group'    => $p->translator->translate('client.group'),
                        'month'           => $p->translator->translate('month'),
                        'year'            => $p->translator->translate('year'),
                        'date'            => $p->translator->translate('date'),
                        'amount_range'    => 'Amount Range',
                        'peppol_workflow' => $p->translator->translate('peppol'),
                        'quick_pay'       => '💰 ' . $p->translator->translate('quick.pay'),
                    ])
                    ->value($p->groupBy)
            )
            ->encode(false)
            ->render();
    }

    private static function buildBatchEmailButton(TranslatorInterface $t): string
    {
        return new A()
            ->addAttributes([
                'type'             => 'button',
                'data-bs-toggle'   => 'modal',
                'data-bs-target'   => '#modal-batch-email',
                'title'            => Html::encode($t->translate('email.client')),
                'style'            => 'text-decoration:none',
            ])
            ->addClass('btn btn-info')
            ->content('☑️📧 ' . $t->translate('email.client'))
            ->id('btn-batch-email')
            ->render();
    }

    private static function buildBatchEmailModal(InvsToolbarParams $p, TranslatorInterface $t): string
    {
        $templateOptions = '';
        /** @var EmailTemplate $tpl */
        foreach ($p->etR->repoEmailTemplateType('invoice') as $tpl) {
            $templateOptions .= Html::tag('option',
                $tpl->getEmailTemplateTitle() ?? '',
                ['value' => (string) $tpl->reqEmailTemplateId()],
            )->render();
        }

        $fromOptions = '';
        /** @var FromDropDown $from */
        foreach ($p->fdR->findAllPreloaded() as $from) {
            $fromOptions .= Html::tag('option',
                Html::encode($from->getEmail()),
                ['value' => (string) $from->reqId()],
            )->render();
        }

        $fromAddUrl = $p->urlGenerator->generate('from/add', ['returnUrl' => 'batchEmail']);

        $fromRow = Html::openTag('div', ['class' => 'mb-3'])
            . Html::tag('label', Html::encode($t->translate('from.email.address')),
                ['class' => 'form-label', 'for' => 'batch-email-from'])
            . Html::openTag('div', ['class' => 'd-flex gap-2 align-items-center'])
            . Html::openTag('select', ['id' => 'batch-email-from', 'class' => 'form-select'])
            . $fromOptions
            . Html::closeTag('select')
            . Html::tag('a', '➕',
                ['href' => $fromAddUrl, 'class' => 'btn btn-outline-secondary flex-shrink-0',
                    'title' => Html::encode($t->translate('add'))])
            . Html::closeTag('div')
            . ($fromOptions === ''
                ? Html::tag('small',
                    Html::encode($t->translate('from.email.address')) . ': '
                    . Html::encode($t->translate('not.set')) . ' — '
                    . Html::encode($t->translate('add')) . ' ➕',
                    ['class' => 'text-warning'])
                : '')
            . Html::closeTag('div');

        return Html::openTag('div', ['class' => 'modal fade', 'id' => 'modal-batch-email',
                'tabindex' => '-1', 'aria-hidden' => 'true'])
            . Html::openTag('div', ['class' => 'modal-dialog modal-lg'])
            . Html::openTag('div', ['class' => 'modal-content'])
            . Html::openTag('div', ['class' => 'modal-header'])
            . Html::tag('h5', '📧 ' . Html::encode($t->translate('email.client')),
                ['class' => 'modal-title'])
            . Html::tag('button', '', ['type' => 'button', 'class' => 'btn-close',
                'data-bs-dismiss' => 'modal', 'aria-label' => 'Close'])
            . Html::closeTag('div')
            . Html::openTag('div', ['class' => 'modal-body'])
            . Html::openTag('div', ['id' => 'batch-email-loading', 'class' => 'text-center py-3'])
            . Html::tag('span', '', ['class' => 'spinner-border text-primary', 'role' => 'status'])
            . Html::closeTag('div')
            . Html::openTag('div', ['id' => 'batch-email-preview', 'class' => 'd-none'])
            . Html::openTag('table', ['class' => 'table table-sm table-bordered mb-3'])
            . Html::openTag('thead')
            . Html::openTag('tr')
            . Html::tag('th', Html::encode($t->translate('client')))
            . Html::tag('th', 'Email')
            . Html::tag('th', 'Source')
            . Html::tag('th', Html::encode($t->translate('invoice')))
            . Html::closeTag('tr')
            . Html::closeTag('thead')
            . Html::openTag('tbody', ['id' => 'batch-email-preview-body'])
            . Html::closeTag('tbody')
            . Html::closeTag('table')
            . $fromRow
            . Html::openTag('div', ['class' => 'mb-3'])
            . Html::tag('label',
                Html::encode($t->translate('email.template')),
                ['class' => 'form-label', 'for' => 'batch-email-template'])
            . Html::openTag('select', ['id' => 'batch-email-template', 'class' => 'form-select'])
            . $templateOptions
            . Html::closeTag('select')
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::openTag('div', ['class' => 'modal-footer'])
            . Html::tag('button', Html::encode($t->translate('cancel')),
                ['type' => 'button', 'class' => 'btn btn-secondary',
                    'data-bs-dismiss' => 'modal'])
            . Html::tag('button', '📧 ' . Html::encode($t->translate('email.client')),
                ['type' => 'button', 'class' => 'btn btn-primary d-none',
                    'id' => 'batch-email-confirm'])
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::closeTag('div')
            . Html::closeTag('div');
    }

    private static function buildCollapseExpand(InvsToolbarParams $p): string
    {
        return $p->enableGrouping
            ? new Div()
                ->addClass('btn-group ms-2')
                ->addAttributes(['role' => 'group'])
                ->content(
                    new HtmlButton()
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes(['data-action' => 'toggle-all-groups', 'data-expand' => 'false',
                            'title' => 'Collapse All Groups'])
                        ->content(new I()->addClass('bi bi-chevron-up'))
                    . new HtmlButton()
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes(['data-action' => 'toggle-all-groups', 'data-expand' => 'true',
                            'title' => 'Expand All Groups'])
                        ->content(new I()->addClass('bi bi-chevron-down'))
                )
                ->encode(false)
                ->render()
            : '';
    }
}
