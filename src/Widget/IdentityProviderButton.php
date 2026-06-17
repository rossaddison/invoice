<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class IdentityProviderButton
{
    public function __construct(private readonly Translator $translator,
        private readonly UrlGenerator $generator)
    {
    }

    public function developerSandboxHmrc(string $developerSandboxHmrcAuthUrl): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->src('/img/govuk-opengraph-image.png')
            ->size(90, 60)
            ->addClass('btn btn-dark')
            ->render() .  new A()
            ->addClass('btn btn-dark')
            ->content($this->translator->translate('continue.with.developersandboxhmrc'))
            ->href($developerSandboxHmrcAuthUrl)
            ->id('btn-sandboxhmrc')
            ->render()
        . Html::closeTag('div');
    }

    public function facebook(string $facebookAuthUrl): string
    {
        return  new A()
        ->addClass('btn btn-primary bi bi-facebook')
        ->content(' ' . $this->translator->translate('continue.with.facebook'))
        ->href($facebookAuthUrl)
        ->id('btn-facebook')
        ->render();
    }

    public function github(string $githubAuthUrl): string
    {
        return  new A()
        ->addClass('btn btn-dark bi bi-github')
        ->content(' ' . $this->translator->translate('continue.with.github'))
        ->href($githubAuthUrl)
        ->id('btn-github')
        ->render();
    }

    public function google(string $googleAuthUrl): string
    {
        return  new A()
        ->addClass('btn btn-success bi bi-google')
        ->content(' ' . $this->translator->translate('continue.with.google'))
        ->href($googleAuthUrl)
        ->id('btn-google')
        ->render();
    }

    /**
     * Related logic: see npm_modules/govuk-frontend
     * Related logic: see public/img/govuk-opengraph-image.png
     */
    public function govuk(string $govukAuthUrl): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->src('/img/govuk-opengraph-image.png')
            ->size(90, 60)
            ->addClass('btn btn-dark')
            ->render() .  new A()
            ->addClass('btn btn-dark')
            ->content($this->translator->translate('continue.with.govuk'))
            ->href($govukAuthUrl)
            ->id('btn-govuk')
            ->render()
        . Html::closeTag('div');
    }

    public function linkedin(string $linkedInAuthUrl): string
    {
        return  new A()
        ->addClass('btn btn-info bi bi-linkedin')
        ->content(' ' . $this->translator->translate('continue.with.linkedin'))
        ->href($linkedInAuthUrl)
        ->id('btn-linkedin')
        ->render();
    }

    public function microsoftonline(string $microsoftOnlineAuthUrl): string
    {
        return  new A()
        ->addClass('btn btn-warning bi bi-microsoft')
        ->content(' ' . $this->translator->translate('continue.with.microsoftonline'))
        ->href($microsoftOnlineAuthUrl)
        ->id('btn-microsoftonline')
        ->render();
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function openbanking(string $openBankingAuthUrl, string $provider): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new A()
            ->addClass('btn btn-dark')
            ->content('🏦  ' . $this->translator->translate('continue.with.openbanking')
                    . '➡️' . ucfirst($provider))
            ->href($openBankingAuthUrl)
            ->id('btn-openbanking')
            ->render()
        . Html::closeTag('div');
    }

    public function vkontakte(string $vkontakteAuthUrl): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->src('/img/vkontakte-24.jpg')
            ->addClass('btn btn-dark')
            ->render() .  new A()
            ->addClass('btn btn-dark')
            ->content($this->translator->translate('continue.with.vkontakte'))
            ->href($vkontakteAuthUrl)
            ->id('btn-vkontakte')
            ->render()
        . Html::closeTag('div');
    }

    public function x(string $xAuthUrl): string
    {
        return  new A()
        ->addClass('btn btn-dark bi bi-twitter')
        ->content(' ' . $this->translator->translate('continue.with.x'))
        ->href($xAuthUrl)
        ->id('btn-x')
        ->render();
    }

    public function yandex(string $yandexAuthUrl): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group'])
            .  new Img()
            ->src('/img/yandex-24.jpg')
            ->addClass('btn btn-dark disabled')
            ->render() .  new A()
            ->addClass('btn btn-dark')
            ->content($this->translator->translate('continue.with.yandex'))
            ->href($yandexAuthUrl)
            ->id('btn-vkontakte')
            ->render()
        . Html::closeTag('div');
    }

    public function regenerateRecoveryCodes(string $regenerateCodesUrl): string
    {
        return  new A()
        ->addClass('btn btn-success')
        ->content(' ' . $this->translator->translate('oauth2.backup.recovery.codes.regenerate'))
        ->href($regenerateCodesUrl)
        ->id('btn-regenerate-codes')
        ->render();
    }
}
