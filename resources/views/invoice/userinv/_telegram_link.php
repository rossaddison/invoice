<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * The one-time Telegram link, shown after "Link Telegram" is pressed on the profile page. Related logic:
 * UserInvController::guestTelegramLink() and App\Invoice\Telegram\TelegramLinkService.
 *
 * @var string $deepLink https://t.me/<bot>?start=<one-time token>
 * @var int $minutes how long the link works
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\View $this
 */

?>

<?= Html::openTag('div', ['class' => 'container py-5']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
    <?= Html::encode($translator->translate('telegram.link.heading')) ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['class' => 'card-body']); ?>
    <p><?= Html::encode($translator->translate('telegram.link.intro')) ?></p>
    <p>
        <?= Html::a(
            $translator->translate('telegram.link.open'),
            $deepLink,
            ['class' => 'btn btn-primary', 'target' => '_blank', 'rel' => 'noopener noreferrer'],
        ) ?>
    </p>
    <p class="text-muted small"><?= Html::encode(sprintf($translator->translate('telegram.link.expires'), $minutes)) ?></p>
    <p><?= Html::encode($translator->translate('telegram.link.after')) ?></p>
    <?= Html::a(
        $translator->translate('telegram.link.back'),
        $urlGenerator->generate('userinv/guest'),
        ['class' => 'btn btn-outline-secondary'],
    ) ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
