<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\H2;
use Yiisoft\Yii\DataView\DetailView\DetailView;
use Yiisoft\Yii\DataView\DetailView\DataField;
use Yiisoft\Yii\DataView\ValuePresenter\SimpleValuePresenter;

/**
 * Related logic: see https://github.com/yiisoft/yii-dataview/blob/master/tests/DetailView/Bootstrap5Test.php
 * @var App\User\User $item
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle('Profile');

$title = Html::encode($this->getTitle());
?>

<?= (string) DetailView::widget()
    ->data($item)
    ->containerAttributes(['class' => 'container'])
    ->listAttributes(['class' => 'row flex-column justify-content-center align-items-center'])
    ->fieldAttributes(['class' => 'col-xl-5'])
    ->fieldTag(H2::tag()->class('text-center')->content("<strong>$title</strong>")->encode(false)->render())
    ->fields(
        new DataField(
            property: 'id',
            label: 'ID',
            value: $item->getId(),
        ),
        new DataField(
            property: 'login',
            label: $translator->translate('gridview.login'),
            value: $item->getLogin(),
        ),
        new DataField(
            property: 'create_at',
            label: $translator->translate('gridview.create.at'),
            value: $item->getCreatedAt()->format('H:i:s d.m.Y'),
        ),
    )
    ->labelAttributes(['class' => 'fw-bold'])
    ->valueAttributes(['class' => 'alert alert-info'])
    ->valuePresenter(new SimpleValuePresenter());
