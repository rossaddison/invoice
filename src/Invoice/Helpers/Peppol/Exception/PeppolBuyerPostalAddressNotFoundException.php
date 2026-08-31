<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class PeppolBuyerPostalAddressNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @param int|null $clientId The offending Client's id, when known at
     *     the throw site -- lets the catcher (Inv\Trait\Peppol) link
     *     straight to `client/edit/{id}#postaladdress_id` instead of
     *     leaving the user to find which client is missing its postal
     *     address by hand.
     */
    public function __construct(public readonly ?int $clientId = null)
    {
    }

    /**
     * @return string
     *
     * @psalm-return 'Client/Customer Postal Address, not found. Business Rule 10 (BR-10): An Invoice shall contain the Buyers postal address (BG-8).'
     */
    #[\Override]
    public function getName(): string
    {
        return 'Client/Customer Postal Address, not found. Business Rule 10 (BR-10): An Invoice shall contain the Buyers postal address (BG-8).';
    }

    #[\Override]
    public function getSolution(): string
    {
        return null === $this->clientId
            ? 'Link this invoice\'s client to a valid Client Postal Address, then retry.'
            : 'Link client #' . $this->clientId
                . ' to a valid Client Postal Address, then retry.';
    }
}
