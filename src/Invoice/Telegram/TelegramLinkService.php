<?php

declare(strict_types=1);

namespace App\Invoice\Telegram;

use App\Auth\TokenRepository;
use App\Infrastructure\Persistence\Token\Token;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Client\ClientRepository;
use App\Invoice\UserInv\UserInvRepository;

/**
 * Links a Telegram chat to an account only when that account's owner proves they hold the chat.
 *
 * A chat id used to be typed into the profile form, so anyone could enter anyone's: reminders would go to
 * a stranger, and a person who never pressed Start could never be reached (a bot cannot message a chat
 * that has not started it). Now the profile page creates a one-time token and shows a t.me link carrying it
 * (`https://t.me/<bot>?start=<token>`). Pressing Start sends the token to the webhook, which stores the
 * chat id of the sender on the account the token was made for.
 *
 * The token is the existing Token entity, type 'telegram-link', and follows the rules of the emailed
 * signup link: it is spent the moment it is read (renamed `already_used_token_<time>`) and its age is the
 * server's own created_at, never anything the sender supplies.
 */
final readonly class TelegramLinkService
{
    public const string TOKEN_TYPE = 'telegram-link';

    /** fifteen minutes: long enough to open Telegram, short enough that a leaked link is soon useless */
    public const int TOKEN_LIFETIME_SECONDS = 900;

    private const string USED_TOKEN_PREFIX = 'already_used_token_';

    /**
     * Token::__construct() makes 32 characters from Random::string(), whose alphabet is the URL-safe one:
     * letters, digits, '-' and '_'. That is also exactly what Telegram allows in a start payload.
     */
    private const string TOKEN_SHAPE = '/^[A-Za-z0-9_-]{32}$/';

    public function __construct(
        private TokenRepository $tR,
        private UserInvRepository $uiR,
        private ClientRepository $cR,
    ) {
    }

    /**
     * A new one-time token for this identity. An earlier one that was never used is spent first, so only
     * the link on screen works.
     *
     * @return non-empty-string
     */
    public function createToken(int $identityId): string
    {
        $previous = $this->tR->findTokenByIdentityIdAndType($identityId, self::TOKEN_TYPE);
        if (null !== $previous && !str_starts_with((string) $previous->getToken(), self::USED_TOKEN_PREFIX)) {
            $this->spend($previous);
        }
        $token = new Token($identityId, self::TOKEN_TYPE);
        $this->tR->save($token);
        $text = (string) $token->getToken();
        return $text === '' ? throw new \LogicException('A Token always has text') : $text;
    }

    /**
     * @param string $tokenText what followed `/start ` in the message
     * @param string $chatId the id of the private chat the message came from
     */
    public function link(string $tokenText, string $chatId): LinkOutcome
    {
        if (preg_match(self::TOKEN_SHAPE, $tokenText) !== 1) {
            return LinkOutcome::InvalidToken;
        }
        $token = $this->tR->findTokenByTokenAndType($tokenText, self::TOKEN_TYPE);
        if (null === $token) {
            return LinkOutcome::InvalidToken;
        }
        // Spent before anything else is decided, so a token can never be tried twice, expired or not.
        $createdAt = $token->getCreatedAt()->getTimestamp();
        $this->spend($token);
        if ($createdAt + self::TOKEN_LIFETIME_SECONDS < time()) {
            return LinkOutcome::Expired;
        }
        $userId = $token->getIdentity()?->getUser()?->reqId();
        $userInv = null !== $userId ? $this->uiR->repoUserInvUserIdquery($userId) : null;
        if (null === $userInv) {
            return LinkOutcome::InvalidToken;
        }
        $userInv->setTelegramChatId($chatId);
        $this->uiR->save($userInv);
        return LinkOutcome::Linked;
    }

    /**
     * Stops everything going to this chat: every account linked to it loses the chat id and its
     * Telegram reminder consent, and so does any client record an administrator typed it into.
     *
     * @return int how many records were unlinked
     */
    public function unlinkChat(string $chatId): int
    {
        $count = 0;
        foreach ($this->uiR->findByTelegramChatId($chatId) as $userInv) {
            $this->unlinkUserInv($userInv);
            ++$count;
        }
        foreach ($this->cR->findByTelegramChatId($chatId) as $client) {
            $client->setClientTelegramChatId(null);
            $this->cR->save($client);
            ++$count;
        }
        return $count;
    }

    public function unlinkUserInv(UserInv $userInv): void
    {
        $userInv->setTelegramChatId(null);
        $userInv->setConsentTelegramOutstanding(false);
        $this->uiR->save($userInv);
    }

    private function spend(Token $token): void
    {
        $token->setToken(self::USED_TOKEN_PREFIX . (string) time());
        $this->tR->save($token);
    }
}
