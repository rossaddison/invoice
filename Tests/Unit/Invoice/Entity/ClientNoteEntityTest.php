<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientNote\ClientNote;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ClientNoteEntityTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $clientNote = new ClientNote();

        $this->assertFalse($clientNote->hasIdentity());
        $this->assertSame('', $clientNote->getNote());
        $this->assertNull($clientNote->getDateNote());
        $this->assertNull($clientNote->getClient());
    }

    public function testConstructorWithAllParameters(): void
    {
        $clientNote = new ClientNote(
            client_id: 100,
            note: 'Important client note'
        );

        $this->assertSame(100, $clientNote->reqClientId());
        $this->assertSame('Important client note', $clientNote->getNote());
    }

    public function testIdSetterAndGetter(): void
    {
        $clientNote = new ClientNote();
        $clientNote->setId(50);

        $this->assertSame(50, $clientNote->reqId());
        $this->assertTrue($clientNote->hasIdentity());
    }

    public function testClientIdSetterAndGetter(): void
    {
        $clientNote = new ClientNote();
        $clientNote->setClientId(200);

        $this->assertSame(200, $clientNote->reqClientId());
    }

    public function testNoteSetterAndGetter(): void
    {
        $clientNote = new ClientNote();
        $clientNote->setNote('Updated client note');

        $this->assertSame('Updated client note', $clientNote->getNote());
    }

    public function testDateNoteSetterAndGetter(): void
    {
        $clientNote = new ClientNote();
        $dateNote = new DateTimeImmutable('2024-12-25');
        $clientNote->setDateNote($dateNote);

        $this->assertSame($dateNote, $clientNote->getDateNote());
    }

    public function testClientRelationshipSetterAndGetter(): void
    {
        $clientNote = new ClientNote();
        $client = $this->createStub(Client::class);

        $clientNote->setClient($client);
        $this->assertSame($client, $clientNote->getClient());

        $clientNote->setClient(null);
        $this->assertNull($clientNote->getClient());
    }

    public function testhasIdentity(): void
    {
        $clientNote = new ClientNote();
        $this->assertFalse($clientNote->hasIdentity());

        $clientNote->setId(1);
        $this->assertTrue($clientNote->hasIdentity());

        // Edge case with ID 0 — persisted since id !== null
        $clientNote->setId(0);
        $this->assertSame(0, $clientNote->reqId());
        $this->assertTrue($clientNote->hasIdentity());
    }

    public function testIdTypeConversion(): void
    {
        $clientNote = new ClientNote();
        $clientNote->setId(999);

        $this->assertIsInt($clientNote->reqId());
        $this->assertSame(999, $clientNote->reqId());
    }

    public function testClientIdTypeConversion(): void
    {
        $clientNote = new ClientNote();
        $clientNote->setClientId(777);

        $this->assertIsInt($clientNote->reqClientId());
        $this->assertSame(777, $clientNote->reqClientId());
    }

    public function testEmptyNote(): void
    {
        $clientNote = new ClientNote();
        $clientNote->setNote('');

        $this->assertSame('', $clientNote->getNote());
    }

    public function testLongNote(): void
    {
        $clientNote = new ClientNote();
        $longNote = str_repeat('This is a very long note that contains important information about the client. ', 100);
        $clientNote->setNote($longNote);

        $this->assertSame($longNote, $clientNote->getNote());
    }

    public function testNoteWithSpecialCharacters(): void
    {
        $clientNote = new ClientNote();
        $specialNote = 'Note with special chars: àáâãäåæçèéêë ™€£¥ 中文 العربية русский';
        $clientNote->setNote($specialNote);

        $this->assertSame($specialNote, $clientNote->getNote());
    }

    public function testNoteWithHtml(): void
    {
        $clientNote = new ClientNote();
        $htmlNote = '<div class="note"><p>This is <strong>HTML</strong> content</p><ul><li>Item 1</li><li>Item 2</li></ul></div>';
        $clientNote->setNote($htmlNote);

        $this->assertSame($htmlNote, $clientNote->getNote());
    }

    public function testNoteWithJson(): void
    {
        $clientNote = new ClientNote();
        $jsonNote = '{"type": "client_note", "priority": "high", "tags": ["important", "urgent"]}';
        $clientNote->setNote($jsonNote);

        $this->assertSame($jsonNote, $clientNote->getNote());
    }

    public function testMultilineNote(): void
    {
        $clientNote = new ClientNote();
        $multilineNote = "Line 1 of the note\nLine 2 with important info\nLine 3 with contact details\n\nLine 5 after empty line";
        $clientNote->setNote($multilineNote);

        $this->assertSame($multilineNote, $clientNote->getNote());
    }

    public function testNoteWithQuotes(): void
    {
        $clientNote = new ClientNote();
        $noteWithQuotes = 'Client said: "I need this done urgently" and \'tomorrow is fine\'';
        $clientNote->setNote($noteWithQuotes);

        $this->assertSame($noteWithQuotes, $clientNote->getNote());
    }

    public function testZeroIds(): void
    {
        $clientNote = new ClientNote();
        $clientNote->setId(0);
        $clientNote->setClientId(0);

        $this->assertSame(0, $clientNote->reqId());
        $this->assertSame(0, $clientNote->reqClientId());
    }

    public function testNegativeIds(): void
    {
        $clientNote = new ClientNote();
        $clientNote->setId(-1);
        $clientNote->setClientId(-5);

        $this->assertSame(-1, $clientNote->reqId());
        $this->assertSame(-5, $clientNote->reqClientId());
    }

    public function testLargeIds(): void
    {
        $clientNote = new ClientNote();
        $largeId = PHP_INT_MAX;

        $clientNote->setId($largeId);
        $clientNote->setClientId($largeId - 1);

        $this->assertSame($largeId, $clientNote->reqId());
        $this->assertSame($largeId - 1, $clientNote->reqClientId());
    }

    public function testDateNoteWithDifferentFormats(): void
    {
        $clientNote = new ClientNote();

        $dates = [
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-12-31 23:59:59'),
            new DateTimeImmutable('tomorrow'),
            new DateTimeImmutable('yesterday'),
            new DateTimeImmutable('now'),
        ];

        foreach ($dates as $date) {
            $clientNote->setDateNote($date);
            $this->assertSame($date, $clientNote->getDateNote());
        }
    }

    public function testCompleteClientNoteSetup(): void
    {
        $clientNote = new ClientNote();
        $client = $this->createStub(Client::class);
        $dateNote = new DateTimeImmutable('2024-06-15');

        $clientNote->setId(1);
        $clientNote->setClientId(100);
        $clientNote->setClient($client);
        $clientNote->setNote('Complete setup note with all properties');
        $clientNote->setDateNote($dateNote);

        $this->assertSame(1, $clientNote->reqId());
        $this->assertSame(100, $clientNote->reqClientId());
        $this->assertSame($client, $clientNote->getClient());
        $this->assertSame('Complete setup note with all properties', $clientNote->getNote());
        $this->assertTrue($clientNote->hasIdentity());
    }

    public function testMethodReturnTypes(): void
    {
        $clientNote = new ClientNote(
            client_id: 100,
            note: 'Test note'
        );
        $clientNote->setId(1);

        $this->assertIsInt($clientNote->reqId());
        $this->assertIsInt($clientNote->reqClientId());
        $this->assertIsString($clientNote->getNote());
        $this->assertIsBool($clientNote->hasIdentity());
        $this->assertNull($clientNote->getClient());
    }

    public function testNoteWithTabsAndSpecialWhitespace(): void
    {
        $clientNote = new ClientNote();
        $noteWithWhitespace = "Column1\tColumn2\tColumn3\r\nNew line\t\tIndented";
        $clientNote->setNote($noteWithWhitespace);

        $this->assertSame($noteWithWhitespace, $clientNote->getNote());
    }

    public function testNoteWithSqlInjection(): void
    {
        $clientNote = new ClientNote();
        $sqlNote = "'; DROP TABLE clients; --";
        $clientNote->setNote($sqlNote);

        $this->assertSame($sqlNote, $clientNote->getNote());
    }

    public function testNoteWithXss(): void
    {
        $clientNote = new ClientNote();
        $xssNote = '<script>alert("XSS attack")</script>';
        $clientNote->setNote($xssNote);

        $this->assertSame($xssNote, $clientNote->getNote());
    }

    public function testNoteWithUrls(): void
    {
        $clientNote = new ClientNote();
        $urlNote = 'Client website: https://example.com/path?param=value&other=123#section';
        $clientNote->setNote($urlNote);

        $this->assertSame($urlNote, $clientNote->getNote());
    }

    public function testNoteWithEmails(): void
    {
        $clientNote = new ClientNote();
        $emailNote = 'Contact emails: john.doe@company.com, jane+marketing@example.org';
        $clientNote->setNote($emailNote);

        $this->assertSame($emailNote, $clientNote->getNote());
    }

    public function testNoteWithPhoneNumbers(): void
    {
        $clientNote = new ClientNote();
        $phoneNote = 'Phone: +1 (555) 123-4567 ext. 890, Mobile: +44 7911 123456';
        $clientNote->setNote($phoneNote);

        $this->assertSame($phoneNote, $clientNote->getNote());
    }

    public function testNoteWithUnicodeEmojis(): void
    {
        $clientNote = new ClientNote();
        $emojiNote = 'Client feedback: 😀😊👍 Very satisfied! 🎉🌟';
        $clientNote->setNote($emojiNote);

        $this->assertSame($emojiNote, $clientNote->getNote());
    }

    public function testNoteWithMarkdown(): void
    {
        $clientNote = new ClientNote();
        $markdownNote = "# Client Meeting Notes\n\n## Agenda\n- Item 1\n- Item 2\n\n**Important:** Follow up needed!";
        $clientNote->setNote($markdownNote);

        $this->assertSame($markdownNote, $clientNote->getNote());
    }

    public function testEntityStateConsistency(): void
    {
        $clientNote = new ClientNote(
            client_id: 999,
            note: 'Initial note',
            date_note: new DateTimeImmutable('2024-01-01')
        );

        $this->assertSame(999, $clientNote->reqClientId());
        $this->assertSame('Initial note', $clientNote->getNote());
        $this->assertFalse($clientNote->hasIdentity());

        $clientNote->setId(1);
        $clientNote->setClientId(888);
        $clientNote->setNote('Modified note');
        $clientNote->setDateNote(new DateTimeImmutable('2024-12-31'));

        $this->assertSame(1, $clientNote->reqId());
        $this->assertSame(888, $clientNote->reqClientId());
        $this->assertSame('Modified note', $clientNote->getNote());
        $this->assertTrue($clientNote->hasIdentity());
    }

    public function testClientRelationshipWorkflow(): void
    {
        $clientNote = new ClientNote();
        $client1 = $this->createStub(Client::class);
        $client2 = $this->createStub(Client::class);

        $this->assertNull($clientNote->getClient());

        $clientNote->setClient($client1);
        $this->assertSame($client1, $clientNote->getClient());

        $clientNote->setClient($client2);
        $this->assertSame($client2, $clientNote->getClient());

        $clientNote->setClient(null);
        $this->assertNull($clientNote->getClient());
    }

    public function testNoteBusinessScenarios(): void
    {
        $clientNote = new ClientNote();

        $meetingNote = "Meeting Date: 2024-06-15\nAttendees: John Doe, Jane Smith\nTopics Discussed:\n- Project timeline\n- Budget approval\n- Next steps\n\nAction Items:\n1. Send proposal by Friday\n2. Schedule follow-up meeting\n3. Prepare cost estimates";
        $clientNote->setNote($meetingNote);
        $this->assertSame($meetingNote, $clientNote->getNote());

        $paymentNote = "Payment Status: OVERDUE\nInvoice #: INV-2024-001\nAmount: \$1,500.00\nDue Date: 2024-05-15\nContact Attempts:\n- Email sent 2024-05-20\n- Phone call 2024-05-25\n- Final notice 2024-06-01";
        $clientNote->setNote($paymentNote);
        $this->assertSame($paymentNote, $clientNote->getNote());

        $supportNote = "Support Ticket #12345\nIssue: Login problems\nSeverity: High\nSteps Taken:\n1. Password reset sent\n2. Account verification completed\n3. Browser cache cleared\nResolution: Issue resolved - user can now login successfully";
        $clientNote->setNote($supportNote);
        $this->assertSame($supportNote, $clientNote->getNote());
    }

    public function testIdNullHandling(): void
    {
        $clientNote = new ClientNote();

        $this->assertFalse($clientNote->hasIdentity());

        $clientNote->setId(1);
        $this->assertSame(1, $clientNote->reqId());
        $this->assertTrue($clientNote->hasIdentity());
    }

    public function testConstructorParameterDefaults(): void
    {
        $clientNote = new ClientNote(client_id: 123);
        $this->assertSame(123, $clientNote->reqClientId());
        $this->assertSame('', $clientNote->getNote());
        $this->assertNull($clientNote->getDateNote());

        $clientNote2 = new ClientNote(note: 'Only note provided');
        $this->assertSame('Only note provided', $clientNote2->getNote());
    }
}
