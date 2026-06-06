<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Upload\Upload;
use App\Invoice\Upload\UploadForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UploadFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new UploadForm();

        $this->assertNull($form->getClientId());
        $this->assertSame('', $form->getUrlKey());
        $this->assertSame('', $form->getFileNameOriginal());
        $this->assertSame('', $form->getFileNameNew());
        $this->assertSame('', $form->getDescription());
        $this->assertSame('', $form->getUploadedDate());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new UploadForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new Upload();
        $entity->setClientId(7);
        $entity->setUrlKey('abc123xyz');
        $entity->setFileNameOriginal('invoice.pdf');
        $entity->setFileNameNew('2026_abc123_invoice.pdf');
        $entity->setDescription('Q1 invoice');

        $form = UploadForm::show($entity);

        $this->assertSame(7, $form->getClientId());
        $this->assertSame('abc123xyz', $form->getUrlKey());
        $this->assertSame('invoice.pdf', $form->getFileNameOriginal());
        $this->assertSame('2026_abc123_invoice.pdf', $form->getFileNameNew());
        $this->assertSame('Q1 invoice', $form->getDescription());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getUploadedDate());
    }

    public function testShowWithEmptyDescription(): void
    {
        $entity = new Upload();
        $entity->setClientId(1);
        $entity->setUrlKey('key');
        $entity->setFileNameOriginal('file.pdf');
        $entity->setFileNameNew('file_new.pdf');
        $entity->setDescription('');

        $form = UploadForm::show($entity);

        $this->assertSame('', $form->getDescription());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new Upload();
        $entity->setClientId(3);
        $entity->setUrlKey('key');
        $entity->setFileNameOriginal('f.pdf');
        $entity->setFileNameNew('f2.pdf');
        $entity->setDescription('');

        $this->assertNotSame(
            UploadForm::show($entity),
            UploadForm::show($entity)
        );
    }

    public function testTypesAreCorrect(): void
    {
        $entity = new Upload();
        $entity->setClientId(5);
        $entity->setUrlKey('zxcvbn');
        $entity->setFileNameOriginal('doc.pdf');
        $entity->setFileNameNew('doc_stored.pdf');
        $entity->setDescription('A document');

        $form = UploadForm::show($entity);

        $this->assertIsInt($form->getClientId());
        $this->assertIsString($form->getUrlKey());
        $this->assertIsString($form->getFileNameOriginal());
        $this->assertIsString($form->getFileNameNew());
        $this->assertIsString($form->getDescription());
        $this->assertInstanceOf(DateTimeImmutable::class, $form->getUploadedDate());
    }
}
