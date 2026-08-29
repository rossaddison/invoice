<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Translator\TranslatorInterface;

final class PeppolTaxCategoryCodeNotFoundException extends \RuntimeException implements FriendlyExceptionInterface
{
    /**
     * @param int|null $taxRateId The offending TaxRate's id, when known at
     *     the throw site — lets the catcher (Inv\Trait\Peppol) link
     *     straight to `taxrate/edit/{id}#peppol_tax_rate_code` instead of
     *     leaving the user to find which tax rate is missing the code by
     *     hand.
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        public readonly ?int $taxRateId = null,
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return $this->translator->translate('peppol.tax.category.not.found');
    }

    /**
     * @return string
     */
    #[\Override]
    public function getSolution(): string
    {
        return null === $this->taxRateId
            ? 'Set a Peppol Tax Rate Code (UNCL5305) on the tax rate(s) '
                . 'used by this invoice, then retry.'
            : 'Set a Peppol Tax Rate Code (UNCL5305) on tax rate #'
                . $this->taxRateId . ', then retry.';
    }
}
