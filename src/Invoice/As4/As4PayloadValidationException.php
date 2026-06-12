<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Invoice\Helpers\Peppol\Rule\ValidationViolation;

/**
 * Thrown by As4PayloadValidatorInterface when the outbound UBL document
 * contains one or more Fatal Peppol / EN16931 violations.
 *
 * The $violations array contains only the fatal entries; warnings that did
 * not block dispatch are returned from validate() rather than thrown.
 */
final class As4PayloadValidationException extends \RuntimeException
{
    /** @param ValidationViolation[] $violations */
    public function __construct(
        public readonly array $violations,
        string $message = '',
    ) {
        parent::__construct(
            $message !== ''
                ? $message
                : sprintf('%d fatal Peppol violation(s) — document must not be dispatched', count($violations))
        );
    }
}
