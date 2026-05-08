<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\Qa\Qa;
use PHPUnit\Framework\TestCase;

class QaEntityTest extends TestCase
{
    public function testIsPersistedReturnsFalseByDefault(): void
    {
        $qa = new Qa();
        $this->assertFalse($qa->hasIdentity());
    }

    public function testReqIdThrowsWhenNotPersisted(): void
    {
        $qa = new Qa();
        $this->expectException(\LogicException::class);
        $qa->reqId();
    }

    public function testReqIdReturnsIntAfterSetId(): void
    {
        $qa = new Qa();
        $qa->setId(7);
        $this->assertSame(7, $qa->reqId());
        $this->assertTrue($qa->hasIdentity());
    }

    public function testConstructorWithDefaults(): void
    {
        $qa = new Qa();
        $this->assertSame('', $qa->getQuestion());
        $this->assertSame('', $qa->getAnswer());
        $this->assertNull($qa->getSortOrder());
        $this->assertSame(0, $qa->getActive());
    }

    public function testConstructorWithAllParameters(): void
    {
        $qa = new Qa(
            question: 'What is UBL?',
            answer: 'Universal Business Language.',
            sort_order: 1,
            active: 1,
        );

        $this->assertSame('What is UBL?', $qa->getQuestion());
        $this->assertSame('Universal Business Language.', $qa->getAnswer());
        $this->assertSame(1, $qa->getSortOrder());
        $this->assertSame(1, $qa->getActive());
        $this->assertFalse($qa->hasIdentity());
    }

    public function testQuestionSetterAndGetter(): void
    {
        $qa = new Qa();
        $qa->setQuestion('How does Peppol work?');
        $this->assertSame('How does Peppol work?', $qa->getQuestion());
    }

    public function testAnswerSetterAndGetter(): void
    {
        $qa = new Qa();
        $qa->setAnswer('Peppol routes e-invoices via access points.');
        $this->assertSame('Peppol routes e-invoices via access points.', $qa->getAnswer());
    }

    public function testSortOrderSetterAndGetter(): void
    {
        $qa = new Qa();
        $qa->setSortOrder(5);
        $this->assertSame(5, $qa->getSortOrder());
    }

    public function testActiveSetterAndGetter(): void
    {
        $qa = new Qa();
        $qa->setActive(1);
        $this->assertSame(1, $qa->getActive());

        $qa->setActive(0);
        $this->assertSame(0, $qa->getActive());
    }

    public function testSetIdUpdatesPersistedState(): void
    {
        $qa = new Qa();
        $this->assertFalse($qa->hasIdentity());
        $qa->setId(42);
        $this->assertTrue($qa->hasIdentity());
        $this->assertSame(42, $qa->reqId());
    }

    public function testEmptyStringFields(): void
    {
        $qa = new Qa();
        $qa->setQuestion('');
        $qa->setAnswer('');
        $this->assertSame('', $qa->getQuestion());
        $this->assertSame('', $qa->getAnswer());
    }

    public function testLongQuestionAndAnswer(): void
    {
        $qa = new Qa();
        $long = str_repeat('A', 1000);
        $qa->setQuestion($long);
        $qa->setAnswer($long);
        $this->assertSame($long, $qa->getQuestion());
        $this->assertSame($long, $qa->getAnswer());
    }

    public function testZeroSortOrder(): void
    {
        $qa = new Qa(sort_order: 0);
        $this->assertSame(0, $qa->getSortOrder());
    }

    public function testMultipleSetIdCalls(): void
    {
        $qa = new Qa();
        $qa->setId(1);
        $qa->setId(99);
        $this->assertSame(99, $qa->reqId());
    }

    public function testReqIdReturnType(): void
    {
        $qa = new Qa();
        $qa->setId(3);
        $this->assertIsInt($qa->reqId());
    }
}
