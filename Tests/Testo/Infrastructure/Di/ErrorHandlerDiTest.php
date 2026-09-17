<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Di;

use RuntimeException;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;
use Yiisoft\ErrorHandler\Exception\UserException;
use Yiisoft\ErrorHandler\Renderer\HtmlRenderer;

/**
 * Regression test: config/web/di/error-handler.php's HtmlRenderer factory
 * must point at a template file that actually exists.
 *
 * Root cause of the original bug: the factory used
 * $aliases->get('@resources/errors/production.php'), but the branded
 * template was created at resources/views/errors/production.php (the
 * app's usual view location, already reachable via the '@views' alias
 * defined in config/common/params.php) -- a plain path mismatch that
 * HtmlRenderer only detects when it actually tries to render, throwing
 * "Template not found" straight from the production error path itself.
 * Never caught locally because the original manual verification
 * constructed HtmlRenderer with an explicit literal path, bypassing this
 * config's own alias resolution entirely.
 */
#[Test]
final class ErrorHandlerDiTest
{
    private readonly Aliases $aliases;
    private readonly HtmlRenderer $renderer;

    public function __construct()
    {
        $params = require dirname(__DIR__, 4) . '/config/common/params.php';
        $this->aliases = new Aliases($params['yiisoft/aliases']['aliases']);

        $config = require dirname(__DIR__, 4) . '/config/web/di/error-handler.php';
        $this->renderer = $config[HtmlRenderer::class]($this->aliases);
    }

    public function templateAliasResolvesToAFileThatExists(): void
    {
        $path = $this->aliases->get('@views/errors/production.php');

        Assert::true(is_file($path), "Template not found at {$path}");
    }

    public function rendersTheBrandedPageWithoutThrowing(): void
    {
        $html = (string) $this->renderer->render(new RuntimeException('simulated db connection refused'));

        Assert::true(str_contains($html, 'Yii3-i'));
    }

    public function hidesThePlainExceptionMessage(): void
    {
        $html = (string) $this->renderer->render(new RuntimeException('simulated db connection refused'));

        Assert::false(str_contains($html, 'simulated db connection refused'));
    }

    public function showsAUserExceptionMessage(): void
    {
        $html = (string) $this->renderer->render(new UserException('a safe, user-facing message'));

        Assert::true(str_contains($html, 'a safe, user-facing message'));
    }
}
