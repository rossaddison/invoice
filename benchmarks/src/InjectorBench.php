<?php

declare(strict_types=1);

/**
 * Injector Benchmark Suite
 *
 * Measures Yiisoft\Injector\Injector — the auto-wiring engine that resolves
 * callable arguments from the DI container using PHP reflection.
 *
 * cacheReflections(true) is the recommended production setting; this suite
 * benchmarks both modes to show the reflection-cache benefit.
 *
 * @return array<string, array{fn:callable, revs:int, warmup:int, its:int}>
 */

use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Injector\Injector;

// ── Fixture classes (prefixed to avoid collision with other suites) ────────────
class InjBenchDepA {}
class InjBenchDepB
{
    public function __construct(public readonly InjBenchDepA $a) {}
}
class InjBenchDepC
{
    public function __construct(public readonly InjBenchDepA $a, public readonly InjBenchDepB $b) {}
}

// ── Fixture callables ─────────────────────────────────────────────────────────
function injBenchCallableOne(InjBenchDepA $a): string
{
    return $a::class;
}

function injBenchCallableThree(InjBenchDepA $a, InjBenchDepB $b, InjBenchDepC $c): string
{
    return $a::class . $b::class . $c::class;
}

// ── Suite factory ─────────────────────────────────────────────────────────────
return static function (): array {
    $config = ContainerConfig::create()->withDefinitions([
        InjBenchDepA::class => InjBenchDepA::class,
        InjBenchDepB::class => InjBenchDepB::class,
        InjBenchDepC::class => InjBenchDepC::class,
    ]);
    $container = new Container($config);
    // Warm up singletons so the injector only pays DI lookup cost.
    $container->get(InjBenchDepC::class);

    $injectorCached   = (new Injector($container))->withCacheReflections(true);
    $injectorUncached = new Injector($container);

    // Closure with three typed params — avoids function-name lookup overhead.
    $closureThree = static fn(InjBenchDepA $a, InjBenchDepB $b, InjBenchDepC $c): string => $a::class . $b::class . $c::class;

    return [
        // ── invoke() with 1 dependency, reflection cached ────────────────
        'benchInvokeOneDep_cached' => [
            'fn'     => static fn() => $injectorCached->invoke('injBenchCallableOne'),
            'revs'   => 2000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── invoke() with 3 dependencies, reflection cached ──────────────
        'benchInvokeThreeDeps_cached' => [
            'fn'     => static fn() => $injectorCached->invoke('injBenchCallableThree'),
            'revs'   => 2000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── invoke() with 3 deps via closure, reflection cached ──────────
        'benchInvokeClosure_cached' => [
            'fn'     => static fn() => $injectorCached->invoke($closureThree),
            'revs'   => 2000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── invoke() with 3 dependencies, NO reflection cache ────────────
        // Demonstrates the overhead of repeated ReflectionFunction construction.
        'benchInvokeThreeDeps_uncached' => [
            'fn'     => static fn() => $injectorUncached->invoke('injBenchCallableThree'),
            'revs'   => 2000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── make() — instantiate class with constructor injection ─────────
        'benchMakeClass' => [
            'fn'     => static fn() => $injectorCached->make(InjBenchDepC::class),
            'revs'   => 2000,
            'warmup' => 3,
            'its'    => 7,
        ],
    ];
};
