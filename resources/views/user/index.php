<?php

declare(strict_types=1);

use App\Invoice\Setting\SettingRepository;
use App\User\Widget\UsersListWidget;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;

/**
 * @var OffsetPaginator $paginator
 * @var SettingRepository $s
 * @var TranslatorInterface $translator
 * @var UrlGenerator $urlGenerator
 * @var WebView $this
 */

$this->setTitle($translator->translate('menu.users'));
$btnLink = 'btn btn-link';
?>
<div class="text-end mb-2">
    <?= Html::a('API v1 Info', $urlGenerator->generate('api/info/v1'),
            ['class' => $btnLink]) ?>
    <?= Html::a('API v2 Info', $urlGenerator->generate('api/info/v2'),
            ['class' => $btnLink]) ?>
    <?= Html::a('API Users List Data', $urlGenerator->generate('api/user/index'),
            ['class' => $btnLink]) ?>
</div>
<?= UsersListWidget::widget()->withPaginator($paginator)->withSR($s) ?>
