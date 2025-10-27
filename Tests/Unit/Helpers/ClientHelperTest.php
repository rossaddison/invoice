<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Invoice\Entity\Client;
use App\Invoice\Helpers\ClientHelper;
use App\Invoice\Setting\SettingRepository;
use Codeception\Test\Unit;
use ReflectionClass;
use Yiisoft\Translator\TranslatorInterface;

class ClientHelperTest extends Unit
{
    private ClientHelper $clientHelper;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMock(TranslatorInterface::class);
        
        // Create ClientHelper using reflection to bypass the final SettingRepository dependency
        $settingRepo = $this->createSettingRepository();
        $this->clientHelper = new ClientHelper($settingRepo);
    }

    private function createSettingRepository(): SettingRepository
    {
        $reflection = new ReflectionClass(SettingRepository::class);
        return $reflection->newInstanceWithoutConstructor();
    }

    public function testFormatClientWithClientEntityWithBothNames(): void
    {
        $client = new Client();
        $client->setClient_name('John');
        $client->setClient_surname('Doe');
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame('John Doe', $result);
    }

    public function testFormatClientWithClientEntityWithNameOnly(): void
    {
        $client = new Client();
        $client->setClient_name('John');
        // Don't set surname, leave it as default
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame('John', $result);
    }

    public function testFormatClientWithClientEntityWithEmptyName(): void
    {
        $client = new Client();
        $client->setClient_name('');
        $client->setClient_surname('Doe');
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame(' Doe', $result);
    }

    public function testFormatClientWithClientEntityWithEmptySurname(): void
    {
        $client = new Client();
        $client->setClient_name('John');
        $client->setClient_surname('');
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame('John', $result);
    }

    public function testFormatClientWithClientEntityBothEmpty(): void
    {
        $client = new Client();
        $client->setClient_name('');
        $client->setClient_surname('');
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame('', $result);
    }

    public function testFormatClientWithNullClient(): void
    {
        $result = $this->clientHelper->format_client(null);
        
        $this->assertSame('', $result);
    }

    public function testFormatClientWithArray(): void
    {
        $client = ['name' => 'John', 'surname' => 'Doe'];
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame('', $result);
    }

    public function testFormatClientWithEmptyArray(): void
    {
        $result = $this->clientHelper->format_client([]);
        
        $this->assertSame('', $result);
    }

    public function testFormatClientWithStdClass(): void
    {
        $client = new \stdClass();
        $client->name = 'John';
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame('', $result);
    }

    public function testFormatClientWithLongNames(): void
    {
        $client = new Client();
        $client->setClient_name('Johnathan Alexander');
        $client->setClient_surname('Smith-Williams-Brown');
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame('Johnathan Alexander Smith-Williams-Brown', $result);
    }

    public function testFormatClientWithSpecialCharacters(): void
    {
        $client = new Client();
        $client->setClient_name('José');
        $client->setClient_surname('García-López');
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame('José García-López', $result);
    }

    public function testFormatClientWithUnicodeCharacters(): void
    {
        $client = new Client();
        $client->setClient_name('张');
        $client->setClient_surname('三');
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame('张 三', $result);
    }

    public function testFormatClientTrimsTrailingSpacesWithDefaultSurname(): void
    {
        $client = new Client();
        $client->setClient_name('John   ');
        // Don't set surname, leave it as default empty string
        
        $result = $this->clientHelper->format_client($client);
        
        // When surname is default empty string, rtrim is used because it's not null
        $this->assertSame('John', $result);
    }

    public function testFormatClientTrimsTrailingSpacesWithSurname(): void
    {
        $client = new Client();
        $client->setClient_name('John   ');
        $client->setClient_surname('Doe');
        
        $result = $this->clientHelper->format_client($client);
        
        // When surname exists, rtrim is used to remove trailing spaces after the surname
        $this->assertSame('John    Doe', $result);
    }

    public function testFormatClientWithNameAndEmptyStringForSurname(): void
    {
        $client = new Client();
        $client->setClient_name('John');
        $client->setClient_surname('');
        
        $result = $this->clientHelper->format_client($client);
        
        $this->assertSame('John', $result);
    }

    public function testFormatGenderMale(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('gender.male')
            ->willReturn('Male');
        
        $result = $this->clientHelper->format_gender(0, $this->translator);
        
        $this->assertSame('Male', $result);
    }

    public function testFormatGenderFemale(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('gender.female')
            ->willReturn('Female');
        
        $result = $this->clientHelper->format_gender(1, $this->translator);
        
        $this->assertSame('Female', $result);
    }

    public function testFormatGenderOther(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('gender.other')
            ->willReturn('Other');
        
        $result = $this->clientHelper->format_gender(2, $this->translator);
        
        $this->assertSame('Other', $result);
    }

    public function testFormatGenderWithNegativeValue(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('gender.other')
            ->willReturn('Other');
        
        $result = $this->clientHelper->format_gender(-1, $this->translator);
        
        $this->assertSame('Other', $result);
    }

    public function testFormatGenderWithLargeValue(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('gender.other')
            ->willReturn('Other');
        
        $result = $this->clientHelper->format_gender(999, $this->translator);
        
        $this->assertSame('Other', $result);
    }

    public function testFormatGenderAllValues(): void
    {
        // Create separate translator mocks for each call
        $translator1 = $this->createMock(TranslatorInterface::class);
        $translator1->expects($this->once())
            ->method('translate')
            ->with('gender.male')
            ->willReturn('Male');
        
        $translator2 = $this->createMock(TranslatorInterface::class);
        $translator2->expects($this->once())
            ->method('translate')
            ->with('gender.female')
            ->willReturn('Female');
        
        $translator3 = $this->createMock(TranslatorInterface::class);
        $translator3->expects($this->once())
            ->method('translate')
            ->with('gender.other')
            ->willReturn('Other');
        
        $this->assertSame('Male', $this->clientHelper->format_gender(0, $translator1));
        $this->assertSame('Female', $this->clientHelper->format_gender(1, $translator2));
        $this->assertSame('Other', $this->clientHelper->format_gender(3, $translator3));
    }

    public function testFormatGenderTranslationKeys(): void
    {
        // Create separate translator mocks for each call with different locale translations
        $translator1 = $this->createMock(TranslatorInterface::class);
        $translator1->expects($this->once())
            ->method('translate')
            ->with('gender.male')
            ->willReturn('Masculino');
        
        $translator2 = $this->createMock(TranslatorInterface::class);
        $translator2->expects($this->once())
            ->method('translate')
            ->with('gender.female')
            ->willReturn('Femenino');
        
        $translator3 = $this->createMock(TranslatorInterface::class);
        $translator3->expects($this->once())
            ->method('translate')
            ->with('gender.other')
            ->willReturn('Otro');
        
        $this->assertSame('Masculino', $this->clientHelper->format_gender(0, $translator1));
        $this->assertSame('Femenino', $this->clientHelper->format_gender(1, $translator2));
        $this->assertSame('Otro', $this->clientHelper->format_gender(5, $translator3));
    }

    public function testConstructorAcceptsSettingRepository(): void
    {
        $settingRepo = $this->createSettingRepository();
        $helper = new ClientHelper($settingRepo);
        
        $this->assertInstanceOf(ClientHelper::class, $helper);
    }
}