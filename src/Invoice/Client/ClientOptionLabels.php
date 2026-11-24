<?php

declare(strict_types=1);

namespace App\Invoice\Client;

use App\Infrastructure\Persistence\Client\Client;

/**
 * The labels of a client select (the Client field of a quote, sales order or invoice).
 *
 * A label is the first name and surname. Two clients can share a name (a webshop order or a sign-up is matched by
 * email or dwelling, not by name, so it is not refused), and two identical labels cannot be told apart. A client whose
 * name is shared gets what tells them apart added in brackets: their email, else their postcode, else their number. A
 * client with a name of their own keeps exactly the label they always had.
 */
final class ClientOptionLabels
{
    /**
     * @param iterable<Client> $clients
     * @param bool $useFullName label a client with getClientFullName() (the filter drop-downs) instead of the
     *     "first name, three spaces, surname" the client selects have always used
     *
     * @return array<int, string> client id => label
     */
    public static function for(iterable $clients, bool $useFullName = false): array
    {
        /** @var array<int, string> $labels */
        $labels = [];
        /** @var array<string, list<Client>> $byName */
        $byName = [];
        foreach ($clients as $client) {
            $labels[$client->reqId()] = $useFullName ? $client->getClientFullName() : self::name($client);
            $byName[mb_strtolower(trim($labels[$client->reqId()]))][] = $client;
        }

        foreach ($byName as $sharing) {
            if (count($sharing) < 2) {
                continue;
            }
            $labels = self::tellApart($sharing, $labels);
        }

        return $labels;
    }

    /**
     * The labels of a client filter drop-down, built from the clients of a list of records (invoices, quotes, sales
     * orders, sent-email logs), so the same client comes up many times and is listed once. A client with no name is
     * left out, as these drop-downs have always done. Labels are the full name, with the same disambiguation.
     *
     * @param iterable<Client|null> $clients
     *
     * @return array<int, string> client id => label
     */
    public static function forFilter(iterable $clients): array
    {
        /** @var array<int, Client> $unique */
        $unique = [];
        foreach ($clients as $client) {
            if (null !== $client && '' !== $client->getClientFullName()) {
                $unique[$client->reqId()] = $client;
            }
        }

        return self::for($unique, true);
    }

    /** The label a client has always had: first name, three spaces, surname. */
    private static function name(Client $client): string
    {
        return ($client->getClientName() ?: '??') . str_repeat(' ', 3) . ($client->getClientSurname() ?? '??');
    }

    /**
     * @param list<Client> $sharing clients that share one name
     * @param array<int, string> $labels
     *
     * @return array<int, string>
     */
    private static function tellApart(array $sharing, array $labels): array
    {
        foreach ($sharing as $client) {
            $id = $client->reqId();
            $labels[$id] .= ' (' . self::distinguisher($client) . ')';
        }
        // the same email or postcode on two of them: the client number is always different
        $seen = [];
        foreach ($sharing as $client) {
            $seen[$labels[$client->reqId()]][] = $client->reqId();
        }
        foreach ($seen as $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    $labels[$id] .= ' #' . $id;
                }
            }
        }

        return $labels;
    }

    private static function distinguisher(Client $client): string
    {
        $email = trim($client->getClientEmail());
        if ('' !== $email) {
            return $email;
        }
        $zip = trim((string) $client->getClientZip());

        return '' !== $zip ? $zip : '#' . $client->reqId();
    }
}
