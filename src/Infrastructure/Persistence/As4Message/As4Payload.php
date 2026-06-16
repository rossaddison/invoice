<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use Cycle\Annotated\Annotation as Cycle;

#[Cycle\Embeddable]
class As4Payload
{
    #[Cycle\Column(type: 'text', nullable: false)]
    private string $soapMessage;

    /** Comma-separated list of payload MIME part IDs (cid:...) */
    #[Cycle\Column(type: 'text', nullable: true)]
    private ?string $payloadPartIds = null;

    public function __construct(string $soapMessage)
    {
        $this->soapMessage = $soapMessage;
    }

    public function getSoapMessage(): string { return $this->soapMessage; }
    public function getPayloadPartIds(): ?string { return $this->payloadPartIds; }

    /**
     * @param string[] $partIds
     */
    public function setPayloadPartIds(array $partIds): void
    {
        $this->payloadPartIds = implode(',', $partIds);
    }
}
