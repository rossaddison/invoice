<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use App\Invoice\Inv\InvService;
// Dummy setup: You must adapt these lines to use actual implementations or mocks for the constructor dependencies
use App\Invoice\Inv\InvRepository;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;

class DummyRepo extends InvRepository
{
}
class DummySession implements SessionInterface
{/* implement required methods as empty */
}
class DummyTranslator implements TranslatorInterface
{/* implement required methods as empty */
}

function profile(Container $container, string $serviceId, int $iterations)
{
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $container->get($serviceId);
    }
    $elapsed = microtime(true) - $start;
    echo "Resolving {$serviceId} {$iterations} times took: {$elapsed} seconds\n";
}

const ITERATIONS = 10000;

// 1. Autowiring (no explicit definition)
$container1 = new Container(ContainerConfig::create()->withDefinitions([
    InvRepository::class => new DummyRepo(),
    SessionInterface::class => new DummySession(),
    TranslatorInterface::class => new DummyTranslator(),
]));
echo "Autowiring:\n";
profile($container1, InvService::class, ITERATIONS);

// 2. Explicit definition
$container2 = new Container(ContainerConfig::create()->withDefinitions([
    InvRepository::class => new DummyRepo(),
    SessionInterface::class => new DummySession(),
    TranslatorInterface::class => new DummyTranslator(),
    InvService::class => [
        'class' => InvService::class,
        '__construct()' => [
            new DummyRepo(),
            new DummySession(),
            new DummyTranslator(),
        ],
    ],
]));
echo "Explicit definition:\n";
profile($container2, InvService::class, ITERATIONS);
