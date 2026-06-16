<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Party configuration section of a PMode (initiator and responder identity).
 */
class PModeParties
{
    private string $initiatorRole = '';
    private string $initiatorPartyType = 'urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088';
    private string $responderRole = '';
    private string $responderPartyType = 'urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088';

    public function __construct(
        private string $initiatorParty,
        private string $responderParty,
        private string $responderProtocolAddress,
    ) {}

    public function getInitiatorParty(): string { return $this->initiatorParty; }
    public function getInitiatorRole(): string { return $this->initiatorRole; }
    public function getInitiatorPartyType(): string { return $this->initiatorPartyType; }

    public function setInitiatorRole(string $role): self
    {
        $this->initiatorRole = $role;
        return $this;
    }

    public function getResponderParty(): string { return $this->responderParty; }
    public function getResponderRole(): string { return $this->responderRole; }
    public function getResponderPartyType(): string { return $this->responderPartyType; }
    public function getResponderProtocolAddress(): string { return $this->responderProtocolAddress; }

    public function setResponderRole(string $role): self
    {
        $this->responderRole = $role;
        return $this;
    }
}
