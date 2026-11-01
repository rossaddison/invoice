<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Payment\PaymentService;

final class CopyInvOptions
{
    public const string FIELD_TERMS = 'terms';
    public const string FIELD_NOTE = 'note';
    public const string FIELD_DOCUMENT_DESCRIPTION = 'document_description';
    public const string FIELD_CLIENT_PO_NUMBER = 'client_po_number';
    public const string FIELD_CLIENT_PO_PERSON = 'client_po_person';

    public const array SUPPRESSIBLE_FIELDS = [
        self::FIELD_TERMS,
        self::FIELD_NOTE,
        self::FIELD_DOCUMENT_DESCRIPTION,
        self::FIELD_CLIENT_PO_NUMBER,
        self::FIELD_CLIENT_PO_PERSON,
    ];

    public function __construct(
        public readonly string $createdDate = '',
        public readonly string $note = '',
        public readonly bool $sameAmount = true,
        public readonly string $paymentDate = '',
        public readonly ?PaymentService $paymentService = null,
        /** @var list<string> Fields left empty on the copy */
        public readonly array $suppressFields = [],
    ) {
    }

    public function suppresses(string $field): bool
    {
        return in_array($field, $this->suppressFields, true);
    }

    /**
     * Whitelists the suppress_fields[] request parameter.
     *
     * @param array<array-key, mixed> $data
     * @return list<string>
     */
    public static function suppressFieldsFromRequest(array $data): array
    {
        return array_values(array_intersect(
            self::SUPPRESSIBLE_FIELDS,
            array_map(
                static fn (mixed $f): string => is_scalar($f) ? (string) $f : '',
                (array) ($data['suppress_fields'] ?? [])
            )
        ));
    }
}
