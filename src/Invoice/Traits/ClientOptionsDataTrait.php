<?php

declare(strict_types=1);

namespace App\Invoice\Traits;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Client\ClientRepository as cR;
use App\Invoice\Entity\PostalAddress;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Trait providing options-data helper methods for the Client domain.
 *
 * Requires the consuming class to expose:
 * @property TranslatorInterface $translator
 */
trait ClientOptionsDataTrait
{
    /**
     * @return array<array-key, string>
     */
    private function optionsDataGender(): array
    {
        $optionsDataGender = [];
        $genders_array = [
            $this->translator->translate('gender.male'),
            $this->translator->translate('gender.female'),
            $this->translator->translate('gender.other'),
        ];
        foreach ($genders_array as $key => $val) {
            $optionsDataGender[(string) $key] = $val;
        }
        return $optionsDataGender;
    }

    /**
     * @param EntityReader $postalAddresses
     * @return array<int, string>
     */
    private function optionsDataPostalAddress(EntityReader $postalAddresses): array
    {
        $optionsDataPostalAddress = [];
        /** @var PostalAddress $postalAddress */
        foreach ($postalAddresses as $postalAddress) {
            $paId = (int) $postalAddress->getId();
            if ($paId > 0) {
                $optionsDataPostalAddress[$paId] = implode(',', $this->buildAddressParts($postalAddress));
            }
        }
        return $optionsDataPostalAddress;
    }

    /**
     * Collect the non-empty address components of a PostalAddress in display order.
     *
     * @param PostalAddress $postalAddress
     * @return array<int, string>
     */
    private function buildAddressParts(PostalAddress $postalAddress): array
    {
        $parts = [];
        if ($postalAddress->getStreetName()) {
            $parts[] = $postalAddress->getStreetName();
        }
        if ($postalAddress->getAdditionalStreetName()) {
            $parts[] = $postalAddress->getAdditionalStreetName();
        }
        if ($postalAddress->getBuildingNumber()) {
            $parts[] = $postalAddress->getBuildingNumber();
        }
        if ($postalAddress->getCityName()) {
            $parts[] = $postalAddress->getCityName();
        }
        if ($postalAddress->getPostalzone()) {
            $parts[] = $postalAddress->getPostalzone();
        }
        if ($postalAddress->getCountrysubentity()) {
            $parts[] = $postalAddress->getCountrysubentity();
        }
        if ($postalAddress->getCountry()) {
            $parts[] = $postalAddress->getCountry();
        }
        return $parts;
    }

    /**
     * @param cR $cR
     * @return array<string, string>
     */
    public function optionsDataClientNameDropdownFilter(cR $cR): array
    {
        $optionsDataClientName = [];
        $clients = $cR->findAllPreloaded();
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $firstname = $client->getClientName();
            $optionsDataClientName[$firstname] = $firstname;
        }
        return $optionsDataClientName;
    }

    /**
     * @param cR $cR
     * @return array<string, string>
     */
    public function optionsDataClientSurnameDropdownFilter(cR $cR): array
    {
        $optionsDataClientSurname = [];
        $clients = $cR->findAllPreloaded();
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $surname = $client->getClientSurname();
            if (null != $surname) {
                $optionsDataClientSurname[$surname] = $surname;
            }
        }
        return $optionsDataClientSurname;
    }

    /**
     * @return array<string, string>
     */
    public function optionsDataClientFrequencyDropdownFilter(): array
    {
        $optionsDataClientFrequency = [];
        $optionsDataClientFrequency['None'] = 'None';
        $optionsDataClientFrequency['Only 1'] = 'Only 1';
        $optionsDataClientFrequency['Daily'] = 'Daily';
        $optionsDataClientFrequency['Weekly'] = 'Weekly';
        $optionsDataClientFrequency['Monthly'] = 'Monthly';
        $optionsDataClientFrequency['2 Monthly'] = '2 Monthly';
        $optionsDataClientFrequency['3 Monthly'] = '3 Monthly';
        $optionsDataClientFrequency['6 Monthly'] = '6 Monthly';
        return $optionsDataClientFrequency;
    }
}
