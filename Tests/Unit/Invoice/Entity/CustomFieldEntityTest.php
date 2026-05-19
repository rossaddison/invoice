<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\CustomField\CustomField;
use PHPUnit\Framework\TestCase;

class CustomFieldEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $cf = new CustomField();
        $this->assertFalse($cf->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $cf = new CustomField();
        $this->expectException(\LogicException::class);
        $cf->reqId();
    }

    public function testHasIdentityReturnsTrueAfterSetId(): void
    {
        $cf = new CustomField();
        $cf->setId(1);
        $this->assertTrue($cf->hasIdentity());
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $cf = new CustomField();
        $cf->setId(7);
        $this->assertSame(7, $cf->reqId());
    }

    public function testConstructorDefaults(): void
    {
        $cf = new CustomField();
        $this->assertSame('', $cf->getTable());
        $this->assertSame('', $cf->getLabel());
        $this->assertSame('', $cf->getType());
        $this->assertFalse($cf->getRequired());
        $this->assertFalse($cf->getEmailMultiple());
        $this->assertSame('hard', $cf->getTextAreaWrap());
    }

    public function testSetAndGetTable(): void
    {
        $cf = new CustomField();
        $cf->setTable('inv');
        $this->assertSame('inv', $cf->getTable());
    }

    public function testSetAndGetLabel(): void
    {
        $cf = new CustomField();
        $cf->setLabel('Purchase Order Number');
        $this->assertSame('Purchase Order Number', $cf->getLabel());
    }

    public function testSetAndGetType(): void
    {
        $cf = new CustomField();
        $cf->setType('TEXT');
        $this->assertSame('TEXT', $cf->getType());
    }

    public function testSetAndGetRequired(): void
    {
        $cf = new CustomField();
        $cf->setRequired(true);
        $this->assertTrue($cf->getRequired());
    }

    public function testSetAndGetEmailMinMaxLength(): void
    {
        $cf = new CustomField();
        $cf->setEmailMinLength(5);
        $cf->setEmailMaxLength(100);
        $this->assertSame(5, $cf->getEmailMinLength());
        $this->assertSame(100, $cf->getEmailMaxLength());
    }

    public function testSetAndGetTextMinMaxLength(): void
    {
        $cf = new CustomField();
        $cf->setTextMinLength(1);
        $cf->setTextMaxLength(150);
        $this->assertSame(1, $cf->getTextMinLength());
        $this->assertSame(150, $cf->getTextMaxLength());
    }

    public function testSetAndGetTextAreaDimensions(): void
    {
        $cf = new CustomField();
        $cf->setTextAreaCols(40);
        $cf->setTextAreaRows(5);
        $cf->setTextAreaWrap('soft');
        $this->assertSame(40, $cf->getTextAreaCols());
        $this->assertSame(5, $cf->getTextAreaRows());
        $this->assertSame('soft', $cf->getTextAreaWrap());
    }

    public function testSetAndGetNumberMinMax(): void
    {
        $cf = new CustomField();
        $cf->setNumberMin(0);
        $cf->setNumberMax(999);
        $this->assertSame(0, $cf->getNumberMin());
        $this->assertSame(999, $cf->getNumberMax());
    }

    public function testSetAndGetUrlMinMaxLength(): void
    {
        $cf = new CustomField();
        $cf->setUrlMinLength(10);
        $cf->setUrlMaxLength(200);
        $this->assertSame(10, $cf->getUrlMinLength());
        $this->assertSame(200, $cf->getUrlMaxLength());
    }

    public function testSetAndGetLocationAndOrder(): void
    {
        $cf = new CustomField();
        $cf->setLocation(2);
        $cf->setOrder(10);
        $this->assertSame(2, $cf->getLocation());
        $this->assertSame(10, $cf->getOrder());
    }
}
