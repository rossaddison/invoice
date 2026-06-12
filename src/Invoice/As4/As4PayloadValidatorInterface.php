<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Invoice\Helpers\Peppol\Rule\ValidationViolation;

interface As4PayloadValidatorInterface
{
    /**
     * Validate a UBL payload before it is wrapped in an AS4 envelope.
     *
     * @return ValidationViolation[]  Non-fatal violations (warnings / info) that
     *                                the caller may log but that do not block dispatch.
     *
     * @throws As4PayloadValidationException  When one or more Fatal violations are found.
     * @throws \InvalidArgumentException      When $payloadXml is empty or not well-formed XML.
     */
    public function validate(string $payloadXml, string $documentTypeId): array;
}
