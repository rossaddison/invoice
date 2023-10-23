<?php

declare(strict_types=1);

namespace App\ViewInjection;

use App\Auth\Identity;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\LayoutParametersInjectionInterface;

final class LayoutViewInjection implements LayoutParametersInjectionInterface
{
    public function __construct(private CurrentUser $currentUser)
    {
    }

    /**
     * @return (\App\User\User|null|string)[]
     *
     * @psalm-return array{brandLabel: 'Yii3-i', user: \App\User\User|null}
     */
    public function getLayoutParameters(): array
    {
        $identity = $this->currentUser->getIdentity();

        return [
            'brandLabel' => 'Yii3-i',
            'user' => $identity instanceof Identity ? $identity->getUser() : null,
        ];
    }
}
