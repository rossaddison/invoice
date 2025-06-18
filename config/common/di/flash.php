<?php

declare(strict_types=1);

use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

return [
    Flash::class => static fn (SessionInterface $session) => new Flash($session),
];