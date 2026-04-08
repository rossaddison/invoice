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
?>
<?= (string) DetailView::widget()
    ->labelPrepend(Html::encode($this->getTitle()) . ' ')
    ->data($item)
    ->containerAttributes(['class' => 'container-fluid'])
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
