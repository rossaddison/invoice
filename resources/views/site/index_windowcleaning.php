<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;

/**
 * The front page of a window-cleaning install (business_type = window_cleaning); see
 * SiteController::index().
 *
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $companyName
 * @var string $companyEmail
 * @var string $companyWeb
 * @var string $companyWhatsApp
 */
$steps = ['windowcleaning.home.step1', 'windowcleaning.home.step2', 'windowcleaning.home.step3'];
$contacts = array_filter([
    $translator->translate('windowcleaning.home.contact.email') => $companyEmail,
    $translator->translate('windowcleaning.home.contact.web') => $companyWeb,
    $translator->translate('windowcleaning.home.contact.whatsapp') => $companyWhatsApp,
], static fn (string $value): bool => '' !== $value);
?>
<?= Html::openTag('div', ['class' => 'container py-5']); ?>
    <?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
        <?= Html::openTag('div', ['class' => 'col-lg-8']); ?>
            <?php // Html::tag() encodes its content itself: pass the raw text, never Html::encode()d text ?>
            <?= Html::tag('h1', '' !== $companyName ? $companyName : $translator->translate('windowcleaning.home.title'), ['class' => 'display-5 fw-bold mb-3']); ?>
            <?= Html::tag('p', $translator->translate('windowcleaning.home.lead'), ['class' => 'lead']); ?>

            <?= Html::openTag('div', ['class' => 'd-flex flex-wrap gap-2 my-4']); ?>
                <?php if ($s->getSetting('stop_homecare_signing_up') !== '1') { ?>
                    <?= new A()
                        ->href($urlGenerator->generate('homecare/signup'))
                        ->addClass('btn btn-primary btn-lg')
                        ->content($translator->translate('homecare.signup.title'))
                        ->render(); ?>
                <?php } ?>
                <?php if ($s->getSetting('stop_logging_in') !== '1') { ?>
                    <?= new A()
                        ->href($urlGenerator->generate('auth/login'))
                        ->addClass('btn btn-outline-secondary btn-lg')
                        ->content($translator->translate('menu.login'))
                        ->render(); ?>
                <?php } ?>
            <?= Html::closeTag('div'); ?>

            <?= Html::tag('h2', $translator->translate('windowcleaning.home.how.heading'), ['class' => 'h4 mt-5']); ?>
            <?= Html::openTag('ol', ['class' => 'fs-5']); ?>
                <?php foreach ($steps as $step) { ?>
                    <?= Html::tag('li', $translator->translate($step)); ?>
                <?php } ?>
            <?= Html::closeTag('ol'); ?>

            <?php if ([] !== $contacts) { ?>
                <?= Html::tag('h2', $translator->translate('windowcleaning.home.contact.heading'), ['class' => 'h4 mt-5']); ?>
                <?= Html::openTag('ul', ['class' => 'list-unstyled']); ?>
                    <?php foreach ($contacts as $label => $value) { ?>
                        <?= Html::tag('li', $label . ': ' . $value); ?>
                    <?php } ?>
                <?= Html::closeTag('ul'); ?>
            <?php } ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
