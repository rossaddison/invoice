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
        
        $this->assertNull($setting->getSetting_id());
        $this->assertSame('', $setting->getSetting_key());
        $this->assertSame('', $setting->getSetting_value());
    }

    public function testConstructorWithParameters(): void
    {
        $setting = new Setting('theme', 'dark');
        
        $this->assertNull($setting->getSetting_id());
        $this->assertSame('theme', $setting->getSetting_key());
        $this->assertSame('dark', $setting->getSetting_value());
    }

    public function testSettingKeySetterAndGetter(): void
    {
        $setting = new Setting();
        $setting->setSetting_key('language');
        
        $this->assertSame('language', $setting->getSetting_key());
    }

    public function testSettingValueSetterAndGetter(): void
    {
        $setting = new Setting();
        $setting->setSetting_value('en_US');
        
        $this->assertSame('en_US', $setting->getSetting_value());
    }

    public function testSettingIdGetter(): void
    {
        $setting = new Setting();
        
        $this->assertNull($setting->getSetting_id());
    }

    public function testCommonSettingTypes(): void
    {
        $booleanSetting = new Setting('debug_mode', 'true');
        $this->assertSame('debug_mode', $booleanSetting->getSetting_key());
        $this->assertSame('true', $booleanSetting->getSetting_value());

        $numericSetting = new Setting('max_files', '100');
        $this->assertSame('max_files', $numericSetting->getSetting_key());
        $this->assertSame('100', $numericSetting->getSetting_value());

        $pathSetting = new Setting('upload_path', '/uploads/documents');
        $this->assertSame('upload_path', $pathSetting->getSetting_key());
        $this->assertSame('/uploads/documents', $pathSetting->getSetting_value());
    }

    public function testLongSettingKeys(): void
    {
        $longKey = str_repeat('key_', 20); // 80 characters
        $setting = new Setting($longKey, 'value');
        
        $this->assertSame($longKey, $setting->getSetting_key());
        $this->assertSame('value', $setting->getSetting_value());
    }

    public function testLongSettingValues(): void
    {
        $longValue = str_repeat('This is a very long setting value. ', 5); // ~175 characters
        $setting = new Setting('description', $longValue);
        
        $this->assertSame('description', $setting->getSetting_key());
        $this->assertSame($longValue, $setting->getSetting_value());
    }

    public function testCompleteSettingSetup(): void
    {
        $setting = new Setting('email_host', 'smtp.example.com');
        $setting->setSetting_key('smtp_host');
        $setting->setSetting_value('mail.company.com');
        
        $this->assertSame('smtp_host', $setting->getSetting_key());
        $this->assertSame('mail.company.com', $setting->getSetting_value());
    }

    public function testChainedSetterCalls(): void
    {
        $setting = new Setting();
        $setting->setSetting_key('timezone');
        $setting->setSetting_value('America/New_York');
        
        $this->assertSame('timezone', $setting->getSetting_key());
        $this->assertSame('America/New_York', $setting->getSetting_value());
    }

    public function testEmptySettingHandling(): void
    {
        $setting = new Setting('', '');
        
        $this->assertSame('', $setting->getSetting_key());
        $this->assertSame('', $setting->getSetting_value());
    }

    public function testSpecialCharactersInSettings(): void
    {
        $setting = new Setting('special_chars', 'Value with @#$%^&*()');
        
        $this->assertSame('special_chars', $setting->getSetting_key());
        $this->assertSame('Value with @#$%^&*()', $setting->getSetting_value());
    }

    public function testUnicodeInSettings(): void
    {
        $setting = new Setting('unicode_test', 'Tëst Vâlùe 测试');
        
        $this->assertSame('unicode_test', $setting->getSetting_key());
        $this->assertSame('Tëst Vâlùe 测试', $setting->getSetting_value());
    }

    public function testJsonValueHandling(): void
    {
        $jsonValue = '{"theme": "dark", "language": "en"}';
        $setting = new Setting('user_preferences', $jsonValue);
        
        $this->assertSame('user_preferences', $setting->getSetting_key());
        $this->assertSame($jsonValue, $setting->getSetting_value());
    }
}
