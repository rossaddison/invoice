<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Client;

use App\Infrastructure\Persistence\Client\Client;
use App\Invoice\Client\ClientOptionLabels;
use Testo\Assert;
use Testo\Test;

/**
 * Two clients can share a first name and surname (a webshop order or a sign-up is not refused for it), and two
 * identical labels in a client select cannot be told apart. Clients that share a name get what tells them apart in
 * brackets; a client with a name of their own keeps the label they always had.
 */
#[Test]
final class ClientOptionLabelsTest
{
    private function client(
        int $id,
        string $name,
        string $surname,
        string $email = '',
        ?string $zip = null,
    ): Client {
        $client = new Client();
        $client->setId($id);
        $client->setClientName($name);
        $client->setClientSurname($surname);
        $client->setClientEmail($email);
        if (null !== $zip) {
            $client->setClientZip($zip);
        }

        return $client;
    }

    public function aClientWithAnUniqueNameKeepsTheLabelTheyAlwaysHad(): void
    {
        $labels = ClientOptionLabels::for([$this->client(1, 'Virtual', 'Tester', 'a@example.test')]);

        // first name, three spaces, surname: exactly as before
        Assert::same($labels, [1 => 'Virtual   Tester']);
    }

    public function clientsThatShareANameAreToldApartByEmail(): void
    {
        $labels = ClientOptionLabels::for([
            $this->client(1, 'Virtual', 'Tester', 'first@example.test'),
            $this->client(2, 'Virtual', 'Tester', 'second@example.test'),
            $this->client(3, 'Test', 'Other', 'other@example.test'),
        ]);

        Assert::same($labels, [
            1 => 'Virtual   Tester (first@example.test)',
            2 => 'Virtual   Tester (second@example.test)',
            3 => 'Test   Other',
        ]);
    }

    public function withNoEmailThePostcodeIsUsedThenTheClientNumber(): void
    {
        $labels = ClientOptionLabels::for([
            $this->client(1, 'Virtual', 'Tester', '', 'ZZ1 1AA'),
            $this->client(2, 'Virtual', 'Tester', '', null),
        ]);

        Assert::same($labels, [
            1 => 'Virtual   Tester (ZZ1 1AA)',
            2 => 'Virtual   Tester (#2)',
        ]);
    }

    public function theSameNameInDifferentCaseCountsAsShared(): void
    {
        $labels = ClientOptionLabels::for([
            $this->client(1, 'Virtual', 'Tester', 'a@example.test'),
            $this->client(2, 'virtual', 'tester', 'b@example.test'),
        ]);

        Assert::same($labels, [
            1 => 'Virtual   Tester (a@example.test)',
            2 => 'virtual   tester (b@example.test)',
        ]);
    }

    public function clientsWithTheSameNameAndTheSameEmailStillGetDifferentLabels(): void
    {
        $labels = ClientOptionLabels::for([
            $this->client(1, 'Virtual', 'Tester', 'same@example.test'),
            $this->client(2, 'Virtual', 'Tester', 'same@example.test'),
        ]);

        Assert::same($labels, [
            1 => 'Virtual   Tester (same@example.test) #1',
            2 => 'Virtual   Tester (same@example.test) #2',
        ]);
        Assert::same(count(array_unique($labels)), 2);
    }

    public function noClientsGiveNoLabels(): void
    {
        Assert::same(ClientOptionLabels::for([]), []);
    }

    // ── the filter drop-downs ────────────────────────────────────────────

    public function aFilterListsEachClientOnceEvenWhenTheyComeUpInManyRecords(): void
    {
        $first = $this->client(1, 'Virtual', 'Tester', 'a@example.test');
        $second = $this->client(2, 'Test', 'Other', 'b@example.test');

        // an invoice list holds the same client once per invoice
        $labels = ClientOptionLabels::forFilter([$first, $first, $second, $first]);

        Assert::same($labels, [1 => 'Virtual Tester', 2 => 'Test Other']);
    }

    public function aFilterLeavesOutRecordsWithNoClientAndClientsWithNoName(): void
    {
        $nameless = new Client();
        $nameless->setId(9);

        Assert::same(ClientOptionLabels::forFilter([null, $nameless]), []);
    }

    public function aFilterTellsClientsThatShareANameApartToo(): void
    {
        $labels = ClientOptionLabels::forFilter([
            $this->client(1, 'Virtual', 'Tester', 'first@example.test'),
            $this->client(2, 'Virtual', 'Tester', 'second@example.test'),
            $this->client(1, 'Virtual', 'Tester', 'first@example.test'),
        ]);

        // two entries, not one: the email-log filter used to drop the second client of a name altogether
        Assert::same($labels, [
            1 => 'Virtual Tester (first@example.test)',
            2 => 'Virtual Tester (second@example.test)',
        ]);
    }

    public function theFullNameLabelKeepsASingleSpaceForAClientWithAnUniqueName(): void
    {
        Assert::same(
            ClientOptionLabels::for([$this->client(1, 'Virtual', 'Tester')], true),
            [1 => 'Virtual Tester'],
        );
    }}
