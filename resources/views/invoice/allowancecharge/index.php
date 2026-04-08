<?php

declare(strict_types=1);

use App\Invoice\Entity\AllowanceCharge;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var bool $canEdit
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 */

$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'allowancecharge/index'))
    ->id('btn-reset')
    ->render();

?>
<?= Html::openTag('div');?>
    <?= Html::openTag('div', ['class' => 'btn-group']);?>
    <?php
        if ($canEdit) {
            echo (new A())
                ->addClass('btn btn-outline-success btn-sm mb-3 me-1')
                ->encode(false)
                ->content(Html::tag('i', '', ['class' => 'bi bi-plus-circle me-1'])
                    . $translator->translate('allowance.or.charge.allowance'))
                ->href($urlGenerator->generate('allowancecharge/addAllowance'))
                ->render();
            echo (new A())
                ->addClass('btn btn-outline-primary btn-sm mb-3')
                ->encode(false)
                ->content(Html::tag('i', '', ['class' => 'bi bi-plus-circle me-1'])
                    . $translator->translate('allowance.or.charge.charge'))
                ->href($urlGenerator->generate('allowancecharge/addCharge'))
                ->render();
        } ?>
    <?= Html::closeTag('div');?>
    <?= Html::Tag('br'); ?>
    <?= Html::Tag('br'); ?>
<?= Html::closeTag('div');?>
<?= Html::openTag('div');?>
    <?= Html::Tag('br'); ?>
<?= Html::closeTag('div');?>

<?php
$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (AllowanceCharge $model) => $model->getId(),
    ),
    new DataColumn(
        property: 'level',
        header: $translator->translate('allowance.or.charge.level'),
        content: static function (AllowanceCharge $model): string {
            return ($model->getLevel() == 0) ? '⬅ Overall' : 'Invoice Line ➡';
        },
    ),
    new DataColumn(
        'reason_code',
        header: $translator->translate('allowance.or.charge.reason.code'),
        content: static fn (AllowanceCharge $model) => $model->getReasonCode(),
    ),
    new DataColumn(
        'reason',
        header: $translator->translate('allowance.or.charge.reason'),
        content: static fn (AllowanceCharge $model) => $model->getReason(),
    ),
    new DataColumn(
        'base_amount',
        header: $translator->translate('allowance.or.charge.base.amount'),
        content: static fn (AllowanceCharge $model) => $model->getBaseAmount(),
    ),
    new DataColumn(
        'multiplier_factor_numeric',
        header: $translator->translate('allowance.or.charge.multiplier.factor.numeric'),
        content: static function (AllowanceCharge $model): string {
            return ($model->getMultiplierFactorNumeric() == 0
                    || $model->getMultiplierFactorNumeric() == 1) ?
        '0 or 1 => Fixed Amount' : (string) $model->getMultiplierFactorNumeric()
                . '>1 => Variable Amount';
        },
    ),
    new DataColumn(
        'amount',
        header: $translator->translate('allowance.or.charge.amount'),
        content: static fn (AllowanceCharge $model) => $model->getAmount(),
    ),
    new DataColumn(
        header: $translator->translate('view'),
        content: static function (AllowanceCharge $model) use ($urlGenerator): A {
            return Html::a(
                Html::tag('i', '', ['class' => 'bi bi-eye']),
                $urlGenerator->generate('allowancecharge/view', ['id' => $model->getId()]),
                ['class' => 'btn btn-outline-info btn-sm'],
            );
        },
        encodeContent: false,
    ),
    new DataColumn(
        'identifier',
        header: $translator->translate('allowance.or.charge.allowance'),
        content: static function (AllowanceCharge $model) use ($urlGenerator): A {
            return !$model->getIdentifier()
                  ? Html::a(
                      Html::tag('i', '', ['class' => 'bi bi-pencil-square']),
                      $urlGenerator->generate(
                          'allowancecharge/editAllowance',
                          ['id' => $model->getId()],
                      ),
                      ['class' => 'btn btn-outline-warning btn-sm'],
                  ) : Html::a();
        },
        encodeContent: false,
    ),
    new DataColumn(
        'identifier',
        header: $translator->translate('allowance.or.charge.charge'),
        content: static function (AllowanceCharge $model) use ($urlGenerator): A {
            return $model->getIdentifier()
                ? Html::a(
                    Html::tag('i', '', ['class' => 'bi bi-pencil-square']),
                    $urlGenerator->generate(
                        'allowancecharge/editCharge',
                        ['id' => $model->getId()],
                    ),
                    ['class' => 'btn btn-outline-warning btn-sm'],
                ) : Html::a();
        },
        encodeContent: false,
    ),
    new DataColumn(
        header: $translator->translate('delete'),
        content: static function (AllowanceCharge $model) use ($translator, $urlGenerator): A {
            return Html::a(
                Html::tag(
                    'button',
                    Html::tag('i', '', ['class' => 'bi bi-trash']),
                    [
                        'type' => 'submit',
                        'class' => 'btn btn-outline-danger btn-sm',
                        'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
                    ],
                ),
                $urlGenerator->generate('allowancecharge/delete', ['id' => $model->getId()]),
                [],
            );
        },
        encodeContent: false,
    ),
];

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$gridSummary = $s->gridSummary(
    $paginator,
    $translator,
    $defaultPageSizeOffsetPaginator,
    $translator->translate('allowance.or.charge'),
    '',
);

$toolbarString
    =  new Form()
    ->post($urlGenerator->generate('allowancecharge/index'))
    ->csrf($csrf)
    ->open()
    .  new Div()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    .  new Form()->close();

echo GridView::widget()
    ->columns(...$columns)
    ->dataReader($paginator)
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75',
        'id' => 'table-allowancecharge'])
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->header($translator->translate('allowance.or.charge'))
    ->id('w3-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($gridSummary)
    ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
    ->noResultsText($translator->translate('no.records'))
    ->toolbar($toolbarString);
