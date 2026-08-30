<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Peppol;

use App\Infrastructure\Persistence\As4Message\As4Message;
use App\Infrastructure\Persistence\As4Message\As4MessageParams;
use App\Infrastructure\Persistence\As4Message\CycleOrmAs4MessageRepository;
use App\Infrastructure\Persistence\PeppolMessage\PeppolMessage;
use App\Invoice\As4\As4MessageState;
use App\Invoice\Peppol\PeppolMessageRepository;
use App\Invoice\Peppol\PeppolStatusPageBuilder;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;

/**
 * Covers PeppolStatusPageBuilder::build() end-to-end, including its three
 * private helpers (as4BilateralStatus, surveyedAccessPointProviders,
 * storecoveClientVersion) — all reachable only through build(), so no
 * separate test entry point exists for them individually.
 */
#[Test]
final class PeppolStatusPageBuilderTest
{
    private const string SENT_DATE = '2026-08-29';

    private function makeAs4Message(): As4Message
    {
        return new As4Message(new As4MessageParams(
            messageId:        'msg-001@as4.example.com',
            conversationId:   'conv-001',
            senderPartyId:    '0088:1234567890123',
            senderRole:       'Seller',
            receiverPartyId:  '0088:9876543210987',
            receiverRole:     'Buyer',
            service:          'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
            action:           'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            receiverEndpoint: 'https://ap.example.com/as4',
            soapMessage:      '<env:Envelope/>',
        ));
    }

    /** @return array{aliases: Aliases, dir: string} */
    private function makeComposerLockFixture(?string $storecoveClientVersion): array
    {
        $dir = sys_get_temp_dir() . '/peppol-status-builder-test-' . bin2hex(random_bytes(8));
        mkdir($dir);
        $packages = $storecoveClientVersion === null
            ? []
            : [['name' => 'rossaddison/storecove-client', 'version' => $storecoveClientVersion]];
        $json = json_encode(['packages' => $packages]);
        file_put_contents($dir . '/composer.lock', $json === false ? '{}' : $json);

        return ['aliases' => new Aliases(['@root' => $dir]), 'dir' => $dir];
    }

    public function buildIncludesStorecoveTestedRowWhenCurrentProviderMatchesAndMessageWasSent(): void
    {
        $peppolMessage = new PeppolMessage(status: 'SENT');
        $peppolMessage->setSentAt(new \DateTimeImmutable(self::SENT_DATE));

        /** @var PeppolMessageRepository&m\MockInterface $pmR */
        $pmR = m::mock(PeppolMessageRepository::class);
        $pmR->shouldReceive('mostRecentByStatus')->with('SENT')->andReturn($peppolMessage);

        /** @var CycleOrmAs4MessageRepository&m\MockInterface $as4R */
        $as4R = m::mock(CycleOrmAs4MessageRepository::class);
        $as4R->shouldReceive('mostRecentByState')->andReturn(null);

        $fixture = $this->makeComposerLockFixture('dev-master');
        $builder = new PeppolStatusPageBuilder($pmR, $as4R, $fixture['aliases']);

        $data = $builder->build('storecove');

        Assert::same('Storecove', $data['rows'][0]['name']);
        Assert::same('dev-master', $data['rows'][0]['sdk_version']);
        Assert::same('pass', $data['rows'][0]['sandbox_status']);
        Assert::same(self::SENT_DATE, $data['rows'][0]['sandbox_tested_at']);
    }

    public function buildMarksStorecoveUntestedWhenCurrentProviderIsDifferent(): void
    {
        $peppolMessage = new PeppolMessage(status: 'SENT');
        $peppolMessage->setSentAt(new \DateTimeImmutable(self::SENT_DATE));

        /** @var PeppolMessageRepository&m\MockInterface $pmR */
        $pmR = m::mock(PeppolMessageRepository::class);
        $pmR->shouldReceive('mostRecentByStatus')->andReturn($peppolMessage);

        /** @var CycleOrmAs4MessageRepository&m\MockInterface $as4R */
        $as4R = m::mock(CycleOrmAs4MessageRepository::class);
        $as4R->shouldReceive('mostRecentByState')->andReturn(null);

        $fixture = $this->makeComposerLockFixture(null);
        $builder = new PeppolStatusPageBuilder($pmR, $as4R, $fixture['aliases']);

        $data = $builder->build('oxalis');

        Assert::same('untested', $data['rows'][0]['sandbox_status']);
        Assert::null($data['rows'][0]['sandbox_tested_at']);
    }

    public function buildMarksStorecoveUntestedWhenNoMessageEverSent(): void
    {
        /** @var PeppolMessageRepository&m\MockInterface $pmR */
        $pmR = m::mock(PeppolMessageRepository::class);
        $pmR->shouldReceive('mostRecentByStatus')->andReturn(null);

        /** @var CycleOrmAs4MessageRepository&m\MockInterface $as4R */
        $as4R = m::mock(CycleOrmAs4MessageRepository::class);
        $as4R->shouldReceive('mostRecentByState')->andReturn(null);

        $fixture = $this->makeComposerLockFixture(null);
        $builder = new PeppolStatusPageBuilder($pmR, $as4R, $fixture['aliases']);

        $data = $builder->build('storecove');

        Assert::same('untested', $data['rows'][0]['sandbox_status']);
        Assert::null($data['rows'][0]['sdk_version']);
    }

