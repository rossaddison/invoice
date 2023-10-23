<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class PeppolBuyerReferenceNotFoundException extends \RuntimeException implements FriendlyExceptionInterface {

  /**
   * @return string
   *
   * @psalm-return 'Client/Customer Purchase Order Number ie. Buyer Reference, not found. An invoice is linked to a sales order. The sales order must have a client/customer purchase order number associated with it.'
   */
  public function getName(): string {
    return 'Client/Customer Purchase Order Number ie. Buyer Reference, not found. An invoice is linked to a sales order. The sales order must have a client/customer purchase order number associated with it.';
  }

  /**
   * @return string
   *
   * @psalm-return '    Please try again'
   */
  public function getSolution(): ?string {
    return <<<'SOLUTION'
                Please try again
            SOLUTION;
  }

}
