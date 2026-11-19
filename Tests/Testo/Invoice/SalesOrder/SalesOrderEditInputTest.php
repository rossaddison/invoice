<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\SalesOrder;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Group\Group;
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Client\ClientRepository;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\SalesOrder\SalesOrderEditInput;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\SalesOrder\SalesOrderService;
use App\User\UserRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * A customer (observer) saving salesorder/edit must not be able to move the order to another client, change
 * its status, discount, links or guest credentials by editing the posted body. The tests run the filtered
 * body through the real SalesOrderService.
 */
#[Test]
final class SalesOrderEditInputTest
{
    private function storedOrder(): SalesOrder
    {
        $so = new SalesOrder();
        $so->setId(30);
        $so->setClientId(1);
        $so->setGroupId(2);
        $so->setUserId(4);
        $so->setInvId(12);
        $so->setStatusId(2);
        $so->setDiscountAmount(5.0);
        $so->setUrlKey('stored-key');
        $so->setPassword('stored-pw');
        return $so;
    }

    /** @return array<string, string> */
    private function hostileBody(): array
    {
        return [
            'client_id' => '99',
            'group_id' => '98',
            'user_id' => '1',
            'quote_id' => '55',
            'inv_id' => '7',
            'status_id' => '4',
            'discount_amount' => '100',
            'url_key' => 'attacker-key',
            'password' => 'attacker-pw',
            'notes' => 'my notes',
            'payment_term' => 'net7',
            'client_po_number' => 'PO-1',
            'client_po_person' => 'Pat',
            'date_created' => '2026-05-01',
        ];
    }

    public function protectedFieldsComeFromTheStoredOrder(): void
    {
        $out = SalesOrderEditInput::forObserver($this->hostileBody(), $this->storedOrder());

        Assert::same('1', $out['client_id']);
        Assert::same('2', $out['group_id']);
        Assert::same('4', $out['user_id']);
        Assert::false(isset($out['quote_id']));
        Assert::same('12', $out['inv_id']);
        Assert::same('2', $out['status_id']);
        Assert::same('5', $out['discount_amount']);
        Assert::same('stored-key', $out['url_key']);
        Assert::same('stored-pw', $out['password']);
    }

    public function theCustomersOwnFieldsPassThrough(): void
    {
        $out = SalesOrderEditInput::forObserver($this->hostileBody(), $this->storedOrder());

        Assert::same('my notes', $out['notes']);
        Assert::same('net7', $out['payment_term']);
        Assert::same('PO-1', $out['client_po_number']);
        Assert::same('Pat', $out['client_po_person']);
        Assert::same('2026-05-01', $out['date_created']);
    }

    public function fieldsMissingFromThePostAreStillPinned(): void
    {
        $out = SalesOrderEditInput::forObserver(['notes' => 'n'], $this->storedOrder());

        Assert::same('1', $out['client_id']);
        Assert::same('2', $out['status_id']);
        Assert::same('stored-key', $out['url_key']);
    }

    public function anOrderWithNoInvoiceOrQuoteKeepsThoseLinksNullWhenACustomerSavesIt(): void
    {
        // a rejected order: no invoice and no quote, both NULL in the table
        $so = new SalesOrder();
        $so->setId(31);
        $so->setClientId(1);
        $so->setGroupId(2);
        $so->setUserId(4);
        $so->setStatusId(5);

        $out = SalesOrderEditInput::forObserver($this->hostileBody(), $so);
        Assert::false(array_key_exists('inv_id', $out), 'a posted inv_id must not survive');
        Assert::false(array_key_exists('quote_id', $out), 'a posted quote_id must not survive');

        $client = new Client();
        $client->setId(1);
        $group = new Group();
        $group->setId(2);
        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn(4);
        $clients = TypedMock::of(ClientRepository::class);
        $clients->shouldReceive('repoClientquery')->with(1)->andReturn($client);
        $groups = TypedMock::of(GroupRepository::class);
        $groups->shouldReceive('repoGroupquery')->with(2)->andReturn($group);
        $users = TypedMock::of(UserRepository::class);
        $users->shouldReceive('findById')->with(4)->andReturn($user);
        $quotes = TypedMock::of(QuoteRepository::class);
        $quotes->shouldReceive('repoQuoteUnLoadedquery')->never();
        $orders = TypedMock::of(SalesOrderRepository::class);
        $orders->shouldReceive('save')->once();

        (new SalesOrderService($orders, $clients, $groups, $users, $quotes))->saveSo($so, $out);

        // reqInvId() reads NULL as 0, so look at the column itself
        foreach (['inv_id', 'quote_id'] as $column) {
            $property = new \ReflectionProperty(SalesOrder::class, $column);
            Assert::null($property->getValue($so), $column . ' must stay NULL');
        }
        Assert::same('my notes', $so->getNotes());
    }

    public function savedThroughTheRealServiceTheOrderKeepsItsClientStatusDiscountAndLinks(): void
    {
        $so = $this->storedOrder();
        $client = new Client();
        $client->setId(1);
        $group = new Group();
        $group->setId(2);
        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn(4);

        $clients = TypedMock::of(ClientRepository::class);
        $clients->shouldReceive('repoClientquery')->with(1)->once()->andReturn($client);
        $groups = TypedMock::of(GroupRepository::class);
        $groups->shouldReceive('repoGroupquery')->with(2)->once()->andReturn($group);
        $users = TypedMock::of(UserRepository::class);
        $users->shouldReceive('findById')->with(4)->andReturn($user);
        $quotes = TypedMock::of(QuoteRepository::class);
        $quotes->shouldReceive('repoQuoteUnLoadedquery')->with(0)->andReturn(null);
        $orders = TypedMock::of(SalesOrderRepository::class);
        $orders->shouldReceive('save')->once()->with($so);

        $service = new SalesOrderService($orders, $clients, $groups, $users, $quotes);
        $service->saveSo($so, SalesOrderEditInput::forObserver($this->hostileBody(), $so));

        Assert::same(1, $so->reqClientId());
        Assert::same(2, $so->reqGroupId());
        Assert::same(4, $so->reqUserId());
        Assert::same(12, $so->reqInvId());
        Assert::same(2, $so->getStatusId());
        Assert::same(5.0, $so->getDiscountAmount());
        Assert::same('stored-key', $so->getUrlKey());
        Assert::same('stored-pw', $so->getPassword());
        Assert::same('my notes', $so->getNotes());
        Assert::same('PO-1', $so->getClientPoNumber());
    }
}
