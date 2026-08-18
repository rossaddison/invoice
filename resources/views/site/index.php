<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Bootstrap5\Carousel;
use Yiisoft\Bootstrap5\CarouselItem;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */
$tooltipTitle = $translator->translate('home.caption.slides.location.debug.mode');
$w = 150;
$h = 50;
$divHeight = (string) 250;
?>
<?php if ($s->getSetting('no_front_site_slider_page') == '0') { ?>

<?= Html::openTag('div', ['class' => 'container py-5']); ?>
    <?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
        <?= Html::openTag('div', ['class' => 'col-lg-10']); ?>
            <?= Html::openTag('div', ['class' => 'd-flex align-items-center gap-3 mb-2']); ?>
                <?= new Img()->src('/img/yii3_sign.svg')->alt('Yii 3 sign')->addAttributes(['style' => 'width: 48px; height: auto'])->render(); ?>
                <?= Html::openTag('h1', ['class' => 'display-6 fw-bold mb-0']); ?>
                    Yii3-i — Open Source Invoicing &amp; E-Invoicing System
                <?= Html::closeTag('h1'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('p', ['class' => 'text-secondary small mb-3']); ?>
                Built on the
                <?= new A()->content('Yii 3')->href('https://www.yiiframework.com/')->addAttributes(['target' => '_blank', 'rel' => 'noopener'])->render(); ?>
                PHP framework. The Yii 3 sign shown above is
                <?= new A()->content('Yii Software\'s')->href('https://www.yiiframework.com/logo')->addAttributes(['target' => '_blank', 'rel' => 'noopener'])->render(); ?>
                own official mark, used unmodified under their
                <?= new A()->content('CC BY-ND 3.0 license')->href('https://creativecommons.org/licenses/by-nd/3.0/')->addAttributes(['target' => '_blank', 'rel' => 'noopener'])->render(); ?>.
            <?= Html::closeTag('p'); ?>
            <?= Html::openTag('p', ['class' => 'fs-5 mb-3']); ?>
                A self-hosted, open-source invoicing platform built in PHP on the
                Yii3 framework. It produces Peppol/UBL 2.4-compliant electronic
                invoices (including AS4 transmission), accepts online payment
                through 17 integrated payment gateways spanning Europe, North
                and South America, Asia, Africa and Oceania, and includes a
                HomeCare module for automating recurring service invoicing —
                cleaning-run scheduling, worker allocation, and an offline-capable
                PWA for field staff. Scheduled console commands handle automated
                backups and recurring billing.
            <?= Html::closeTag('p'); ?>
            <?= Html::openTag('p', ['class' => 'mb-1']); ?>
                <?= new A()
                    ->content('⭐ View the source on GitHub — github.com/rossaddison/invoice')
                    ->href('/go/github')
                    ->addAttributes(['class' => 'btn btn-primary', 'target' => '_blank', 'rel' => 'noopener nofollow'])
                    ->render(); ?>
                <?php if ($s->getSetting('no_front_gateway_status_page') == '0') { ?>
                    <?= new A()
                        ->content('Payment gateway coverage ➡️')
                        ->href('/gateway-status')
                        ->addAttributes(['class' => 'btn btn-outline-secondary ms-2'])
                        ->render(); ?>
                <?php } ?>
            <?= Html::closeTag('p'); ?>
            <?= Html::openTag('p', ['class' => 'text-secondary small mb-2']); ?>
                Clicking through to GitHub records which country it came from
                (nothing more identifying than that) — it directly helps decide
                which payment gateways get prioritized next, since regions we
                see real interest from are the ones worth building for.
            <?= Html::closeTag('p'); ?>
            <?= Html::openTag('p', ['class' => 'text-secondary small mb-0']); ?>
                Open source under the BSD-3-Clause license.
            <?= Html::closeTag('p'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'container-fluid bg-light py-4']); ?>
    <?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
        <?= Html::openTag('div', ['class' => 'col-md-6']); ?>
            <?= Html::openTag('h3', ['class' => 'h5']); ?>
                Also on this site: OAuth2.0 Client Development
            <?= Html::closeTag('h3'); ?>
            <?= Html::openTag('p', ['class' => 'mb-1']); ?>
                A separate, related development project — an OAuth 2.0 client
                library for the Yii3 framework.
            <?= Html::closeTag('p'); ?>
            <?= Html::openTag('p', ['class' => 'mb-0']); ?>
                <?= new A()
                    ->content('github.com/rossaddison/yii-auth-client')
                    ->href('https://github.com/rossaddison/yii-auth-client')
                    ->addAttributes(['target' => '_blank', 'rel' => 'noopener'])
                    ->render(); ?>
            <?= Html::closeTag('p'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['class' => 'container-fluid py-4']); ?>
    <?= Html::openTag('h2', ['class' => 'h4 text-center mb-3']); ?>
        Admin Setup Walkthrough
    <?= Html::closeTag('h2'); ?>

    <?= Html::openTag('header', ['class' => 'text-center']); ?>
        <?= ($s->getSetting('debug_mode') == '1')
            ? Html::openTag('a', ['data-bs-toggle' => 'tooltip', 'title' => $tooltipTitle])
                    . Html::openTag('i', ['class' => 'bi bi-info-circle'])
                    . Html::closeTag('i')
                . Html::closeTag('a')
            : ''; ?>
    <?= Html::closeTag('header'); ?>

    <?= Carousel::widget()
        ->items(
            CarouselItem::to(
                content: '<div class="bg-dark" style="height: '
                            . $divHeight
                            . 'px; text-align: center"><br>'
                            .  new Img()
                            ->src('/img/step1.jpg')
                            ->size($w, $h)
                            ->render()
                        . '</div>',
                active: true,
                caption: $translator->translate('home.caption.slide1'),
                encodeCaption: false,
                captionAttributes: ['class' => ['d-none', 'd-md-block']],
            ),
            CarouselItem::to(
                content: '<div class="bg-dark" style="height: '
                            . $divHeight
                            . 'px; text-align: center"><br>'
                            .  new Img()
                            ->src('/img/step2.jpg')
                            ->size($w, $h)
                            ->render()
                        . '</div>',
                caption: $translator->translate('home.caption.slide2'),
                encodeCaption: true,
                captionAttributes: ['class' => ['d-none', 'd-md-block']],
            ),
            CarouselItem::to(
                content: '<div class="bg-dark" style="height: '
                            . $divHeight
                            . 'px; text-align: center"><br>'
                            .  new Img()
                            ->src('/img/step3.jpg')
                            ->size($w, $h)
                            ->render()
                        . '</div>',
                caption: $translator->translate('home.caption.slide3'),
                encodeCaption: true,
                captionAttributes: ['class' => ['d-none', 'd-md-block']],
            ),
        )
        ->render();
        ?>
<?= Html::closeTag('div'); ?>
<?php } ?>
