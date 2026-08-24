<?php

declare(strict_types=1);

namespace App\Webshop\Checkout;

use App\Webshop\Delivery\DeliveryAddress;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

/**
 * Matches `App\Api\OrderService::createOrder()`'s `$customer` array shape
 * exactly — name, surname, email required, the rest optional.
 */
final class CheckoutForm extends FormModel implements RulesProviderInterface
{
    private string $name = '';
    private string $surname = '';
    private string $email = '';
    private string $address1 = '';
    private string $address2 = '';
    private string $city = '';
    private string $zip = '';
    private string $country = '';
    private string $phone = '';

    #[\Override]
    public function getFormName(): string
    {
        return 'Checkout';
    }

    #[\Override]
    public function getRules(): array
    {
        return [
            'name' => [new Required(), new Length(max: 100)],
            'surname' => [new Required(), new Length(max: 100)],
            'email' => [new Required(), new Email()],
            'address1' => [new Length(max: 100)],
            'address2' => [new Length(max: 100)],
            'city' => [new Length(max: 100)],
            'zip' => [new Length(max: 20)],
            'country' => [new Length(max: 100)],
            'phone' => [new Length(max: 30)],
        ];
    }

    /**
     * Prefills from the navbar's "Deliver to" widget (App\Webshop\Delivery)
     * — `DeliveryAddress::$name`/`$surname` match this form's own field
     * names on purpose, so all four fields map straight across with no
     * guesswork. Only touches fields still at their default (`''`) —
     * called once, right after construction in
     * `CheckoutController::index()`, before any POST data could have
     * populated them, but written defensively either way rather than
     * assuming that call order.
     */
    public function fillFromDeliveryAddress(DeliveryAddress $address): void
    {
        if ($this->name === '') {
            $this->name = $address->name;
        }
        if ($this->surname === '') {
            $this->surname = $address->surname;
        }
        if ($this->city === '') {
            $this->city = $address->city;
        }
        if ($this->zip === '') {
            $this->zip = $address->postcode;
        }
    }

    /**
     * All nine keys are always present — "the rest optional" in this
     * class's own docblock describes `OrderService::createOrder()`'s
     * side (which tolerates a missing key), not this method's actual
     * return shape.
     *
     * @return array{name: string, surname: string, email: string, address1: string,
     *     address2: string, city: string, zip: string, country: string, phone: string}
     */
    public function toCustomerArray(): array
    {
        return [
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'zip' => $this->zip,
            'country' => $this->country,
            'phone' => $this->phone,
        ];
    }
}
