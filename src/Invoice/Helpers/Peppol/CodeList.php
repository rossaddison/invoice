<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use InvalidArgumentException;

/**
 * Loads Peppol / EN16931 code lists from resources/peppol/*.json and provides
 * a fast membership test.
 *
 * Files are loaded once per process and cached in a static array so repeated
 * calls within a single validation run incur no I/O or parsing overhead.
 * PHP opcache compiles each data file to bytecode, making the first load as
 * fast as a native array literal.
 *
 * Usage:
 *   CodeList::contains(CodeLists::EAID, $schemeId)
 *   CodeList::contains(CodeLists::ISO4217, $currencyCode)
 */
final class CodeList
{
    /**
     * What?  Per-process cache mapping each CodeLists case to its loaded array.
     * Why?   Avoids re-reading and re-parsing the JSON file on every contains() call.
     * When?  Populated lazily the first time a given list is requested.
     * Where? Read and written only inside load().
     * How?   Keyed by CodeLists::value (the JSON filename stem).
     *
     * @var array<string, array<int, string>>
     */
    private static array $cache = [];

    /**
     * Test whether $value is a member of the given code list.
     *
     * @param CodeLists $list  The code list to search.
     * @param string    $value The value to look up (case-sensitive).
     */
    public static function contains(CodeLists $list, string $value): bool
    {
        return in_array($value, self::load($list), true);
    }

    /**
     * Return the full array for a code list, loading from disk on first access.
     *
     * Each file in resources/peppol/ is a plain PHP file that returns an array,
     * so PHP opcache can compile it to bytecode — no JSON parsing required.
     *
     * @return array<int, string>
     * @throws InvalidArgumentException When the PHP data file is missing or does not return an array.
     */
    public static function load(CodeLists $list): array
    {
        $key = $list->value;

        if (!array_key_exists($key, self::$cache)) {
            // Four directory levels up from src/Invoice/Helpers/Peppol/ reaches the project root.
            $path = dirname(__DIR__, 4) . '/resources/peppol/' . $key . '.php';

            if (!is_file($path)) {
                throw new InvalidArgumentException(
                    "Peppol code list file not found: {$path}"
                );
            }

            /** @var mixed $data */
            $data = require $path;

            if (!is_array($data)) {
                throw new InvalidArgumentException(
                    "Peppol code list file must return an array: {$path}"
                );
            }

            /** @var array<int, string> $data */
            self::$cache[$key] = $data;
        }

        return self::$cache[$key];
    }

    /**
     * Flush the static cache (useful in tests that need a clean slate).
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
