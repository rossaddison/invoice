<?php

declare(strict_types=1);

namespace App\Invoice\Dwelling;

use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Invoice\Client\ClientDwellingRepository;
use Yiisoft\Data\Cycle\Reader\EntityReader;

final readonly class DwellingService
{
    public function __construct(
        private DwellingRepository $repository,
        private ClientDwellingRepository $clientDwellingRepository,
    ) {
    }

    /**
     * @param Dwelling $model
     * @param array $array
     */
    public function saveDwelling(Dwelling $model, array $array): void
    {
        $this->applyDwellingIdentityFields($model, $array);
        $this->applyDwellingLocationFields($model, $array);
        $this->repository->save($model);
    }

    private function applyDwellingIdentityFields(Dwelling $model, array $array): void
    {
        isset($array['family_id']) ? $model->setFamilyId((int) $array['family_id']) : '';
        isset($array['house_number_numeric']) ? $model->setHouseNumberNumeric((int) $array['house_number_numeric']) : '';
        isset($array['house_number_suffix']) ? $model->setHouseNumberSuffix(self::nullableString($array['house_number_suffix'])) : '';
        isset($array['flat_unit']) ? $model->setFlatUnit(self::nullableString($array['flat_unit'])) : '';
    }

    private function applyDwellingLocationFields(Dwelling $model, array $array): void
    {
        isset($array['postcode']) ? $model->setPostcode((string) $array['postcode']) : '';
        isset($array['latitude']) ? $model->setLatitude(self::nullableFloat($array['latitude'])) : '';
        isset($array['longitude']) ? $model->setLongitude(self::nullableFloat($array['longitude'])) : '';
        isset($array['source']) ? $model->setSource(self::nullableString($array['source'])) : '';
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === '' ? null : (string) $value;
    }

    private static function nullableFloat(mixed $value): ?float
    {
        return $value === '' ? null : (float) $value;
    }

    public function deleteDwelling(Dwelling $model): void
    {
        $this->repository->delete($model);
    }

    /**
     * Dwellings on a street with no Client currently occupying them — the worker canvassing dropdown's
     * "which houses can I sign up new interest at" list. Composes ClientRepository (the "already claimed"
     * side) with DwellingRepository (the anti-join), keeping each repository self-contained rather than
     * having one reach into the other's table directly — same composition-at-the-service-layer pattern
     * CategorySecondaryService already uses with CategoryPrimaryRepository.
     */
    public function repoUnclaimedDwellings(int $familyId): EntityReader
    {
        return $this->repository->repoUnclaimedByFamilyIdQuery(
            $familyId,
            $this->clientDwellingRepository->repoClaimedDwellingIds(),
        );
    }

    /**
     * Resolves a customer-entered house number (free text, e.g. "12" or "12A") against an existing
     * Dwelling on the given Family — exact match on family_id + parsed house number/suffix — or creates
     * a new one when no Dwelling has been pre-entered for that street/number combination yet (source
     * 'signup_freehand', matching HomeCareSignupForm's option 2: the public form's free-text street/
     * building-number fields stay unchanged, and this resolves them at confirm-time rather than
     * requiring the customer to pick from a pre-populated list). Mirrors
     * FamilyService::findOrCreateByStreetName()'s exact shape.
     */
    public function findOrCreateDwelling(int $familyId, string $rawHouseNumber): Dwelling
    {
        [$numeric, $suffix] = self::parseHouseNumber($rawHouseNumber);
        $existing = $this->repository->repoDwellingByFamilyIdAndHouseNumberQuery($familyId, $numeric, $suffix);
        if ($existing !== null) {
            return $existing;
        }
        $dwelling = new Dwelling();
        $this->saveDwelling($dwelling, [
            'family_id' => $familyId,
            'house_number_numeric' => $numeric,
            'house_number_suffix' => $suffix ?? '',
            'source' => 'signup_freehand',
        ]);
        return $dwelling;
    }

    /**
     * Splits free-text house-number entry into the numeric portion and any trailing letter suffix,
     * matching the split-column natural-sort design ({@see Dwelling}'s own class docblock) — "12A"
     * becomes numeric 12 / suffix "A", so it sorts correctly between "12" and "13". Input that doesn't
     * start with digits at all (an unexpected free-text entry) falls back to numeric 0 with the raw
     * trimmed text — truncated to fit the column — kept as the suffix, rather than silently discarding
     * it.
     *
     * @return array{0: int, 1: ?string}
     */
    private static function parseHouseNumber(string $raw): array
    {
        $trimmed = trim($raw);
        if (preg_match('/^(\d+)\s*([A-Za-z]*)$/', $trimmed, $matches) === 1) {
            $suffix = strtoupper($matches[2]);
            return [(int) $matches[1], $suffix === '' ? null : $suffix];
        }
        return [0, $trimmed === '' ? null : mb_substr(strtoupper($trimmed), 0, 5)];
    }
}
