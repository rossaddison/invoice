<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\AuthService;
use App\Auth\Form\LoginForm;
use GuzzleHttp\Psr7\ServerRequest;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Hydrator\Hydrator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Validator;

/**
 * The "Remember me" tick must reach the login form. FormHydrator fills only properties that have
 * validation rules unless told otherwise, and LoginForm validates login and password only: before
 * the explicit property list the tick was dropped, so no auto-login cookie was ever issued.
 */
#[Test]
final class LoginFormRememberMeTest
{
    private function form(): LoginForm
    {
        $translator = TypedMock::of(TranslatorInterface::class);
        $translator->shouldIgnoreMissing("");

        return new LoginForm(TypedMock::of(AuthService::class), $translator);
    }

    private function post(string $remember): ServerRequest
    {
        // a checkbox posts a hidden 0 first and the ticked 1 after it; PHP keeps the last value
        return new ServerRequest('POST', '/login')->withParsedBody(
            ['Login' => ['login' => 'admin', 'password' => 'x', 'rememberMe' => $remember]],
        );
    }

    public function aTickedBoxIsReadWhenThePropertyIsListed(): void
    {
        $form = $this->form();
        new FormHydrator(new Hydrator(), new Validator())
            ->populateFromPost($form, $this->post('1'), LoginForm::HYDRATED_PROPERTIES);

        Assert::true($form->getPropertyValue('rememberMe'));
    }

    public function anUntickedBoxIsReadAsFalse(): void
    {
        $form = $this->form();
        new FormHydrator(new Hydrator(), new Validator())
            ->populateFromPost($form, $this->post('0'), LoginForm::HYDRATED_PROPERTIES);

        Assert::false($form->getPropertyValue('rememberMe'));
    }

    public function withoutTheListTheTickIsDropped(): void
    {
        // documents why the list exists: this is the behaviour that broke remember-me
        $form = $this->form();
        new FormHydrator(new Hydrator(), new Validator())
            ->populateFromPost($form, $this->post('1'));

        Assert::false($form->getPropertyValue('rememberMe'));
    }
}
