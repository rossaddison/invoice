<?php

declare(strict_types=1);

namespace App\Invoice\Telegram;

/**
 * What came of a person pressing Start in the bot with a link token.
 */
enum LinkOutcome
{
    /** the chat is now linked to the account that created the token */
    case Linked;

    /** no such token, wrong shape, already used, or its account has no profile row */
    case InvalidToken;

    /** the token was real but older than TelegramLinkService::TOKEN_LIFETIME_SECONDS; it is spent either way */
    case Expired;
}
