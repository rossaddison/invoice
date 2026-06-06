<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Emit\Vo;

use App\Invoice\Helpers\Peppol\Rule\Severity;

/**
 * A single validation violation produced by a generated rule function.
 * Identical concept in every target language — ruleId, severity, message.
 */
readonly class Violation
{
    public function __construct(
        public string   $ruleId,
        public Severity $severity,
        public string   $message,
    ) {}

    public static function fatal(string $ruleId, string $message): self
    {
        return new self($ruleId, Severity::Fatal, $message);
    }

    public static function warning(string $ruleId, string $message): self
    {
        return new self($ruleId, Severity::Warning, $message);
    }
}