    public function buildAlwaysIncludesTheFixedOxalisRow(): void
    {
        /** @var PeppolMessageRepository&m\MockInterface $pmR */
        $pmR = m::mock(PeppolMessageRepository::class);
        $pmR->shouldReceive('mostRecentByStatus')->andReturn(null);

        /** @var CycleOrmAs4MessageRepository&m\MockInterface $as4R */
        $as4R = m::mock(CycleOrmAs4MessageRepository::class);
        $as4R->shouldReceive('mostRecentByState')->andReturn(null);

        $fixture = $this->makeComposerLockFixture(null);
        $builder = new PeppolStatusPageBuilder($pmR, $as4R, $fixture['aliases']);

        $data = $builder->build('storecove');

        Assert::same('Oxalis', $data['rows'][1]['name']);
        Assert::null($data['rows'][1]['sdk_version']);
        Assert::same('untested', $data['rows'][1]['sandbox_status']);
        Assert::null($data['rows'][1]['sandbox_tested_at']);
    }

    public function buildIncludesTheSurveyedReferenceProviders(): void
    {
        /** @var PeppolMessageRepository&m\MockInterface $pmR */
        $pmR = m::mock(PeppolMessageRepository::class);
        $pmR->shouldReceive('mostRecentByStatus')->andReturn(null);

        /** @var CycleOrmAs4MessageRepository&m\MockInterface $as4R */
        $as4R = m::mock(CycleOrmAs4MessageRepository::class);
        $as4R->shouldReceive('mostRecentByState')->andReturn(null);

        $fixture = $this->makeComposerLockFixture(null);
        $builder = new PeppolStatusPageBuilder($pmR, $as4R, $fixture['aliases']);

        $data = $builder->build('storecove');

        Assert::same(1, count($data['referenceProviders']));
        Assert::same('PeppolSoft', $data['referenceProviders'][0]['name']);
    }

    public function buildMarksAs4BilateralTestedWhenAReceiptWasReceived(): void
    {
        $message = $this->makeAs4Message();
        $message->markReceiptReceived('receipt-msg-001', 'abc123digest==');

        /** @var PeppolMessageRepository&m\MockInterface $pmR */
        $pmR = m::mock(PeppolMessageRepository::class);
        $pmR->shouldReceive('mostRecentByStatus')->andReturn(null);

        /** @var CycleOrmAs4MessageRepository&m\MockInterface $as4R */
        $as4R = m::mock(CycleOrmAs4MessageRepository::class);
        $as4R->shouldReceive('mostRecentByState')
            ->with(As4MessageState::receiptReceived)
            ->andReturn($message);

        $fixture = $this->makeComposerLockFixture(null);
        $builder = new PeppolStatusPageBuilder($pmR, $as4R, $fixture['aliases']);

        $data = $builder->build('storecove');

        Assert::true($data['as4Bilateral']['tested']);
        Assert::same('0088:9876543210987', $data['as4Bilateral']['peer_party_id']);
        Assert::notNull($data['as4Bilateral']['tested_at']);
    }

    public function buildMarksAs4BilateralUntestedWhenNoReceiptEverReceived(): void
    {
        /** @var PeppolMessageRepository&m\MockInterface $pmR */
        $pmR = m::mock(PeppolMessageRepository::class);
        $pmR->shouldReceive('mostRecentByStatus')->andReturn(null);

        /** @var CycleOrmAs4MessageRepository&m\MockInterface $as4R */
        $as4R = m::mock(CycleOrmAs4MessageRepository::class);
        $as4R->shouldReceive('mostRecentByState')->andReturn(null);

        $fixture = $this->makeComposerLockFixture(null);
        $builder = new PeppolStatusPageBuilder($pmR, $as4R, $fixture['aliases']);

        $data = $builder->build('storecove');

        Assert::false($data['as4Bilateral']['tested']);
        Assert::null($data['as4Bilateral']['tested_at']);
        Assert::null($data['as4Bilateral']['peer_party_id']);
    }

    public function storecoveClientVersionReturnsNullWhenComposerLockIsMissing(): void
    {
        /** @var PeppolMessageRepository&m\MockInterface $pmR */
        $pmR = m::mock(PeppolMessageRepository::class);
        $pmR->shouldReceive('mostRecentByStatus')->andReturn(null);

        /** @var CycleOrmAs4MessageRepository&m\MockInterface $as4R */
        $as4R = m::mock(CycleOrmAs4MessageRepository::class);
        $as4R->shouldReceive('mostRecentByState')->andReturn(null);

        $aliases = new Aliases(['@root' => sys_get_temp_dir() . '/peppol-status-builder-nonexistent-' . bin2hex(random_bytes(8))]);
        $builder = new PeppolStatusPageBuilder($pmR, $as4R, $aliases);

        $data = $builder->build('storecove');

        Assert::null($data['rows'][0]['sdk_version']);
    }
}
