<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Client\ClientForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ClientFormTest extends TestCase
{
    public function testDefaultsAreEmpty(): void
    {
        $form = new ClientForm();

        $this->assertSame('', $form->getFormName());
        $this->assertSame('', $form->getClientName());
        $this->assertSame('', $form->getClientEmail());
        $this->assertFalse($form->getClientActive());
        $this->assertNull($form->getClientAge());
        $this->assertNull($form->getClientGender());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new ClientForm())->getFormName());
    }

    public function testShowPopulatesCoreFields(): void
    {
        $client = new Client();
        $client->setClientName('Acme Corp');
        $client->setClientSurname('Ltd');
        $client->setClientEmail('billing@acme.co.uk');
        $client->setClientPhone('+44 20 7946 0958');
        $client->setClientAddress1('123 High Street');
        $client->setClientAddress2('Floor 2');
        $client->setClientCity('London');
        $client->setClientState('England');
        $client->setClientZip('EC1A 1BB');
        $client->setClientCountry('GB');
        $client->setClientActive(true);
        $client->setClientAge(35);
        $client->setClientGender(1);

        $form = ClientForm::show($client);

        $this->assertSame('Acme Corp', $form->getClientName());
        $this->assertSame('Ltd', $form->getClientSurname());
        $this->assertSame('billing@acme.co.uk', $form->getClientEmail());
        $this->assertSame('+44 20 7946 0958', $form->getClientPhone());
        $this->assertSame('123 High Street', $form->getClientAddress1());
        $this->assertSame('Floor 2', $form->getClientAddress2());
        $this->assertSame('London', $form->getClientCity());
        $this->assertSame('England', $form->getClientState());
        $this->assertSame('EC1A 1BB', $form->getClientZip());
        $this->assertSame('GB', $form->getClientCountry());
        $this->assertTrue($form->getClientActive());
        $this->assertSame(35, $form->getClientAge());
        $this->assertSame(1, $form->getClientGender());
    }

    public function testShowWithBirthdateFormatted(): void
    {
        $client = new Client();
        $client->setClientName('Jane Doe');
        $client->setClientEmail('jane@example.com');
        $client->setClientAge(30);
        $client->setClientGender(2);
        $client->setClientBirthdate(new DateTimeImmutable('1994-06-15'));

        $form = ClientForm::show($client);

        $this->assertSame('1994-06-15', $form->getClientBirthdate());
    }

    public function testShowWithNullBirthdate(): void
    {
        $client = new Client();
        $client->setClientName('John Smith');
        $client->setClientEmail('john@example.com');
        $client->setClientAge(25);
        $client->setClientGender(1);
        $client->setClientBirthdate(null);

        $form = ClientForm::show($client);

        $this->assertNull($form->getClientBirthdate());
    }

    public function testShowWithVatAndTaxFields(): void
    {
        $client = new Client();
        $client->setClientName('VAT Registered Co');
        $client->setClientEmail('vat@co.uk');
        $client->setClientAge(40);
        $client->setClientGender(0);
        $client->setClientVatId('GB123456789');
        $client->setClientTaxCode('TC001');
        $client->setClientLanguage('en-GB');

        $form = ClientForm::show($client);

        $this->assertSame('GB123456789', $form->getClientVatId());
        $this->assertSame('TC001', $form->getClientTaxCode());
        $this->assertSame('en-GB', $form->getClientLanguage());
    }

    public function testShowReturnsNewInstance(): void
    {
        $client = new Client();
        $client->setClientName('Test');
        $client->setClientEmail('t@t.com');
        $client->setClientAge(20);
        $client->setClientGender(1);

        $this->assertNotSame(ClientForm::show($client), ClientForm::show($client));
    }

    public function testOptionalStringFieldsDefaultEmpty(): void
    {
        $form = new ClientForm();

        $this->assertSame('', $form->getClientAddress1());
        $this->assertSame('', $form->getClientCity());
        $this->assertSame('', $form->getClientZip());
        $this->assertSame('', $form->getClientPhone());
    }
}
