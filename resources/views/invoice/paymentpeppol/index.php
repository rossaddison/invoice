<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\PaymentPeppol $paymentpeppol
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var OffsetPaginator $paginator
 * @var string $id
 */

echo $alert;

?>
<h1><?= $translator->translate('invoice.paymentpeppol') ?></h1>
<?php
    $columns = [
        new DataColumn(
            'id',
            header:  $s->trans('id'),
            content: static fn($model) => $model->getId()
        ),
        new DataColumn(
            header:  $s->trans('view'),
            content: static function ($model) use ($urlGenerator): string {
              return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('paymentpeppol/view', ['id' => $model->getId()]), [])->render();
            }
        ),
        new DataColumn(
            header:  $s->trans('edit'),
            content: static function ($model) use ($urlGenerator): string {
              return Html::a(Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']), $urlGenerator->generate('paymentpeppol/edit', ['id' => $model->getId()]), [])->render();
            }
        ),
        new DataColumn(
            header:  $s->trans('delete'),
            content: static function ($model) use ($s, $urlGenerator): string {
            return Html::a(Html::tag('button',
                Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                [
                  'type' => 'submit',
                  'class' => 'dropdown-button',
                  'onclick' => "return confirm(" . "'" . $s->trans('delete_record_warning') . "');"
                ]
              ),
              $urlGenerator->generate('paymentpeppol/delete', ['id' => $model->getId()]), []
            )->render();
            }
        ),
    ];
?>
<?=
    $header = Div::tag()
      ->addClass('row')
      ->content(
        H5::tag()
        ->addClass('bg-primary text-white p-3 rounded-top')
        ->content(
          I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.paymentpeppol'))
        )
      )
    ->render();
    $toolbarReset = A::tag()
      ->addAttributes(['type' => 'reset'])
      ->addClass('btn btn-danger me-1 ajax-loader')
      ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
      ->href($urlGenerator->generate($currentRoute->getName()))
      ->id('btn-reset')
      ->render();
    $toolbar = Div::tag();
    
    GridView::widget()
      ->columns(...$columns)
      ->dataReader($paginator)      
      ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
      ->filterPosition('header')
      ->filterModelName('paymentpeppol')
      ->header($header)
      ->id('w137-grid')
      ->pagination(
        OffsetPagination::widget()
        ->menuClass('pagination justify-content-center')
        ->paginator($paginator)
        // No need to use page argument since built-in. Use status bar value passed from urlGenerator to inv/guest
        //->urlArguments(['status'=>$status])
        ->render(),
      )
      ->rowAttributes(['class' => 'align-middle'])
      ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
      ->summary($grid_summary)
      ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
      ->emptyText((string) $translator->translate('invoice.invoice.no.records'))
      ->tableAttributes(['class' => 'table table-striped text-center h-99999999999999999', 'id' => 'table-delivery'])
      ->toolbar(
        Form::tag()->post($urlGenerator->generate('paymentpeppol/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
        