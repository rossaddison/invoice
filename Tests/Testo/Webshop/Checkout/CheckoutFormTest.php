<?php

declare(strict_types=1);

namespace Tests\Testo\Webshop\Checkout;

use App\Webshop\Checkout\CheckoutForm;
use App\Webshop\Delivery\DeliveryAddress;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CheckoutForm::fillFromDeliveryAddress() — see that method's
 * own docblock for why all four fields map straight across from the
 * navbar's "Deliver to" widget.
 */
#[Test]
final class CheckoutFormTest
{
    public function fillsAllFourFieldsFromTheDeliveryAddress(): void
    {
        $form = new CheckoutForm();
        $form->fillFromDeliveryAddress(new DeliveryAddress('John', 'Smith', 'Glasgow', 'G32 6AB'));

        $customer = $form->toCustomerArray();
        Assert::same('John', $customer['name']);
        Assert::same('Smith', $customer['surname']);
        Assert::same('Glasgow', $customer['city']);
        Assert::same('G32 6AB', $customer['zip']);
    }

    public function neverOverwritesAFieldThatsAlreadySet(): void
    {
        $form = new CheckoutForm();
        $form->fillFromDeliveryAddress(new DeliveryAddress('Anne', 'Jones', 'Edinburgh', 'EH1 1AA'));
        $form->fillFromDeliveryAddress(new DeliveryAddress('John', 'Smith', 'Glasgow', 'G32 6AB'));

        $customer = $form->toCustomerArray();
        Assert::same('Anne', $customer['name']);
        Assert::same('Jones', $customer['surname']);
        Assert::same('Edinburgh', $customer['city']);
        Assert::same('EH1 1AA', $customer['zip']);
    }
}
