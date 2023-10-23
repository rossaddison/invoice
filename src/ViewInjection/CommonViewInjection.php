<?php

declare(strict_types=1);

namespace App\ViewInjection;

use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\CommonParametersInjectionInterface;

final class CommonViewInjection implements CommonParametersInjectionInterface
{
    public function __construct(private UrlGeneratorInterface $url)
    {
    }

    /**
     * @return UrlGeneratorInterface[]
     *
     * @psalm-return array{url: UrlGeneratorInterface}
     */
    public function getCommonParameters(): array
    {
        return [
            'url' => $this->url,
        ];
    }
}
