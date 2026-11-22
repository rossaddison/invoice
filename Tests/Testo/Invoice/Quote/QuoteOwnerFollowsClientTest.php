<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Quote;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Quote\Quote;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Quote\QuoteDeletionService as QDS;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\Quote\QuoteService;
use App\Invoice\Setting\SettingRepository as SR;
use App\User\UserRepository as UR;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * A quote belongs to the user assigned to its client. The guest menu, the emailed link and the guest checks all go by
 * that owner. The edit form lets staff change a quote's client, and used to leave the owner as the OLD client's user,
 * so a quote moved to client 12 (assigned to user 1011) still belonged to user 2 and was invisible to 1011.
 *
 * QuoteService::saveQuote() is handed the user for the client being saved. For an existing quote it now makes that
 * user the owner when the client changes, and otherwise leaves the owner alone.
 */
#[Test]
final class QuoteOwnerFollowsClientTest
{
    private const int OLD_CLIENT = 1;
    private const int OLD_OWNER = 2;
    private const int NEW_CLIENT = 12;
    private const int NEW_OWNER = 1011;

    private function user(int $id): User
    {
        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn($id);
        return $user;
    }

    /** A saved quote that belongs to user 2 through client 1. */
    private function existingQuote(): Quote
    {
        $quote = new Quote();
        $quote->setId(5);
        $quote->setClientId(self::OLD_CLIENT);
        $quote->setUserId(self::OLD_OWNER);
        return $quote;
    }

    /**
     * @param array<string, mixed> $body
     * @param int|null $explicitUserId the user_id in the body, whose user the user repository then returns
     */
    private function save(User $handedUser, Quote $quote, array $body, ?int $explicitUserId = null): Quote
    {
        $clients = TypedMock::of(CR::class);
        $clients->shouldReceive('repoClientquery')->andReturn(TypedMock::of(Client::class));
        $groups = TypedMock::of(GR::class);
        $groups->shouldNotReceive('generateNumber');
        $users = TypedMock::of(UR::class);
        if ($explicitUserId !== null) {
            $users->shouldReceive('findById')->with($explicitUserId)->andReturn($this->user($explicitUserId));
        } else {
            $users->shouldNotReceive('findById');
        }
        $repository = TypedMock::of(QuoteRepository::class);
        $repository->shouldReceive('save')->once();
        $settings = TypedMock::of(SR::class);

        $service = new QuoteService($repository, $clients, $groups, $users, TypedMock::of(QDS::class));

        // status 2, not a draft, so no quote number is generated
        return $service->saveQuote($handedUser, $quote, $body + ['status_id' => 2], $settings, $groups);
    }

    public function aQuoteMovedToAnotherClientBelongsToThatClientsUser(): void
    {
        $quote = $this->save($this->user(self::NEW_OWNER), $this->existingQuote(), ['client_id' => self::NEW_CLIENT]);

        Assert::same($quote->reqClientId(), self::NEW_CLIENT);
        Assert::same($quote->reqUserId(), self::NEW_OWNER);
    }

    public function aQuoteLeftOnItsClientKeepsItsOwnerWhoeverIsHandedIn(): void
    {
        $quote = $this->save($this->user(self::NEW_OWNER), $this->existingQuote(), ['client_id' => self::OLD_CLIENT]);

        Assert::same($quote->reqClientId(), self::OLD_CLIENT);
        Assert::same($quote->reqUserId(), self::OLD_OWNER);
    }

    public function aSaveThatDoesNotMentionTheClientKeepsTheOwner(): void
    {
        $quote = $this->save($this->user(self::NEW_OWNER), $this->existingQuote(), ['notes' => 'only the notes changed']);

        Assert::same($quote->reqClientId(), self::OLD_CLIENT);
        Assert::same($quote->reqUserId(), self::OLD_OWNER);
    }

    public function anOwnerNamedInTheBodyIsNotOverriddenByTheHandedInUser(): void
    {
        $quote = $this->save(
            $this->user(self::NEW_OWNER),
            $this->existingQuote(),
            ['client_id' => self::NEW_CLIENT, 'user_id' => 9],
            explicitUserId: 9,
        );

        Assert::same($quote->reqClientId(), self::NEW_CLIENT);
        Assert::same($quote->reqUserId(), self::OLD_OWNER, 'the passed-in user must not replace an owner the body names');
    }

    public function movingTwiceFollowsEachClient(): void
    {
        $quote = $this->save($this->user(self::NEW_OWNER), $this->existingQuote(), ['client_id' => self::NEW_CLIENT]);
        Assert::same($quote->reqUserId(), self::NEW_OWNER);

        $quote = $this->save($this->user(self::OLD_OWNER), $quote, ['client_id' => self::OLD_CLIENT]);

        Assert::same($quote->reqClientId(), self::OLD_CLIENT);
        Assert::same($quote->reqUserId(), self::OLD_OWNER);
    }
}
