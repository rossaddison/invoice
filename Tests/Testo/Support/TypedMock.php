<?php

declare(strict_types=1);

namespace Tests\Testo\Support;

use Mockery as m;

/**
 * Mockery::mock() is typed as returning a plain MockInterface, so Psalm rejects a mock wherever the
 * mocked class is expected (a constructor argument, a declared return type). Asking for the mock
 * through here says what it is: an instance of the class, that is also a mock.
 */
final class TypedMock
{
    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T&m\MockInterface
     */
    public static function of(string $class): object
    {
        /** @var T&m\MockInterface $mock */
        $mock = m::mock($class);
        return $mock;
    }

    /**
     * A real instance of a class whose constructor takes only collaborators (a dependency bundle):
     * every object-typed parameter becomes a mock unless it is given, so a test names only the
     * dependencies it cares about and never has to assign to a readonly property afterwards.
     *
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, object> $given constructor arguments by parameter name
     * @return T
     */
    public static function build(string $class, array $given = []): object
    {
        $reflection = new \ReflectionClass($class);
        $arguments = [];
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            if (isset($given[$name])) {
                $arguments[$name] = $given[$name];
            } elseif ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[$name] = self::of($type->getName());
            }
        }
        /** @var T */
        return $reflection->newInstanceArgs($arguments);
    }
}
