<?php

declare(strict_types=1);

return [
    function (Psr\Container\ContainerInterface $container) {
        $urlGenerator = $container->get(Yiisoft\Router\UrlGeneratorInterface::class);
        assert($urlGenerator instanceof Yiisoft\Router\UrlGeneratorInterface);
        $urlGenerator->setUriPrefix($_ENV['BASE_URL']);
    },
];
