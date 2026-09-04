<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use App\Invoice\Setting\SettingRepository as SR;

/**
 * Chooses which Access Point actually sends, per the
 * peppol_access_point_provider setting — read fresh on every call, not
 * baked into a DI binding chosen once at container build time, so
 * switching providers in Settings takes effect immediately, the same way
 * every other runtime-configurable choice in this app already works
 * (e.g. SettingRepository::getSetting() calls elsewhere are never cached
 * past request scope).
 *
 * Defaults to Storecove: Oxalis has never actually been live in this
 * deployment (docs/OXALIS_INTEGRATION.md's own architecture doc describes
 * it as a plan, still Phase 1 — Infrastructure), so there is no existing
 * working behaviour defaulting to it would protect.
 */
final readonly class PeppolSendServiceRouter implements PeppolSendServiceInterface
{
    private const string DEFAULT_PROVIDER = 'storecove';

    public function __construct(
        private OxalisPeppolSendService $oxalis,
        private StorecovePeppolSendService $storecove,
        private SR $sR,
    ) {
    }

    #[\Override]
    public function send(
        int $invId,
        string $ublXml,
        string $recipientId,
        string $documentTypeId =
            'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        string $processId =
            'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
    ): PeppolMessage {
        return $this->resolve()->send($invId, $ublXml, $recipientId, $documentTypeId, $processId);
    }

    #[\Override]
    public function retry(
        PeppolMessage $message,
        string $ublXml,
    ): PeppolMessage {
        return $this->resolve()->retry($message, $ublXml);
    }

    private function resolve(): PeppolSendServiceInterface
    {
        $provider = $this->sR->getSetting('peppol_access_point_provider');
        if ($provider === '') {
            $provider = self::DEFAULT_PROVIDER;
        }
        return $provider === 'oxalis' ? $this->oxalis : $this->storecove;
    }
}
