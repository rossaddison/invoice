<?php

declare(strict_types=1);

/**
 * DI Container Benchmark Suite
 *
 * Measures the performance of Yiisoft\Di\Container — Yii3's core DI component.
 * Container uses an internal singleton cache, so repeated get() calls on the
 * same binding hit that cache.  "build" benchmarks bypass the cache by
 * constructing a fresh Container each time.
 *
 * @return array<string, array{fn:callable, revs:int, warmup:int, its:int}>
 */

use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

// ── Fixture classes (prefixed to avoid collisions with other suites) ──────────
class DiBenchServiceA {}
class DiBenchServiceB
{
    public function __construct(public readonly DiBenchServiceA $a) {}
}
class DiBenchServiceC
{
    public function __construct(public readonly DiBenchServiceB $b) {}
}
class DiBenchServiceD
{
    public function __construct(public readonly DiBenchServiceC $c) {}
}
class DiBenchServiceE
{
    public function __construct(public readonly DiBenchServiceD $d) {}
}
class DiBenchServiceF
{
    public function __construct(
        public readonly DiBenchServiceA $a,
        public readonly DiBenchServiceB $b,
        public readonly DiBenchServiceC $c,
    ) {}
}

// ── Suite factory ─────────────────────────────────────────────────────────────
return static function (): array {
    $definitions = [
        DiBenchServiceA::class => DiBenchServiceA::class,
        DiBenchServiceB::class => DiBenchServiceB::class,
        DiBenchServiceC::class => DiBenchServiceC::class,
        DiBenchServiceD::class => DiBenchServiceD::class,
        DiBenchServiceE::class => DiBenchServiceE::class,
        DiBenchServiceF::class => DiBenchServiceF::class,
    ];

    // Shared container — singletons are resolved once and cached internally.
    $config    = ContainerConfig::create()->withDefinitions($definitions);
    $container = new Container($config);

    // Warm up the singleton cache for every binding.
    $container->get(DiBenchServiceA::class);
    $container->get(DiBenchServiceE::class);
    $container->get(DiBenchServiceF::class);

    return [
        // ── Cached singleton (fastest path: map lookup only) ──────────────
        'benchSingletonGet' => [
            'fn'     => static fn() => $container->get(DiBenchServiceA::class),
            'revs'   => 5000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── Five levels of constructor injection, all cached ─────────────
        'benchDeepChainGet' => [
            'fn'     => static fn() => $container->get(DiBenchServiceE::class),
            'revs'   => 5000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── Three-argument constructor injection, all cached ─────────────
        'benchWideConstructorGet' => [
            'fn'     => static fn() => $container->get(DiBenchServiceF::class),
            'revs'   => 5000,
            'warmup' => 3,
            'its'    => 7,
        ],

        // ── Build a fresh Container from scratch (cold path) ─────────────
        // This shows DI bootstrap overhead and is slower by design.
        'benchContainerBuild' => [
            'fn'     => static function () use ($definitions): void {
                new Container(ContainerConfig::create()->withDefinitions($definitions));
            },
            'revs'   => 200,
            'warmup' => 2,
            'its'    => 5,
        ],

        // ── hasDefinition() check (boolean path) ─────────────────────────
        'benchHasDefinition' => [
            'fn'     => static fn() => $container->has(DiBenchServiceC::class),
            'revs'   => 5000,
            'warmup' => 3,
            'its'    => 7,
        ],
    ];
};
