<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final readonly class NotFoundHandler implements RequestHandlerInterface
{
    private ViewRenderer $viewRenderer;

    public function __construct(ViewRenderer $viewRenderer)
    {
        $this->viewRenderer = $viewRenderer->withControllerName('site');
    }

    /**
     * @return \Yiisoft\DataResponse\DataResponse
     */
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->viewRenderer
            ->render('404')
            ->withStatus(Status::NOT_FOUND);
    }
}
