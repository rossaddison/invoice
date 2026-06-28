<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Di;

use App\Invoice\Inv\InvIndexFilter;
use Nyholm\Psr7\ServerRequest;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Hydrator\AttributeHandling\ResolverFactory\ContainerAttributeResolverFactory;
use Yiisoft\Hydrator\Hydrator;
use Yiisoft\Input\Http\Attribute\Data\FromQueryResolver;
use Yiisoft\Input\Http\RequestInputParametersResolver;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;
use Yiisoft\RequestProvider\RequestProvider;

/**
 * Regression test: InvIndexFilter must be hydrated from URL query params.
 *
 * Root cause of the original bug: RequestInputParametersResolver was missing from
 * CompositeParametersResolver in config/web/di/middleware-dispatcher.php.
 * Without it, InvIndexFilter was resolved by the DI container as a blank object
 * (all properties null), so isset(null) was always false and no filter fired.
 */
#[Test]
final class InvIndexFilterHydrationTest
{
    /** Dummy signature — provides a ReflectionParameter typed as InvIndexFilter. */
    private static function filterSignature(InvIndexFilter $filter): void {}

    /** @return array<string, \ReflectionParameter> */
    private function reflectionParams(): array
    {
        $result = [];
        foreach ((new \ReflectionMethod(self::class, 'filterSignature'))->getParameters() as $param) {
            $result[$param->getName()] = $param;
        }
        return $result;
    }

    private function makeResolver(ServerRequestInterface $request): RequestInputParametersResolver
    {
        $provider  = new RequestProvider($request);
        $container = new class ($provider) implements ContainerInterface {
            public function __construct(private readonly RequestProvider $p) {}
            public function get(string $id): mixed { return new FromQueryResolver($this->p); }
            public function has(string $id): bool  { return $id === FromQueryResolver::class; }
        };
        return new RequestInputParametersResolver(
            new Hydrator(attributeResolverFactory: new ContainerAttributeResolverFactory($container))
        );
    }

    public function resolverImplementsParametersResolverInterface(): void
    {
        $request  = new ServerRequest('GET', '/inv');
        Assert::instanceOf($this->makeResolver($request), ParametersResolverInterface::class);
    }

    public function resolverPopulatesFilterInvNumber(): void
    {
        $request = (new ServerRequest('GET', '/inv'))
            ->withQueryParams(['filterInvNumber' => 'INV251']);

        $result = $this->makeResolver($request)->resolve($this->reflectionParams(), $request);

        Assert::true(isset($result['filter']));
        Assert::instanceOf($result['filter'], InvIndexFilter::class);
        Assert::same($result['filter']->filterInvNumber, 'INV251');
    }

    public function resolverLeavesEmptyParamAsNullOrEmpty(): void
    {
        $request = (new ServerRequest('GET', '/inv'))
            ->withQueryParams(['filterInvNumber' => 'INV251', 'filterClient' => '']);

        $result = $this->makeResolver($request)->resolve($this->reflectionParams(), $request);

        Assert::instanceOf($result['filter'], InvIndexFilter::class);
        Assert::same($result['filter']->filterInvNumber, 'INV251');
        Assert::true(
            $result['filter']->filterClient === null || $result['filter']->filterClient === '',
            'Empty query param must not produce a non-empty string'
        );
    }

    public function resolverIgnoresUnrelatedQueryParams(): void
    {
        $request = (new ServerRequest('GET', '/inv'))
            ->withQueryParams(['filterStatus' => '2', 'someOtherParam' => 'ignored']);

        $result = $this->makeResolver($request)->resolve($this->reflectionParams(), $request);

        Assert::instanceOf($result['filter'], InvIndexFilter::class);
        Assert::same($result['filter']->filterStatus, '2');
        Assert::null($result['filter']->filterInvNumber);
    }

    public function invIndexFilterDefaultsAllFieldsToNull(): void
    {
        $filter = new InvIndexFilter();

        Assert::null($filter->filterInvNumber);
        Assert::null($filter->filterCreditInvNumber);
        Assert::null($filter->filterFamilyName);
        Assert::null($filter->filterClient);
        Assert::null($filter->filterInvAmountTotal);
        Assert::null($filter->filterInvAmountPaid);
        Assert::null($filter->filterInvAmountBalance);
        Assert::null($filter->filterClientGroup);
        Assert::null($filter->filterClientAddress1);
        Assert::null($filter->filterDateCreatedYearMonth);
        Assert::null($filter->filterStatus);
        Assert::same($filter->groupBy, 'none');
    }
}
