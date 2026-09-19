<?php

declare(strict_types=1);

namespace Tests\Testo\Bookkeeping\Infrastructure\FrontAccounting;

/**
 * Reads a url-encoded request body by key path, e.g. get('items', '0', 'price').
 */
final class FormReader
{
    /**
     * @param array<array-key, mixed> $data
     */
    public function __construct(private readonly array $data)
    {
    }

    public static function fromBody(string $body): self
    {
        parse_str($body, $data);

        return new self($data);
    }

    public function get(string ...$keys): string
    {
        /** @var mixed $value */
        $value = $this->data;
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return '';
            }
            /** @var mixed $value */
            $value = $value[$key];
        }

        return is_string($value) ? $value : '';
    }
}
