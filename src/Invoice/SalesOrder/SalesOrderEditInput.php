<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrder;

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;

/**
 * What an observer (a customer) may change when they save a sales order through salesorder/edit.
 *
 * SalesOrderService::saveSo() was written for staff and applies whatever it is given: the client and group
 * the order belongs to, the user, the quote and invoice it is linked to, its status, its discount, and the
 * guest url key and password. The edit form renders those as inputs a visitor can change or add to, and the
 * whole posted body was passed through, so a customer could move their order to another client, give it a
 * discount, mark it confirmed or point it at a different invoice.
 *
 * For an observer these are replaced with the stored values. Date, notes, payment term and the customer's
 * own purchase order number and person pass through. Administrators and accountants are not filtered.
 */
final class SalesOrderEditInput
{
    /**
     * @param array<array-key, mixed> $posted the body of the request
     *
     * @return array<array-key, mixed> the body with the protected fields replaced by the stored ones
     */
    public static function forObserver(array $posted, SalesOrder $stored): array
    {
        $posted['client_id'] = (string) $stored->reqClientId();
        $posted['group_id'] = (string) $stored->reqGroupId();
        $posted['user_id'] = (string) $stored->reqUserId();
        // reqQuoteId()/reqInvId() read a missing link as 0. Writing that 0 back would turn a NULL link (a rejected
        // order has no invoice) into 0, so when there is no link the keys are left out and the row is untouched.
        unset($posted['quote_id'], $posted['inv_id']);
        if ($stored->reqQuoteId() > 0) {
            $posted['quote_id'] = (string) $stored->reqQuoteId();
        }
        if ($stored->reqInvId() > 0) {
            $posted['inv_id'] = (string) $stored->reqInvId();
        }
        $posted['status_id'] = (string) ($stored->getStatusId() ?? 1);
        $posted['discount_amount'] = (string) ($stored->getDiscountAmount() ?? 0.0);
        $posted['url_key'] = $stored->getUrlKey();
        $posted['password'] = $stored->getPassword() ?? '';
        return $posted;
    }
}
