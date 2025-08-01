<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class Timer
{
    private array $timers = [];

    public function start(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    public function get(string $name): float
    {
        if (!array_key_exists($name, $this->timers)) {
            throw new InvalidArgumentException("There is no \"$name\" timer started");
        }

        /**
         * @var string $this->timers[$name]
         */
        return microtime(true) - (float) $this->timers[$name];
    }
}
