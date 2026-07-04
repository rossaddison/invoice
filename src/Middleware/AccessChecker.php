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
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Http\Status;
use Yiisoft\User\CurrentUser;

final class AccessChecker implements MiddlewareInterface
{
    private ?string $permission = null;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UserService $userService,
        private LoggerInterface $logger,
        private CurrentUser $currentUser,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->permission === null) {
            throw new InvalidArgumentException('Permission not set.');
        }

        if (!$this->userService->hasPermission($this->permission)) {
            $this->logger->log(
                LogLevel::WARNING,
                'Access denied: userId=' . ($this->currentUser->getId() ?? 'guest')
                    . ' permission=' . $this->permission,
            );
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
