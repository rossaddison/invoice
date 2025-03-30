<?php

declare(strict_types=1);

use App\Invoice\Entity\Merchant;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 */

echo $alert;

?>
<?php
$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'payment/online_log'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<?php

$columns = [
    new DataColumn(
        'id',
        header:  $translator->translate('i.id'),
        content: static fn (Merchant $model) => $model->getId()
    ),
    new DataColumn(
        field: 'inv_id',
        property: 'filterInvNumber',
        header:  $translator->translate('i.invoice'),
        content: static function (Merchant $model) use ($urlGenerator): string|A {
            $return = '';
            if (null !== $model->getInv()) {
                $return = Html::a($model->getInv()?->getNumber() ?? '#', $urlGenerator->generate('inv/view', ['id' => $model->getInv_id()]), ['style' => 'text-decoration:none']);
            }
            return $return;
        },
        filter: true
    ),
    new DataColumn(
        'successful',
        header:  $translator->translate('g.transaction_successful'),
        content: static function (Merchant $model) use ($translator): Yiisoft\Html\Tag\CustomTag {
            return $model->getSuccessful() ? Html::tag('Label', $translator->translate('i.yes'), ['class' => 'btn btn-success']) : Html::tag('Label', $translator->translate('i.no'), ['class' => 'btn btn-danger']);
        }
    ),
    new DataColumn(
        'date',
        header:  $translator->translate('i.payment_date'),
        content: static fn (Merchant $model): string|DateTimeImmutable => !is_string($date = $model->getDate())
                                                                          ? $date->format('Y-m-d') : ''
    ),
    new DataColumn(
        field: 'driver',
        property: 'filterPaymentProvider',
        header:  $translator->translate('g.payment_provider'),
        content: static fn (Merchant $model): string => Html::encode($model->getDriver()),
        filter: true
    ),
    new DataColumn(
        'response',
        header:  $translator->translate('g.provider_response'),
        content: static fn (Merchant $model): string => Html::encode($model->getResponse())
    ),
    new DataColumn(
        'reference',
        header:  $translator->translate('g.transaction_reference'),
        content: static fn (Merchant $model): string => Html::encode($model->getReference())
    ),
];
?>
<?php
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('i.payment_logs'),
    ''
);
$toolbarString = Form::tag()->post($urlGenerator->generate('payment/index'))->csrf($csrf)->open() .
                 Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
                 Form::tag()->close();
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-payment-online-log'])
->columns(...$columns)
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($gridComponents->header(' ' . $translator->translate('i.payment_logs')))
->id('w79-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
/**
 * @see config/common/params.php `yiisoft/view` => ['parameters' => ['pageSizeLimiter' ... No need to be in payment/index
 */
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'payment').' '.$grid_summary)
->toolbar($toolbarString);
