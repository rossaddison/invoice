<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Inv\InvForm;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class InvFormTest extends TestCase
{
    private function buildInvMock(
        int $clientId = 1,
        int $groupId = 1,
        int $statusId = 1,
        bool $readOnly = false,
    ): Inv {
        /** @var Inv&\PHPUnit\Framework\MockObject\MockObject $inv */
        $inv = $this->createMock(Inv::class);
        $inv->method('getNumber')->willReturn('');
        $inv->method('getDateCreated')->willReturn(new DateTimeImmutable('2026-01-01'));
        $inv->method('getDateModified')->willReturn(new DateTimeImmutable('2026-01-01'));
        $inv->method('reqClientId')->willReturn($clientId);
        $inv->method('reqGroupId')->willReturn($groupId);
        $inv->method('reqStatusId')->willReturn($statusId);
        $inv->method('getContractId')->willReturn(null);
        $inv->method('getDeliveryId')->willReturn(null);
        $inv->method('getDeliveryLocationId')->willReturn(null);
        $inv->method('getPostalAddressId')->willReturn(null);
        $inv->method('getSoId')->willReturn(null);
        $inv->method('getQuoteId')->willReturn(null);
        $inv->method('getIsReadOnly')->willReturn($readOnly);
        $inv->method('getPassword')->willReturn('');
        $inv->method('getTimeCreated')->willReturn(new DateTimeImmutable('2026-01-01 09:00:00'));
        $inv->method('getDateTaxPoint')->willReturn(new DateTimeImmutable('2026-01-01'));
        $inv->method('getStandInCode')->willReturn('');
        $inv->method('getDateSupplied')->willReturn(new DateTimeImmutable('2026-01-01'));
        $inv->method('getDateDue')->willReturn(new DateTimeImmutable('2026-02-01'));
        $inv->method('getDiscountAmount')->willReturn(0.00);
        $inv->method('getTerms')->willReturn('');
        $inv->method('getNote')->willReturn('');
        $inv->method('getDocumentDescription')->willReturn('');
        $inv->method('getClientPoNumber')->willReturn('');
        $inv->method('getClientPoPerson')->willReturn('');
        $inv->method('getUrlKey')->willReturn('');
        $inv->method('getPaymentMethod')->willReturn(0);
        $inv->method('getCreditinvoiceParentId')->willReturn(null);
        $inv->method('getClient')->willReturn(null);
        return $inv;
    }

    public function testDefaultsAreEmpty(): void
    {
        $form = new InvForm();

        $this->assertSame('', $form->getFormName());
        $this->assertSame('', $form->getNumber());
        $this->assertNull($form->getGroupId());
        $this->assertNull($form->getClientId());
        $this->assertSame(1, $form->getStatusId());
        $this->assertSame(0.00, $form->getDiscountAmount());
        $this->assertFalse($form->getIsReadOnly());
        $this->assertSame('', $form->getNote());
        $this->assertSame('', $form->getTerms());
        $this->assertNull($form->getClient());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new InvForm())->getFormName());
    }

    public function testShowPopulatesCoreFields(): void
    {
        /** @var Inv&\PHPUnit\Framework\MockObject\MockObject $inv */
        $inv = $this->createMock(Inv::class);
        $inv->method('getNumber')->willReturn('INV-0001');
        $inv->method('getDateCreated')->willReturn(new DateTimeImmutable('2026-01-01'));
        $inv->method('getDateModified')->willReturn(new DateTimeImmutable('2026-01-02'));
        $inv->method('reqClientId')->willReturn(3);
        $inv->method('reqGroupId')->willReturn(1);
        $inv->method('reqStatusId')->willReturn(2);
        $inv->method('getContractId')->willReturn(null);
        $inv->method('getDeliveryId')->willReturn(null);
        $inv->method('getDeliveryLocationId')->willReturn(null);
        $inv->method('getPostalAddressId')->willReturn(null);
        $inv->method('getSoId')->willReturn(null);
        $inv->method('getQuoteId')->willReturn(null);
        $inv->method('getIsReadOnly')->willReturn(false);
        $inv->method('getPassword')->willReturn('');
        $inv->method('getTimeCreated')->willReturn(new DateTimeImmutable('2026-01-01 09:00:00'));
        $inv->method('getDateTaxPoint')->willReturn(new DateTimeImmutable('2026-01-01'));
        $inv->method('getStandInCode')->willReturn('');
        $inv->method('getDateSupplied')->willReturn(new DateTimeImmutable('2026-01-01'));
        $inv->method('getDateDue')->willReturn(new DateTimeImmutable('2026-02-01'));
        $inv->method('getDiscountAmount')->willReturn(0.00);
        $inv->method('getTerms')->willReturn('Net 30');
        $inv->method('getNote')->willReturn('Please pay promptly');
        $inv->method('getDocumentDescription')->willReturn('');
        $inv->method('getClientPoNumber')->willReturn('PO-12345');
        $inv->method('getClientPoPerson')->willReturn('Jane Buyer');
        $inv->method('getUrlKey')->willReturn('');
        $inv->method('getPaymentMethod')->willReturn(1);
        $inv->method('getCreditinvoiceParentId')->willReturn(null);
        $inv->method('getClient')->willReturn(null);

        $form = InvForm::show($inv);

        $this->assertSame('INV-0001', $form->getNumber());
        $this->assertSame(3, $form->getClientId());
        $this->assertSame(1, $form->getGroupId());
        $this->assertSame(2, $form->getStatusId());
        $this->assertSame('Net 30', $form->getTerms());
        $this->assertSame('Please pay promptly', $form->getNote());
        $this->assertSame('PO-12345', $form->getClientPoNumber());
        $this->assertSame('Jane Buyer', $form->getClientPoPerson());
        $this->assertSame(1, $form->getPaymentMethod());
        $this->assertFalse($form->getIsReadOnly());
    }

    public function testShowWithReadOnlyFlag(): void
    {
        $form = InvForm::show($this->buildInvMock(clientId: 1, groupId: 1, statusId: 4, readOnly: true));

        $this->assertTrue($form->getIsReadOnly());
        $this->assertSame(4, $form->getStatusId());
    }

    public function testShowReturnsNewInstance(): void
    {
        $inv = $this->buildInvMock();

        $this->assertNotSame(InvForm::show($inv), InvForm::show($inv));
    }

    public function testPoFieldsDefaultEmpty(): void
    {
        $form = new InvForm();

        $this->assertSame('', $form->getClientPoNumber());
        $this->assertSame('', $form->getClientPoPerson());
    }
}
