<?php

declare(strict_types=1);

use App\Invoice\Entity\PostalAddress;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

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

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate(($routeCurrent->getName() ?? 'postaladdress/index')))
    ->id('btn-reset')
    ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (PostalAddress $model) => $model->getId(),
    ),
    new DataColumn(
        'client_id',
        header: $translator->translate('client.name'),
        content: static function (PostalAddress $model) use ($cR): string {
            $clientName = ($cR->repoClientCount($model->getClientId()) > 0 ?
                Html::encode(
                    ($cR->repoClientquery(
                            $model->getClientId()))->getClientName()) : '');
            return $clientName;
        },
    ),
    new DataColumn(
        'client_id',
        header: $translator->translate('client.surname'),
        content: static function (PostalAddress $model) use ($cR): string {
            $clientId = $model->getClientId();
            if ($clientId) {
                $clientSurname = ($cR->repoClientCount($clientId) > 0 ?
                    Html::encode(($cR->repoClientquery($clientId))->getClientSurname())
                        : '');
                return $clientSurname;
            }
            return '';
        },
    ),
    new DataColumn(
        'client_id',
        header: $translator->translate('active'),
        content: static function (PostalAddress $model)
            use ($cR, $urlGenerator): Yiisoft\Html\Tag\A|string {
            $client = $cR->repoClientquery($model->getClientId());
            if (null !== $client->getPostaladdressId()
                    && $client->getPostaladdressId() > 0) {
                return 'Postal Address Used';
            } else {
                return Html::a('No Postal address', $urlGenerator->generate('client/edit',
                            ['id' => $model->getClientId(), 'origin' => 'inv']));
            }
        },
    ),
    new DataColumn(
        header: $translator->translate('view'),
        content: static function (PostalAddress $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']),
                $urlGenerator->generate('postaladdress/view',
                    ['id' => $model->getId()]), []);
        },
    ),
    new DataColumn(
        header: $translator->translate('edit'),
        content: static function (PostalAddress $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']),
                $urlGenerator->generate('postaladdress/edit',
                    ['id' => $model->getId()]), []);
        },
    ),
    new DataColumn(
        header: $translator->translate('delete'),
        content: static function (PostalAddress $model) use ($translator,
            $urlGenerator): A {
            return Html::a(
                Html::tag(
                    'button',
                    Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                    [
                        'type' => 'submit',
                        'class' => 'dropdown-button',
                        'onclick' => "return confirm("
                        . "'"
                        . $translator->translate('delete.record.warning')
                        . "');",
                    ],
                ),
                    $urlGenerator->generate('postaladdress/delete',
                        ['id' => $model->getId()]),
                [],
            );
        },
    ),
];

$gridSummary = $s->gridSummary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('postaladdresses'),
    '',
);

$toolbarString =  new Form()->post($urlGenerator->generate('postaladdress/index'))->csrf($csrf)->open()
    .  new Div()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    .  new Form()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-85','id' => 'table-postaladdress'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('client.postaladdress'))
->id('w3-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryTemplate($pageSizeLimiter::buttons($routeCurrent, $s, $translator, $urlFastRouteGenerator, 'postaladdress') . ' ' . $gridSummary)
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->toolbar($toolbarString);
