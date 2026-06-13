<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\As4;

use App\Invoice\As4\As4Constants;
use App\Invoice\As4\As4SmpEndpoint;
use PHPUnit\Framework\TestCase;

class As4SmpEndpointTest extends TestCase
{
    private const string ENDPOINT_URL = 'https://ap.example.com/as4/receive';
    private const string CERT_PEM     = "-----BEGIN CERTIFICATE-----\nMIIBtest==\n-----END CERTIFICATE-----\n";

    private function sut(): As4SmpEndpoint
    {
        return new As4SmpEndpoint(
            endpointUrl:      self::ENDPOINT_URL,
            certificatePem:   self::CERT_PEM,
            transportProfile: As4Constants::PEPPOL_TRANSPORT_PROFILE,
        );
    }

    public function testConstructorStoresEndpointUrl(): void
    {
        $this->assertSame(self::ENDPOINT_URL, $this->sut()->endpointUrl);
    }

    public function testConstructorStoresCertificatePem(): void
    {
        $this->assertSame(self::CERT_PEM, $this->sut()->certificatePem);
    }

    public function testConstructorStoresTransportProfile(): void
    {
        $this->assertSame(As4Constants::PEPPOL_TRANSPORT_PROFILE, $this->sut()->transportProfile);
    }
}
