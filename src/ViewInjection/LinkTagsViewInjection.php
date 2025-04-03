<?php

declare(strict_types=1);

namespace App\ViewInjection;

use Yiisoft\Yii\View\Renderer\LinkTagsInjectionInterface;

final class LinkTagsViewInjection implements LinkTagsInjectionInterface
{
    /**
     * @return string[][]
     *
     * @psalm-return array{favicon: array{rel: 'icon', href: '/favicon.ico'}}
     */
    #[\Override]
    public function getLinkTags(): array
    {
        return [
            'favicon' => [
                'rel' => 'icon',
                'href' => '/favicon.ico',
            ],
        ];
    }
}
