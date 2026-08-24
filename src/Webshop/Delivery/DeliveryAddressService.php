<?php

declare(strict_types=1);

namespace App\Webshop\Delivery;

use Yiisoft\Session\SessionInterface;

/**
 * The navbar's "Deliver to" widget — session-only, exactly like
 * `App\Webshop\Cart\CartService` (no database row, no account of any
 * kind required). Not the source of truth for an actual order either —
 * `App\Webshop\Checkout\CheckoutForm` still collects everything itself at
 * submit time and is the only thing that ever reaches `OrderService`;
 * `CheckoutForm::fillFromDeliveryAddress()` only ever prefills its fields
 * from this, as a convenience, on the initial page load.
 */
final readonly class DeliveryAddressService
{
    private const SESSION_KEY = 'delivery_address';
    private const MAX_NAME_LENGTH = 100;
    private const MAX_POSTCODE_LENGTH = 20;

    public function __construct(private SessionInterface $session)
    {
    }

    public function get(): ?DeliveryAddress
    {
        /** @var mixed $raw */
        $raw = $this->session->get(self::SESSION_KEY);
        if (!is_array($raw)) {
            return null;
        }

        /** @var mixed $name */
        $name = $raw['name'] ?? null;
        /** @var mixed $surname */
        $surname = $raw['surname'] ?? null;
        if ((!is_string($name) || $name === '') && (!is_string($surname) || $surname === '')) {
            return null;
        }

        /** @var mixed $city */
        $city = $raw['city'] ?? null;
        /** @var mixed $postcode */
        $postcode = $raw['postcode'] ?? null;

        return new DeliveryAddress(
            name: is_string($name) ? $name : '',
            surname: is_string($surname) ? $surname : '',
            city: is_string($city) ? $city : '',
            postcode: is_string($postcode) ? $postcode : '',
        );
    }

    /** Blank $name and $surname together clear the address — same "empty means unset" shape as CartService::updateQuantity()'s zero-quantity case. */
    public function set(string $name, string $surname, string $city, string $postcode): void
    {
        $name = mb_substr(trim($name), 0, self::MAX_NAME_LENGTH);
        $surname = mb_substr(trim($surname), 0, self::MAX_NAME_LENGTH);
        if ($name === '' && $surname === '') {
            $this->session->remove(self::SESSION_KEY);
            return;
        }

        $this->session->set(self::SESSION_KEY, [
            'name' => $name,
            'surname' => $surname,
            'city' => mb_substr(trim($city), 0, self::MAX_NAME_LENGTH),
            'postcode' => mb_substr(trim($postcode), 0, self::MAX_POSTCODE_LENGTH),
        ]);
    }
}
