<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\Family\FamilyRepository;

/**
 * Builds the Client form's Dwelling (service address) dropdown: every Dwelling
 * not already claimed by another Client, plus the Client's own current one so
 * an edit never loses it. A Dwelling belongs to at most one Client (see
 * {@see Dwelling}), so a claimed one is also rejected on save via
 * {@see self::isClaimedByAnother()}.
 */
final readonly class ClientDwellingPicker
{
    public function __construct(
        private DwellingRepository $dwellingRepository,
        private ClientDwellingRepository $clientDwellingRepository,
        private FamilyRepository $familyRepository,
    ) {
    }

    /**
     * @return array{options: array<int, string>, data: array<int, array{street: string, number: string, flat: string, postcode: string}>}
     */
    public function forClient(?int $currentDwellingId): array
    {
        $claimed = $this->clientDwellingRepository->repoClaimedDwellingIds();
        $streets = $this->familyRepository->optionsDataAllFamilyNames();
        $rows = [];
        /** @var Dwelling $dwelling */
        foreach ($this->dwellingRepository->findAllPreloaded() as $dwelling) {
            $id = $dwelling->reqId();
            if ($id !== $currentDwellingId && in_array($id, $claimed, true)) {
                continue;
            }
            $street = $streets[$dwelling->getFamilyId() ?? 0] ?? '';
            $rows[] = [
                'id' => $id,
                'sort' => [$street, $dwelling->getHouseNumberNumeric(), $dwelling->getHouseNumberSuffix() ?? ''],
                'data' => [
                    'street' => $street,
                    'number' => $dwelling->getHouseNumberDisplay(),
                    'flat' => $dwelling->getFlatUnit() ?? '',
                    'postcode' => $dwelling->getPostcode(),
                ],
            ];
        }
        usort($rows, static fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);
        $options = [];
        $data = [];
        foreach ($rows as $row) {
            $options[$row['id']] = implode(', ', array_filter($row['data'], static fn (string $p): bool => $p !== ''));
            $data[$row['id']] = $row['data'];
        }
        return ['options' => $options, 'data' => $data];
    }

    public function isClaimedByAnother(int $dwellingId, ?int $clientId): bool
    {
        return $this->clientDwellingRepository->isClaimedByAnother($dwellingId, $clientId);
    }
}
