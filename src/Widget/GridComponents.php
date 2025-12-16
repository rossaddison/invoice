<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Entity\Client;
use App\Invoice\Entity\Inv;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;

final readonly class GridComponents
{
    public function __construct(private CurrentRoute $currentRoute, private Translator $translator, private UrlGenerator $generator)
    {
    }

    public function header(string $translatorString): string
    {
        return  Div::tag()
                ->addClass('row')
                ->content(
                    H5::tag()
                        ->addClass('bg-primary text-white p-3 rounded-top')
                        ->content(
                            I::tag()
                            ->addClass('bi bi-receipt')
                            ->content(' ' . $this->translator->translate($translatorString)),
                        ),
                )
                ->render();
    }

    public function offsetPaginationWidget(OffsetPaginator $sortedAndPagedPaginator): \Yiisoft\Yii\DataView\Pagination\PaginationWidgetInterface
    {
        return OffsetPagination::widget()
        ->paginator($sortedAndPagedPaginator)
        ->listTag('ul')
        ->listAttributes(['class' => 'pagination'])
        ->itemTag('li')
        ->itemAttributes(['class' => 'page-item'])
        ->linkAttributes(['class' => 'page-link'])
        ->currentItemClass('active')
        ->disabledItemClass('disabled');
    }

    public function toolbarReset(UrlGenerator $generator): string
    {
        $route = $this->currentRoute->getName();
        return   null !== $route ? A::tag()
                ->addAttributes(['type' => 'reset'])
                ->addClass('btn btn-danger me-1 ajax-loader')
                ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
                ->href($generator->generate($route))
                ->id('btn-reset')
                ->render() : '';
    }

    /**
     * @param Client $model
     * @param int $max_per_row
     * @param UrlGenerator $urlGenerator
     * @return string
     */
    public function gridMiniTableOfInvoicesForClient(Client $model, int $max_per_row, UrlGenerator $urlGenerator): string
    {
        $invoices = $model->getInvs()->toArray();
        if (empty($invoices)) {
            return '';
        }

        $itemCount = 0;

        // Table with black border, collapsed borders, and black text inside
        $html = Html::openTag('table', [
            'style' => 'border:1px solid #000; border-collapse:collapse; color:#000;',
            'class' => 'table table-sm mb-0',
        ]);
        $html .= Html::openTag('tr', ['class' => 'card-header bg-info text-black']);

        /** @var \App\Invoice\Entity\Inv $invoice */
        foreach ($invoices as $invoice) {
            if ($itemCount === $max_per_row) {
                $html .= Html::closeTag('tr');
                $html .= Html::openTag('tr', ['class' => 'card-header bg-info text-black']);
                $itemCount = 0;
            }

            $invId = $invoice->getId();
            $invNumberRaw = $invoice->getNumber();
            $invNumberLabel = (null !== $invNumberRaw && null !== $invId)
                ? $invNumberRaw
                : $this->translator->translate('number.missing.therefore.use.invoice.id') . ($invId ?? '');

            // Format amount: round to 2 decimals then trim trailing zeros (at most 2 decimals)
            $invBalance = $invoice->getInvAmount()->getBalance();
            $balancePart = '';
            if ($invBalance !== null) {
                $formatted = number_format($invBalance, 2, '.', '');
                $formatted = rtrim(rtrim($formatted, '0'), '.'); // remove trailing zeros and possible trailing dot
                $balancePart = ' ' . Html::encode($formatted);
            }

            // Tooltip date (safe): guard against missing/invalid date
            $dateTitle = '';
            $dateObj = $invoice->getDate_created();

             try {
                 $dateTitle = $dateObj->format('m-d');
             } catch (\Throwable) {
                 $dateTitle = '';
             }


            $anchorHtml = A::tag()
                ->addAttributes([
                    // ensure link text is black and no underline
                    'style' => 'color:#000; text-decoration:none;',
                    'data-bs-toggle' => 'tooltip',
                    'title' => Html::encode($dateTitle),
                ])
                ->href($urlGenerator->generate('inv/view', ['id' => $invId]))
                ->content(Html::encode($invNumberLabel) . $balancePart)
                ->render();

            // Each cell has a black border and black text
            $html .= Html::openTag('td', ['style' => 'border:1px solid #000; padding:0.25rem; color:#000;']) . $anchorHtml . Html::closeTag('td');

            $itemCount++;
        }

        $html .= Html::closeTag('tr');
        $html .= Html::closeTag('table');

        return $html;
    }   

    /**
     * @param Inv $model
     * @param int $max_per_row
     * @param UrlGenerator $urlGenerator
     * @return string
     */
    public function gridMiniTableOfInvSentLogsForInv(Inv $model, int $max_per_row, UrlGenerator $urlGenerator): string
    {
        $invSentLogs = $model->getInvSentLogs()->toArray();
        if (empty($invSentLogs)) {
            return '';
        }

        $itemCount = 0;

        // Table with black border, collapsed borders, and black text inside
        $html = Html::openTag('table', [
            'style' => 'border:1px solid #000; border-collapse:collapse; color:#000;',
            'class' => 'table table-sm mb-0',
        ]);
        $html .= Html::openTag('tr', ['class' => 'card-header bg-info text-black']);

        /** @var \App\Invoice\Entity\InvSentLog $invSentLog */
        foreach ($invSentLogs as $invSentLog) {
            if ($itemCount === $max_per_row) {
                $html .= Html::closeTag('tr');
                $html .= Html::openTag('tr', ['class' => 'card-header bg-info text-black']);
                $itemCount = 0;
            }

            $invSentLogId = $invSentLog->getId();

            // Tooltip date (safe)
            $dateTitle = '';
            $dateObj = $invSentLog->getDate_sent();
            try {
                $dateTitle = $dateObj->format('m-d');
            } catch (\Throwable) {
                $dateTitle = '';
            }

            $anchorHtml = A::tag()
                ->addAttributes([
                    // ensure link text is black and no underline
                    'style' => 'color:#000; text-decoration:none;',
                    'data-bs-toggle' => 'tooltip',
                    'title' => Html::encode($dateTitle),
                ])
                ->href($urlGenerator->generate('invsentlog/view', ['id' => $invSentLogId]))
                ->content(Html::encode((string)$invSentLogId))
                ->render();

            // Each cell has a 1px black border and black text
            $html .= Html::openTag('td', ['style' => 'border:1px solid #000; padding:0.25rem; color:#000;']) . $anchorHtml . Html::closeTag('td');

            $itemCount++;
        }

        $html .= Html::closeTag('tr');
        $html .= Html::closeTag('table');

        return $html;
    }
}
