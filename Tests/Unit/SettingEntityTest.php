<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\Setting;
use Codeception\Test\Unit;

final class SettingEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $setting = new Setting();
        
        $this->assertNull($setting->getSettingId());
        $this->assertSame('', $setting->getSettingKey());
        $this->assertSame('', $setting->getSettingValue());
    }

    public function testConstructorWithParameters(): void
    {
        $setting = new Setting('theme', 'dark');
        
        $this->assertNull($setting->getSettingId());
        $this->assertSame('theme', $setting->getSettingKey());
        $this->assertSame('dark', $setting->getSettingValue());
    }

    public function testSettingKeySetterAndGetter(): void
    {
        $setting = new Setting();
        $setting->setSettingKey('language');
        
        $this->assertSame('language', $setting->getSettingKey());
    }

    public function testSettingValueSetterAndGetter(): void
    {
        $setting = new Setting();
        $setting->setSettingValue('en_US');
        
        $this->assertSame('en_US', $setting->getSettingValue());
    }

    public function testSettingIdGetter(): void
    {
        $setting = new Setting();
        
        $this->assertNull($setting->getSettingId());
    }

    public function testCommonSettingTypes(): void
    {
        $booleanSetting = new Setting('debug_mode', 'true');
        $this->assertSame('debug_mode', $booleanSetting->getSettingKey());
        $this->assertSame('true', $booleanSetting->getSettingValue());

        $numericSetting = new Setting('max_files', '100');
        $this->assertSame('max_files', $numericSetting->getSettingKey());
        $this->assertSame('100', $numericSetting->getSettingValue());

        $pathSetting = new Setting('upload_path', '/uploads/documents');
        $this->assertSame('upload_path', $pathSetting->getSettingKey());
        $this->assertSame('/uploads/documents', $pathSetting->getSettingValue());
    }

    public function testLongSettingKeys(): void
    {
        $longKey = str_repeat('key_', 20); // 80 characters
        $setting = new Setting($longKey, 'value');
        
        $this->assertSame($longKey, $setting->getSettingKey());
        $this->assertSame('value', $setting->getSettingValue());
    }

    public function testLongSettingValues(): void
    {
        $longValue = str_repeat('This is a very long setting value. ', 5); // ~175 characters
        $setting = new Setting('description', $longValue);
        
        $this->assertSame('description', $setting->getSettingKey());
        $this->assertSame($longValue, $setting->getSettingValue());
    }

    public function testCompleteSettingSetup(): void
    {
        $setting = new Setting('email_host', 'smtp.example.com');
        $setting->setSettingKey('smtp_host');
        $setting->setSettingValue('mail.company.com');
        
        $this->assertSame('smtp_host', $setting->getSettingKey());
        $this->assertSame('mail.company.com', $setting->getSettingValue());
    }

    public function testChainedSetterCalls(): void
    {
        $setting = new Setting();
        $setting->setSettingKey('timezone');
        $setting->setSettingValue('America/New_York');
        
        $this->assertSame('timezone', $setting->getSettingKey());
        $this->assertSame('America/New_York', $setting->getSettingValue());
    }

    public function testEmptySettingHandling(): void
    {
        $setting = new Setting('', '');
        
        $this->assertSame('', $setting->getSettingKey());
        $this->assertSame('', $setting->getSettingValue());
    }

    public function testSpecialCharactersInSettings(): void
    {
        $setting = new Setting('special_chars', 'Value with @#$%^&*()');
        
        $this->assertSame('special_chars', $setting->getSettingKey());
        $this->assertSame('Value with @#$%^&*()', $setting->getSettingValue());
    }

    public function testUnicodeInSettings(): void
    {
        $setting = new Setting('unicode_test', 'Tëst Vâlùe 测试');
        
        $this->assertSame('unicode_test', $setting->getSettingKey());
        $this->assertSame('Tëst Vâlùe 测试', $setting->getSettingValue());
    }

    public function testJsonValueHandling(): void
    {
        $jsonValue = '{"theme": "dark", "language": "en"}';
        $setting = new Setting('user_preferences', $jsonValue);
        
        $this->assertSame('user_preferences', $setting->getSettingKey());
        $this->assertSame($jsonValue, $setting->getSettingValue());
    }
}
