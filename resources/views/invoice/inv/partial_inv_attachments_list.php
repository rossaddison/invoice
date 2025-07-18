<?php

declare(strict_types=1);

use App\Invoice\Entity\Upload;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Column\DataColumn;

/**
 * @var App\Invoice\Inv\InvAttachmentsForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $invEdit
 */

?>

<div>
    <?php
        $columns = [
            new DataColumn(
                'file_name_original',
                header: $translator->translate('name'),
                content: static fn(Upload $model): string => ($model->getFile_name_original()),
            ),
            new DataColumn(
                'uploaded_date',
                header: $translator->translate('date'),
                content: static fn(Upload $model): string => ($model->getUploaded_date())->format('Y-m-d'),
            ),
            new DataColumn(
                header: $translator->translate('download'),
                content: static function (Upload $model) use ($urlGenerator): A {
                    return Html::a(
                        Html::tag(
                            'button',
                            Html::tag('i', '', ['class' => 'fa fa-download fa-margin']),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                            ],
                        ),
                        $urlGenerator->generate('inv/download_file', ['upload_id' => $model->getId(), '_language' => 'en']),
                        [],
                    );
                },
            ),
            new DataColumn(
                visible: $invEdit,
                header: $translator->translate('edit'),
                content: static function (Upload $model) use ($urlGenerator): A {
                    return Html::a(
                        Html::tag(
                            'button',
                            Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']),
                            [
                                'type' => 'submit',
                                'class' => 'dropdown-button',
                            ],
                        ),
                        $urlGenerator->generate('upload/edit', ['id' => $model->getId(), '_language' => 'en']),
                        [],
                    );
                },
            ),
            new DataColumn(
                visible: $invEdit,
                header: $translator->translate('delete'),
                content: static function (Upload $model) use ($translator, $urlGenerator): A {
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
                        $urlGenerator->generate('upload/delete', ['id' => $model->getId(), '_language' => 'en']),
                        [],
                    );
                },
            ),
        ];
?>
    <?php
    $grid_summary = $s->grid_summary(
        $paginator,
        $translator,
        (int) $s->getSetting('default_list_limit'),
        $translator->translate('attachment.list'),
        '',
    );
echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-inv-attachments-list'])
->columns(...$columns)
->dataReader($paginator)
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('no.attachments'))
?>
</div>
