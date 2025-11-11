<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;

/**
 * @var string $identityProvider e.g facebook
 * @var TranslatorInterface   $translator
 * @var UrlGeneratorInterface $urlGenerator
 * @var CurrentRoute          $currentRoute
 * @var WebView               $this
 */
$this->setTitle($translator->translate('layout.page.user-cancelled-oauth2'));
?>

<div class="card shadow p-5 my-5 mx-5 bg-white rounded">
    <div class="card-body text-center ">
        <label class="card-title display-20 fw-bold"><?= $translator->translate('layout.page.user-cancelled-oauth2'); ?></label>
        <p class="card-text">
            <?=
                $currentPath = $currentRoute->getUri()?->getPath();
null !== $currentPath
    ? $translator->translate('layout.page.user-cancelled-oauth2', [
        'url' => Html::span(
            Html::encode($currentPath),
            ['class' => 'text-muted'],
        ),
    ]) : '';
?>
        </p>
        <p>
            <?= Html::a(
                $translator->translate('layout.go.home'),
                $urlGenerator->generate('site/index'),
                ['class' => 'btn btn-outline-primary mt-5'],
            );
?>
        </p>
    </div>
</div>
