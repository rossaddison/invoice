<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

use Exception;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use InvalidArgumentException;

class Attachment implements XmlSerializable
{
    public function __construct(private ?string $filePath, private ?string $externalReference)
    {
    }

    /**
     * @throws Exception
     * @return string
     */
    public function getFileMimeType(): string
    {
        if (null !== $this->filePath) {
            if (($mime_type = mime_content_type($this->filePath)) !== false) {
                return $mime_type;
            }
            throw new Exception('Could not determine mime_type of ' . $this->filePath);
        }
        throw new Exception('Cannot determine MimeType. FilePath does not exist.');
    }

    /**
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * @param string|null $filePath
     * @return Attachment
     */
    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }

    /**
     * @param string|null $externalReference
     * @return Attachment
     */
    public function setExternalReference(?string $externalReference): self
    {
        $this->externalReference = $externalReference;
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->filePath === null && $this->externalReference === null) {
            throw new InvalidArgumentException('Attachment must have a filePath or an ExternalReference');
        }

        if ($this->filePath !== null && !file_exists($this->filePath)) {
            throw new InvalidArgumentException('Attachment at filePath does not exist');
        }
    }

    /**
     * @param Writer $writer
     */
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();

        $filePath = $this->filePath;

        if (null !== $filePath) {
            $fileContents = file_get_contents($filePath, true);

            if ($fileContents != false) {
                $newFileContents = base64_encode($fileContents);
                $mimeType = $this->getFileMimeType();

                $writer->write([
                    'name' => Schema::CBC . 'EmbeddedDocumentBinaryObject',
                    'value' => $newFileContents,
                    'attributes' => [
                        'mimeCode' => $mimeType,
                        'filename' => basename($filePath),
                    ],
                ]);
            }
        }

        if (null !== $this->externalReference) {
            $writer->writeElement(
                Schema::CAC . 'ExternalReference',
                [Schema::CBC . 'URI' => $this->externalReference]
            );
        }
    }
}
