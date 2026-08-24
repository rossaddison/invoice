<?php

declare(strict_types=1);

namespace App\Webshop\Delivery;

/**
 * What the navbar's "Deliver to" widget shows — a per-session display
 * preference, not a saved account address (see
 * `DeliveryAddressService`'s own docblock). `$name`/`$surname` match
 * `App\Webshop\Checkout\CheckoutForm`'s own field names on purpose:
 * `CheckoutForm::fillFromDeliveryAddress()` prefills straight from
 * these, no guesswork splitting a single freeform name.
 */
final readonly class DeliveryAddress
{
    public function __construct(
        public string $name,
        public string $surname,
        public string $city,
        public string $postcode,
    ) {
    }

    /** The widget's first line — e.g. "Deliver to John Smith", or just one of the two if only one was given. */
    public function fullName(): string
    {
        return trim($this->name . ' ' . $this->surname);
    }

    /** The widget's second line — e.g. "Glasgow G32 6AB", or just one of the two if only one was given. */
    public function locationLine(): string
    {
        return trim($this->city . ' ' . $this->postcode);
    }
}
