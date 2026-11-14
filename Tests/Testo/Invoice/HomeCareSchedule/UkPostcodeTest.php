<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareSchedule;

use App\Invoice\Helpers\GoogleMapsLink;
use App\Invoice\HomeCareSchedule\UkPostcode;
use Testo\Assert;
use Testo\Test;

/**
 * A run's starting postcode and the Google Maps link made from it.
 */
#[Test]
final class UkPostcodeTest
{
    public function postcodesAsTheyAreTypedBecomeTheOneCanonicalForm(): void
    {
        foreach (['ML2 0NE', 'ml2 0ne', 'ML20NE', ' ml2  0ne ', 'Ml2 0Ne'] as $typed) {
            Assert::same(UkPostcode::normalise($typed), 'ML2 0NE');
        }
        Assert::same(UkPostcode::normalise('ML1 2FD'), 'ML1 2FD');
        Assert::same(UkPostcode::normalise('G32 6AB'), 'G32 6AB');
        Assert::same(UkPostcode::normalise('EH1 1YZ'), 'EH1 1YZ');
        Assert::same(UkPostcode::normalise('W1A 1AA'), 'W1A 1AA');
    }

    public function somethingThatIsNotAPostcodeIsNull(): void
    {
        foreach (['', '  ', 'Berryhill', 'ML2', 'ML2 0N', 'ML2 0NEE', '12345', 'ML2-0NE', 'ML2 ONE'] as $text) {
            Assert::null(UkPostcode::normalise($text));
            Assert::false(UkPostcode::isValid($text));
        }
        Assert::true(UkPostcode::isValid('ml20ne'));
    }

    public function directionsToAPostcodeAreAGoogleMapsDrivingLink(): void
    {
        Assert::same(
            GoogleMapsLink::directionsTo('ML2 0NE'),
            'https://www.google.com/maps/dir/?api=1&destination=ML2%200NE&travelmode=driving',
        );
        Assert::same(GoogleMapsLink::directionsTo('51.1,-3.8'), 'https://www.google.com/maps/dir/?api=1&destination=51.1%2C-3.8&travelmode=driving');
    }

    public function aSearchLinkEncodesTheQuery(): void
    {
        Assert::same(GoogleMapsLink::search('ML2 0NE'), 'https://www.google.com/maps/search/?api=1&query=ML2%200NE');
        Assert::same(GoogleMapsLink::search('12 High St & Co'), 'https://www.google.com/maps/search/?api=1&query=12%20High%20St%20%26%20Co');
    }
}