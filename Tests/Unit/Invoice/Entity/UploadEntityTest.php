<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\Client;
use App\Invoice\Entity\Upload;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UploadEntityTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $upload = new Upload();
        
        $this->assertSame('', $upload->getId());
        $this->assertSame('', $upload->getClient_id());
        $this->assertSame('', $upload->getUrl_key());
        $this->assertSame('', $upload->getFile_name_original());
        $this->assertSame('', $upload->getFile_name_new());
        $this->assertSame('', $upload->getDescription());
        $this->assertInstanceOf(DateTimeImmutable::class, $upload->getUploaded_date());
        $this->assertNull($upload->getClient());
    }

    public function testConstructorWithAllParameters(): void
    {
        $upload = new Upload(
            id: 1,
            client_id: 100,
            url_key: 'abc123def456',
            file_name_original: 'document.pdf',
            file_name_new: 'hashed_document_12345.pdf',
            description: 'Important client document'
        );
        
        $this->assertSame('1', $upload->getId());
        $this->assertSame('100', $upload->getClient_id());
        $this->assertSame('abc123def456', $upload->getUrl_key());
        $this->assertSame('document.pdf', $upload->getFile_name_original());
        $this->assertSame('hashed_document_12345.pdf', $upload->getFile_name_new());
        $this->assertSame('Important client document', $upload->getDescription());
        $this->assertInstanceOf(DateTimeImmutable::class, $upload->getUploaded_date());
    }

    public function testIdSetterAndGetter(): void
    {
        $upload = new Upload();
        $upload->setId(50);
        
        $this->assertSame('50', $upload->getId());
    }

    public function testClientIdSetterAndGetter(): void
    {
        $upload = new Upload();
        $upload->setClient_id(200);
        
        $this->assertSame('200', $upload->getClient_id());
    }

    public function testUrlKeySetterAndGetter(): void
    {
        $upload = new Upload();
        $upload->setUrl_key('xyz789abc123');
        
        $this->assertSame('xyz789abc123', $upload->getUrl_key());
    }

    public function testFileNameOriginalSetterAndGetter(): void
    {
        $upload = new Upload();
        $upload->setFile_name_original('contract.docx');
        
        $this->assertSame('contract.docx', $upload->getFile_name_original());
    }

    public function testFileNameNewSetterAndGetter(): void
    {
        $upload = new Upload();
        $upload->setFile_name_new('hashed_contract_67890.docx');
        
        $this->assertSame('hashed_contract_67890.docx', $upload->getFile_name_new());
    }

    public function testDescriptionSetterAndGetter(): void
    {
        $upload = new Upload();
        $upload->setDescription('Updated description');
        
        $this->assertSame('Updated description', $upload->getDescription());
    }

    public function testUploadedDateSetterAndGetter(): void
    {
        $upload = new Upload();
        $customDate = new DateTimeImmutable('2024-06-15 14:30:00');
        $upload->setUploaded_date($customDate);
        
        $this->assertSame($customDate, $upload->getUploaded_date());
        $this->assertSame('2024-06-15 14:30:00', $upload->getUploaded_date()->format('Y-m-d H:i:s'));
    }

    public function testClientRelationshipSetterAndGetter(): void
    {
        $upload = new Upload();
        $client = $this->createMock(Client::class);
        
        $upload->setClient($client);
        $this->assertSame($client, $upload->getClient());
        
        $upload->setClient(null);
        $this->assertNull($upload->getClient());
    }

    public function testNullifyRelationOnChange(): void
    {
        $upload = new Upload();
        $client = $this->createMock(Client::class);
        
        $upload->setClient_id(100);
        $upload->setClient($client);
        
        // Same client ID - should not nullify relation
        $upload->nullifyRelationOnChange(100);
        $this->assertSame($client, $upload->getClient());
        
        // Different client ID - should nullify relation
        $upload->nullifyRelationOnChange(200);
        $this->assertNull($upload->getClient());
    }

    public function testIdTypeConversion(): void
    {
        $upload = new Upload();
        $upload->setId(999);
        
        $this->assertIsString($upload->getId());
        $this->assertSame('999', $upload->getId());
    }

    public function testClientIdTypeConversion(): void
    {
        $upload = new Upload();
        $upload->setClient_id(777);
        
        $this->assertIsString($upload->getClient_id());
        $this->assertSame('777', $upload->getClient_id());
    }

    public function testZeroIds(): void
    {
        $upload = new Upload();
        $upload->setId(0);
        $upload->setClient_id(0);
        
        $this->assertSame('0', $upload->getId());
        $this->assertSame('0', $upload->getClient_id());
    }

    public function testNegativeIds(): void
    {
        $upload = new Upload();
        $upload->setId(-1);
        $upload->setClient_id(-5);
        
        $this->assertSame('-1', $upload->getId());
        $this->assertSame('-5', $upload->getClient_id());
    }

    public function testLargeIds(): void
    {
        $upload = new Upload();
        $largeId = PHP_INT_MAX;
        
        $upload->setId($largeId);
        $upload->setClient_id($largeId - 1);
        
        $this->assertSame((string)$largeId, $upload->getId());
        $this->assertSame((string)($largeId - 1), $upload->getClient_id());
    }

    public function testEmptyStrings(): void
    {
        $upload = new Upload();
        $upload->setUrl_key('');
        $upload->setFile_name_original('');
        $upload->setFile_name_new('');
        $upload->setDescription('');
        
        $this->assertSame('', $upload->getUrl_key());
        $this->assertSame('', $upload->getFile_name_original());
        $this->assertSame('', $upload->getFile_name_new());
        $this->assertSame('', $upload->getDescription());
    }

    public function testLongStrings(): void
    {
        $upload = new Upload();
        
        $longKey = str_repeat('a', 32);
        $longOriginalName = str_repeat('very_long_filename_', 50) . '.pdf';
        $longNewName = str_repeat('hashed_name_', 50) . '.pdf';
        $longDescription = str_repeat('This is a very detailed description of the uploaded file. ', 100);
        
        $upload->setUrl_key($longKey);
        $upload->setFile_name_original($longOriginalName);
        $upload->setFile_name_new($longNewName);
        $upload->setDescription($longDescription);
        
        $this->assertSame($longKey, $upload->getUrl_key());
        $this->assertSame($longOriginalName, $upload->getFile_name_original());
        $this->assertSame($longNewName, $upload->getFile_name_new());
        $this->assertSame($longDescription, $upload->getDescription());
    }

    public function testSpecialCharactersInFields(): void
    {
        $upload = new Upload();
        $upload->setUrl_key('abc-123_def.456');
        $upload->setFile_name_original('file (1) [copy].pdf');
        $upload->setFile_name_new('hashed_file_123-456.pdf');
        $upload->setDescription('Description with special chars: àáâãäåæçèéêë ™€£¥');
        
        $this->assertSame('abc-123_def.456', $upload->getUrl_key());
        $this->assertSame('file (1) [copy].pdf', $upload->getFile_name_original());
        $this->assertSame('hashed_file_123-456.pdf', $upload->getFile_name_new());
        $this->assertSame('Description with special chars: àáâãäåæçèéêë ™€£¥', $upload->getDescription());
    }

    public function testVariousFileExtensions(): void
    {
        $upload = new Upload();
        
        $fileTypes = [
            ['document.pdf', 'hash_doc.pdf'],
            ['image.jpg', 'hash_img.jpg'],
            ['spreadsheet.xlsx', 'hash_sheet.xlsx'],
            ['presentation.pptx', 'hash_pres.pptx'],
            ['archive.zip', 'hash_arch.zip'],
            ['video.mp4', 'hash_vid.mp4'],
            ['audio.mp3', 'hash_aud.mp3'],
        ];
        
        foreach ($fileTypes as [$original, $new]) {
            $upload->setFile_name_original($original);
            $upload->setFile_name_new($new);
            
            $this->assertSame($original, $upload->getFile_name_original());
            $this->assertSame($new, $upload->getFile_name_new());
        }
    }

    public function testUnicodeFileNames(): void
    {
        $upload = new Upload();
        $upload->setFile_name_original('文档.pdf');
        $upload->setFile_name_new('hashed_文档.pdf');
        $upload->setDescription('Chinese document: 中文文档描述');
        
        $this->assertSame('文档.pdf', $upload->getFile_name_original());
        $this->assertSame('hashed_文档.pdf', $upload->getFile_name_new());
        $this->assertSame('Chinese document: 中文文档描述', $upload->getDescription());
    }

    public function testUploadedDateImmutability(): void
    {
        $upload = new Upload();
        $originalDate = $upload->getUploaded_date();
        
        // Try to modify the returned date
        $modifiedDate = $originalDate->modify('+1 day');
        
        // Original should be unchanged (immutable)
        $this->assertNotSame($originalDate, $modifiedDate);
        $this->assertSame($originalDate, $upload->getUploaded_date());
    }

    public function testDateTimeImmutableHandling(): void
    {
        $upload = new Upload();
        
        $date1 = new DateTimeImmutable('2024-01-01 00:00:00');
        $date2 = new DateTimeImmutable('2024-12-31 23:59:59');
        
        $upload->setUploaded_date($date1);
        $this->assertSame($date1, $upload->getUploaded_date());
        
        $upload->setUploaded_date($date2);
        $this->assertSame($date2, $upload->getUploaded_date());
    }

    public function testCompleteUploadSetup(): void
    {
        $upload = new Upload();
        $client = $this->createMock(Client::class);
        $uploadDate = new DateTimeImmutable('2024-06-15 10:30:00');
        
        $upload->setId(1);
        $upload->setClient_id(100);
        $upload->setClient($client);
        $upload->setUrl_key('complete_key_123');
        $upload->setFile_name_original('complete_document.pdf');
        $upload->setFile_name_new('complete_hashed_doc_456.pdf');
        $upload->setDescription('Complete upload setup with all properties');
        $upload->setUploaded_date($uploadDate);
        
        $this->assertSame('1', $upload->getId());
        $this->assertSame('100', $upload->getClient_id());
        $this->assertSame($client, $upload->getClient());
        $this->assertSame('complete_key_123', $upload->getUrl_key());
        $this->assertSame('complete_document.pdf', $upload->getFile_name_original());
        $this->assertSame('complete_hashed_doc_456.pdf', $upload->getFile_name_new());
        $this->assertSame('Complete upload setup with all properties', $upload->getDescription());
        $this->assertSame($uploadDate, $upload->getUploaded_date());
    }

    public function testMethodReturnTypes(): void
    {
        $upload = new Upload(
            id: 1,
            client_id: 100,
            url_key: 'test_key',
            file_name_original: 'test.pdf',
            file_name_new: 'hashed_test.pdf',
            description: 'Test description'
        );
        
        $this->assertIsString($upload->getId());
        $this->assertIsString($upload->getClient_id());
        $this->assertIsString($upload->getUrl_key());
        $this->assertIsString($upload->getFile_name_original());
        $this->assertIsString($upload->getFile_name_new());
        $this->assertIsString($upload->getDescription());
        $this->assertInstanceOf(DateTimeImmutable::class, $upload->getUploaded_date());
        $this->assertNull($upload->getClient());
    }

    public function testFilePathsWithDirectories(): void
    {
        $upload = new Upload();
        $upload->setFile_name_original('documents/subfolder/important_file.pdf');
        $upload->setFile_name_new('uploads/2024/06/hashed_file_123.pdf');
        
        $this->assertSame('documents/subfolder/important_file.pdf', $upload->getFile_name_original());
        $this->assertSame('uploads/2024/06/hashed_file_123.pdf', $upload->getFile_name_new());
    }

    public function testUrlKeyFormats(): void
    {
        $upload = new Upload();
        
        $urlKeys = [
            'abcdef123456',
            'ABC123DEF456',
            'mixed123CASE456',
            '1234567890123456',
            'key-with-dashes',
            'key_with_underscores',
            'key.with.dots'
        ];
        
        foreach ($urlKeys as $key) {
            $upload->setUrl_key($key);
            $this->assertSame($key, $upload->getUrl_key());
        }
    }

    public function testDescriptionWithHtml(): void
    {
        $upload = new Upload();
        $htmlDescription = '<div class="upload-info"><p>This is an <strong>important</strong> document.</p><ul><li>Priority: High</li><li>Category: Legal</li></ul></div>';
        $upload->setDescription($htmlDescription);
        
        $this->assertSame($htmlDescription, $upload->getDescription());
    }

    public function testDescriptionWithJson(): void
    {
        $upload = new Upload();
        $jsonDescription = '{"type": "document", "category": "contract", "priority": "high", "tags": ["legal", "important"]}';
        $upload->setDescription($jsonDescription);
        
        $this->assertSame($jsonDescription, $upload->getDescription());
    }

    public function testDescriptionMultiline(): void
    {
        $upload = new Upload();
        $multilineDescription = "File Upload Details:\n\nOriginal Name: document.pdf\nSize: 2.5 MB\nType: Legal Contract\nStatus: Approved\n\nNotes:\n- Reviewed by legal team\n- Approved for client access\n- Backup created";
        $upload->setDescription($multilineDescription);
        
        $this->assertSame($multilineDescription, $upload->getDescription());
    }

    public function testClientRelationshipWorkflow(): void
    {
        $upload = new Upload();
        $client1 = $this->createMock(Client::class);
        $client2 = $this->createMock(Client::class);
        
        // Initially null
        $this->assertNull($upload->getClient());
        
        // Set first client
        $upload->setClient_id(100);
        $upload->setClient($client1);
        $this->assertSame($client1, $upload->getClient());
        
        // Nullify relation when client ID changes
        $upload->nullifyRelationOnChange(200);
        $this->assertNull($upload->getClient());
        
        // Set new client
        $upload->setClient_id(200);
        $upload->setClient($client2);
        $this->assertSame($client2, $upload->getClient());
        
        // No change when same client ID
        $upload->nullifyRelationOnChange(200);
        $this->assertSame($client2, $upload->getClient());
    }

    public function testEntityStateConsistency(): void
    {
        $upload = new Upload(
            id: 999,
            client_id: 888,
            url_key: 'initial_key',
            file_name_original: 'initial.pdf',
            file_name_new: 'initial_hash.pdf',
            description: 'Initial description'
        );
        
        // Verify initial state
        $this->assertSame('999', $upload->getId());
        $this->assertSame('888', $upload->getClient_id());
        $this->assertSame('initial_key', $upload->getUrl_key());
        $this->assertSame('initial.pdf', $upload->getFile_name_original());
        $this->assertSame('initial_hash.pdf', $upload->getFile_name_new());
        $this->assertSame('Initial description', $upload->getDescription());
        
        // Modify and verify changes
        $upload->setId(111);
        $upload->setClient_id(222);
        $upload->setUrl_key('modified_key');
        $upload->setFile_name_original('modified.pdf');
        $upload->setFile_name_new('modified_hash.pdf');
        $upload->setDescription('Modified description');
        
        $this->assertSame('111', $upload->getId());
        $this->assertSame('222', $upload->getClient_id());
        $this->assertSame('modified_key', $upload->getUrl_key());
        $this->assertSame('modified.pdf', $upload->getFile_name_original());
        $this->assertSame('modified_hash.pdf', $upload->getFile_name_new());
        $this->assertSame('Modified description', $upload->getDescription());
    }

    public function testTimestampConsistency(): void
    {
        $beforeTime = time();
        $upload = new Upload();
        $afterTime = time();
        
        $uploadTime = $upload->getUploaded_date()->getTimestamp();
        
        $this->assertGreaterThanOrEqual($beforeTime, $uploadTime);
        $this->assertLessThanOrEqual($afterTime, $uploadTime);
    }

    public function testFileUploadScenarios(): void
    {
        $upload = new Upload();
        
        // PDF document scenario
        $upload->setUrl_key('pdf_key_123');
        $upload->setFile_name_original('Legal_Contract_2024.pdf');
        $upload->setFile_name_new('hash_456789_contract.pdf');
        $upload->setDescription('Legal contract for client review and signature');
        
        $this->assertSame('pdf_key_123', $upload->getUrl_key());
        $this->assertSame('Legal_Contract_2024.pdf', $upload->getFile_name_original());
        $this->assertSame('hash_456789_contract.pdf', $upload->getFile_name_new());
        
        // Image upload scenario
        $upload->setUrl_key('img_key_789');
        $upload->setFile_name_original('Profile_Photo.jpg');
        $upload->setFile_name_new('hash_abc123_photo.jpg');
        $upload->setDescription('Client profile photo for ID verification');
        
        $this->assertSame('img_key_789', $upload->getUrl_key());
        $this->assertSame('Profile_Photo.jpg', $upload->getFile_name_original());
        $this->assertSame('hash_abc123_photo.jpg', $upload->getFile_name_new());
    }
}