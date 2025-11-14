<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Invoice\Entity\Client;
use App\Invoice\Entity\ClientCustom;
use App\Invoice\Entity\CustomField;
use PHPUnit\Framework\TestCase;

class ClientCustomEntityTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $clientCustom = new ClientCustom();
        
        // ID getters return string conversion of null values, which becomes empty strings
        $this->assertSame('', $clientCustom->getId());
        $this->assertSame('', $clientCustom->getClient_id());
        $this->assertSame('', $clientCustom->getCustom_field_id());
        $this->assertNull($clientCustom->getValue());
        $this->assertNull($clientCustom->getClient());
        $this->assertNull($clientCustom->getCustomField());
    }

    public function testConstructorWithAllParameters(): void
    {
        $clientCustom = new ClientCustom(
            id: 1,
            client_id: 100,
            custom_field_id: 200,
            value: 'Test Custom Value'
        );
        
        $this->assertSame('1', $clientCustom->getId());
        $this->assertSame('100', $clientCustom->getClient_id());
        $this->assertSame('200', $clientCustom->getCustom_field_id());
        $this->assertSame('Test Custom Value', $clientCustom->getValue());
    }

    public function testIdSetterAndGetter(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setId(50);
        
        $this->assertSame('50', $clientCustom->getId());
    }

    public function testClientIdSetterAndGetter(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setClient_id(150);
        
        $this->assertSame('150', $clientCustom->getClient_id());
    }

    public function testCustomFieldIdSetterAndGetter(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setCustom_field_id(300);
        
        $this->assertSame('300', $clientCustom->getCustom_field_id());
    }

    public function testValueSetterAndGetter(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setValue('Updated Custom Value');
        
        $this->assertSame('Updated Custom Value', $clientCustom->getValue());
    }

    public function testClientRelationship(): void
    {
        $clientCustom = new ClientCustom();
        $client = $this->createMock(Client::class);
        
        // Initially null
        $this->assertNull($clientCustom->getClient());
        
        // Note: There's no setter for client relationship in the entity
        // This tests the getter returns null as expected initially
        $this->assertNull($clientCustom->getClient());
    }

    public function testCustomFieldRelationship(): void
    {
        $clientCustom = new ClientCustom();
        $customField = $this->createMock(CustomField::class);
        
        // Initially null
        $this->assertNull($clientCustom->getCustomField());
        
        // Note: There's no setter for custom field relationship in the entity
        // This tests the getter returns null as expected initially
        $this->assertNull($clientCustom->getCustomField());
    }

    public function testIdTypeConversion(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setId(999);
        
        $this->assertIsString($clientCustom->getId());
        $this->assertSame('999', $clientCustom->getId());
    }

    public function testClientIdTypeConversion(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setClient_id(777);
        
        $this->assertIsString($clientCustom->getClient_id());
        $this->assertSame('777', $clientCustom->getClient_id());
    }

    public function testCustomFieldIdTypeConversion(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setCustom_field_id(888);
        
        $this->assertIsString($clientCustom->getCustom_field_id());
        $this->assertSame('888', $clientCustom->getCustom_field_id());
    }

    public function testEmptyStringValue(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setValue('');
        
        $this->assertSame('', $clientCustom->getValue());
    }

    public function testLongTextValue(): void
    {
        $clientCustom = new ClientCustom();
        $longText = str_repeat('This is a long custom field value. ', 100);
        $clientCustom->setValue($longText);
        
        $this->assertSame($longText, $clientCustom->getValue());
    }

    public function testSpecialCharactersInValue(): void
    {
        $clientCustom = new ClientCustom();
        $specialValue = 'Special chars: àáâãäåæçèéêë ™€£¥ 中文 العربية русский';
        $clientCustom->setValue($specialValue);
        
        $this->assertSame($specialValue, $clientCustom->getValue());
    }

    public function testJsonStringValue(): void
    {
        $clientCustom = new ClientCustom();
        $jsonValue = '{"name": "John Doe", "age": 30, "city": "New York"}';
        $clientCustom->setValue($jsonValue);
        
        $this->assertSame($jsonValue, $clientCustom->getValue());
    }

    public function testHtmlStringValue(): void
    {
        $clientCustom = new ClientCustom();
        $htmlValue = '<div class="custom-field"><p>This is <strong>HTML</strong> content</p></div>';
        $clientCustom->setValue($htmlValue);
        
        $this->assertSame($htmlValue, $clientCustom->getValue());
    }

    public function testNumericStringValue(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setValue('123.456');
        
        $this->assertSame('123.456', $clientCustom->getValue());
    }

    public function testZeroIds(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setId(0);
        $clientCustom->setClient_id(0);
        $clientCustom->setCustom_field_id(0);
        
        $this->assertSame('0', $clientCustom->getId());
        $this->assertSame('0', $clientCustom->getClient_id());
        $this->assertSame('0', $clientCustom->getCustom_field_id());
    }

    public function testNegativeIds(): void
    {
        $clientCustom = new ClientCustom();
        $clientCustom->setId(-1);
        $clientCustom->setClient_id(-5);
        $clientCustom->setCustom_field_id(-10);
        
        $this->assertSame('-1', $clientCustom->getId());
        $this->assertSame('-5', $clientCustom->getClient_id());
        $this->assertSame('-10', $clientCustom->getCustom_field_id());
    }

    public function testCompleteClientCustomSetup(): void
    {
        $clientCustom = new ClientCustom();
        
        $clientCustom->setId(1);
        $clientCustom->setClient_id(100);
        $clientCustom->setCustom_field_id(200);
        $clientCustom->setValue('Complete setup value');
        
        $this->assertSame('1', $clientCustom->getId());
        $this->assertSame('100', $clientCustom->getClient_id());
        $this->assertSame('200', $clientCustom->getCustom_field_id());
        $this->assertSame('Complete setup value', $clientCustom->getValue());
        $this->assertNull($clientCustom->getClient());
        $this->assertNull($clientCustom->getCustomField());
    }

    public function testMethodReturnTypes(): void
    {
        $clientCustom = new ClientCustom(
            id: 1,
            client_id: 100,
            custom_field_id: 200,
            value: 'Test value'
        );
        
        $this->assertIsString($clientCustom->getId());
        $this->assertIsString($clientCustom->getClient_id());
        $this->assertIsString($clientCustom->getCustom_field_id());
        $this->assertIsString($clientCustom->getValue());
        $this->assertNull($clientCustom->getClient());
        $this->assertNull($clientCustom->getCustomField());
    }

    public function testMultilineValue(): void
    {
        $clientCustom = new ClientCustom();
        $multilineValue = "Line 1\nLine 2\nLine 3\n\nLine 5 with spaces";
        $clientCustom->setValue($multilineValue);
        
        $this->assertSame($multilineValue, $clientCustom->getValue());
    }

    public function testTabsAndSpecialWhitespace(): void
    {
        $clientCustom = new ClientCustom();
        $valueWithTabs = "Column1\tColumn2\tColumn3\r\nNew Row\t\t";
        $clientCustom->setValue($valueWithTabs);
        
        $this->assertSame($valueWithTabs, $clientCustom->getValue());
    }

    public function testValueWithQuotes(): void
    {
        $clientCustom = new ClientCustom();
        $valueWithQuotes = 'Single \'quotes\' and "double quotes" and `backticks`';
        $clientCustom->setValue($valueWithQuotes);
        
        $this->assertSame($valueWithQuotes, $clientCustom->getValue());
    }

    public function testSqlInjectionStringValue(): void
    {
        $clientCustom = new ClientCustom();
        $sqlValue = "'; DROP TABLE users; --";
        $clientCustom->setValue($sqlValue);
        
        $this->assertSame($sqlValue, $clientCustom->getValue());
    }

    public function testXssStringValue(): void
    {
        $clientCustom = new ClientCustom();
        $xssValue = '<script>alert("XSS")</script>';
        $clientCustom->setValue($xssValue);
        
        $this->assertSame($xssValue, $clientCustom->getValue());
    }

    public function testUrlValue(): void
    {
        $clientCustom = new ClientCustom();
        $urlValue = 'https://example.com/path?param=value&other=123#section';
        $clientCustom->setValue($urlValue);
        
        $this->assertSame($urlValue, $clientCustom->getValue());
    }

    public function testEmailValue(): void
    {
        $clientCustom = new ClientCustom();
        $emailValue = 'user.name+tag@example.com';
        $clientCustom->setValue($emailValue);
        
        $this->assertSame($emailValue, $clientCustom->getValue());
    }

    public function testPhoneNumberValue(): void
    {
        $clientCustom = new ClientCustom();
        $phoneValue = '+1 (555) 123-4567 ext. 890';
        $clientCustom->setValue($phoneValue);
        
        $this->assertSame($phoneValue, $clientCustom->getValue());
    }

    public function testDateTimeStringValue(): void
    {
        $clientCustom = new ClientCustom();
        $dateTimeValue = '2024-12-25 14:30:00.123456';
        $clientCustom->setValue($dateTimeValue);
        
        $this->assertSame($dateTimeValue, $clientCustom->getValue());
    }

    public function testBooleanStringValue(): void
    {
        $clientCustom = new ClientCustom();
        
        $clientCustom->setValue('true');
        $this->assertSame('true', $clientCustom->getValue());
        
        $clientCustom->setValue('false');
        $this->assertSame('false', $clientCustom->getValue());
        
        $clientCustom->setValue('1');
        $this->assertSame('1', $clientCustom->getValue());
        
        $clientCustom->setValue('0');
        $this->assertSame('0', $clientCustom->getValue());
    }

    public function testArrayStringValue(): void
    {
        $clientCustom = new ClientCustom();
        $arrayValue = '[1, 2, 3, "test", {"key": "value"}]';
        $clientCustom->setValue($arrayValue);
        
        $this->assertSame($arrayValue, $clientCustom->getValue());
    }

    public function testCsvStringValue(): void
    {
        $clientCustom = new ClientCustom();
        $csvValue = 'Name,Age,City\n"John Doe",30,"New York"\n"Jane Smith",25,"Los Angeles"';
        $clientCustom->setValue($csvValue);
        
        $this->assertSame($csvValue, $clientCustom->getValue());
    }

    public function testXmlStringValue(): void
    {
        $clientCustom = new ClientCustom();
        $xmlValue = '<?xml version="1.0"?><root><item id="1">Test</item></root>';
        $clientCustom->setValue($xmlValue);
        
        $this->assertSame($xmlValue, $clientCustom->getValue());
    }

    public function testBase64Value(): void
    {
        $clientCustom = new ClientCustom();
        $base64Value = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
        $clientCustom->setValue($base64Value);
        
        $this->assertSame($base64Value, $clientCustom->getValue());
    }

    public function testUnicodeEmojisValue(): void
    {
        $clientCustom = new ClientCustom();
        $emojiValue = '😀😁😂🤣😃😄😅😆😉😊😋😎😍😘🥰😗😙😚🙂🤗🤩🤔🤨😐😑😶🙄😏😣😥😮🤐😯😪😫🥱😴😌😛😜😝🤤😒😓😔😕🙃🤑😲☹️🙁😖😞😟😤😢😭😦😧😨😩🤯😬😰😱🥵🥶😳🤪😵🥴😠😡🤬😷🤒🤕🤢🤮🤧😇🥳🥺🤠🤥🤫🤭🧐🤓😈👿👹👺💀☠️👻👽👾🤖💩😺😸😹😻😼😽🙀😿😾';
        $clientCustom->setValue($emojiValue);
        
        $this->assertSame($emojiValue, $clientCustom->getValue());
    }

    public function testEntityStateConsistency(): void
    {
        $clientCustom = new ClientCustom(
            id: 999,
            client_id: 888,
            custom_field_id: 777,
            value: 'Consistency test'
        );
        
        // Verify initial state
        $this->assertSame('999', $clientCustom->getId());
        $this->assertSame('888', $clientCustom->getClient_id());
        $this->assertSame('777', $clientCustom->getCustom_field_id());
        $this->assertSame('Consistency test', $clientCustom->getValue());
        
        // Modify and verify changes
        $clientCustom->setId(111);
        $clientCustom->setClient_id(222);
        $clientCustom->setCustom_field_id(333);
        $clientCustom->setValue('Modified value');
        
        $this->assertSame('111', $clientCustom->getId());
        $this->assertSame('222', $clientCustom->getClient_id());
        $this->assertSame('333', $clientCustom->getCustom_field_id());
        $this->assertSame('Modified value', $clientCustom->getValue());
    }

    public function testLargeIdValues(): void
    {
        $clientCustom = new ClientCustom();
        
        $largeId = PHP_INT_MAX;
        $clientCustom->setId($largeId);
        $clientCustom->setClient_id($largeId - 1);
        $clientCustom->setCustom_field_id($largeId - 2);
        
        $this->assertSame((string)$largeId, $clientCustom->getId());
        $this->assertSame((string)($largeId - 1), $clientCustom->getClient_id());
        $this->assertSame((string)($largeId - 2), $clientCustom->getCustom_field_id());
    }
}
