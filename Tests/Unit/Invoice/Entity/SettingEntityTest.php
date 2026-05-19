<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Setting\Setting;
use PHPUnit\Framework\TestCase;

class SettingEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $s = new Setting();
        $this->assertFalse($s->hasIdentity());
    }

    public function testReqSettingIdThrowsWhenNotPersisted(): void
    {
        $s = new Setting();
        $this->expectException(\LogicException::class);
        $s->reqSettingId();
    }

    public function testConstructorDefaults(): void
    {
        $s = new Setting();
        $this->assertSame('', $s->getSettingKey());
        $this->assertSame('', $s->getSettingValue());
    }

    public function testSetAndGetSettingKey(): void
    {
        $s = new Setting();
        $s->setSettingKey('currency_symbol');
        $this->assertSame('currency_symbol', $s->getSettingKey());
    }

    public function testSetAndGetSettingValue(): void
    {
        $s = new Setting();
        $s->setSettingValue('£');
        $this->assertSame('£', $s->getSettingValue());
    }

    public function testKeyValuePair(): void
    {
        $s = new Setting('date_format', 'd/m/Y');
        $this->assertSame('date_format', $s->getSettingKey());
        $this->assertSame('d/m/Y', $s->getSettingValue());
    }
}
