<?php
declare(strict_types=1);

use App\Invoice\Entity\ProductImage;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Column\DataColumn;

/**
 * @see ...src\Invoice\Product\ProductController function view_partial_product_image
 * @see ...resources\views\invoice\product\views\partial_product_image.php
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
                header:  $translator->translate('i.name'),
                content: static fn (ProductImage $model): string => ($model->getFile_name_original())
            ),
            new DataColumn(
                'uploaded_date',
                header:  $translator->translate('i.date'),
                content: static fn (ProductImage $model): string => ($model->getUploaded_date())->format('Y-m-d')
            ),
            new DataColumn(
                header:  $translator->translate('i.download'),
                content: static function (ProductImage $model) use ($urlGenerator): string {
                    return Html::a(
                        Html::tag(
                            'button',
                            Html::tag('i', '', ['class' => 'fa fa-download fa-margin']),
                            [
                                  'type' => 'submit',
                                  'class' => 'dropdown-button'
                              ]
                        ),
                        // route action => product/download_image_file
                        // route name => /image
                        $urlGenerator->generate('product/download_image_file', ['product_image_id' => $model->getId(), '_language' => 'en']),
                        []
                    )->render();
                }
            ),
            new DataColumn(
                visible: $invEdit,
                header:  $translator->translate('i.edit'),
                content: static function (ProductImage $model) use ($urlGenerator): string {
                    return Html::a(
                        Html::tag(
                            'button',
                            Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']),
                            [
                                        'type' => 'submit',
                                        'class' => 'dropdown-button'
                                    ]
                        ),
                        $urlGenerator->generate('productimage/edit', ['id' => $model->getId(), '_language' => 'en']),
                        []
                    )->render();
                }
            ),
            new DataColumn(
                visible: $invEdit,
                header:  $translator->translate('i.delete'),
                content: static function (ProductImage $model) use ($translator, $urlGenerator): string {
                    return Html::a(
                        Html::tag(
                            'button',
                            Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                            [
                                        'type' => 'submit',
                                        'class' => 'dropdown-button',
                                        'onclick' => "return confirm(" . "'" . $translator->translate('i.delete_record_warning') . "');"
                                    ]
                        ),
                        $urlGenerator->generate('productimage/delete', ['id' => $model->getId(), '_language' => 'en']),
                        []
                    )->render();
                }
            ),
        ]
?>
    <?php
    $grid_summary = $s->grid_summary(
        $paginator,
        $translator,
        (int)$s->getSetting('default_list_limit'),
        $translator->translate('invoice.productimage.list'),
        ''
    );
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-475', 'id' => 'table-product-image-list'])
->columns(...$columns)
->dataReader($paginator)
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('invoice.invoice.no.attachments'))
?>
</div>
