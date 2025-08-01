<?php

declare(strict_types=1);

use App\User\User;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Select;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var string $csrf
 */

$this->setTitle($translator->translate('menu.users'));

// Define header gridview
$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-people-fill')->content(' ' . $translator->translate('gridview.title')),
            ),
    )
    ->render();

$toolbarApplyChange = Button::tag()
    ->addClass('btn btn-success me-1')
    ->content(I::tag()->addClass('bi bi-check-all'))
    ->id('btn-apply-changes')
    ->type('submit')
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'user/index'))
    ->id('btn-reset')
    ->render();

$toolbarSelect = Select::tag()
    ->addClass('form-select ms-3')
    ->id('pageSize')
    ->name('pageSize')
    ->optionsData(
        [
            '1' => '1',
            '5' => '5',
            '10' => '10',
            '15' => '15',
            '20' => '20',
            '25' => '25',
        ],
    )
    ->value($paginator->getPageSize())
    ->render();

$toolbar = Div::tag();
?>

<div>
    <div class="text-end">
        <?= Html::a('API v1 Info', $urlGenerator->generate('api/info/v1'), ['class' => 'btn btn-link']) ?>
        <?= Html::a('API v2 Info', $urlGenerator->generate('api/info/v2'), ['class' => 'btn btn-link']) ?>
        <?= Html::a('API Users List Data', $urlGenerator->generate('api/user/index'), ['class' => 'btn btn-link'])?>
    </div>
</div>
<?php
    $columns = [
        new DataColumn(
            'id',
            content: static function (User $data): string {
                return (string) $data->getId();
            },
        ),
        new DataColumn(
            'login',
            content: static fn(User $data) => $data->getLogin(),
            header: $translator->translate('gridview.login'),
        ),
        new DataColumn(
            'create_at',
            content: static fn(User $data) => $data->getCreatedAt()->format('r'),
            header: $translator->translate('gridview.create.at'),
        ),
        new DataColumn(
            'api',
            content: static function (User $data) use ($urlGenerator): A {
                return Html::a(
                    'API User Data',
                    $urlGenerator->generate(
                        'api/user/profile',
                        ['login' => $data->getLogin()],
                    ),
                    ['target' => '_blank'],
                );
            },
            header: $translator->translate('gridview.api'),
        ),
        new DataColumn(
            'profile',
            content: static function (User $data) use ($urlGenerator): A {
                return Html::a(
                    Html::tag('i', '', [
                        'class' => 'bi bi-person-fill ms-1',
                        'style' => 'font-size: 1.5em;',
                    ]),
                    $urlGenerator->generate('user/profile', ['login' => $data->getLogin()]),
                    ['class' => 'btn btn-link'],
                );
            },
            header: $translator->translate('gridview.profile'),
        ),
    ];
?>
<?php
$toolbarString =
    Form::tag()->post($urlGenerator->generate('user/index'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-start m-3')->content($toolbarSelect)->encode(false)->render() .
    Div::tag()->addClass('float-end m-3')->content($toolbarApplyChange . $toolbarReset)->encode(false)->render() .
    Form::tag()->close();
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-hover'])
->dataReader($paginator)
->columns(...$columns)
->header($header)
->id('w1-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'summary text-end mb-5'])
->toolbar($toolbarString);
?>