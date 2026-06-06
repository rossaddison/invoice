<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Rule;

use DOMXPath;

/**
 * Holds an ordered list of ValidationRule instances and runs them all.
 *
 * What?  A simple collection that fires every registered rule against a document
 *        and aggregates the resulting violations.
 * Why?   Keeps PeppolValidator::validate() clean — it registers rules once and calls
 *        run(); adding a new rule is a one-line registration change, not a new method.
 * When?  Instantiated inside PeppolValidator::validateWithRegistry() on each validate()
 *        call.  Rules are stateless so the registry can be recreated cheaply.
 * Where? Used only by PeppolValidator::validateWithRegistry().
 * How?   register() accepts variadic ValidationRule arguments; run() iterates them and
 *        merges their violation arrays.
 */
final class RuleRegistry
{
    /** @var array<int, ValidationRule> */
    private array $rules = [];

    public function register(ValidationRule ...$rules): void
    {
        foreach ($rules as $rule) {
            $this->rules[] = $rule;
        }
    }

    /**
     * Run every registered rule and return all violations.
     *
     * @return array<int, ValidationViolation>
     */
    public function run(DOMXPath $xpath, ValidationContext $context): array
    {
        $violations = [];
        foreach ($this->rules as $rule) {
            array_push($violations, ...$rule->validate($xpath, $context));
        }
        return $violations;
    }
}
