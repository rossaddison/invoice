<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\Quote\QuoteForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class QuoteFormTest extends TestCase
{
    private function buildQuoteMock(
        string $number = 'Q-0001',
        int $groupId = 1,
        int $clientId = 1,
        int $statusId = 1,
    ): Quote {
        /** @var Quote&\PHPUnit\Framework\MockObject\Stub $quote */
        $quote = $this->createStub(Quote::class);
        $quote->method('getNumber')->willReturn($number);
        $quote->method('getDateCreated')->willReturn(new DateTimeImmutable('2026-01-01'));
        $quote->method('reqGroupId')->willReturn($groupId);
        $quote->method('reqClientId')->willReturn($clientId);
        $quote->method('reqStatusId')->willReturn($statusId);
        $quote->method('getDiscountAmount')->willReturn(0.00);
        $quote->method('getUrlKey')->willReturn('');
        $quote->method('getPassword')->willReturn('');
        $quote->method('getNotes')->willReturn('');
        $quote->method('getInvId')->willReturn(null);
        $quote->method('getSoId')->willReturn(null);
        $quote->method('getDeliveryLocationId')->willReturn(null);
        return $quote;
    }

    public function testDefaultsAreEmpty(): void
    {
        $form = new QuoteForm();

        $this->assertSame('', $form->getNumber());
        $this->assertSame('', $form->getFormName());
        $this->assertNull($form->getGroupId());
        $this->assertNull($form->getClientId());
        $this->assertSame(1, $form->getStatusId());
        $this->assertSame(0.0, $form->getDiscountAmount());
        $this->assertSame('', $form->getUrlKey());
        $this->assertSame('', $form->getPassword());
        $this->assertSame('', $form->getNotes());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new QuoteForm())->getFormName());
    }

    public function testShowPopulatesFromMockedQuote(): void
    {
        /** @var Quote&\PHPUnit\Framework\MockObject\Stub $quote */
        $quote = $this->createStub(Quote::class);
        $quote->method('getNumber')->willReturn('Q-0001');
        $quote->method('getDateCreated')->willReturn(new DateTimeImmutable('2026-01-01'));
        $quote->method('reqGroupId')->willReturn(1);
        $quote->method('reqClientId')->willReturn(5);
        $quote->method('reqStatusId')->willReturn(1);
        $quote->method('getDiscountAmount')->willReturn(10.00);
        $quote->method('getUrlKey')->willReturn('abc123');
        $quote->method('getPassword')->willReturn('');
        $quote->method('getNotes')->willReturn('Standard terms apply');
        $quote->method('getInvId')->willReturn(null);
        $quote->method('getSoId')->willReturn(null);
        $quote->method('getDeliveryLocationId')->willReturn(null);

        $form = QuoteForm::show($quote);

        $this->assertSame('Q-0001', $form->getNumber());
        $this->assertSame(1, $form->getGroupId());
        $this->assertSame(5, $form->getClientId());
        $this->assertSame(1, $form->getStatusId());
        $this->assertSame(10.00, $form->getDiscountAmount());
        $this->assertSame('abc123', $form->getUrlKey());
        $this->assertSame('Standard terms apply', $form->getNotes());
        $this->assertNull($form->getInvId());
        $this->assertNull($form->getSoId());
        $this->assertNull($form->getDeliveryLocationId());
    }

    public function testShowWithLinkedInvoiceAndSalesOrder(): void
    {
        /** @var Quote&\PHPUnit\Framework\MockObject\Stub $quote */
        $quote = $this->createStub(Quote::class);
        $quote->method('getNumber')->willReturn('Q-0002');
        $quote->method('getDateCreated')->willReturn(new DateTimeImmutable('2026-02-01'));
        $quote->method('reqGroupId')->willReturn(2);
        $quote->method('reqClientId')->willReturn(10);
        $quote->method('reqStatusId')->willReturn(3);
        $quote->method('getDiscountAmount')->willReturn(0.00);
        $quote->method('getUrlKey')->willReturn('');
        $quote->method('getPassword')->willReturn('');
        $quote->method('getNotes')->willReturn('');
        $quote->method('getInvId')->willReturn(42);
        $quote->method('getSoId')->willReturn(7);
        $quote->method('getDeliveryLocationId')->willReturn(3);

        $form = QuoteForm::show($quote);

        $this->assertSame(42, $form->getInvId());
        $this->assertSame(7, $form->getSoId());
        $this->assertSame(3, $form->getDeliveryLocationId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $quote = $this->buildQuoteMock();

        $this->assertNotSame(QuoteForm::show($quote), QuoteForm::show($quote));
    }

    public function testStatusIdVariants(): void
    {
        foreach ([1, 2, 3, 4] as $statusId) {
            $form = QuoteForm::show($this->buildQuoteMock(statusId: $statusId));
            $this->assertSame($statusId, $form->getStatusId());
        }
    }
}
