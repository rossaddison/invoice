<?php

declare(strict_types=1);

use App\Invoice\Entity\PostalAddress;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Entity\PostalAddress $postaladdress
 * @var App\Invoice\Client\ClientRepository $cR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\CurrentRoute $routeCurrent
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlFastRouteGenerator
 * @var string $alert
 * @var string $csrf
 * @var bool $canEdit
 * @var string $id
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
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('client.postaladdress'))
            )
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate(($routeCurrent->getName() ?? 'postaladdress/index')))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>
<h1><?= $translator->translate('client.postaladdress'); ?></h1>
<?php
    $columns = [
        new DataColumn(
            'id',
            header:  $translator->translate('id'),
            content: static fn (PostalAddress $model) => $model->getId()
        ),
        new DataColumn(
            'client_id',
            header:  $translator->translate('client.name'),
            content: static function (PostalAddress $model) use ($cR): string {
                $clientName = ($cR->repoClientCount($model->getClient_id()) > 0 ? Html::encode(($cR->repoClientquery($model->getClient_id()))->getClient_name()) : '');
                return $clientName;
            }
        ),
        new DataColumn(
            'client_id',
            header:  $translator->translate('client.surname'),
            content: static function (PostalAddress $model) use ($cR): string {
                $clientId = $model->getClient_id();
                if ($clientId) {
                    $clientSurname = ($cR->repoClientCount($clientId) > 0 ? Html::encode(($cR->repoClientquery($clientId))->getClient_surname()) : '');
                    return $clientSurname;
                }
                return '';
            }
        ),
        new DataColumn(
            'client_id',
            header:  $translator->translate('active'),
            content: static function (PostalAddress $model) use ($cR, $urlGenerator): Yiisoft\Html\Tag\A|string {
                $client = $cR->repoClientquery($model->getClient_id());
                if (null !== $client->getPostaladdress_id() && $client->getPostaladdress_id() > 0) {
                    return 'used';
                } else {
                    return Html::a('Not used - assign postal address to client', $urlGenerator->generate('client/edit', ['id' => $model->getClient_id(), 'origin' => 'inv']));
                }
                return 'no client assigned to postal address';
            }
        ),
        new DataColumn(
            header:  $translator->translate('view'),
            content: static function (PostalAddress $model) use ($urlGenerator): A {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('postaladdress/view', ['id' => $model->getId()]), []);
            }
        ),
        new DataColumn(
            header:  $translator->translate('edit'),
            content: static function (PostalAddress $model) use ($urlGenerator): A {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('postaladdress/edit', ['id' => $model->getId()]), []);
            }
        ),
        new DataColumn(
            header:  $translator->translate('delete'),
            content: static function (PostalAddress $model) use ($translator, $urlGenerator): A {
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
                    $urlGenerator->generate('postaladdress/delete', ['id' => $model->getId()]),
                    []
                );
            }
        ),
    ];
?>
<?php
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int)$s->getSetting('default_list_limit'),
    $translator->translate('postaladdresses'),
    ''
);
$toolbarString = Form::tag()->post($urlGenerator->generate('postaladdress/index'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-85','id' => 'table-postaladdress'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($header)
->id('w3-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryTemplate($pageSizeLimiter::buttons($routeCurrent, $s, $translator, $urlFastRouteGenerator, 'postaladdress').' '.$grid_summary)
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->toolbar($toolbarString);
