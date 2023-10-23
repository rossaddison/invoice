<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Exception;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class PeppolInvoicePeriodDetailsIncompleteException extends \RuntimeException implements FriendlyExceptionInterface {

  /**
   *
   * @return string
   *
   * @psalm-return 'Invoice Period Details Incomplete or Non-existant. See delivery/edit/{inv_id}'
   */
  public function getName(): string {
    return 'Invoice Period Details Incomplete or Non-existant. See delivery/edit/{inv_id}';
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
