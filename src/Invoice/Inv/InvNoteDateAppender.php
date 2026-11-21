<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;

/**
 * Appends a payment date to an invoice note, for the "Append date to Note" option of Quick Pay.
 *
 * The inv/index invoice-number button shows the note in its tooltip, so the payment date becomes visible
 * on hover. Appending is idempotent: a note that already ends with the date is returned unchanged, so a
 * repeated click does not stack the same date twice.
 */
final class InvNoteDateAppender
{
    public static function append(?string $note, string $date): string
    {
        $existing = trim($note ?? '');
        $date     = trim($date);
        if ($date === '') {
            return $existing;
        }
        if ($existing === '') {
            return $date;
        }
        if (str_ends_with($existing, $date)) {
            return $existing;
        }
        return $existing . ' ' . $date;
    }

    /** Appends the date to the invoice's note and saves the invoice. */
    public static function applyTo(Inv $inv, string $date, InvRepository $repository): void
    {
        $inv->setNote(self::append($inv->getNote(), $date));
        $repository->save($inv);
    }
}
