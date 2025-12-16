<?php
declare(strict_types=1);

use App\Invoice\Entity\ProductImage;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/**
 * Related logic: see ...src\Invoice\Product\ProductController function view_partial_product_image
 * Related logic: see ...resources\views\invoice\product\views\partial_product_image.php
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var bool $invEdit
 * @var bool $invView
 */
?>

<div>
    <?php
        $columns = [
            new DataColumn(
                'file_name_original',
                header: $translator->translate('name'),
                content: static fn (ProductImage $model): string => Html::encode($model->getFile_name_original()),
            ),
            new DataColumn(
                'uploaded_date',
                header: $translator->translate('date'),
                content: static fn (ProductImage $model): string => ($model->getUploaded_date())->format('Y-m-d'),
            ),
            new DataColumn(
                header: $translator->translate('download'),
                content: static function (ProductImage $model) use ($urlGenerator): A {
                    return Html::a(
                        Html::tag(
                            'button',
                            Html::tag('i', '', ['class' => 'fa fa-download fa-margin']),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                            ],
                        ),
                        // route action => product/download_image_file
                        // route name => /image
                        $urlGenerator->generate('product/download_image_file', ['product_image_id' => $model->getId(), '_language' => 'en']),
                        [],
                    );
                },
            ),
            new DataColumn(
                visible: $invEdit,
                header: $translator->translate('edit'),
                content: static function (ProductImage $model) use ($urlGenerator): A {
                    return Html::a(
                        Html::tag(
                            'button',
                            Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                            ],
                        ),
                        $urlGenerator->generate('productimage/edit', ['id' => $model->getId(), '_language' => 'en']),
                        [],
                    );
                },
            ),
            new DataColumn(
                visible: $invEdit,
                header: $translator->translate('delete'),
                content: static function (ProductImage $model) use ($translator, $urlGenerator): A {
                    return Html::a(
                        Html::tag(
                            'button',
                            Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                                'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
                            ],
                        ),
                        $urlGenerator->generate('productimage/delete', ['id' => $model->getId(), '_language' => 'en']),
                        [],
                    );
                },
            ),
        ]
?>
    <?php
    $grid_summary = $s->grid_summary(
        $paginator,
        $translator,
        (int) $s->getSetting('default_list_limit'),
        $translator->translate('productimage.list'),
        '',
    );
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-475', 'id' => 'table-product-image-list'])
->columns(...$columns)
->dataReader($paginator)
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.attachments'))
?>
</div>
