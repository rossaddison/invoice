<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;
use Yiisoft\Router\CurrentRoute;

/**
 * @var string $csrf
 * @var CurrentRoute $currentRoute
 * @var \Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 */

  echo $alert;
?>

<?php
$header = Div::tag()
  ->addClass('row')
  ->content(
    H5::tag()
    ->addClass('bg-primary text-white p-3 rounded-top')
    ->content(
      I::tag()->addClass('bi bi-receipt')
      ->content(' ' . Html::encode($translator->translate('invoice.delivery')))
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
?>
<br>
<?=
  GridView::widget()
  ->columns(
    DataColumn::create()
    ->attribute('id')
    ->label($s->trans('id'))
    ->value(static fn(object $model) => Html::encode($model->getId())),
    DataColumn::create()
    ->attribute('start_date')
    ->label($s->trans('start_date'))
    ->value(static fn(object $model) => Html::encode(($model->getStart_date())->format($datehelper->style()))),
    DataColumn::create()
    ->attribute('actual_delivery_date')
    ->label($s->trans('actual_delivery_date'))
    ->value(static fn(object $model) => Html::encode(($model->getActual_delivery_date())->format($datehelper->style()))),
    DataColumn::create()
    ->attribute('end_date')
    ->label($s->trans('end_date'))
    ->value(static fn(object $model) => Html::encode(($model->getEnd_date())->format($datehelper->style()))),
    DataColumn::create() 
    ->value(static function ($model) use ($urlGenerator, $translator): string {
        return Html::a($translator->translate('invoice.back'), $urlGenerator->generate('inv/edit', ['id' => $model->getInv_id()]), ['style' => 'text-decoration:none'])->render();
    }
    ),
    DataColumn::create()
    ->label($translator->translate('invoice.delivery.location.global.location.number'))
    ->attribute('delivery_location_id')
    ->value(static fn($model): string => Html::encode($model->getDelivery_location()?->getGlobal_location_number())
    ),
  )
  ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
  ->filterPosition('header')
  ->filterModelName('delivery')
  ->header($header)
  ->id('w14-grid')
  ->paginator($paginator)
  ->pagination(
    OffsetPagination::widget()
    ->menuClass('pagination justify-content-center')
    ->paginator($paginator)
    ->urlArguments([])
    ->render(),
  )
  ->rowAttributes(['class' => 'align-middle'])
  ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
  ->summary($grid_summary)
  ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
  ->emptyText((string) $translator->translate('invoice.invoice.no.records'))
  ->tableAttributes(['class' => 'table table-striped text-center h-191', 'id' => 'table-delivery'])
  ->toolbar(
    Form::tag()->post($urlGenerator->generate('delivery/index'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close()
);

$pageSize = $paginator->getCurrentPageSize();
if ($pageSize > 0) {
  echo Html::p(
    sprintf($translator->translate('invoice.index.footer.showing').' deliveries: Max ' . $max . ' deliveries per page: Total Deliveries ' . $paginator->getTotalItems(), $pageSize, $paginator->getTotalItems()),
    ['class' => 'text-muted']
  );
} else {
  echo Html::p($translator->translate('invoice.records.no'));
}
