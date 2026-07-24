<?php

declare(strict_types=1);

namespace App\Install;

use RuntimeException;

/**
 * Rewrites individual KEY=value lines in a .env file in place, preserving
 * every other line (comments, blank lines, unrelated keys) untouched.
 */
final class EnvFileWriter
{
    /**
     * @param array<string, string> $keyValues
     */
    public function write(string $envPath, array $keyValues): void
    {
        $contents = file_get_contents($envPath);
        if ($contents === false) {
            throw new RuntimeException('Unable to read .env file at: ' . $envPath);
        }

        $lines = explode("\n", $contents);
        $remaining = $keyValues;

        foreach ($lines as $index => $line) {
            foreach ($remaining as $key => $value) {
                if (preg_match('/^' . preg_quote($key, '/') . '=/', $line) === 1) {
                    $lines[$index] = $key . '=' . $value;
                    unset($remaining[$key]);
                    break;
                }
            }
        }

        foreach ($remaining as $key => $value) {
            $lines[] = $key . '=' . $value;
        }

        if (file_put_contents($envPath, implode("\n", $lines)) === false) {
            throw new RuntimeException('Unable to write .env file at: ' . $envPath);
        }
    }
}
