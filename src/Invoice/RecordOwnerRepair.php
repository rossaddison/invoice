<?php

declare(strict_types=1);

namespace App\Invoice;

use Cycle\Database\DatabaseManager;

/**
 * Finds, and repairs, the quotes, sales orders and invoices whose owner is not the user of their client.
 *
 * A quote, sales order or invoice belongs to the user assigned to its client (the guest checks compare the record's
 * user_id with the signed-in guest and require that user to be linked to the record's client). Before November 2026 the
 * edit forms let staff move a record to another client without moving its user, so a moved record kept the old
 * client's user and neither client's guest could open it. New moves are fixed; this repairs the ones made before.
 *
 * A record is a candidate when its client has a user and the record's user_id is a different user. Records whose client
 * has no user at all are not candidates: there is nobody to give them to. A deleted invoice is left alone (quotes and
 * sales orders have no deleted flag). A candidate is repairable only when the client's user has an active account, the
 * same rule the edit forms use; the rest are reported and left as they are.
 */
final readonly class RecordOwnerRepair
{
    /** Table names, in the order they are reported. Fixed here, never taken from input. */
    public const array TABLES = ['quote', 'sales_order', 'inv'];

    public function __construct(private DatabaseManager $databases)
    {
    }

    /**
     * @return list<array{table: string, id: int, number: string, clientId: int, userId: int, ownerId: int, repairable: bool}>
     */
    public function findMismatches(): array
    {
        $db = $this->databases->database();

        $found = [];
        foreach (self::TABLES as $table) {
            $notDeleted = 'inv' === $table ? ' AND r.deleted_at IS NULL' : '';
            /** @var list<array<string, mixed>> $rows */
            $rows = $db->query(
                'SELECT r.id AS id, r.number AS number, r.client_id AS client_id, r.user_id AS user_id,'
                . ' uc.user_id AS owner_id, ui.active AS owner_active'
                . ' FROM ' . $table . ' r'
                . ' INNER JOIN user_client uc ON uc.client_id = r.client_id'
                . ' LEFT JOIN user_inv ui ON ui.user_id = uc.user_id'
                . ' WHERE r.user_id <> uc.user_id' . $notDeleted
                . ' ORDER BY r.id',
            )->fetchAll();
            foreach ($rows as $row) {
                $found[] = [
                    'table' => $table,
                    'id' => (int) $row['id'],
                    'number' => (string) ($row['number'] ?? ''),
                    'clientId' => (int) $row['client_id'],
                    'userId' => (int) $row['user_id'],
                    'ownerId' => (int) $row['owner_id'],
                    'repairable' => 1 === (int) ($row['owner_active'] ?? 0),
                ];
            }
        }

        return $found;
    }

    /**
     * Gives each repairable record to its client's user, in one transaction: all of them or none. A record whose
     * owner changed since it was listed is left alone (the update only matches the owner it was listed with).
     *
     * @param list<array{table: string, id: int, number: string, clientId: int, userId: int, ownerId: int, repairable: bool}> $mismatches
     *
     * @return array<string, int> records changed per table
     */
    public function repair(array $mismatches): array
    {
        $db = $this->databases->database();

        /** @var array<string, int> $changed */
        $changed = [];
        $db->transaction(function () use ($db, $mismatches, &$changed): void {
            foreach ($mismatches as $record) {
                if (!$record['repairable'] || !in_array($record['table'], self::TABLES, true)) {
                    continue;
                }
                $count = $db->update($record['table'], ['user_id' => $record['ownerId']], [
                    'id' => $record['id'],
                    'user_id' => $record['userId'],
                ])->run();
                $changed[$record['table']] = ($changed[$record['table']] ?? 0) + $count;
            }
        });

        return array_filter($changed);
    }
}
