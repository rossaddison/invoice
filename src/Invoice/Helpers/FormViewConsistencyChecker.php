<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

/**
 * Detects #[Required] / #[GreaterThan] / etc. on FormModel properties that are
 * never rendered via Field::xxx() in any view, causing silent validation failure.
 *
 * Usage (CLI):
 *   php bin/check_form_views.php
 *
 * Usage (PHPUnit):
 *   $checker = new FormViewConsistencyChecker($srcDir, $viewsDir);
 *   $this->assertEmpty($checker->check(), implode("\n", $checker->formatIssues($checker->check())));
 */
final class FormViewConsistencyChecker
{
    private const VALIDATOR_ATTRIBUTES = [
        'Required',
        'GreaterThan',
        'GreaterThanOrEqual',
        'LessThan',
        'LessThanOrEqual',
        'Length',
        'Number',
        'Email',
        'Url',
        'Regex',
        'Each',
        'Composite',
        'InRange',
        'NotEqual',
        'Equal',
    ];

    public function __construct(
        private readonly string $srcDir,
        private readonly string $viewsDir,
    ) {
    }

    /**
     * Returns one entry per problematic property:
     * ['form' => path, 'property' => name, 'validators' => [string]]
     *
     * @return array<int, array{form: string, property: string, validators: list<string>}>
     */
    public function check(): array
    {
        $renderedFields = $this->collectRenderedFields();
        $issues = [];

        foreach ($this->findFormFiles() as $formFile) {
            foreach ($this->getValidatedProperties($formFile) as $property => $validators) {
                if (!isset($renderedFields[$property])) {
                    $issues[] = [
                        'form'       => $formFile,
                        'property'   => $property,
                        'validators' => $validators,
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Human-readable summary lines, one per issue.
     *
     * @param array<int, array{form: string, property: string, validators: list<string>}> $issues
     * @return array<int, string>
     */
    public function formatIssues(array $issues): array
    {
        return array_values(array_map(
            static fn(array $i): string => sprintf(
                '%s  ->  $%s  [%s]  — never rendered in any view',
                basename($i['form']),
                $i['property'],
                implode(', ', $i['validators']),
            ),
            $issues,
        ));
    }

    /**
     * Collect every field name referenced as Field::xxx($anyVar, 'fieldname')
     * across all view files, keyed by field name for O(1) lookup.
     *
     * @return array<string, true>
     */
    private function collectRenderedFields(): array
    {
        $fields = [];

        foreach ($this->findViewFiles() as $viewFile) {
            $content = (string) file_get_contents($viewFile);
            // Matches: Field::text($form, 'name')  Field::select($form, "name")
            if (preg_match_all(
                '/Field::\w+\(\s*\$\w+\s*,\s*[\'"](\w+)[\'"]/m',
                $content,
                $matches,
            ) !== false && $matches[1] !== []) {
                foreach ($matches[1] as $fieldName) {
                    $fields[$fieldName] = true;
                }
            }
        }

        return $fields;
    }

    /**
     * Return properties that carry at least one validator attribute.
     * Uses a line-by-line pass so multi-attribute stacks are handled correctly.
     *
     * @return array<string, list<string>>   property => [ValidatorName, ...]
     */
    private function getValidatedProperties(string $formFile): array
    {
        $raw   = file($formFile, FILE_IGNORE_NEW_LINES);
        $lines = $raw !== false ? $raw : [];
        $result  = [];
        $pending = [];   // validator names accumulated for the next property

        $validatorPattern = '/#\[(' . implode('|', self::VALIDATOR_ATTRIBUTES) . ')[\[(]/';
        $propertyPattern  = '/(?:private|protected|public)\s+\S+\s+\$(\w+)/';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Another attribute — may or may not be a validator
            if (str_starts_with($trimmed, '#[')) {
                if (preg_match($validatorPattern, $trimmed, $m)) {
                    $pending[] = $m[1];
                }
                // Non-validator attributes (e.g. #[Override], #[Column]) are
                // ignored but do NOT reset $pending
                continue;
            }

            // Property declaration — attach accumulated validators
            if (!empty($pending) && preg_match($propertyPattern, $trimmed, $m)) {
                $property = $m[1];
                /** @var list<string> $merged */
                $merged = array_merge($result[$property] ?? [], $pending);
                $result[$property] = $merged;
                $pending = [];
                continue;
            }

            // Anything else (blank line, comment, method, etc.) resets pending
            if ($trimmed !== '' && !str_starts_with($trimmed, '//') && !str_starts_with($trimmed, '*')) {
                $pending = [];
            }
        }

        return $result;
    }

    /** @return \Generator<string> */
    private function findFormFiles(): \Generator
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->srcDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), 'Form.php')) {
                yield $file->getPathname();
            }
        }
    }

    /** @return \Generator<string> */
    private function findViewFiles(): \Generator
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->viewsDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                yield $file->getPathname();
            }
        }
    }
}
