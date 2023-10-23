<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class PeppolBuyerNotFoundException extends \RuntimeException implements FriendlyExceptionInterface {

  /**
   * @return string
   * @psalm-return 'Client/Customer not found.'
   */
  public function getName(): string {
    return 'Client/Customer not found.';
  }

  /**
   * @return string
   * @psalm-return '    Please try again'
   */
  public function getSolution(): ?string {
    return <<<'SOLUTION'
                Please try again
            SOLUTION;
  }

}
