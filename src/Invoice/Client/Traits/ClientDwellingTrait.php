<?php

declare(strict_types=1);

namespace App\Invoice\Client\Traits;

use App\Invoice\Client\ClientDwellingPicker;

/**
 * Trait providing the Dwelling (HomeCare service address) dropdown data and
 * the one-Client-per-Dwelling guard for the Client add/edit form. Extracted
 * from ClientController to stay within SonarQube S1448 (≤ 20 methods).
 *
 * Requires the consuming class to expose:
 * @property \Yiisoft\Translator\TranslatorInterface $translator
 */
trait ClientDwellingTrait
{
    /**
     * Dwelling dropdown options and the per-Dwelling data the form's
     * client-addresses.ts uses for its summary line and "use as billing" button.
     *
     * @return array{optionsDataDwellings: array<int|string, string>, dwellingData: array<int, array{street: string, number: string, flat: string, postcode: string}>}
     */
    private function dwellingParameters(ClientDwellingPicker $picker, ?int $currentDwellingId): array
    {
        $built = $picker->forClient($currentDwellingId);
        return [
            'optionsDataDwellings' => ['' => $this->translator->translate('client.dwelling.none')] + $built['options'],
            'dwellingData'         => $built['data'],
        ];
    }

    /**
     * @param array<array-key, mixed> $body
     */
    private static function postedDwellingId(array $body): ?int
    {
        $id = (int) ($body['dwelling_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function dwellingClaimed(?int $dwellingId, ?int $clientId, ClientDwellingPicker $picker): bool
    {
        return $dwellingId !== null && $dwellingId > 0 && $picker->isClaimedByAnother($dwellingId, $clientId);
    }

    /**
     * @return array{dwelling_id: list<string>}
     */
    private function dwellingClaimedErrors(): array
    {
        return ['dwelling_id' => [$this->translator->translate('client.dwelling.already.claimed')]];
    }
}
