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
        $this->assertSame('', $upload->getClientId());
        $this->assertSame('', $upload->getUrlKey());
        $this->assertSame('', $upload->getFileNameOriginal());
        $this->assertSame('', $upload->getFileNameNew());
        $this->assertSame('', $upload->getDescription());
        $this->assertInstanceOf(DateTimeImmutable::class, $upload->getUploadedDate());
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
        $this->assertSame('100', $upload->getClientId());
        $this->assertSame('abc123def456', $upload->getUrlKey());
        $this->assertSame('document.pdf', $upload->getFileNameOriginal());
        $this->assertSame('hashed_document_12345.pdf', $upload->getFileNameNew());
        $this->assertSame('Important client document', $upload->getDescription());
        $this->assertInstanceOf(DateTimeImmutable::class, $upload->getUploadedDate());
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
        $upload->setClientId(200);
        
        $this->assertSame('200', $upload->getClientId());
    }

    public function testUrlKeySetterAndGetter(): void
    {
        $upload = new Upload();
        $upload->setUrlKey('xyz789abc123');
        
        $this->assertSame('xyz789abc123', $upload->getUrlKey());
    }

    public function testFileNameOriginalSetterAndGetter(): void
    {
        $upload = new Upload();
        $upload->setFileNameOriginal('contract.docx');
        
        $this->assertSame('contract.docx', $upload->getFileNameOriginal());
    }

    public function testFileNameNewSetterAndGetter(): void
    {
        $upload = new Upload();
        $upload->setFileNameNew('hashed_contract_67890.docx');
        
        $this->assertSame('hashed_contract_67890.docx', $upload->getFileNameNew());
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
        $upload->setUploadedDate($customDate);
        
        $this->assertSame($customDate, $upload->getUploadedDate());
        $this->assertSame('2024-06-15 14:30:00', $upload->getUploadedDate()->format('Y-m-d H:i:s'));
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
        $upload->setClientId(777);
        
        $this->assertIsString($upload->getClientId());
        $this->assertSame('777', $upload->getClientId());
    }

    public function testZeroIds(): void
    {
        $upload = new Upload();
        $upload->setId(0);
        $upload->setClientId(0);
        
        $this->assertSame('0', $upload->getId());
        $this->assertSame('0', $upload->getClientId());
    }

    public function testNegativeIds(): void
    {
        $upload = new Upload();
        $upload->setId(-1);
        $upload->setClientId(-5);
        
        $this->assertSame('-1', $upload->getId());
        $this->assertSame('-5', $upload->getClientId());
    }

    public function testLargeIds(): void
    {
        $upload = new Upload();
        $largeId = PHP_INT_MAX;
        
        $upload->setId($largeId);
        $upload->setClientId($largeId - 1);
        
        $this->assertSame((string)$largeId, $upload->getId());
        $this->assertSame((string)($largeId - 1), $upload->getClientId());
    }

    public function testEmptyStrings(): void
    {
        $upload = new Upload();
        $upload->setUrlKey('');
        $upload->setFileNameOriginal('');
        $upload->setFileNameNew('');
        $upload->setDescription('');
        
        $this->assertSame('', $upload->getUrlKey());
        $this->assertSame('', $upload->getFileNameOriginal());
        $this->assertSame('', $upload->getFileNameNew());
        $this->assertSame('', $upload->getDescription());
    }

    public function testLongStrings(): void
    {
        $upload = new Upload();
        
        $longKey = str_repeat('a', 32);
        $longOriginalName = str_repeat('very_long_filename_', 50) . '.pdf';
        $longNewName = str_repeat('hashed_name_', 50) . '.pdf';
        $longDescription = str_repeat('This is a very detailed description of the uploaded file. ', 100);
        
        $upload->setUrlKey($longKey);
        $upload->setFileNameOriginal($longOriginalName);
        $upload->setFileNameNew($longNewName);
        $upload->setDescription($longDescription);
        
        $this->assertSame($longKey, $upload->getUrlKey());
        $this->assertSame($longOriginalName, $upload->getFileNameOriginal());
        $this->assertSame($longNewName, $upload->getFileNameNew());
        $this->assertSame($longDescription, $upload->getDescription());
    }

    public function testSpecialCharactersInFields(): void
    {
        $upload = new Upload();
        $upload->setUrlKey('abc-123_def.456');
        $upload->setFileNameOriginal('file (1) [copy].pdf');
        $upload->setFileNameNew('hashed_file_123-456.pdf');
        $upload->setDescription('Description with special chars: àáâãäåæçèéêë ™€£¥');
        
        $this->assertSame('abc-123_def.456', $upload->getUrlKey());
        $this->assertSame('file (1) [copy].pdf', $upload->getFileNameOriginal());
        $this->assertSame('hashed_file_123-456.pdf', $upload->getFileNameNew());
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
            $upload->setFileNameOriginal($original);
            $upload->setFileNameNew($new);
            
            $this->assertSame($original, $upload->getFileNameOriginal());
            $this->assertSame($new, $upload->getFileNameNew());
        }
    }

    public function testUnicodeFileNames(): void
    {
        $upload = new Upload();
        $upload->setFileNameOriginal('文档.pdf');
        $upload->setFileNameNew('hashed_文档.pdf');
        $upload->setDescription('Chinese document: 中文文档描述');
        
        $this->assertSame('文档.pdf', $upload->getFileNameOriginal());
        $this->assertSame('hashed_文档.pdf', $upload->getFileNameNew());
        $this->assertSame('Chinese document: 中文文档描述', $upload->getDescription());
    }

    public function testUploadedDateImmutability(): void
    {
        $upload = new Upload();
        $originalDate = $upload->getUploadedDate();
        
        // Try to modify the returned date
        $modifiedDate = $originalDate->modify('+1 day');
        
        // Original should be unchanged (immutable)
        $this->assertNotSame($originalDate, $modifiedDate);
        $this->assertSame($originalDate, $upload->getUploadedDate());
    }

    public function testDateTimeImmutableHandling(): void
    {
        $upload = new Upload();
        
        $date1 = new DateTimeImmutable('2024-01-01 00:00:00');
        $date2 = new DateTimeImmutable('2024-12-31 23:59:59');
        
        $upload->setUploadedDate($date1);
        $this->assertSame($date1, $upload->getUploadedDate());
        
        $upload->setUploadedDate($date2);
        $this->assertSame($date2, $upload->getUploadedDate());
    }

    public function testCompleteUploadSetup(): void
    {
        $upload = new Upload();
        $client = $this->createMock(Client::class);
        $uploadDate = new DateTimeImmutable('2024-06-15 10:30:00');
        
        $upload->setId(1);
        $upload->setClientId(100);
        $upload->setClient($client);
        $upload->setUrlKey('complete_key_123');
        $upload->setFileNameOriginal('complete_document.pdf');
        $upload->setFileNameNew('complete_hashed_doc_456.pdf');
        $upload->setDescription('Complete upload setup with all properties');
        $upload->setUploadedDate($uploadDate);
        
        $this->assertSame('1', $upload->getId());
        $this->assertSame('100', $upload->getClientId());
        $this->assertSame($client, $upload->getClient());
        $this->assertSame('complete_key_123', $upload->getUrlKey());
        $this->assertSame('complete_document.pdf', $upload->getFileNameOriginal());
        $this->assertSame('complete_hashed_doc_456.pdf', $upload->getFileNameNew());
        $this->assertSame('Complete upload setup with all properties', $upload->getDescription());
        $this->assertSame($uploadDate, $upload->getUploadedDate());
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
        $this->assertIsString($upload->getClientId());
        $this->assertIsString($upload->getUrlKey());
        $this->assertIsString($upload->getFileNameOriginal());
        $this->assertIsString($upload->getFileNameNew());
        $this->assertIsString($upload->getDescription());
        $this->assertInstanceOf(DateTimeImmutable::class, $upload->getUploadedDate());
        $this->assertNull($upload->getClient());
    }

    public function testFilePathsWithDirectories(): void
    {
        $upload = new Upload();
        $upload->setFileNameOriginal('documents/subfolder/important_file.pdf');
        $upload->setFileNameNew('uploads/2024/06/hashed_file_123.pdf');
        
        $this->assertSame('documents/subfolder/important_file.pdf', $upload->getFileNameOriginal());
        $this->assertSame('uploads/2024/06/hashed_file_123.pdf', $upload->getFileNameNew());
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
            $upload->setUrlKey($key);
            $this->assertSame($key, $upload->getUrlKey());
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
        $upload->setClientId(100);
        $upload->setClient($client1);
        $this->assertSame($client1, $upload->getClient());
        
        // Set new client
        $upload->setClientId(200);
        $upload->setClient($client2);
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
        $this->assertSame('888', $upload->getClientId());
        $this->assertSame('initial_key', $upload->getUrlKey());
        $this->assertSame('initial.pdf', $upload->getFileNameOriginal());
        $this->assertSame('initial_hash.pdf', $upload->getFileNameNew());
        $this->assertSame('Initial description', $upload->getDescription());
        
        // Modify and verify changes
        $upload->setId(111);
        $upload->setClientId(222);
        $upload->setUrlKey('modified_key');
        $upload->setFileNameOriginal('modified.pdf');
        $upload->setFileNameNew('modified_hash.pdf');
        $upload->setDescription('Modified description');
        
        $this->assertSame('111', $upload->getId());
        $this->assertSame('222', $upload->getClientId());
        $this->assertSame('modified_key', $upload->getUrlKey());
        $this->assertSame('modified.pdf', $upload->getFileNameOriginal());
        $this->assertSame('modified_hash.pdf', $upload->getFileNameNew());
        $this->assertSame('Modified description', $upload->getDescription());
    }

    public function testTimestampConsistency(): void
    {
        $beforeTime = time();
        $upload = new Upload();
        $afterTime = time();
        
        $uploadTime = $upload->getUploadedDate()->getTimestamp();
        
        $this->assertGreaterThanOrEqual($beforeTime, $uploadTime);
        $this->assertLessThanOrEqual($afterTime, $uploadTime);
    }

    public function testFileUploadScenarios(): void
    {
        $upload = new Upload();
        
        // PDF document scenario
        $upload->setUrlKey('pdf_key_123');
        $upload->setFileNameOriginal('Legal_Contract_2024.pdf');
        $upload->setFileNameNew('hash_456789_contract.pdf');
        $upload->setDescription('Legal contract for client review and signature');
        
        $this->assertSame('pdf_key_123', $upload->getUrlKey());
        $this->assertSame('Legal_Contract_2024.pdf', $upload->getFileNameOriginal());
        $this->assertSame('hash_456789_contract.pdf', $upload->getFileNameNew());
        
        // Image upload scenario
        $upload->setUrlKey('img_key_789');
        $upload->setFileNameOriginal('Profile_Photo.jpg');
        $upload->setFileNameNew('hash_abc123_photo.jpg');
        $upload->setDescription('Client profile photo for ID verification');
        
        $this->assertSame('img_key_789', $upload->getUrlKey());
        $this->assertSame('Profile_Photo.jpg', $upload->getFileNameOriginal());
        $this->assertSame('hash_abc123_photo.jpg', $upload->getFileNameNew());
    }
}
