<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

/**
 * Decides whether a call to the public /peppol/inbound/delivery route comes from the access point.
 *
 * The route cannot sit behind a login (the Oxalis access point calls it with no session), and it used to
 * check nothing: anyone who knew or guessed a messageId could mark an outbound e-invoice DELIVERED. The
 * caller must now present the shared secret from PEPPOL_INBOUND_SECRET, as the X-Peppol-Callback-Secret
 * header or, for an access point that can only be given a URL, as the "secret" query parameter. With no
 * secret configured every call is refused, so the route fails closed.
 */
final class PeppolInboundGuard
{
    public const string SECRET_NOT_SET = 'callback secret not set';
    public const string SECRET_MISMATCH = 'callback secret missing or wrong';

    /** @return string|null why the call is refused, or null when it may proceed */
    public static function refusal(string $configuredSecret, string $presentedSecret): ?string
    {
        if ($configuredSecret === '') {
            return self::SECRET_NOT_SET;
        }
        // constant time: the comparison must not reveal how many leading characters were right
        return hash_equals($configuredSecret, $presentedSecret) ? null : self::SECRET_MISMATCH;
    }
}
