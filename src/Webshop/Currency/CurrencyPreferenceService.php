<?php

declare(strict_types=1);

namespace App\Webshop\Currency;

use Yiisoft\Session\SessionInterface;

/**
 * The navbar currency toggle's own choice — session-only, exactly like
 * `App\Webshop\Cart\CartService`/`App\Webshop\Delivery\DeliveryAddressService`
 * (no database row). Only ever `NATIVE` or `DOCUMENT`: this shop's dual
 * currency setup (see `CurrencyInfo`'s own docblock) is a single pair,
 * not an arbitrary currency list, so there's nothing else to choose
 * between.
 */
final readonly class CurrencyPreferenceService
{
    public const NATIVE = 'native';
    public const DOCUMENT = 'document';

    private const SESSION_KEY = 'currency_preference';

    public function __construct(private SessionInterface $session)
    {
    }

    public function get(): string
    {
        return $this->session->get(self::SESSION_KEY) === self::DOCUMENT ? self::DOCUMENT : self::NATIVE;
    }

    public function set(string $preference): void
    {
        $this->session->set(
            self::SESSION_KEY,
            $preference === self::DOCUMENT ? self::DOCUMENT : self::NATIVE,
        );
    }
}
