<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class NotFoundHandler implements RequestHandlerInterface
{
    private WebViewRenderer $webViewRenderer;

    public function __construct(WebViewRenderer $webViewRenderer)
    {
        $this->webViewRenderer = $webViewRenderer->withControllerName('site');
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->webViewRenderer
            ->render('404')
            ->withStatus(Status::NOT_FOUND);
    }
}
