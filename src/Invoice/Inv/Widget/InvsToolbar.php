<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button as HtmlButton;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Select;

/**
 * Toolbar HTML builder extracted from InvsListWidget to stay within S1448 limit.
 */
final class InvsToolbar
{
    private const string ROUTE_INDEX = 'inv/index';

    public static function build(InvsToolbarParams $p): string
    {
        $toolbarReset = (new A())
            ->addAttributes(['type' => 'reset'])
            ->addClass('btn btn-primary me-1 ajax-loader')
            ->content(new I()->addClass('bi bi-bootstrap-reboot'))
            ->href($p->urlGenerator->generate($p->currentRoute->getName() ?? self::ROUTE_INDEX))
            ->id('btn-reset')
            ->render();

        $allVisible = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                'title' => $p->translator->translate('hide.or.unhide.columns')])
            ->addClass('btn btn-warning me-1 ajax-loader')
            ->content('↔️')
            ->href($p->urlGenerator->generate('setting/visible', ['origin' => 'inv']))
            ->id('btn-all-visible')
            ->render();

        $copyMultiple = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'modal',
                'title' => Html::encode($p->translator->translate('copy.invoice'))])
            ->addClass('btn btn-success')
            ->href('#modal-copy-inv-multiple')
            ->content('☑️' . $p->translator->translate('copy.invoice'))
            ->id('btn-modal-copy-inv-multipe')
            ->render();

        $markAsSent = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                'title' => Html::encode($p->translator->translate('sent'))])
            ->addClass('btn btn-success')
            ->content('☑️' . $p->translator->translate('sent') . $p->iR->getSpecificStatusArrayEmoji(2))
            ->id('btn-mark-as-sent')
            ->render();

        $markSentAsDraft = $p->sR->getSetting('disable_read_only') === '0'
            ? (new A())
                ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                    'title' => Html::encode(
                        $p->translator->translate('security.disable.read.only.info')),
                    'disabled' => 'disabled', 'style' => 'text-decoration:none'])
                ->addClass('btn btn-success')
                ->content('☑️' . $p->translator->translate('draft') . $p->iR->getSpecificStatusArrayEmoji(1))
                ->id('btn-mark-sent-as-draft')
                ->render()
            : (new A())
                ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
                    'title' => Html::encode($p->translator->translate('draft')),
                    'style' => 'text-decoration:none'])
                ->addClass('btn btn-success')
                ->content('☑️' . $p->translator->translate('draft') . $p->iR->getSpecificStatusArrayEmoji(1))
                ->id('btn-mark-sent-as-draft')
                ->render();

        $markRecurring = (new A())
            ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'modal'])
            ->addClass('btn btn-info')
            ->href('#create-recurring-multiple')
            ->content('☑️' . $p->translator->translate('recurring') . '♻️')
            ->render();

        $addBtn = $p->clientCount > 0
            ? (new A())
                ->addAttributes(['class' => 'btn btn-info', 'data-bs-toggle' => 'modal',
                    'style' => 'text-decoration:none'])
                ->content('➕')
                ->href('#modal-add-inv')
                ->id('btn-enabled-invoice-add-button')
                ->render()
            : (new A())
                ->addAttributes(['class' => 'btn btn-info', 'data-bs-toggle' => 'tooltip',
                    'title' => $p->translator->translate('add.client'),
                    'disabled' => 'disabled', 'style' => 'text-decoration:none'])
                ->content('➕')
                ->href('#modal-add-inv')
                ->id('btn-disabled-invoice-add-button')
                ->render();

        $groupBySelect = (new Div())
            ->addClass('btn-group ms-3')
            ->addAttributes(['role' => 'group'])
            ->content(
                (new Label())
                    ->addClass('btn btn-outline-secondary active bi bi-collection me-1')
                    ->content(' ' . $p->translator->translate('group.by') . ':')
                . (new Select())
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
                    ])
                    ->value($p->groupBy)
            )
            ->encode(false)
            ->render();

        $collapseExpand = $p->enableGrouping
            ? (new Div())
                ->addClass('btn-group ms-2')
                ->addAttributes(['role' => 'group'])
                ->content(
                    (new HtmlButton())
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes(['onclick' => 'toggleAllGroups(false)',
                            'title' => 'Collapse All Groups'])
                        ->content(new I()->addClass('bi bi-chevron-up'))
                    . (new HtmlButton())
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes(['onclick' => 'toggleAllGroups(true)',
                            'title' => 'Expand All Groups'])
                        ->content(new I()->addClass('bi bi-chevron-down'))
                )
                ->encode(false)
                ->render()
            : '';

        return (new Form())
                ->post($p->urlGenerator->generate(self::ROUTE_INDEX))
                ->csrf($p->csrf)
                ->open()
            . (new Div())->addClass('float-start')->content(
                (new H4())
                    ->addClass('me-3 d-inline-block')
                    ->content($p->translator->translate('invoice'))
                . Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
                . $allVisible
                . $toolbarReset
                . $copyMultiple
                . $markAsSent
                . $markSentAsDraft
                . $markRecurring
                . $addBtn
                . Html::closeTag('div')
                . $groupBySelect
                . $collapseExpand
            )->encode(false)->render()
            . (new Form())->close();
    }
}
