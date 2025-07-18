<?php

declare(strict_types=1);

namespace App\Middleware;

use App\User\UserService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;

final class AccessChecker implements MiddlewareInterface
{
    private ?string $permission = null;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UserService $userService,
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->permission === null) {
            throw new InvalidArgumentException('Permission not set.');
        }

        if (!$this->userService->hasPermission($this->permission)) {
            return $this->responseFactory->createResponse(Status::FORBIDDEN);
        }

        return $handler->handle($request);
    }

    public function withPermission(string $permission): self
    {
        $new = clone $this;
        $new->permission = $permission;

        return $new;
    }
}
