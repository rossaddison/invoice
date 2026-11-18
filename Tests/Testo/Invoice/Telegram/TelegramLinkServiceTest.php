<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Telegram;

use App\Auth\TokenRepository;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Identity\Identity;
use App\Infrastructure\Persistence\Token\Token;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Telegram\LinkOutcome;
use App\Invoice\Telegram\TelegramLinkService;
use App\Invoice\UserInv\UserInvRepository;
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * A chat is linked only by someone who holds a live, unused token made for the account; the token is
 * spent the moment it is read; and leaving or stopping unlinks every record that names the chat.
 *
 * @see TelegramLinkService
 */
#[Test]
final class TelegramLinkServiceTest
{
    private const string TOKEN = 'AbCdEfGhIjKlMnOpQrStUvWxYz012345';
    private const string SPENT = '/^already_used_token_\d+$/';

    /** @return Token&m\MockInterface a stored token of the link type, made $age ago, belonging to user 7 */
    private function storedToken(string $age): Token
    {
        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn(7);
        $identity = TypedMock::of(Identity::class);
        $identity->shouldReceive('getUser')->andReturn($user);
        $token = TypedMock::of(Token::class);
        $token->shouldReceive('getToken')->andReturn(self::TOKEN);
        $token->shouldReceive('getCreatedAt')->andReturn(new DateTimeImmutable($age));
        $token->shouldReceive('getIdentity')->andReturn($identity);
        return $token;
    }

    private function service(
        ?TokenRepository $tR = null,
        ?UserInvRepository $uiR = null,
        ?ClientRepository $cR = null,
    ): TelegramLinkService {
        return new TelegramLinkService(
            $tR ?? TypedMock::of(TokenRepository::class),
            $uiR ?? TypedMock::of(UserInvRepository::class),
            $cR ?? TypedMock::of(ClientRepository::class),
        );
    }

    public function aLiveTokenLinksTheChatToTheAccountItWasMadeFor(): void
    {
        $token = $this->storedToken('-60 seconds');
        $token->shouldReceive('setToken')->once()->with(m::pattern(self::SPENT));
        $tR = TypedMock::of(TokenRepository::class);
        $tR->shouldReceive('findTokenByTokenAndType')->once()->with(self::TOKEN, 'telegram-link')->andReturn($token);
        $tR->shouldReceive('save')->once()->with($token);
        $userInv = new UserInv();
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdquery')->with(7)->andReturn($userInv);
        $uiR->shouldReceive('save')->once()->with($userInv);

        Assert::same($this->service($tR, $uiR)->link(self::TOKEN, '555'), LinkOutcome::Linked);
        Assert::same($userInv->getTelegramChatId(), '555');
    }

    public function aTokenContainingHyphensAndUnderscoresIsAccepted(): void
    {
        // Random::string() draws from the URL-safe alphabet, so most real tokens contain - or _
        $text = 'Ab-dEfGhIjKlMn_pQrStUvWxYz01234-';
        Assert::same(strlen($text), 32);
        $token = $this->storedToken('-60 seconds');
        $token->shouldReceive('setToken')->once()->with(m::pattern(self::SPENT));
        $tR = TypedMock::of(TokenRepository::class);
        $tR->shouldReceive('findTokenByTokenAndType')->once()->with($text, 'telegram-link')->andReturn($token);
        $tR->shouldReceive('save')->once();
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdquery')->andReturn(new UserInv());
        $uiR->shouldReceive('save')->once();

        Assert::same($this->service($tR, $uiR)->link($text, '555'), LinkOutcome::Linked);
    }

    public function anExpiredTokenIsSpentAndLinksNothing(): void
    {
        $token = $this->storedToken('-1 hour');
        $token->shouldReceive('setToken')->once()->with(m::pattern(self::SPENT));
        $tR = TypedMock::of(TokenRepository::class);
        $tR->shouldReceive('findTokenByTokenAndType')->andReturn($token);
        $tR->shouldReceive('save')->once();
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdquery')->never();
        $uiR->shouldReceive('save')->never();

        Assert::same($this->service($tR, $uiR)->link(self::TOKEN, '555'), LinkOutcome::Expired);
    }

    public function aTokenIsAtMostFifteenMinutesOld(): void
    {
        Assert::same(TelegramLinkService::TOKEN_LIFETIME_SECONDS, 900);
    }

    public function aTokenThatDoesNotExistLinksNothing(): void
    {
        $tR = TypedMock::of(TokenRepository::class);
        $tR->shouldReceive('findTokenByTokenAndType')->once()->andReturn(null);
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('save')->never();

        Assert::same($this->service($tR, $uiR)->link(self::TOKEN, '555'), LinkOutcome::InvalidToken);
    }

