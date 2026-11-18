<?php

declare(strict_types=1);

namespace Tests\Testo\Auth;

use App\Auth\Controller\HomeCareSignupDeps;
use App\Auth\HomeCareSignupEmailService;
use App\Auth\TokenRepository;
use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\Service\WebControllerService;
use Mockery as m;
use Psr\Log\NullLogger;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Mailer\MessageInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The HomeCare signup confirmation email, like the generic one, must carry the link in the HTML and
 * the plain-text part, and the user's extension row is created (inactive) before it is sent.
 *
 * @see HomeCareSignupEmailService::processSendSignupEmail()
 */
#[Test]
final class HomeCareSignupEmailServiceTest
{
    public function theEmailCarriesTheLinkInBothPartsAndTheRowIsCreatedInactive(): void
    {
        $confirmUrl = 'https://example.test/en/homecare/signup-confirm/English/MASKED-TOKEN/homecare-email-verification';
        $identity = TypedMock::of(Identity::class);
        $identity->shouldReceive('getId')->andReturn('5');
        $user = TypedMock::of(User::class);
        $user->shouldReceive('getEmail')->andReturn('new.user@example.test');
        $user->shouldReceive('getLogin')->andReturn('virtualtest');
        $user->shouldReceive('getIdentity')->andReturn($identity);
        $tR = TypedMock::of(TokenRepository::class);
        $tR->shouldReceive('save');
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('createInactiveFor')->once()->with($user, 'English');

        $sR = TypedMock::of(SettingRepository::class);
        $sR->shouldReceive('getSetting')->andReturnUsing(
            static fn (string $key): string => $key === 'email_send_method' ? 'symfony' : '',
        );
        $sR->shouldReceive('getConfigAdminEmail')->andReturn('admin@example.test');
        $sR->shouldReceive('localeLanguageArray')->andReturn(['en' => 'English']);
        $translator = TypedMock::of(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(
            static fn (string $key): string => $key === 'email.link.click.confirm' ? 'Please confirm' : $key,
        );
        $urlGenerator = TypedMock::of(UrlGenerator::class);
        $urlGenerator->shouldReceive('generateAbsolute')
            ->with('homecare/signup-confirm', m::on(static fn (array $arguments): bool => $arguments['tokenType'] === 'homecare-email-verification'
                && $arguments['language'] === 'English' && $arguments['_language'] === 'en'))
            ->andReturn($confirmUrl);
        $sent = [];
        $mailer = TypedMock::of(MailerInterface::class);
        $mailer->shouldReceive('send')->once()->andReturnUsing(function (MessageInterface $message) use (&$sent): void {
            $sent[] = $message;
        });
        $route = TypedMock::of(CurrentRoute::class);
        $route->shouldReceive('getArgument')->with('_language')->andReturn('en');

        $service = new HomeCareSignupEmailService(
            $mailer,
            $sR,
            $translator,
            $urlGenerator,
            new NullLogger(),
            TypedMock::of(WebControllerService::class),
        );
        $result = $service->processSendSignupEmail(
            $user,
            $route,
            TypedMock::build(HomeCareSignupDeps::class, ['tR' => $tR, 'uiR' => $uiR]),
        );

        Assert::null($result);
        Assert::same(count($sent), 1);
        Assert::true(str_contains((string) $sent[0]->getHtmlBody(), '<a href="' . $confirmUrl . '">Please confirm</a>'));
        Assert::true(str_contains((string) $sent[0]->getHtmlBody(), '<p>' . $confirmUrl . '</p>'));
        Assert::true(str_contains((string) $sent[0]->getTextBody(), $confirmUrl));
    }
}
