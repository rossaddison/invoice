<?php

declare(strict_types=1);

use Yiisoft\Strings\Inflector;

/**
 * @var App\Invoice\Entity\Gentor $generator
 */

echo "<?php\n";
$random = (new DateTimeImmutable())->getTimestamp();
?>

declare(strict_types=1);

use App\Invoice\Entity\<?= $generator->getCamelcase_capital_name(); ?>;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use  Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use  Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use  Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;

/**
 * @var App\Invoice\Entity\<?= $generator->getCamelcase_capital_name(); ?> $<?= $generator->getSmall_singular_name() . "\n"; ?>
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator 
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf 
 */
 
 echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

?>
<?php
        $inf = new Inflector();
echo '<h1>' . $inf->toSentence($generator->getPre_entity_table(), false) . '</h1>' . "\n";
echo "<?= Html::a(Html::tag('" . "i','',['class'=>'fa fa-plus btn btn-primary fa-margin'])," . '$urlGenerator->generate(' . "'" . $generator->getSmall_singular_name() . "/add'),[]); ?>";
?>

<?php   echo "<?php\n"; ?>
    $header = Div::tag()
      ->addClass('row')
      ->content(
        H5::tag()
        ->addClass('bg-primary text-white p-3 rounded-top')
        ->content(
          I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('put.your.translation.here'))
        )
      )
      ->render();

    $toolbarReset = A::tag()
      ->addAttributes(['type' => 'reset'])
      ->addClass('btn btn-danger me-1 ajax-loader')
      ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
      ->href($urlGenerator->generate($currentRoute->getName() ?? '<?= $generator->getSmall_singular_name(); ?>/index'))
      ->id('btn-reset')
      ->render();

    $toolbar = Div::tag();
    
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('id'),
            content: static fn(<?= $generator->getCamelcase_capital_name(); ?> $model) => $model->getId()
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: function (<?= $generator->getCamelcase_capital_name(); ?> $model) use ($urlGenerator): string {
                    /** @psalm-suppress InvalidArgument */
                    return $urlGenerator->generate('<?= $generator->getSmall_singular_name(); ?>/view', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('view'),
                ]
            ),
            new ActionButton(
                content: '✎',
                url: function (<?= $generator->getCamelcase_capital_name(); ?> $model) use ($urlGenerator): string {
                    /** @psalm-suppress InvalidArgument */
                    return $urlGenerator->generate('<?= $generator->getSmall_singular_name(); ?>/edit', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('edit'),
                ]
            ),
            new ActionButton(
                content: '❌',
                url: function (<?= $generator->getCamelcase_capital_name(); ?> $model) use ($urlGenerator): string {
                    /** @psalm-suppress InvalidArgument */
                    return $urlGenerator->generate('<?= $generator->getSmall_singular_name(); ?>/delete', ['id' => $model->getId()]);
                },
                attributes: [
                    'title' => $translator->translate('delete'),
                    'onclick' => "return confirm("."'".$translator->translate('delete.record.warning')."');"
                ]
            ),
        ]),
    ];
    $toolbarString = Form::tag()->post($urlGenerator->generate('<?= $generator->getSmall_singular_name(); ?>/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
    $grid_summary = $s->grid_summary($paginator, $translator, (int) $s->getSetting('default_list_limit'), $translator->translate('plural'), '');    
    echo GridView::widget()
      ->bodyRowAttributes(['class' => 'align-middle'])
      ->tableAttributes(['class' => 'table table-striped text-center h-<?= $random; ?>', 'id' => 'table-<?= $generator->getSmall_singular_name(); ?>'])
      ->columns(...$columns)
      ->dataReader($paginator)
      ->headerRowAttributes(['class' => 'card-header bg-info text-black'])      
      ->header($header)
      ->id('w<?= $random; ?>-grid')
      ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
      ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
      ->summaryTemplate($grid_summary)
      ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
      ->noResultsText($translator->translate('no.records'))
      ->toolbar($toolbarString);
?>      