    public function textThatIsNotTheShapeOfATokenIsNotEvenLookedUp(): void
    {
        $tR = TypedMock::of(TokenRepository::class);
        $tR->shouldReceive('findTokenByTokenAndType')->never();
        $service = $this->service($tR);

        foreach (['', 'nonsense', self::TOKEN . 'x', substr(self::TOKEN, 1), 'already_used_token_1700000000', str_repeat('a', 31) . '!'] as $text) {
            Assert::same($service->link($text, '555'), LinkOutcome::InvalidToken, $text);
        }
    }

    public function aTokenWhoseAccountHasNoProfileRowIsSpentAndLinksNothing(): void
    {
        $token = $this->storedToken('-60 seconds');
        $token->shouldReceive('setToken')->once()->with(m::pattern(self::SPENT));
        $tR = TypedMock::of(TokenRepository::class);
        $tR->shouldReceive('findTokenByTokenAndType')->andReturn($token);
        $tR->shouldReceive('save')->once();
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('repoUserInvUserIdquery')->with(7)->andReturn(null);
        $uiR->shouldReceive('save')->never();

        Assert::same($this->service($tR, $uiR)->link(self::TOKEN, '555'), LinkOutcome::InvalidToken);
    }

    public function creatingATokenSpendsAnEarlierUnusedOneAndSavesANewOne(): void
    {
        $earlier = TypedMock::of(Token::class);
        $earlier->shouldReceive('getToken')->andReturn(self::TOKEN);
        $earlier->shouldReceive('setToken')->once()->with(m::pattern(self::SPENT));
        $saved = [];
        $tR = TypedMock::of(TokenRepository::class);
        $tR->shouldReceive('findTokenByIdentityIdAndType')->with(5, 'telegram-link')->andReturn($earlier);
        $tR->shouldReceive('save')->twice()->andReturnUsing(function (Token $token) use (&$saved): void {
            $saved[] = $token;
        });

        $text = $this->service($tR)->createToken(5);

        Assert::same(preg_match('/^[A-Za-z0-9_-]{32}$/', $text), 1);
        Assert::same(count($saved), 2);
        Assert::same($saved[0], $earlier);
        Assert::same($saved[1]->getToken(), $text);
        Assert::same($saved[1]->getIdentityId(), 5);
    }

    public function creatingATokenLeavesAnAlreadyUsedOneAlone(): void
    {
        $used = TypedMock::of(Token::class);
        $used->shouldReceive('getToken')->andReturn('already_used_token_1700000000');
        $used->shouldReceive('setToken')->never();
        $tR = TypedMock::of(TokenRepository::class);
        $tR->shouldReceive('findTokenByIdentityIdAndType')->andReturn($used);
        $tR->shouldReceive('save')->once();

        $this->service($tR)->createToken(5);
    }

    public function unlinkingAChatClearsEveryAccountAndClientRecordThatNamesIt(): void
    {
        $account = new UserInv();
        $account->setTelegramChatId('555');
        $account->setConsentTelegramOutstanding(true);
        $client = new Client();
        $client->setClientTelegramChatId('555');
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('findByTelegramChatId')->with('555')->andReturn([$account]);
        $uiR->shouldReceive('save')->once()->with($account);
        $cR = TypedMock::of(ClientRepository::class);
        $cR->shouldReceive('findByTelegramChatId')->with('555')->andReturn([$client]);
        $cR->shouldReceive('save')->once()->with($client);

        Assert::same($this->service(null, $uiR, $cR)->unlinkChat('555'), 2);
        Assert::null($account->getTelegramChatId());
        Assert::false($account->getConsentTelegramOutstanding());
        Assert::null($client->getClientTelegramChatId());
    }

    public function unlinkingAChatNobodyUsesChangesNothing(): void
    {
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('findByTelegramChatId')->andReturn([]);
        $uiR->shouldReceive('save')->never();
        $cR = TypedMock::of(ClientRepository::class);
        $cR->shouldReceive('findByTelegramChatId')->andReturn([]);
        $cR->shouldReceive('save')->never();

        Assert::same($this->service(null, $uiR, $cR)->unlinkChat('999'), 0);
    }

    public function unlinkingOneAccountClearsItsChatAndItsReminderConsent(): void
    {
        $account = new UserInv();
        $account->setTelegramChatId('555');
        $account->setConsentTelegramOutstanding(true);
        $uiR = TypedMock::of(UserInvRepository::class);
        $uiR->shouldReceive('save')->once()->with($account);

        $this->service(null, $uiR)->unlinkUserInv($account);

        Assert::null($account->getTelegramChatId());
        Assert::false($account->getConsentTelegramOutstanding());
    }
}
