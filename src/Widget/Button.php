<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Inv\InvRepository as iR;
use Yiisoft\Bootstrap5\Button as B5;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class Button
{
    public function __construct(private CurrentRoute $currentRoute, private Translator $translator, private UrlGenerator $generator)
    {
    }

    public static function tfaToggleSecret(): string
    {
        return B5::widget()
        // The encode => false option ensures the span (icon) is rendered as HTML, not escaped text.
        ->label('<span id="eyeIcon" class="bi bi-eye"></span>', false)
        ->class('btn', 'btn-outline-primary')
        ->id('toggleSecret')
        ->attribute('type', 'button')
        ->render();
    }

    public static function tfaCopyToClipboard(): string
    {
        return B5::widget()
        ->label('<span id="copySecret" class="bi bi-clipboard"></span>', false)
        ->class('btn', 'btn-outline-primary')
        ->id('copySecret')
        ->attribute('type', 'button')
        ->attribute('title', 'Copy to clipboard')
        ->render();
    }

    public static function back(): string
    {
        $string = Html::openTag('div', ['class' => 'headerbar-item pull-right']);
        $buttonsDataArray = [
            [
                //$translator->translate('i.back'),
                '',
                'type' => 'reset',
                'onclick' => 'window.history.back()',
                'class' => 'btn btn-danger bi bi-arrow-left',
                'id' => 'btn-cancel',
                'name' => 'btn_cancel',
                'value' => '1',
            ],
        ];
        $string .= (string)Field::buttongroup()
            ->buttonsData($buttonsDataArray);
        return $string .= Html::closeTag('div');
    }

    public static function backSave(): string
    {
        $string = Html::openTag('div', ['class' => 'headerbar-item pull-right']);
        $buttonsDataArray = [
            [
                //$translator->translate('i.back'),
                '',
                'type' => 'reset',
                'onclick' => 'window.history.back()',
                'class' => 'btn btn-danger bi bi-arrow-left',
                'id' => 'btn-cancel',
                'name' => 'btn_cancel',
                'value' => 'main',
            ],
            [
                //$translator->translate('i.save'),
                '',
                'type' => 'submit',
                'class' => 'btn btn-success bi bi-save pull-right',
                'id' => 'btn-submit',
                'name' => 'btn_submit',
                'value' => '1',
            ],
        ];
        $string .= (string)Field::buttongroup()
            ->buttonsData($buttonsDataArray);
        return $string .= Html::closeTag('div');
    }

    public static function save(): string
    {
        $string = Html::openTag('div', ['class' => 'headerbar-item pull-right']);
        $buttonsDataArray = [
            [
                //$translator->translate('i.save'),
                '',
                'type' => 'submit',
                'class' => 'btn btn-success bi bi-save pull-right',
                'id' => 'btn-submit',
                'name' => 'btn_submit',
                'value' => '1',
            ],
        ];
        $string .= (string)Field::buttongroup()
            ->buttonsData($buttonsDataArray);
        return $string .= Html::closeTag('div');
    }

    public static function activeLabel(Translator $translator): Span
    {
        return Span::tag()
                ->addClass('label active')
                ->content(Html::encode($translator->translate('i.yes')));
    }

    public static function inactiveLabel(Translator $translator): Span
    {
        return Span::tag()
                ->addClass('label inactive')
                ->content(Html::encode($translator->translate('i.no')));
    }

    public static function inactiveWithAddUserAccount(UrlGenerator $generator, Translator $translator): Span
    {
        return Span::tag()
                ->content(
                    Html::a(
                        '',
                        $generator->generate('userinv/index'),
                        [
                            'class' => 'fa fa-plus',
                            'style' => 'text-decoration:none',
                            'tooltip' => 'data-bs-toggle',
                            'title' => $translator->translate('invoice.client.has.not.user.account'),
                        ]
                    )
                );
    }

    public static function ascDesc(UrlGenerator $generator, string $field, string $class, string $translated, bool $guest = false): string
    {
        return A::tag()
        ->addClass('btn btn-' . $class)
        ->content('⬆️')
        ->href($generator->generate('inv/' . ($guest ? 'guest' : 'index'), [], ['sort' => $field]))
        ->id('btn-' . $field . '-asc')
        ->render() . ' ' . $translated . ' ' . A::tag()
        ->addClass('btn btn-' . $class)
        ->content('⬇')
        ->href($generator->generate('inv/' . ($guest ? 'guest' : 'index'), [], ['sort' => '-' . $field]))
        ->id('btn-' . $field . '-desc')
        ->render();
    }

    public static function statusMark(
        UrlGenerator $generator,
        iR $iR,
        int $status,
        string $translated,
        bool $guest = false
    ): string {
        return A::tag()
        ->addClass('btn btn-' . $iR->getSpecificStatusArrayClass($status))
        ->content($iR->getSpecificStatusArrayEmoji($status) . ' ' . $iR->getSpecificStatusArrayLabel((string)$status))
        ->href($generator->generate('inv/' . ($guest ? 'guestmark' : 'indexmark'), ['status' => $status]))
        ->id('btn-' . $iR->getSpecificStatusArrayClass($status))
        ->render() . ' ' . $translated . ' ';
    }

    /**
     * @see src\Auth\Controller\SignupController.php
     * @see src\Invoice\UserInv\UserInvController function signup
     * @param UrlGenerator $generator
     * @param string $_language
     * @return string
     */
    public static function setOrUnsetAssignClientToUserAutomatically(UrlGenerator $generator, string $_language): string
    {
        return A::tag()
        ->addClass('btn btn-primary')
        ->content('✎️')
        ->href($generator->generate('setting/auto_client', ['_language' => $_language]))
        ->id('btn-primary')
        ->render();
    }

    /**
     * @see TelegramController function delete_webhook
     * @see ..config/common/routes/routes.php
     * @param UrlGenerator $generator
     * @param Translator $translator
     * @return string
     */
    public static function deleteWebhook(UrlGenerator $generator, Translator $translator): string
    {
        return A::tag()
        ->addClass('btn btn-primary')
        ->content($translator->translate('invoice.invoice.telegram.bot.api.webhook.delete') . ' ' . '️❌')
        ->href($generator->generate('telegram/delete_webhook', ['_language' => 'en']))
        ->id('btn-primary')
        ->render();
    }

    public static function identityProviderAuthenticationSuccessful(string $buttonHtml): string
    {
        return $buttonHtml;
    }

    public function developerSandboxHmrc(string $developerSandboxHmrcAuthUrl): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']) .
            Img::tag()
            ->src('/img/govuk-opengraph-image.png')
            ->size(90, 60)
            ->addClass('btn btn-dark')
            ->render() . A::tag()
            ->addClass('btn btn-dark')
            ->content($this->translator->translate('invoice.invoice.continue.with.developer.sandbox.hmrc'))
            ->href($developerSandboxHmrcAuthUrl)
            ->id('btn-sandboxhmrc')
            ->render() .
        Html::closeTag('div');
    }

    public function facebook(string $facebookAuthUrl): string
    {
        return A::tag()
        ->addClass('btn btn-primary bi bi-facebook')
        ->content(' ' . $this->translator->translate('invoice.invoice.continue.with.facebook'))
        ->href($facebookAuthUrl)
        ->id('btn-facebook')
        ->render();
    }

    public function github(string $githubAuthUrl): string
    {
        return A::tag()
        ->addClass('btn btn-dark bi bi-github')
        ->content(' ' . $this->translator->translate('invoice.invoice.continue.with.github'))
        ->href($githubAuthUrl)
        ->id('btn-github')
        ->render();
    }

    public function google(string $googleAuthUrl): string
    {
        return A::tag()
        ->addClass('btn btn-success bi bi-google')
        ->content(' ' . $this->translator->translate('invoice.invoice.continue.with.google'))
        ->href($googleAuthUrl)
        ->id('btn-google')
        ->render();
    }

    /**
     * 24/04/2025
     * @see npm_modules/govuk-frontend
     * @see public/img/govuk-opengraph-image.png
     * @param string $govukAuthUrl
     * @return string
     */
    public function govuk(string $govukAuthUrl): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']) .
            Img::tag()
            ->src('/img/govuk-opengraph-image.png')
            ->size(90, 60)
            ->addClass('btn btn-dark')
            ->render() . A::tag()
            ->addClass('btn btn-dark')
            ->content($this->translator->translate('invoice.invoice.continue.with.govuk'))
            ->href($govukAuthUrl)
            ->id('btn-govuk')
            ->render() .
        Html::closeTag('div');
    }

    public function linkedin(string $linkedInAuthUrl): string
    {
        return A::tag()
        ->addClass('btn btn-info bi bi-linkedin')
        ->content(' ' . $this->translator->translate('invoice.invoice.continue.with.linkedin'))
        ->href($linkedInAuthUrl)
        ->id('btn-linkedin')
        ->render();
    }

    public function microsoftonline(string $microsoftOnlineAuthUrl): string
    {
        return A::tag()
        ->addClass('btn btn-warning bi bi-microsoft')
        ->content(' ' . $this->translator->translate('invoice.invoice.continue.with.microsoftonline'))
        ->href($microsoftOnlineAuthUrl)
        ->id('btn-microsoftonline')
        ->render();
    }

    public function vkontakte(string $vkontakteAuthUrl): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']) .
            Img::tag()
            ->src('/img/vkontakte-24.jpg')
            ->addClass('btn btn-dark')
            ->render() . A::tag()
            ->addClass('btn btn-dark')
            ->content($this->translator->translate('invoice.invoice.continue.with.vkontakte'))
            ->href($vkontakteAuthUrl)
            ->id('btn-vkontakte')
            ->render() .
        Html::closeTag('div');
    }

    public function x(string $xAuthUrl): string
    {
        return A::tag()
        ->addClass('btn btn-dark bi bi-twitter')
        ->content(' ' . $this->translator->translate('invoice.invoice.continue.with.x'))
        ->href($xAuthUrl)
        ->id('btn-x')
        ->render();
    }

    public function yandex(string $yandexAuthUrl): string
    {
        return
        Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']) .
            Img::tag()
            ->src('/img/yandex-24.jpg')
            ->addClass('btn btn-dark disabled')
            ->render() . A::tag()
            ->addClass('btn btn-dark')
            ->content($this->translator->translate('invoice.invoice.continue.with.yandex'))
            ->href($yandexAuthUrl)
            ->id('btn-vkontakte')
            ->render() .
        Html::closeTag('div');
    }
}
