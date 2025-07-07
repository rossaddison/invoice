<?php
declare(strict_types=1);

use App\Invoice\Entity\Upload;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Entity\Upload $upload
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Router\CurrentRoute $currentRoute
 * @var string $alert
 * @var string $csrf
 */

echo $alert;

?>
<h1><?= $translator->translate('upload.index'); ?></h1>
<div>
<?php
    echo Html::a(
        I::tag()->addClass('fa fa-plus'),
        $urlGenerator->generate('upload/add'),
        ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']
    );
?>
</div>
<?php
    $header = Div::tag()
        ->addClass('row')
        ->content(
            H5::tag()
                ->addClass('bg-primary text-white p-3 rounded-top')
                ->content(
                    I::tag()->addClass('bi bi-receipt')
                            ->content(' ' . Html::encode($translator->translate('client')))
                )
        )
        ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'upload/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>
<?php
$columns = [
    new DataColumn(
        'id',
        header:  $translator->translate('id'),
        content: static fn (Upload $model) => Html::encode($model->getId())
    ),
    new DataColumn(
        'client_id',
        header:  $translator->translate('client'),
        content: static fn (Upload $model): string => Html::encode($model->getClient()?->getClient_name() ?? '')
    ),
    new DataColumn(
        'file_name_original',
        header:  $translator->translate('upload.filename.original'),
        content: static fn (Upload $model): string => Html::encode($model->getFile_name_original())
    ),
    new DataColumn(
        'file_name_new',
        header:  $translator->translate('upload.filename.new'),
        content: static fn (Upload $model): string => Html::encode($model->getFile_name_new())
    ),
    new DataColumn(
        'description',
        header:  $translator->translate('upload.filename.description'),
        content: static fn (Upload $model): string => Html::encode($model->getDescription())
    ),
    new DataColumn(
        header:  $translator->translate('view'),
        content: static function (Upload $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('upload/view', ['id' => $model->getId()]), []);
        }
    ),
    new DataColumn(
        header:  $translator->translate('edit'),
        content: static function (Upload $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-edit fa-margin']), $urlGenerator->generate('upload/edit', ['id' => $model->getId()]), []);
        }
    ),
    new DataColumn(
        header:  $translator->translate('delete'),
        content: static function (Upload $model) use ($translator, $urlGenerator): A {
            return Html::a(
                Html::tag(
                    'button',
                    Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                    [
                     'type' => 'submit',
                     'class' => 'dropdown-button',
                     'onclick' => "return confirm("."'".$translator->translate('delete.record.warning')."');"
                    ]
                ),
                $urlGenerator->generate('upload/delete', ['id' => $model->getId()]),
                []
            );
        }
    ),
]
?>
<?php
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('upload.plural'),
    ''
);
$toolbarString = Form::tag()->post($urlGenerator->generate('upload/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->id('w4-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('no.records'))
->tableAttributes(['class' => 'table table-striped text-center h-125','id' => 'table-upload'])
->toolbar($toolbarString);
?>