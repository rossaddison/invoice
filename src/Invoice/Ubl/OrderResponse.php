<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

// Sabre
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;
use App\Invoice\Setting\SettingRepository;
use DateTime;
use InvalidArgumentException;

/**
 * Peppol BIS Advanced Ordering's OrderResponseAdvanced — the only Ordering
 * document this app ever sends (see App\Invoice\As4\As4OrderImportService's
 * own docblock: this app always plays Seller for Ordering, it never issues
 * Order/OrderChange/OrderCancellation, only ever replies to an inbound
 * Order with this document). Mirrors Invoice.php's XmlSerializable/validate()
 * structure, but is deliberately a standalone class rather than an Invoice
 * subclass -- OrderResponse doesn't share Invoice's shape at all (no lines
 * total, no tax total, no payment terms).
 */
class OrderResponse implements XmlSerializable
{
    private ?string $UBLVersionID = '2.4';
    private ?string $customizationID = Schema::ORDER_RESPONSE_ADVANCED_CUSTOMIZATION_ID;
    private ?string $profileID = Schema::ORDER_RESPONSE_ADVANCED_PROFILE_ID;
    private string $documentCurrencyCode = 'EUR';

    /**
     * @param OrderResponseLine[] $lines
     */
    public function __construct(
        private readonly SettingRepository $sR,
        private readonly ?string $id,
        private readonly DateTime $issueDate,
        private readonly OrderResponseCode $orderResponseCode,
        private readonly ?string $orderReferenceId,
        private readonly ?Party $sellerSupplierParty,
        private readonly ?Party $buyerCustomerParty,
        private readonly array $lines,
    ) {}

    public function setDocumentCurrencyCode(): self
    {
        $this->documentCurrencyCode = $this->sR->getSetting('peppol_document_currency');
        return $this;
    }

    public function getDocumentCurrencyCode(): string
    {
        return $this->documentCurrencyCode;
    }

    /**
     * @throws InvalidArgumentException An error with information about
     * required data that is missing to write the XML
     */
    public function validate(): void
    {
        $m = 'Missing OrderResponse';
        if ($this->id === null || $this->id === '') {
            throw new InvalidArgumentException($m . ' id');
        }
        if ($this->orderReferenceId === null || $this->orderReferenceId === '') {
            throw new InvalidArgumentException($m . ' OrderReference/ID');
        }
        if ($this->sellerSupplierParty === null) {
            throw new InvalidArgumentException($m . ' SellerSupplierParty');
        }
        if ($this->buyerCustomerParty === null) {
            throw new InvalidArgumentException($m . ' BuyerCustomerParty');
        }
        if (empty($this->lines)) {
            throw new InvalidArgumentException($m . ' lines');
        }
    }

    #[\Override]
    public function xmlSerialize(Writer $writer): void
    {
        $this->validate();
        $this->writeHeaderFields($writer);
        $this->writePartyFields($writer);
        $this->writeLines($writer);
    }

    private function writeHeaderFields(Writer $writer): void
    {
        $writer->write([
            Schema::CBC . 'UBLVersionID'    => $this->UBLVersionID,
            Schema::CBC . 'CustomizationID' => $this->customizationID,
            Schema::CBC . 'ProfileID'       => $this->profileID,
            Schema::CBC . 'ID'              => $this->id,
            Schema::CBC . 'IssueDate'       => $this->issueDate->format('Y-m-d'),
            Schema::CBC . 'IssueTime'       => $this->issueDate->format('H:i:s'),
            Schema::CBC . 'OrderResponseCode' => $this->orderResponseCode->value,
            Schema::CBC . 'DocumentCurrencyCode' => $this->getDocumentCurrencyCode(),
            Schema::CAC . 'OrderReference' => [
                Schema::CBC . 'ID' => $this->orderReferenceId,
            ],
        ]);
    }

    private function writePartyFields(Writer $writer): void
    {
        $writer->write([
            Schema::CAC . 'SellerSupplierParty' => [
                Schema::CAC . 'Party' => $this->sellerSupplierParty,
            ],
            Schema::CAC . 'BuyerCustomerParty' => [
                Schema::CAC . 'Party' => $this->buyerCustomerParty,
            ],
        ]);
    }

    private function writeLines(Writer $writer): void
    {
        foreach ($this->lines as $line) {
            $writer->write([Schema::CAC . 'OrderLine' => $line]);
        }
    }
}
