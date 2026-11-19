<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * INTERIM gate for the public /as4/receive route.
 *
 * The route is documented as secured by AS4/ebMS3 message signing, but nothing on the inbound path verifies a
 * signature (As4SignatureVerifier is not called, and it would take the key from the message itself, so it
 * would prove integrity, not who sent it). The sender is the unsigned senderPartyId inside the message, and
 * anyone who knows a registered client's public Peppol endpoint id could post a forged Order or Invoice.
 *
 * Until messages are verified against the registered partner's certificate, the caller must present the
 * shared secret AS4_RECEIVE_SECRET, as the X-As4-Receive-Secret header or the "secret" query parameter
 * (an endpoint URL can carry it). With no secret configured every call is refused, so the route is closed
 * unless it has been switched on deliberately.
 */
final class As4ReceiveGuard
{
    public const string SECRET_NOT_SET = 'receive secret not set';
    public const string SECRET_MISMATCH = 'receive secret missing or wrong';

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
