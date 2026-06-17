<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Widget;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button as HtmlButton;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Select;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Toolbar builder extracted from QuotesListWidget to stay within the S1448 limit.
 */
final class QuotesToolbar
{
    private const string ROUTE_INDEX = 'quote/index';
    private const string CSS_DROPDOWN_STATUS_ITEM = 'dropdown-item quote-status-item';

    public static function build(
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        CurrentRoute $currentRoute,
        string|\Stringable $csrf,
        int $clientCount,
        string $groupBy,
        bool $enableGrouping,
    ): string {
        $currentRouteName = $currentRoute->getName() ?? self::ROUTE_INDEX;
        [$toolbarReset, $allVisible, $enabledAddBtn, $disabledAddBtn]
            = self::buildToolbarButtons($currentRouteName, $urlGenerator, $translator);
        $buttons = new QuotesToolbarButtons(
            toolbarReset:         $toolbarReset,
            allVisible:           $allVisible,
            enabledAddBtn:        $enabledAddBtn,
            disabledAddBtn:       $disabledAddBtn,
            changeStatusDropdown: self::buildChangeStatusDropdown($translator),
        );
        return self::buildToolbarString(
            $translator, $urlGenerator, $csrf, $clientCount, $groupBy, $enableGrouping,
            $buttons,
        );
    }

    /** @return array{0: string, 1: string, 2: string, 3: string} */
    private static function buildToolbarButtons(
        string $currentRouteName,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
    ): array {
        $toolbarReset = (new A())
            ->addAttributes(['type' => 'reset'])
            ->addClass('btn btn-primary me-1 ajax-loader')
            ->content(new I()->addClass('bi bi-bootstrap-reboot'))
            ->href($urlGenerator->generate($currentRouteName))
            ->id('btn-reset')
            ->render();

        $allVisible = (new A())
            ->addAttributes([
                'type'           => 'reset',
                'data-bs-toggle' => 'tooltip',
                'title'          => $translator->translate('hide.or.unhide.columns'),
            ])
            ->addClass('btn btn-warning me-1 ajax-loader')
            ->content('↔️')
            ->href($urlGenerator->generate('setting/visible', ['origin' => 'quote']))
            ->id('btn-all-visible')
            ->render();

        $btnStyle = 'text-decoration:none; background-color: #ffffff !important;'
            . ' border: 2px solid #b19cd9 !important; color: #b19cd9 !important;'
            . ' font-weight: 500;';

        $enabledAddBtn = (new A())
            ->addAttributes([
                'class'          => 'btn',
                'data-bs-toggle' => 'modal',
                'style'          => $btnStyle,
            ])
            ->content('➕')
            ->href('#modal-add-quote')
            ->id('btn-enabled-quote-add-button')
            ->render();

        $disabledAddBtn = (new A())
            ->addAttributes([
                'class'          => 'btn',
                'data-bs-toggle' => 'tooltip',
                'title'          => $translator->translate('add.client'),
                'disabled'       => 'disabled',
                'style'          => $btnStyle . ' opacity: 0.5;',
            ])
            ->content('➕')
            ->href('#modal-add-quote')
            ->id('btn-disabled-quote-add-button')
            ->render();

        return [$toolbarReset, $allVisible, $enabledAddBtn, $disabledAddBtn];
    }

    private static function buildChangeStatusDropdown(TranslatorInterface $translator): string
    {
        return Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
            . Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            . Html::openTag('button', [
                'type'               => 'button',
                'class'              => 'btn btn-success dropdown-toggle',
                'data-bs-toggle'     => 'dropdown',
                'aria-expanded'      => 'false',
                'id'                 => 'btn-quote-change-status',
                'data-bs-auto-close' => 'true',
            ])
            . '☑️ ' . Html::encode($translator->translate('status'))
            . Html::closeTag('button')
            . Html::openTag('ul', [
                'class'           => 'dropdown-menu',
                'aria-labelledby' => 'btn-quote-change-status',
            ])
            . Html::openTag('li')
            . Html::tag('a', '🗋 ' . Html::encode($translator->translate('draft')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM, 'data-status-id' => '1', 'href' => '#'])
            . Html::closeTag('li')
            . Html::openTag('li')
            . Html::tag('a', '📨 ' . Html::encode($translator->translate('sent')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM, 'data-status-id' => '2', 'href' => '#'])
            . Html::closeTag('li')
            . Html::openTag('li')
            . Html::tag('a', '👀 ' . Html::encode($translator->translate('viewed')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM, 'data-status-id' => '3', 'href' => '#'])
            . Html::closeTag('li')
            . Html::openTag('li')
            . Html::tag('a', '✅ ' . Html::encode($translator->translate('approved')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM, 'data-status-id' => '4', 'href' => '#'])
            . Html::closeTag('li')
            . Html::openTag('li')
            . Html::tag('a', '❌ ' . Html::encode($translator->translate('rejected')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM, 'data-status-id' => '5', 'href' => '#'])
            . Html::closeTag('li')
            . Html::openTag('li')
            . Html::tag('a', '🚫 ' . Html::encode($translator->translate('canceled')),
                ['class' => self::CSS_DROPDOWN_STATUS_ITEM, 'data-status-id' => '6', 'href' => '#'])
            . Html::closeTag('li')
            . Html::closeTag('ul')
            . Html::closeTag('div')
            . Html::closeTag('div');
    }

    private static function buildToolbarString(
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        string|\Stringable $csrf,
        int $clientCount,
        string $groupBy,
        bool $enableGrouping,
        QuotesToolbarButtons $buttons,
    ): string {
        $collapseExpandButtons = $enableGrouping
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

        $groupBySelect = (new Div())
            ->addClass('btn-group ms-3')
            ->addAttributes(['role' => 'group'])
            ->content(
                (new Label())
                    ->addClass('btn btn-outline-secondary active bi bi-collection me-1')
                    ->content(' ' . $translator->translate('group.by') . ':')
                . (new Select())
                    ->addClass('form-select group-by-select')
                    ->addAttributes([
                        'style'         => 'max-width: 150px;',
                        'data-base-url' => $urlGenerator->generate(self::ROUTE_INDEX),
                    ])
                    ->optionsData([
                        'none'         => $translator->translate('grouping.none'),
                        'status'       => $translator->translate('status'),
                        'client'       => $translator->translate('client'),
                        'client_group' => $translator->translate('client.group'),
                        'month'        => $translator->translate('month'),
                        'year'         => $translator->translate('year'),
                        'date'         => $translator->translate('date'),
                        'amount_range' => 'Amount Range',
                    ])
                    ->value($groupBy)
            )
            ->encode(false)
            ->render();

        return (new Form())->post($urlGenerator->generate(self::ROUTE_INDEX))
                ->csrf($csrf)->open()
            . (new Div())->addClass('float-start')->content(
                (new H4())
                    ->addClass('me-3 d-inline-block')
                    ->content($translator->translate('quote'))
                . Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
                . $buttons->allVisible
                . $buttons->toolbarReset
                . ($clientCount == 0 ? $buttons->disabledAddBtn : $buttons->enabledAddBtn)
                . Html::closeTag('div')
                . $buttons->changeStatusDropdown
                . $groupBySelect
                . $collapseExpandButtons
            )->encode(false)->render()
            . (new Form())->close();
    }
}
