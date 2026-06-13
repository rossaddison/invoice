<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4SmpQuery;
use PHPUnit\Framework\TestCase;

class As4SmpQueryTest extends TestCase
{
    private const string PARTICIPANT_ID   = '0088:1234567890123';
    private const string DOCUMENT_TYPE_ID = 'busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::UBL-Invoice-2.1::2.1';
    private const string PROCESS_ID       = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';

    private function sut(): As4SmpQuery
    {
        return new As4SmpQuery(
            participantId:  self::PARTICIPANT_ID,
            documentTypeId: self::DOCUMENT_TYPE_ID,
            processId:      self::PROCESS_ID,
        );
    }

    public function testConstructorStoresParticipantId(): void
    {
        $this->assertSame(self::PARTICIPANT_ID, $this->sut()->participantId);
    }

    public function testConstructorStoresDocumentTypeId(): void
    {
        $this->assertSame(self::DOCUMENT_TYPE_ID, $this->sut()->documentTypeId);
    }

    public function testConstructorStoresProcessId(): void
    {
        $this->assertSame(self::PROCESS_ID, $this->sut()->processId);
    }
}
