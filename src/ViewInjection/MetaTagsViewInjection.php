<?php

declare(strict_types=1);

namespace App\ViewInjection;

use Yiisoft\Yii\View\Renderer\MetaTagsInjectionInterface;

final class MetaTagsViewInjection implements MetaTagsInjectionInterface
{
    /**
     * @return string[][]
     *
     * @psalm-return array{generator: array{name: 'generator', content: 'Yii'}}
     */
    #[\Override]
    public function getMetaTags(): array
    {
        return [
            'generator' => [
                'name' => 'generator',
                'content' => 'Yii',
            ],
        ];
    }
}
