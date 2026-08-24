<?php

declare(strict_types=1);

namespace App\Invoice\Traits;

use App\Invoice\Enum\FlashScope;
use Yiisoft\Session\Flash\Flash;

trait FlashMessage
{
    /**
     * @param string $level
     * @param string $message
     * @param FlashScope|null $scope Which layout's reader this message is
     *     for — see FlashScope's own docblock. Omit for the staff/guest-
     *     portal default (resources/views/invoice/layout/alert.php); pass
     *     FlashScope::Shop for anything reachable from `/shop` (the
     *     storefront layout is the only other reader today).
     * @return Flash|null
     */
    protected function flashMessage(string $level, string $message, ?FlashScope $scope = null): ?Flash
    {
        $key = null === $scope ? $level : $scope->prefix($level);
        if ((strlen($message) > 0) && !$this->flash->has($message)) {
            $this->flash->add($key, $message, true);
            return $this->flash;
        }
        return null;
    }
}
