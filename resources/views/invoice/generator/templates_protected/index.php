<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Entity\Gentor $generator
 */

echo "<?php\n";
$random =  new DateTimeImmutable()->getTimestamp();
?>

declare(strict_types=1);

use App\Invoice\Entity\<?= $generator->getCamelcaseCapitalName(); ?>;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Entity\<?= $generator->getCamelcaseCapitalName(); ?> $<?= $generator->getSmallSingularName() . "\n"; ?>
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator 
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf 
 * @psalm-var positive-int $page 
 */
 
echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate(
       $currentRoute->getName() ?? '<?= $generator->getSmallSingularName(); ?>/index'))
       ->id('btn-reset')
       ->render();

echo new Div();
    
$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn(<?= $generator->getCamelcaseCapitalName(); ?> $model) => $model->getId()
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: function (<?= $generator->getCamelcaseCapitalName(); ?> $model)
                use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate(
                    '<?= $generator->getSmallSingularName(); ?>/view',
                        ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ]
        ),
        new ActionButton(
            content: '✎',
            url: function (<?= $generator->getCamelcaseCapitalName(); ?> $model)
                use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate(
                    '<?= $generator->getSmallSingularName(); ?>/edit',
                        ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ]
        ),
        new ActionButton(
            content: '❌',
            url: function (<?= $generator->getCamelcaseCapitalName(); ?> $model)
                use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate(
                    '<?= $generator->getSmallSingularName(); ?>/delete',
                        ['id' => $model->getId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm("
                    . "'"
                    . $translator->translate('delete.record.warning')
                    . "');"
            ]
        ),
    ]),
];
    
$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));
$sort = Sort::only(['id'])
        ->withOrderString($sortString);
        
$toolbarString =
    new Form()
        ->post($urlGenerator->generate(
        '<?= $generator->getSmallSingularName(); ?>/index'))
        ->csrf($csrf)
        ->open() .
    new A()
        ->href($urlGenerator->generate(
            '<?= $generator->getSmallSingularName(); ?>/add')
        )
        ->addStyle('text-decoration:none')
        ->content('➕')
        ->render() .           
    new Div()
        ->addClass('float-end m-3')
        ->content($toolbarReset)
        ->encode(false)
        ->render() .
    new Form()->close();
         
$sortedAndPagedPaginator =
    (new OffsetPaginator($<?= $generator->getSmallSingularName(); ?>s))
    ->withPageSize($defaultPageSizeOffsetPaginator > 0 ?
            $defaultPageSizeOffsetPaginator : 1)
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));
         
$gridSummary = $s->gridSummary(
    $sortedAndPagedPaginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('<?= $generator->getSmallPluralName(); ?>'),
    '',
);

echo GridView::widget()
  ->bodyRowAttributes(['class' => 'align-middle'])
  ->tableAttributes(
        ['class' => 'table table-striped text-center h-<?= $random; ?>',
         'id' => 'table-<?= $generator->getSmallSingularName(); ?>'])
  ->columns(...$columns)
  ->dataReader($sortedAndPagedPaginator)
  ->urlCreator($urlCreator)
  ->headerRowAttributes(['class' => 'card-header bg-info text-black'])      
  ->header($header)
  ->multiSort(true)
  ->id('w<?= $random; ?>-grid')
  ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
  ->summaryTemplate('<div class="d-flex align-items-center">'
    . $pageSizeLimiter::buttons(
        $currentRoute, $s, $translator, $urlGenerator,
        '<?= $generator->getSmallSingularName(); ?>')
    . ' ' . $gridSummary . '</div>')
  ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
  ->noResultsText($translator->translate('no.records'))
  ->toolbar($toolbarString);
?>
