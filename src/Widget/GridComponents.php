<?php
declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Entity\Client;
use Yiisoft\Data\Paginator\OffsetPaginator as DataOffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Yii\DataView\OffsetPagination;
use Yiisoft\Yii\DataView\UrlConfig;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

final class GridComponents
{
    private CurrentRoute $currentRoute;
    private Translator $translator;
    private UrlGenerator $generator;
    
    public function __construct(CurrentRoute $currentRoute, Translator $translator, UrlGenerator $generator) {
        $this->currentRoute = $currentRoute;
        $this->translator = $translator;
        $this->generator = $generator;
    }
    
    public function header(string $translatorString)  : string {
        return  Div::tag()
                ->addClass('row')
                ->content(
                    H5::tag()
                        ->addClass('bg-primary text-white p-3 rounded-top')
                        ->content(
                            I::tag()
                            ->addClass('bi bi-receipt')
                            ->content(' ' . $this->translator->translate($translatorString))
                    )
                )
                ->render();
    }
     
    public function offsetPaginationWidget(int $pageSize, DataOffsetPaginator $paginator) : string
    {
        return OffsetPagination::widget()
        ->listTag('ul')    
        ->listAttributes(['class' => 'pagination'])
        ->itemTag('li')
        ->itemAttributes(['class' => 'page-item'])
        ->linkAttributes(['class' => 'page-link'])
        ->currentItemClass('active')
        ->currentLinkClass('page-link')
        ->disabledItemClass('disabled')
        ->disabledLinkClass('disabled')
        ->defaultPageSize($pageSize)
        ->urlConfig(new UrlConfig()) 
        ->urlCreator(new UrlCreator($this->generator))    
        ->paginator($paginator)
        ->render();
    } 
    
    public function toolbarReset(UrlGenerator $generator) : string
    {
        $route = $this->currentRoute->getName();
        return   null!==$route ? A::tag()
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
    public function gridMiniTableOfInvoicesForClient(Client $model, int $max_per_row, UrlGenerator $urlGenerator) : string {
        $item_count = 0;
        $string = Html::openTag('table');
        $string .= Html::openTag('tr', [
            'class' => 'card-header bg-info text-black'
        ]);
        $invoices = $model->getInvs()->toArray();
        // Work with the Array Collection to build an output string
        /**
         * @var \App\Invoice\Entity\Inv $invoice 
         */
        foreach ($invoices as $invoice) 
        {
            if ($item_count == $max_per_row)
            {
                $string .= Html::closeTag('tr');
                $string .= Html::openTag('tr', ['class' => 'card-header bg-info text-black']);
                $item_count = 0;
            }
            $invNumber = $invoice->getNumber();
            $invId = $invoice->getId();
            $invBalance = $invoice->getInvAmount()->getBalance();
            $string .= Html::openTag('td'). 
                A::tag()
                    ->addAttributes([
                        'style' => 'text-decoration:none', 
                        'data-bs-toggle' => 'tooltip', 
                        'title' => $invoice->getDate_created()->format('m-d')])
                    ->href($urlGenerator->generate('inv/view', ['id' => $invId]))
                    ->content(
                        ((null!== $invNumber && null!==$invId)
                              ? $invNumber 
                              : $this->translator
                                     ->translate('invoice.invoice.number.missing.therefore.use.invoice.id'). 
                                       ($invId ?? '')). 
                                       ' ' . 
                                       (null!==$invBalance
                                             ? $invBalance
                                             : '')
                    )
                    ->render(). 
                Html::closeTag('td'); 
            $item_count++;
        }
        $string .= Html::closeTag('tr');
        $string .= Html::closeTag('table'); 
        return $string;
    }
}