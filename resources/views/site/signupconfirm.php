<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Landing page for the emailed signup confirmation link (generic and HomeCare). Opening the link
 * (GET) only shows this page; the account is confirmed when the button is pressed (POST, with the
 * CSRF token), so a mail scanner or link previewer that merely fetches the URL cannot use up the
 * one-time token.
 *
 * @var string $actionPath The path of the confirmation link itself, posted back to
 * @var string $csrf
 * @var TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 */

$this->setTitle($translator->translate('signup.confirm.account'));
?>
<div class="container py-4">
    <h1 class="h3"><?= H::encode($this->getTitle()) ?></h1>
    <p><?= H::encode($translator->translate('signup.confirm.account.intro')) ?></p>
    <?= new Form()->post($actionPath)->csrf($csrf)->id('signupConfirmForm')->open() ?>
    <?= H::submitButton($translator->translate('signup.confirm.account'), ['class' => 'btn btn-primary btn-lg']) ?>
    <?= H::closeTag('form') ?>
</div>
