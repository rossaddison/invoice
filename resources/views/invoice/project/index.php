<?php
declare(strict_types=1);

use App\Invoice\Entity\Project;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var Project                                $project
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var App\Widget\GridComponents              $gridComponents
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $projects
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var string                                 $alert
 * @var string                                 $csrf
 * @var CurrentRoute                           $currentRoute
 * @var OffsetPaginator                        $paginator
 *
 * @psalm-var positive-int $page
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
                    ->content(' '.Html::encode($translator->translate('project'))),
            ),
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'project/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<div>
    <h5><?php echo $translator->translate('project'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?php echo $urlGenerator->generate('project/add'); ?>">
            <i class="fa fa-plus"></i> <?php echo Html::encode($translator->translate('new')); ?>
        </a>
    </div>
</div>
<br>
<div>

</div>
<div>
<?php
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('id'),
            content: static fn (Project $model) => Html::encode($model->getId()),
        ),
        new DataColumn(
            'client_id',
            header: $translator->translate('client'),
            content: static function (Project $model): string {
                $clientName    = $model->getClient()?->getClient_name()       ?? '';
                $clientSurname = $model->getClient()?->getClient_surname() ?? '';
                if ((strlen($clientName) > 0) && (strlen($clientSurname) > 0)) {
                    return Html::encode($clientName.' '.$clientSurname);
                } else {
                    return '#';
                }
            },
        ),
        new DataColumn(
            'name',
            header: $translator->translate('project.name'),
            content: static fn (Project $model): string => Html::encode(ucfirst($model->getName() ?? '')),
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function (Project $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('project/view', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title'          => $translator->translate('view'),
                ],
            ),
            new ActionButton(
                content: '✎',
                url: static function (Project $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('project/edit', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title'          => $translator->translate('edit'),
                ],
            ),
            new ActionButton(
                content: '❌',
                url: static function (Project $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('project/delete', ['id' => $model->getId()]);
                },
                attributes: [
                    'title'   => $translator->translate('delete'),
                    'onclick' => 'return confirm('."'".$translator->translate('delete.record.warning')."');",
                ],
            ),
        ]),
    ];
?>
<?php
$paginator = (new OffsetPaginator($projects))
    ->withPageSize($s->positiveListLimit())
    ->withCurrentPage($page)
    ->withToken(PageToken::next((string) $page));

$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('projects'),
    '',
);
$toolbarString = Form::tag()->post($urlGenerator->generate('project/index'))->csrf($csrf)->open().
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render().
        Form::tag()->close();
echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-project'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->urlCreator(new UrlCreator($urlGenerator))
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->header($header)
    ->id('w84-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('no.records'))
    ->toolbar($toolbarString);
?>
</div>


