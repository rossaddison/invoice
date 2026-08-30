<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
use App\Invoice\As4\As4MessageState;
use Yiisoft\Aliases\Aliases;

/**
 * Assembles the row/section data for the public Peppol Access Point status
 * page (SiteController::peppolStatus()). Split out of SiteController itself
 * -- see docs/SONARQUBE_S107_APPLICATION_SERVICE.md's convention -- since
 * these three helpers share nothing with SiteController's other job
 * (rendering gated static pages); bundling them there just to keep one
 * class was what pushed it over Sonar's 20-method ceiling (S1448).
 */
final class PeppolStatusPageBuilder
{
    public function __construct(
        private readonly PeppolMessageRepository $peppolMessageRepository,
        private readonly CycleOrmAs4MessageRepository $as4MessageRepository,
        private readonly Aliases $aliases,
    ) {}

    /**
     * @return array{
     *     rows: list<array{name: string, sdk_version: string|null, sandbox_status: string, sandbox_tested_at: string|null, notes: string}>,
     *     referenceProviders: list<array{name: string, regions: string, notes: string}>,
     *     as4Bilateral: array{tested: bool, tested_at: string|null, peer_party_id: string|null},
     * }
     */
    public function build(string $currentProvider): array
    {
        $lastSent = $this->peppolMessageRepository->mostRecentByStatus('SENT');

        // PeppolMessage has no column recording which provider actually
        // sent it -- StorecovePeppolSendService and OxalisPeppolSendService
        // both write to the same table. Attributing an existing SENT row
        // to "whichever provider is currently configured" is the best
        // available signal without a schema change, and holds for the
        // common case (providers aren't switched often) -- noted here
        // rather than silently assumed.
        $storecoveTested = $currentProvider === 'storecove' && $lastSent !== null;
        $sentAt = $lastSent?->getSentAt();

        $rows = [
            [
                'name' => 'Storecove',
                'sdk_version' => $this->storecoveClientVersion(),
                'sandbox_status' => $storecoveTested ? 'pass' : 'untested',
                'sandbox_tested_at' => $storecoveTested && $sentAt !== null
                    ? $sentAt->format('Y-m-d') : null,
                'notes' => 'Managed Access Point API — the default provider.',
            ],
            [
                'name' => 'Oxalis',
                'sdk_version' => null,
                'sandbox_status' => 'untested',
                'sandbox_tested_at' => null,
                'notes' => 'Self-hosted AS4 gateway — not yet used for a'
                    . ' real send in this deployment.',
            ],
        ];

        return [
            'rows' => $rows,
            'referenceProviders' => $this->surveyedAccessPointProviders(),
            'as4Bilateral' => $this->as4BilateralStatus(),
        ];
    }

    /**
     * AS4 Bilateral is a separate, self-hosted AS4 stack (src\Invoice\As4)
     * used for BIS Advanced Ordering and point-to-point testing — it is
     * never selected via the Peppol Access Point Provider setting the
     * rows in build() cover, so it gets its own section on the page rather
     * than a third row that would misrepresent it as a selectable
     * provider. Status comes from a real As4Message row this app actually
     * sent and got a receipt back for (As4MessageDispatcher persists one
     * per send — see docs\AS4_BILATERAL_ROADMAP.md), not a synthetic ping.
     *
     * @return array{tested: bool, tested_at: string|null, peer_party_id: string|null}
     */
    private function as4BilateralStatus(): array
    {
        $lastReceipt = $this->as4MessageRepository->mostRecentByState(As4MessageState::receiptReceived);
        $receivedAt = $lastReceipt?->getReceiptInfo()->getReceiptReceivedAt();

        return [
            'tested' => $lastReceipt !== null,
            'tested_at' => $receivedAt !== null
                ? $receivedAt->format('Y-m-d') : null,
            'peer_party_id' => $lastReceipt?->getRouting()->getReceiverPartyId(),
        ];
    }

    /**
     * Other Peppol Access Point providers surveyed for future regional
     * coverage — deliberately not wired into PeppolSendServiceRouter or
     * the Access Point Provider settings dropdown, so this app never
     * offers a choice with nothing real behind it (the exact class of bug
     * this session spent several PRs fixing for the two providers that
     * ARE wired up). None of this app's own message-send history applies
     * to these, so they get their own section on the page rather than
     * reusing build()'s "sandbox tested ✓" badge, which specifically means
     * this app sent a real message through that provider — not true here.
     *
     * Research, 2026-08-29: Storecove (already integrated) is accredited
     * across all four original Peppol territories (EU, Australia, New
     * Zealand, Singapore) plus expanding into Malaysia and CTC-clearance
     * countries (Italy, Poland, Romania, India) — most realistic regional
     * ground is already covered by the one integration this app has.
     * Checked against a real, no-sales-call sandbox before including
     * anything else: Tradeshift's sandbox is only documented in their own
     * support knowledgebase, with no self-serve signup path found on
     * their own site; Basware's requires going through their support team
     * to provision; Pagero's current ownership is genuinely unclear as of
     * this writing (a 2023-24 Vertex acquisition bid was withdrawn after
     * competing Thomson Reuters/Avalara offers) — all three excluded
     * rather than guessed at. PeppolSoft is the one that held up: a
     * complimentary sandbox with no sales gate (their own site: "Sandbox
     * environment free"), transparent $0.10/invoice pricing with no
     * subscription or minimum, and explicit UK coverage "launching soon,
     * ahead of the 2029 mandate" — directly relevant to the HMRC timeline
     * this project already tracks (see project_uk_einvoicing_2029 memory).
     *
     * @return list<array{name: string, regions: string, notes: string}>
     */
    private function surveyedAccessPointProviders(): array
    {
        return [
            [
                'name' => 'PeppolSoft',
                'regions' => 'US (DBNAlliance), EU, UK (launching, ahead of the 2029 mandate)',
                'notes' => 'Free complimentary sandbox, no sales call required. Pay-per-transaction'
                    . ' ($0.10/invoice), no subscription or minimum. Not yet integrated into this app.',
            ],
        ];
    }

    /**
     * rossaddison/storecove-client is a VCS (git) dependency, not a tagged
     * release -- composer.lock's 'version' field for it is literally the
     * branch name ('dev-master'), the same simple field gateway-status
     * reads for every gateway package. Shown as-is rather than resolved
     * to a commit hash, for consistency with how every row on this page
     * (and gateway-status's own rows) sources its version the same way.
     */
    private function storecoveClientVersion(): ?string
    {
        $path = $this->aliases->get('@root') . '/composer.lock';
        $contents = is_file($path) ? file_get_contents($path) : false;
        if ($contents === false) {
            return null;
        }

        /** @var array{packages?: list<array{name?: string, version?: string}>}|null $decoded */
        $decoded = json_decode($contents, true);
        $packages = is_array($decoded) ? ($decoded['packages'] ?? []) : [];

        foreach ($packages as $package) {
            if (($package['name'] ?? null) === 'rossaddison/storecove-client') {
                return $package['version'] ?? null;
            }
        }

        return null;
    }
}
