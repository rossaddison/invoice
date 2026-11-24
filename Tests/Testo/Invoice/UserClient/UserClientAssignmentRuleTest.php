<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\UserClient;

use App\Infrastructure\Persistence\UserClient\UserClient;
use App\Invoice\UserClient\UserClientAssignmentRule;
use App\Invoice\UserClient\UserClientRepository;
use Cycle\Annotated\Annotation\Table\Index;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * A client has exactly one user: user_client.client_id is unique. The assign form refuses a client that already has a
 * user (a crafted post is not limited to what the dropdown showed), and the entity declares the unique index so the
 * database refuses it too.
 */
#[Test]
final class UserClientAssignmentRuleTest
{
    private const int USER = 7;
    private const int CLIENT = 12;

    private function rule(int $sameUserLinks, int $anyUserLinks): UserClientAssignmentRule
    {
        $repository = TypedMock::of(UserClientRepository::class);
        $repository->shouldReceive('repoUserClientqueryCount')->with(self::USER, self::CLIENT)->andReturn($sameUserLinks);
        $repository->shouldReceive('repoUserqueryCount')->with(self::CLIENT)->andReturn($anyUserLinks);

        return new UserClientAssignmentRule($repository);
    }

    public function aClientWithNoUserCanBeAssigned(): void
    {
        Assert::null($this->rule(0, 0)->refusalFor(self::USER, self::CLIENT));
    }

    public function aClientAlreadyAssignedToThisUserIsRefused(): void
    {
        Assert::same($this->rule(1, 1)->refusalFor(self::USER, self::CLIENT), 'client.already.exists');
    }

    public function aClientAlreadyAssignedToAnotherUserIsRefused(): void
    {
        Assert::same($this->rule(0, 1)->refusalFor(self::USER, self::CLIENT), 'client.already.has.user');
    }

    public function theEntityDeclaresClientIdUniqueSoTheDatabaseEnforcesItToo(): void
    {
        $unique = [];
        foreach (new ReflectionClass(UserClient::class)->getAttributes(Index::class) as $attribute) {
            $name = self::uniqueClientIdIndexName($attribute->getArguments());
            if ('' !== $name) {
                $unique[] = $name;
            }
        }

        Assert::same($unique, ['user_client_client_id_unique']);
    }

    /**
     * The name of an index attribute that makes client_id unique, or '' for any other index.
     *
     * @param array<array-key, mixed> $arguments the attribute's arguments
     */
    private static function uniqueClientIdIndexName(array $arguments): string
    {
        $isUniqueClientId = ($arguments['columns'] ?? null) === ['client_id'] && ($arguments['unique'] ?? false) === true;

        return $isUniqueClientId ? (string) ($arguments['name'] ?? '') : '';
    }
}
