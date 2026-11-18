<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\UserInv;

use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Telegram\TelegramLinkService;
use App\Invoice\UserInv\UserInvRepository;
use App\Invoice\UserInv\UserInvTelegramController;
use App\Service\WebControllerService;
use App\User\UserService;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Http\Header;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The signed-in user's own Telegram link and unlink, from the profile page: nothing happens for a GET or for
 * someone with no profile, a link is never started while Telegram is off or not set up, and unlinking clears
 * the chat and the reminder consent. (Starting a link with a working bot needs a real Telegram connection,
 * so it is checked on the live site.)
 *
 * @see UserInvTelegramController
 */
#[Test]
final class UserInvTelegramControllerTest
{
    /** @var list<array{string, string}> every flash message added: level and text */
    private array $flashed = [];

    /**
     * @param User|null $user who is signed in
     * @param array<string, string> $settings
     */
    private function controller(?User $user, array $settings = []): UserInvTelegramController
    {
        $this->flashed = [];

        $webService = TypedMock::of(WebControllerService::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn(new HttpFactory()->createResponse(404));
        $webService->shouldReceive('getRedirectResponse')->andReturnUsing(
            static fn (string $route): ResponseInterface => new HttpFactory()
                ->createResponse(302)
                ->withHeader(Header::LOCATION, $route),
        );
        $userService = TypedMock::of(UserService::class);
        $userService->shouldReceive('getUser')->andReturn($user);
        $translator = TypedMock::of(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key): string => $key);
        $sR = TypedMock::of(SettingRepository::class);
        $sR->shouldReceive('getSetting')->andReturnUsing(static fn (string $key): string => $settings[$key] ?? '');
        $flash = TypedMock::of(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->andReturnUsing(function (string $level, string $text): void {
            $this->flashed[] = [$level, $text];
        });

        $reflection = new ReflectionClass(UserInvTelegramController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        foreach ([
            'webService' => $webService,
            'userService' => $userService,
            'translator' => $translator,
            'sR' => $sR,
            'flash' => $flash,
        ] as $property => $value) {
            $reflection->getProperty($property)->setValue($controller, $value);
        }
        return $controller;
    }

    private function user(): User
    {
        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn(7);
        return $user;
    }

    private function profiles(?UserInv $row): UserInvRepository
    {
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdquery')->with(7)->andReturn($row);
        return $uiR;
    }

    private function request(string $method): ServerRequestInterface
    {
        return new HttpFactory()->createServerRequest($method, 'https://example.test/invoice/userinv/guest/telegram-link');
    }

    private function neverCreatesAToken(): TelegramLinkService
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('createToken')->never();
        return $links;
    }

    public function linkingNeedsASignedInUserWithAProfile(): void
    {
        $links = $this->neverCreatesAToken();

        $signedOut = $this->controller(null)->link($this->request('POST'), $this->profiles(null), $links, new NullLogger());
        $noProfile = $this->controller($this->user())->link($this->request('POST'), $this->profiles(null), $links, new NullLogger());

        Assert::same($signedOut->getStatusCode(), 404);
        Assert::same($noProfile->getStatusCode(), 404);
    }

    public function openingTheLinkAddressWithGetStartsNothing(): void
    {
        $response = $this->controller($this->user(), ['enable_telegram' => '1', 'telegram_token' => '123456:ABC'])
            ->link($this->request('GET'), $this->profiles(new UserInv()), $this->neverCreatesAToken(), new NullLogger());

        Assert::same($response->getStatusCode(), 302);
        Assert::same($response->getHeaderLine(Header::LOCATION), 'userinv/guest');
        Assert::same($this->flashed, []);
    }

    public function aLinkIsNotStartedWhileTelegramIsSwitchedOff(): void
    {
        $controller = $this->controller($this->user(), ['enable_telegram' => '0', 'telegram_token' => '123456:ABC']);

        $response = $controller->link($this->request('POST'), $this->profiles(new UserInv()), $this->neverCreatesAToken(), new NullLogger());

        Assert::same($response->getHeaderLine(Header::LOCATION), 'userinv/guest');
        Assert::same($this->flashed, [['warning', 'telegram.link.unavailable']]);
    }

    public function aLinkIsNotStartedWithoutABotToken(): void
    {
        foreach (['', 'x'] as $token) {
            $controller = $this->controller($this->user(), ['enable_telegram' => '1', 'telegram_token' => $token]);

            $response = $controller->link($this->request('POST'), $this->profiles(new UserInv()), $this->neverCreatesAToken(), new NullLogger());

            Assert::same($response->getHeaderLine(Header::LOCATION), 'userinv/guest', $token);
            Assert::same($this->flashed, [['warning', 'telegram.link.unavailable']], $token);
        }
    }

    public function unlinkingNeedsASignedInUserWithAProfile(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('unlinkUserInv')->never();

        Assert::same($this->controller(null)->unlink($this->request('POST'), $this->profiles(null), $links)->getStatusCode(), 404);
        Assert::same($this->controller($this->user())->unlink($this->request('POST'), $this->profiles(null), $links)->getStatusCode(), 404);
    }

    public function openingTheUnlinkAddressWithGetUnlinksNothing(): void
    {
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('unlinkUserInv')->never();

        $response = $this->controller($this->user())->unlink($this->request('GET'), $this->profiles(new UserInv()), $links);

        Assert::same($response->getHeaderLine(Header::LOCATION), 'userinv/guest');
        Assert::same($this->flashed, []);
    }

    public function pressingUnlinkClearsTheLinkTellsTheUserAndReturnsToTheProfile(): void
    {
        $profile = new UserInv();
        $links = TypedMock::of(TelegramLinkService::class);
        $links->shouldReceive('unlinkUserInv')->once()->with($profile);

        $response = $this->controller($this->user())->unlink($this->request('POST'), $this->profiles($profile), $links);

        Assert::same($response->getStatusCode(), 302);
        Assert::same($response->getHeaderLine(Header::LOCATION), 'userinv/guest');
        Assert::same($this->flashed, [['info', 'telegram.unlinked']]);
    }
}
