<?php

declare(strict_types=1);

namespace App\Widget;

use App\Invoice\Inv\InvRepository as iR;
use Yiisoft\Bootstrap5\Button as B5;
use Yiisoft\Router\UrlGeneratorInterface as UrlGenerator;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Translator\TranslatorInterface as Translator;

final readonly class Button
{
    private const string HDRBAR_ITEM_FLTEND = 'headerbar-item float-end';
    
    public function __construct(private readonly Translator $translator,
        private readonly UrlGenerator $generator)
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
        $buttonsDataArray = [
            [
                //$translator->translate('back'),
                '',
                'type' => 'reset',
                'onclick' => 'window.history.back()',
                'class' => 'btn btn-danger bi bi-arrow-left',
                'value' => '1',
            ],
        ];
        return Html::openTag('div', ['class' => self::HDRBAR_ITEM_FLTEND])
                . (string) Field::buttongroup()
                        ->buttonsData($buttonsDataArray)
                .  Html::closeTag('div');
    }

    public static function backSave(): string
    {
        $buttonsDataArray = [
            [
                '',
                'type' => 'button',
                'onclick' => 'window.history.back()',
                'class' => 'btn btn-danger bi bi-arrow-left',
                'data-bs-toggle' => 'tooltip',
                'title' => 'Back',
                'value' => 'main',
            ],
            [
                '',
                'type' => 'submit',
                'class' => 'btn btn-success bi bi-floppy',
                'id' => 'btn-submit',
                'data-bs-toggle' => 'tooltip',
                'title' => 'Save',
                'value' => '1',
            ],
        ];
        return (string) Field::buttongroup()
            ->buttonsData($buttonsDataArray)
            ->containerAttributes(['class' => 'btn-group mb-2', 'role' => 'group']);
    }

    public static function save(): string
    {
        $buttonsDataArray = [
            [
                //$translator->translate('save'),
                '',
                'type' => 'submit',
                'class' => 'btn btn-success bi bi-save float-end',
                'value' => '1',
            ],
        ];
        return Html::openTag('div', ['class' => self::HDRBAR_ITEM_FLTEND])
            . (string) Field::buttongroup()
                ->buttonsData($buttonsDataArray)
            .  Html::closeTag('div');
    }

    public function saveCancel(): string
    {
        $buttonsDataArray = [
            [
                '',
                'type' => 'submit',
                'class' => 'btn btn-success bi bi-save float-end',
                'value' => '1',
            ],
            [
                $this->translator->translate('cancel'),
                'type' => 'cancel',
                'class' => 'btn btn-secondary float-end',
                'href' => $this->generator->generate('family/index')
            ],
        ];
        return Html::openTag('div', ['class' => self::HDRBAR_ITEM_FLTEND])
            . (string) Field::buttongroup()
                ->buttonsData($buttonsDataArray)
            .  Html::closeTag('div');
    }

    public static function activeLabel(Translator $translator): Span
    {
        return  new Span()
                ->addClass('label active')
                ->content(Html::encode($translator->translate('yes')));
    }

    public static function inactiveLabel(Translator $translator): Span
    {
        return  new Span()
                ->addClass('label inactive')
                ->content(Html::encode($translator->translate('no')));
    }

    public static function inactiveWithAddUserAccount(
        UrlGenerator $generator,
        Translator $translator): Span
    {
        return  new Span()
                ->content(
                    Html::a(
                        Html::tag('i', '', ['class' => 'bi bi-person-plus']),
                        $generator->generate('userinv/index'),
                        [
                            'class' => 'btn btn-outline-secondary btn-sm',
                            'style' => 'text-decoration:none',
                            'data-bs-toggle' => 'tooltip',
                            'title' => $translator->translate(
                                    'client.has.not.user.account'),
                        ],
                    ),
                );
    }

    public static function ascDesc(UrlGenerator $generator, string $field, string $class, string $translated, bool $guest = false): string
    {
        return  new A()
        ->addClass('btn btn-' . $class)
        ->content('⬆️')
        ->href($generator->generate('inv/' . ($guest ? 'guest' : 'index'), [], ['sort' => $field]))
        ->id('btn-' . $field . '-asc')
        ->render() . ' ' . $translated . ' ' .  new A()
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
        bool $guest = false,
    ): string {
        return  new A()
        ->addClass('btn btn-' . $iR->getSpecificStatusArrayClass($status))
        ->content($iR->getSpecificStatusArrayEmoji($status) . ' ' . $iR->getSpecificStatusArrayLabel((string) $status))
        ->href($generator->generate('inv/' . ($guest ? 'guestmark' : 'indexmark'), ['status' => $status]))
        ->id('btn-' . $iR->getSpecificStatusArrayClass($status))
        ->render() . ' ' . $translated . ' ';
    }

    /**
     * Related logic: see src\Auth\Controller\SignupController.php
     * Related logic: see src\Invoice\UserInv\UserInvController function signup
     * @param UrlGenerator $generator
     * @param string $_language
     * @return string
     */
    public static function setOrUnsetAssignClientToUserAutomatically(UrlGenerator $generator, string $_language): string
    {
        return  new A()
        ->addClass('btn btn-primary')
        ->content('✎️')
        ->href($generator->generate('setting/autoClient', ['_language' => $_language]))
        ->id('btn-primary')
        ->render();
    }

    public static function defaultPaymentMethod(UrlGenerator $generator, Translator $translator): string
    {
        return  new A()
        ->addClass('btn btn-success')
        ->href(
            $generator->generate(
                'setting/tabIndex',
                ['language' => 'en'],
                ['active' => 'invoices'],
                'settings[invoice_default_payment_method]',
            ),
        )
        ->content($translator->translate('default.payment.method'))
        ->render();
    }

    /**
     * Related logic: see TelegramController function deleteWebhook
     * Related logic: see ..config/common/routes/routes.php
     * @param UrlGenerator $generator
     * @param Translator $translator
     * @return string
     */
    public static function deleteWebhook(UrlGenerator $generator, Translator $translator): string
    {
        return  new A()
        ->addClass('btn btn-primary')
        ->content($translator->translate('telegram.bot.api.webhook.delete')
            . ' '
            . '️❌')
        ->href($generator->generate('telegram/deleteWebhook', ['_language' => 'en']))
        ->id('btn-primary')
        ->render();
    }

    public static function identityProviderAuthenticationSuccessful(string $buttonHtml): string
    {
        return $buttonHtml;
    }

}
