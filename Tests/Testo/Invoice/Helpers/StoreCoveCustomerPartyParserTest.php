<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Helpers;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\PostalAddress\PostalAddress;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\Helpers\Peppol\Exception\PeppolBuyerPostalAddressNotFoundException;
use App\Invoice\Helpers\Peppol\Exception\PeppolClientNotFoundException;
use App\Invoice\Helpers\StoreCove\StoreCoveCustomerPartyParser;
use App\Invoice\PostalAddress\PostalAddressRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Builds the Storecove customer (buyer) party from a client, its Peppol settings and its postal address,
 * then reads the pieces back out as UBL value objects. Repositories and entities are mocks.
 */
#[Test]
final class StoreCoveCustomerPartyParserTest
{
    private function parser(): StoreCoveCustomerPartyParser
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);
        return new StoreCoveCustomerPartyParser($translator);
    }

    private function client(?int $postalAddressId = 5): Client
    {
        $client = TypedMock::of(Client::class);
        $client->shouldReceive('reqId')->andReturn(7);
        $client->shouldReceive('getPostaladdressId')->andReturn($postalAddressId);
        $client->shouldReceive('getClientAddress1')->andReturn('2 Virtual Road');
        $client->shouldReceive('getClientAddress2')->andReturn('Unit 3');
        $client->shouldReceive('getClientBuildingNumber')->andReturn('2');
        $client->shouldReceive('getClientCity')->andReturn('Testville');
        $client->shouldReceive('getClientZip')->andReturn('ZZ2 2BB');
        $client->shouldReceive('getClientState')->andReturn('Exampleshire');
        $client->shouldReceive('getClientCountry')->andReturn('GB');
        $client->shouldReceive('getClientName')->andReturn('Virtual Buyer Ltd');
        $client->shouldReceive('getClientPhone')->andReturn('01632 960010');
        $client->shouldReceive('getClientEmail')->andReturn('buyer@example.test');
        return $client;
    }

    private function clientPeppol(): ClientPeppol
    {
        $cp = TypedMock::of(ClientPeppol::class);
        $cp->shouldReceive('getEndpointid')->andReturn('buyer@example.test');
        $cp->shouldReceive('getEndpointidSchemeid')->andReturn('EM');
        $cp->shouldReceive('getIdentificationid')->andReturn('IDENT-1');
        $cp->shouldReceive('getIdentificationidSchemeid')->andReturn('0088');
        $cp->shouldReceive('getTaxschemecompanyid')->andReturn('GB123456789');
        $cp->shouldReceive('getTaxSchemeid')->andReturn('VAT');
        $cp->shouldReceive('getLegalEntityRegistrationName')->andReturn('Virtual Buyer Limited');
        $cp->shouldReceive('getLegalEntityCompanyid')->andReturn('12345678');
        $cp->shouldReceive('getLegalEntityCompanyidSchemeid')->andReturn('GB:COH');
        $cp->shouldReceive('getLegalEntityCompanyLegalForm')->andReturn('Private limited');
        return $cp;
    }

    private function postalAddress(): PostalAddress
    {
        $pa = TypedMock::of(PostalAddress::class);
        $pa->shouldReceive('getStreetName')->andReturn('9 Postal Street');
        $pa->shouldReceive('getAdditionalStreetName')->andReturn('Block B');
        $pa->shouldReceive('getBuildingNumber')->andReturn('9');
        $pa->shouldReceive('getCityName')->andReturn('Postville');
        $pa->shouldReceive('getPostalZone')->andReturn('ZZ3 3CC');
        $pa->shouldReceive('getCountrysubentity')->andReturn('Postshire');
        $pa->shouldReceive('getCountry')->andReturn('GB');
        return $pa;
    }

    private function invoice(?Client $client): Inv
    {
        $inv = new Inv();
        $inv->setClient($client);
        return $inv;
    }

    private function paRepo(?PostalAddress $address): PostalAddressRepository
    {
        $paR = TypedMock::of(PostalAddressRepository::class);
        $paR->shouldReceive('repoClient')->andReturn($address);
        return $paR;
    }

    private function cpRepo(?ClientPeppol $clientPeppol): ClientPeppolRepository
    {
        $cpR = TypedMock::of(ClientPeppolRepository::class);
        $cpR->shouldReceive('repoClientPeppolLoadedquery')->andReturn($clientPeppol);
        return $cpR;
    }

    /** @return array<string, array<string, mixed>> */
    private function party(): array
    {
        /** @var array<string, array<string, mixed>> $party */
        $party = $this->parser()->buildPeppolAccountingCustomerPartyArray(
            $this->invoice($this->client()),
            $this->paRepo($this->postalAddress()),
            $this->cpRepo($this->clientPeppol()),
        );
        return $party;
    }

    /** @param callable(): mixed $call */
    private function thrown(callable $call): ?\Throwable
    {
        try {
            $call();
        } catch (\Throwable $e) {
            return $e;
        }
        return null;
    }

    // ── buildPeppolAccountingCustomerPartyArray ──────────────────────────────

    public function anInvoiceWithoutAClientCannotHaveACustomerParty(): void
    {
        $error = $this->thrown(fn () => $this->parser()->buildPeppolAccountingCustomerPartyArray(
            $this->invoice(null),
            $this->paRepo(null),
            $this->cpRepo(null),
        ));

        Assert::instanceOf($error, PeppolClientNotFoundException::class);
    }

    public function aClientWithoutAPostalAddressIdIsReportedByClientId(): void
    {
        $error = $this->thrown(fn () => $this->parser()->buildPeppolAccountingCustomerPartyArray(
            $this->invoice($this->client(null)),
            $this->paRepo(null),
            $this->cpRepo($this->clientPeppol()),
        ));

        Assert::instanceOf($error, PeppolBuyerPostalAddressNotFoundException::class);
        Assert::same($error->clientId, 7);
    }

    public function aMissingPostalAddressOrPeppolRecordGivesAnEmptyParty(): void
    {
        $parser = $this->parser();
        $noAddress = $parser->buildPeppolAccountingCustomerPartyArray(
            $this->invoice($this->client()),
            $this->paRepo(null),
            $this->cpRepo($this->clientPeppol()),
        );
        $noPeppol = $parser->buildPeppolAccountingCustomerPartyArray(
            $this->invoice($this->client()),
            $this->paRepo($this->postalAddress()),
            $this->cpRepo(null),
        );

        Assert::same($noAddress, []);
        Assert::same($noPeppol, []);
    }

    public function theCustomerPartyCarriesTheEndpointAndIdentification(): void
    {
        $party = $this->party()['Party'];

        Assert::same($party['EndPointID'], ['value' => 'buyer@example.test', 'schemeID' => 'EM']);
        Assert::same($party['PartyIdentification'], ['ID' => ['value' => 'IDENT-1', 'schemeID' => '0088']]);
    }

    public function theCustomerPartyCarriesThePostalAddressFromTheAddressBook(): void
    {
        /** @var array<string, mixed> $postal */
        $postal = $this->party()['Party']['PostalAddress'];

        Assert::same($postal['StreetName'], '9 Postal Street');
        Assert::same($postal['AdditionalStreetName'], 'Block B');
        Assert::same($postal['AddressLine'], ['Line' => '9']);
        Assert::same($postal['CityName'], 'Postville');
        Assert::same($postal['PostalZone'], 'ZZ3 3CC');
        Assert::same($postal['CountrySubentity'], 'Postshire');
        Assert::same($postal['Country'], ['IdentificationCode' => 'GB', 'ListId' => 'ISO3166-1:Alpha2']);
    }

    public function theCustomerPartyCarriesThePhysicalLocationFromTheClientRecord(): void
    {
        /** @var array<string, mixed> $physical */
        $physical = $this->party()['Party']['PhysicalLocation'];

        Assert::same($physical['StreetName'], '2 Virtual Road');
        Assert::same($physical['AdditionalStreetName'], 'Unit 3');
        Assert::same($physical['AddressLine'], ['Line' => '2']);
        Assert::same($physical['CityName'], 'Testville');
        Assert::same($physical['PostalZone'], 'ZZ2 2BB');
        Assert::same($physical['CountrySubentity'], 'Exampleshire');
        Assert::same($physical['Country'], ['IdentificationCode' => 'GB', 'ListId' => 'ISO3166-1:Alpha2']);
    }

    public function theCustomerPartyCarriesTheContactTaxSchemeAndLegalEntity(): void
    {
        $party = $this->party()['Party'];

        Assert::same($party['Contact'], [
            'Name' => 'Virtual Buyer Ltd', 'Telephone' => '01632 960010', 'ElectronicMail' => 'buyer@example.test',
        ]);
        Assert::same($party['PartyTaxScheme'], ['CompanyID' => 'GB123456789', 'TaxScheme' => ['ID' => 'VAT']]);
        /** @var array<string, string> $legal */
        $legal = $party['PartyLegalEntity'];
        Assert::same($legal['RegistrationName'], 'Virtual Buyer Limited');
    }

    // ── the readers ──────────────────────────────────────────────────────────

    public function theLegalEntityReadsRegistrationNameCompanyIdAttributesAndLegalForm(): void
    {
        $legal = $this->parser()->buildCustomerLegalEntity(['Party' => ['PartyLegalEntity' => [
            'RegistrationName' => 'Reg Name',
            'CompanyID' => 'CID-1',
            'Attributes' => ['schemeID' => 'GB:COH'],
            'CompanyLegalForm' => 'LLP',
        ]]]);

        Assert::same($legal->getRegistrationName(), 'Reg Name');
        Assert::same($legal->getCompanyId(), 'CID-1');
        Assert::same($legal->getCompanyIdAttributeSchemeId(), 'GB:COH');
    }

    public function anEmptyPartyGivesAnEmptyLegalEntity(): void
    {
        $legal = $this->parser()->buildCustomerLegalEntity([]);

        Assert::same($legal->getRegistrationName(), '');
        Assert::same($legal->getCompanyId(), '');
        Assert::same($legal->getCompanyIdAttributeSchemeId(), '');
    }

    public function thePartyTaxSchemeReadsTheCompanyIdAndSchemeId(): void
    {
        $tax = $this->parser()->buildCustomerPartyTaxScheme(['Party' => ['PartyTaxScheme' => [
            'CompanyID' => 'GB123456789',
            'TaxScheme' => ['ID' => 'VAT'],
        ]]]);

        Assert::same($tax->getCompanyId(), 'GB123456789');
        Assert::same($tax->getTaxScheme()->reqId(), 'VAT');
    }

    public function aPartyTaxSchemeWithNoSchemeIdFallsBackToEmpty(): void
    {
        $tax = $this->parser()->buildCustomerPartyTaxScheme(['Party' => ['PartyTaxScheme' => ['CompanyID' => 'X']]]);

        Assert::same($tax->getTaxScheme()->reqId(), '');
    }

    /** @return array<string, mixed> */
    private function addressBlock(string $prefix): array
    {
        return [
            'StreetName' => $prefix . ' street',
            'AdditionalStreetName' => $prefix . ' street 2',
            'AddressLine' => ['Line' => $prefix . ' line'],
            'CityName' => $prefix . ' city',
            'PostalZone' => $prefix . ' zone',
            'CountrySubentity' => $prefix . ' county',
            'Country' => ['IdentificationCode' => 'GB', 'ListId' => 'ISO3166-1:Alpha2'],
        ];
    }

    public function thePhysicalLocationAndPostalAddressReadEveryAddressField(): void
    {
        $party = ['Party' => ['PhysicalLocation' => $this->addressBlock('phys'), 'PostalAddress' => $this->addressBlock('post')]];

        $physical = $this->parser()->buildCustomerPhysicalLocation($party);
        $postal = $this->parser()->buildCustomerPostalAddress($party);

        Assert::same($physical->getStreetName(), 'phys street');
        Assert::same($physical->getAdditionalStreetName(), 'phys street 2');
        Assert::same($physical->getBuildingNumber(), 'phys line');
        Assert::same($physical->getCityName(), 'phys city');
        Assert::same($physical->getPostalZone(), 'phys zone');
        Assert::same($physical->getCountrySubEntity(), 'phys county');
        Assert::same($physical->getCountry()?->getIdentificationCode(), 'GB');
        Assert::same($postal->getStreetName(), 'post street');
        Assert::same($postal->getBuildingNumber(), 'post line');
        Assert::same($postal->getCityName(), 'post city');
        Assert::same($postal->getCountry()?->getIdentificationCode(), 'GB');
    }

    public function anEmptyPartyGivesEmptyAddresses(): void
    {
        $physical = $this->parser()->buildCustomerPhysicalLocation([]);
        $postal = $this->parser()->buildCustomerPostalAddress([]);

        Assert::same($physical->getStreetName(), '');
        Assert::same($physical->getCountry()?->getIdentificationCode(), '');
        Assert::same($postal->getStreetName(), '');
        Assert::same($postal->getCountry()?->getIdentificationCode(), '');
    }

    public function theContactReadsNameFirstAndLastNameTelephoneAndEmail(): void
    {
        $contact = $this->parser()->buildCustomerContact(['Party' => ['Contact' => [
            'Name' => 'Buyer Name', 'FirstName' => 'Bea', 'LastName' => 'Buyer',
            'Telephone' => '01632 960011', 'ElectronicMail' => 'bea@example.test',
        ]]]);

        Assert::same($contact->getName(), 'Buyer Name');
        Assert::same($contact->getFirstName(), 'Bea');
        Assert::same($contact->getLastName(), 'Buyer');
        Assert::same($contact->getTelephone(), '01632 960011');
        Assert::same($contact->getTelefax(), '');
        Assert::same($contact->getElectronicMail(), 'bea@example.test');
    }

    public function anEmptyPartyGivesAnEmptyContact(): void
    {
        $contact = $this->parser()->buildCustomerContact([]);

        Assert::same($contact->getName(), '');
        Assert::same($contact->getElectronicMail(), '');
    }
}
