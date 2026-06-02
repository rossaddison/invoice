<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Helpers\Peppol\Ast\Expression;
use App\Invoice\Helpers\Peppol\Ast\Rule;

/**
 * The parsed output of a Schematron file.
 *
 * $namespaces  — prefix → URI map from <sch:ns> declarations.
 *               Register these with DOMXPath before evaluating rules.
 *
 * $variables   — name → Expression map from schema/pattern-level <sch:variable>.
 *               Evaluate each select expression against the document root to
 *               build the initial $bindings array for ExpressionEvaluator.
 *
 * $rules       — ordered list of Rule objects, each with a context and assertions.
 */
readonly class SchematronDocument
{
    /**
     * @param array<string, string>     $namespaces  prefix → namespace URI
     * @param array<string, Expression> $variables   name → select Expression
     * @param Rule[]                    $rules
     */
    public function __construct(
        public array $namespaces,
        public array $variables,
        public array $rules,
    ) {}
}
