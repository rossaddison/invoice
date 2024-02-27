<?php
declare(strict_types=1);

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Column\DataColumn;

/**
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var WebView $this
 */
?>

<div>
    <?php
        $columns = [
            new DataColumn(
                'file_name_original',
                header:  $translator->translate('i.name'),
                content: static fn($model): string => ($model->getFile_name_original())),
            new DataColumn(
                'uploaded_date',
                header:  $translator->translate('i.date'),
                content: static fn($model): string => ($model->getUploaded_date())->format($datehelper->style())
            ),
            new DataColumn(
                header:  $translator->translate('i.download'),
                content: static function ($model) use ($urlGenerator): string {
                    return Html::a(Html::tag('button',
                                  Html::tag('i', '', ['class' => 'fa fa-download fa-margin']),
                                    [
                                        'type' => 'submit',
                                        'class' => 'dropdown-button'
                                    ]
                    ),
                    $urlGenerator->generate('inv/download_file', ['upload_id' => $model->getId(), '_language' => 'en']), []
                )->render();
            }),
            new DataColumn(
                visible: $invEdit,
                header:  $translator->translate('i.edit'),
                content: static function ($model) use ($urlGenerator): string {
                    return Html::a(Html::tag('button',
                                    Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']),
                                    [
                                        'type' => 'submit',
                                        'class' => 'dropdown-button'
                                    ]
                            ),
                            $urlGenerator->generate('upload/edit', ['id' => $model->getId(), '_language' => 'en']), []
                    )->render();
                }
            ),
            new DataColumn(
                visible: $invEdit,
                header:  $translator->translate('i.delete'),
                content: static function ($model) use ($translator, $urlGenerator): string {
                    return Html::a(Html::tag('button',
                                    Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                                    [
                                        'type' => 'submit',
                                        'class' => 'dropdown-button',
                                        'onclick' => "return confirm(" . "'" . $translator->translate('i.delete_record_warning') . "');"
                                    ]
                            ),
                            $urlGenerator->generate('upload/delete', ['id' => $model->getId(), '_language' => 'en']), []
                    )->render();
                }
            ),
        ];
    ?>
    <?=
            GridView::widget()
            ->columns(...$columns)
            ->dataReader($dataReader)
            ->rowAttributes(['class' => 'align-middle'])
            ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
            ->summaryTemplate($grid_summary)
            ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
            ->emptyText((string) $translator->translate('invoice.invoice.no.attachments'))
            ->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-inv-attachments-list'])
    ?>
</div>
