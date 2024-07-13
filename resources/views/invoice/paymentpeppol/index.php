<?php
declare(strict_types=1);

use App\Invoice\Entity\PaymentPeppol;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var App\Invoice\Entity\PaymentPeppol $paymentpeppol 
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $routeCurrent
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var string $alert
 * @var string $csrf
 * @var string $id
 */

echo $alert;
?>
<h1><?= $translator->translate('invoice.paymentpeppol') ?></h1>
<?php
    $columns = [
        new DataColumn(
            'id',
            header:  $translator->translate('i.id'),
            content: static fn(PaymentPeppol $model) => $model->getId()
        ),
        new DataColumn(
            header:  $translator->translate('i.view'),
            content: static function (PaymentPeppol $model) use ($urlGenerator): string {
              return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('paymentpeppol/view', ['id' => $model->getId()]), [])->render();
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.edit'),
            content: static function (PaymentPeppol $model) use ($urlGenerator): string {
              return Html::a(Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']), $urlGenerator->generate('paymentpeppol/edit', ['id' => $model->getId()]), [])->render();
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.delete'),
            content: static function (PaymentPeppol $model) use ($translator, $urlGenerator): string {
            return Html::a(Html::tag('button',
                Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                [
                  'type' => 'submit',
                  'class' => 'dropdown-button',
                  'onclick' => "return confirm(" . "'" . $translator->translate('i.delete_record_warning') . "');"
                ]
              ),
              $urlGenerator->generate('paymentpeppol/delete', ['id' => $model->getId()]), []
            )->render();
            }
        ),
    ];
?>
<?php
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
      ->href($urlGenerator->generate($routeCurrent->getName() ?? 'paymentpeppol/index'))
      ->id('btn-reset')
      ->render();
    $toolbar = Div::tag();
    $toolbarString = Form::tag()->post($urlGenerator->generate('paymentpeppol/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int)$s->get_setting('default_list_limit'), 
        $translator->translate('invoice.paymentpeppol.reference.plural'),
        ''
    );
    echo GridView::widget()
      ->rowAttributes(['class' => 'align-middle'])
      ->tableAttributes(['class' => 'table table-striped text-center h-99999999999999999', 'id' => 'table-delivery'])
      ->columns(...$columns)
      ->dataReader($paginator)      
      ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
      ->header($header)
      ->id('w137-grid')
      ->pagination(
        OffsetPagination::widget()
        ->paginator($paginator)
        ->render(),
      )
      ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
      ->summaryTemplate($grid_summary)
      ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
      ->emptyText($translator->translate('invoice.invoice.no.records'))
      ->toolbar($toolbarString);
        