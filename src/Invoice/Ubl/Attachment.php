<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

class Attachment implements XmlSerializable
{
    public function __construct(private ?string $filePath, private ?string $externalReference)
    {
    }

    /**
     * @throws \Exception
     */
    public function getFileMimeType(): string
    {
        if (null !== $this->filePath) {
            if (($mime_type = mime_content_type($this->filePath)) !== false) {
                return $mime_type;
            }
            throw new \Exception('Could not determine mime_type of '.$this->filePath);
        }
        throw new \Exception('Cannot determine MimeType. FilePath does not exist.');
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }

    public function setExternalReference(?string $externalReference): self
    {
        $this->externalReference = $externalReference;

        return $this;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (null === $this->filePath && null === $this->externalReference) {
            throw new \InvalidArgumentException('Attachment must have a filePath or an ExternalReference');
        }

        if (null !== $this->filePath && !file_exists($this->filePath)) {
            throw new \InvalidArgumentException('Attachment at filePath does not exist');
        }
    }

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        $filePath = $this->filePath;

        if (null !== $filePath) {
            $fileContents = file_get_contents($filePath, true);

            if (false != $fileContents) {
                $newFileContents = base64_encode($fileContents);
                $mimeType        = $this->getFileMimeType();

                $writer->write([
                    'name'       => Schema::CBC.'EmbeddedDocumentBinaryObject',
                    'value'      => $newFileContents,
                    'attributes' => [
                        'mimeCode' => $mimeType,
                        'filename' => basename($filePath),
                    ],
                ]);
            }
        }

        if (null !== $this->externalReference) {
            $writer->writeElement(
                Schema::CAC.'ExternalReference',
                [Schema::CBC.'URI' => $this->externalReference],
            );
        }
    }
}
